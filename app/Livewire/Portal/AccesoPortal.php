<?php

namespace App\Livewire\Portal;

use App\Helpers\Pii;
use App\Models\ActivityLog;
use App\Models\Inquilino;
use App\Models\Propietario;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * [PORTAL / Fase 1.4] Tarjeta "Acceso al Portal" integrada en
 * PropietarioShow / InquilinoShow.
 *
 * Permite al admin:
 *  - Crear el acceso (usuario = CI de la persona, contraseña temporal)
 *  - Resetear la contraseña (nueva temporal + cambio forzado en login)
 *  - Activar/desactivar el acceso (baja lógica)
 *
 * La contraseña temporal se muestra UNA VEZ en pantalla para copiar y
 * entregar en persona/WhatsApp. Nunca se guarda en claro ni va a logs.
 */
class AccesoPortal extends Component
{
    #[Locked]
    public string $tipo;          // 'propietario' | 'inquilino'
    #[Locked]
    public int $personaId;

    /** Contraseña temporal a mostrar UNA VEZ (solo en RAM de la página). */
    public ?string $passwordTemporal = null;

    public function mount(string $tipo, int $personaId): void
    {
        $this->tipo = $tipo;
        $this->personaId = $personaId;
    }

    #[Computed]
    public function persona(): Propietario|Inquilino|null
    {
        return $this->tipo === 'propietario'
            ? Propietario::find($this->personaId)
            : Inquilino::find($this->personaId);
    }

    #[Computed]
    public function acceso(): ?User
    {
        return User::where('tipo', $this->tipo)
            ->where('persona_id', $this->personaId)
            ->first();
    }

    /**
     * Crear el acceso del portal para esta persona.
     * Usuario = CI de la persona (login por ci_hash).
     *
     * [Fase 3.4] Para INQUILINOS: solo se crea la cuenta si el propietario
     * autorizó el portal en al menos una unidad.
     */
    public function crearAcceso(): void
    {
        $persona = $this->persona;

        if (!$persona) {
            $this->dispatch('toast-error', message: 'Persona no encontrada');
            return;
        }

        if ($this->tipo === 'inquilino' && !$persona->tieneAutorizacionPortal()) {
            $this->dispatch('toast-error', message: 'El propietario no autorizó el portal para este inquilino en ninguna unidad. No se puede crear la cuenta.');
            return;
        }

        if (empty($persona->ci)) {
            $this->dispatch('toast-error', message: 'La persona no tiene CI cargado. Edítala y carga el CI antes de crear el acceso.');
            return;
        }

        $ciHash = Pii::hash($persona->ci);

        if (User::where('ci_hash', $ciHash)->exists()) {
            $this->dispatch('toast-error', message: 'Ya existe una cuenta con ese CI.');
            return;
        }

        $temporal = Str::password(12, symbols: false);

        $user = User::create([
            'name' => $persona->nombre_completo,
            'email' => $persona->email, // opcional/informativo
            'password' => $temporal,    // cast 'hashed'
            'tipo' => $this->tipo,
            'persona_id' => $this->personaId,
            'ci_hash' => $ciHash,
            'activo' => true,
            'must_change_password' => true,
        ]);

        $user->sincronizarRol();

        // Mostrar UNA VEZ
        $this->passwordTemporal = $temporal;

        $this->auditar('acceso_portal_creado', "Acceso al portal creado para {$this->tipo} #{$this->personaId}");

        $this->dispatch('toast-success', message: 'Acceso creado. Entrega la contraseña temporal en persona.');
    }

    /**
     * Resetear la contraseña: genera una nueva temporal y fuerza el cambio.
     */
    public function resetearPassword(): void
    {
        $acceso = $this->acceso;

        if (!$acceso) {
            $this->dispatch('toast-error', message: 'Esta persona no tiene acceso al portal');
            return;
        }

        $temporal = Str::password(12, symbols: false);

        $acceso->forceFill([
            'password' => $temporal,
            'must_change_password' => true,
            'activo' => true,
        ])->saveQuietly();

        $this->passwordTemporal = $temporal;

        $this->auditar('acceso_portal_reseteado', "Contraseña temporal emitida para {$this->tipo} #{$this->personaId}");

        $this->dispatch('toast-success', message: 'Contraseña temporal generada. Entrégala en persona.');
    }

    /**
     * Activar / desactivar el acceso (baja lógica).
     */
    public function toggleActivo(): void
    {
        $acceso = $this->acceso;

        if (!$acceso) {
            $this->dispatch('toast-error', message: 'Esta persona no tiene acceso al portal');
            return;
        }

        $acceso->forceFill(['activo' => !$acceso->activo])->saveQuietly();

        $nuevo = $acceso->activo ? 'activado' : 'desactivado';
        $this->auditar('acceso_portal_toggle', "Acceso {$nuevo} para {$this->tipo} #{$this->personaId}");

        $this->dispatch('toast-success', message: "Acceso {$nuevo}");
    }

    /**
     * Auditoría explícita SIN datos sensibles (nunca password ni CI).
     */
    private function auditar(string $action, string $description): void
    {
        try {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'model_type' => User::class,
                'model_id' => $this->acceso?->id,
                'description' => $description,
                'properties' => [
                    'tipo' => $this->tipo,
                    'persona_id' => $this->personaId,
                ],
            ]);
        } catch (\Throwable $e) {
            logger()->error('Error auditando AccesoPortal', ['error' => $e->getMessage()]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | [Fase 3.3 — RF-48] Fallback admin: autorización por unidad (inquilinos)
    |--------------------------------------------------------------------------
    */

    /**
     * Pares (unidad, autorizado) del inquilino actual — solo tipo inquilino.
     */
    #[Computed]
    public function unidadesInquilino(): Collection
    {
        if ($this->tipo !== 'inquilino') {
            return collect();
        }

        $persona = $this->persona;

        return $persona?->unidadesActuales()
            ->withPivot('portal_autorizado', 'autorizado_en')
            ->get()
            ->map(fn ($unidad) => (object) [
                'unidad' => $unidad,
                'autorizado' => (bool) ($unidad->pivot->portal_autorizado ?? false),
            ]) ?? collect();
    }

    /**
     * Toggle de autorización de una unidad (fallback admin).
     */
    public function toggleAutorizacion(int $unidadId): void
    {
        if ($this->tipo !== 'inquilino') {
            return;
        }

        $persona = $this->persona;
        $pivot = DB::table('inquilino_unidad')
            ->where('inquilino_id', $this->personaId)
            ->where('unidad_id', $unidadId)
            ->where('es_inquilino_actual', true)
            ->first();

        if (!$pivot) {
            $this->dispatch('toast-error', message: 'Par inquilino/unidad no encontrado');
            return;
        }

        $nuevoEstado = !$pivot->portal_autorizado;

        DB::table('inquilino_unidad')
            ->where('inquilino_id', $this->personaId)
            ->where('unidad_id', $unidadId)
            ->where('es_inquilino_actual', true)
            ->update([
                'portal_autorizado' => $nuevoEstado,
                'autorizado_en' => $nuevoEstado ? now() : null,
            ]);

        try {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => $nuevoEstado ? 'portal_inquilino_autorizado' : 'portal_inquilino_revocado',
                'model_type' => Inquilino::class,
                'model_id' => $this->personaId,
                'description' => ($nuevoEstado ? 'Autorización OTORGADA' : 'Autorización REVOCADA')
                    . " (admin) al inquilino #{$this->personaId} para unidad #$unidadId",
                'properties' => ['unidad_id' => $unidadId, 'portal_autorizado' => $nuevoEstado],
            ]);
        } catch (\Throwable $e) {
            logger()->error('Error auditando toggle autorización admin', ['error' => $e->getMessage()]);
        }

        unset($this->unidadesInquilino);

        $this->dispatch('toast-success', message: $nuevoEstado ? 'Autorizado' : 'Revocado');
    }

    public function render()
    {
        return view('livewire.portal.acceso-portal');
    }
}
