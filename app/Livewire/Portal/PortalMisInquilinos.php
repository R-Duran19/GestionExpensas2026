<?php

namespace App\Livewire\Portal;

use App\Models\ActivityLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * [PORTAL / Fase 3.2 — HU-46/47, RF-48] "Mis Inquilinos" del propietario.
 *
 * El propietario autoriza/revoca el acceso al portal de los inquilinos de
 * SUS unidades. La revocación quita la visibilidad en el siguiente request.
 *
 * ANTI-IDOR: toggleAutorizacion verifica que la unidad pertenezca al
 * propietario autenticado antes de modificar.
 */
#[Title('Mis Inquilinos')]
class PortalMisInquilinos extends Component
{
    #[Computed]
    public function user(): \App\Models\User
    {
        return auth()->user();
    }

    /**
     * Unidades ALQUILADAS del propietario (las que tienen inquilino actual)
     * con el inquilino y su estado de autorización.
     */
    #[Computed]
    public function alquiladas(): Collection
    {
        $persona = $this->user->persona;

        if (!$persona || $this->user->tipo !== 'propietario') {
            return collect();
        }

        return $persona->unidadesActuales()
            ->has('inquilinoActual')
            ->with('inquilinoActual')
            ->orderBy('codigo')
            ->get()
            ->map(function ($unidad) {
                $inquilino = $unidad->inquilinoActual->first();

                return (object) [
                    'unidad' => $unidad,
                    'inquilino' => $inquilino,
                    'autorizado' => (bool) ($inquilino?->pivot->portal_autorizado ?? false),
                ];
            });
    }

    /**
     * Autorizar o revocar al inquilino de una unidad PROPIA.
     */
    public function toggleAutorizacion(int $unidadId): void
    {
        $user = $this->user;
        $persona = $user->persona;

        if (!$persona || $user->tipo !== 'propietario') {
            abort(403);
        }

        // ANTI-IDOR: la unidad debe ser del propietario autenticado
        $unidad = $persona->unidadesActuales()
            ->where('unidades.id', $unidadId)
            ->first();

        if (!$unidad) {
            $this->dispatch('toast-error', message: 'Unidad no encontrada entre tus propiedades');
            return;
        }

        $inquilino = $unidad->inquilinoActual->first();

        if (!$inquilino) {
            $this->dispatch('toast-error', message: 'La unidad no tiene inquilino actual');
            return;
        }

        $nuevoEstado = !$inquilino->pivot->portal_autorizado;

        DB::table('inquilino_unidad')
            ->where('inquilino_id', $inquilino->id)
            ->where('unidad_id', $unidad->id)
            ->where('es_inquilino_actual', true)
            ->update([
                'portal_autorizado' => $nuevoEstado,
                'autorizado_en' => $nuevoEstado ? now() : null,
            ]);

        try {
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => $nuevoEstado ? 'portal_inquilino_autorizado' : 'portal_inquilino_revocado',
                'model_type' => \App\Models\Inquilino::class,
                'model_id' => $inquilino->id,
                'description' => ($nuevoEstado ? 'Autorización OTORGADA' : 'Autorización REVOCADA')
                    . " al inquilino #{$inquilino->id} para la unidad {$unidad->codigo}",
                'properties' => [
                    'unidad_id' => $unidad->id,
                    'inquilino_id' => $inquilino->id,
                    'portal_autorizado' => $nuevoEstado,
                ],
            ]);
        } catch (\Throwable $e) {
            logger()->error('Error auditando autorización portal', ['error' => $e->getMessage()]);
        }

        // Limpiar computed para refrescar el estado
        unset($this->alquiladas);

        $this->dispatch('toast-success', message: $nuevoEstado
            ? 'Inquilino autorizado. Ya puede usar el portal.'
            : 'Autorización revocada. Perderá el acceso a esta unidad.');
    }

    public function render()
    {
        // Página exclusiva de propietarios
        if ($this->user->tipo !== 'propietario') {
            abort(403, 'Solo los propietarios gestionan autorizaciones.');
        }

        return view('livewire.portal.portal-mis-inquilinos')
            ->layout('layouts.portal');
    }
}
