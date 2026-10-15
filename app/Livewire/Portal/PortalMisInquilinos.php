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
 * Agrupado POR INQUILINO: se lista cada inquilino con TODAS las propiedades
 * que le fueron asignadas y UN SOLO botón para autorizar/revocar el acceso a
 * todas a la vez (decisión del admin 2026-09-24).
 *
 * [FIX 2026-09-24] El listado anterior llamaba ->first() sobre el ACCESOR
 * getInquilinoActualAttribute (que devuelve un MODELO, no una colección):
 * Model->first() ejecuta Inquilino::first() y mostraba SIEMPRE el primer
 * inquilino de la tabla, mezclando datos en pantalla. Ahora se usa la
 * relación eager-loaded vía getRelation() — nunca el accesor.
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
     * Inquilinos de las unidades alquiladas del propietario, AGRUPADOS,
     * con el estado de autorización de cada unidad.
     */
    #[Computed]
    public function grupos(): Collection
    {
        $persona = $this->user->persona;

        if (!$persona || $this->user->tipo !== 'propietario') {
            return collect();
        }

        // Relación eager-loaded (pivote completo, incluye portal_autorizado)
        $unidades = $persona->unidadesActuales()
            ->has('inquilinoActual')
            ->with('inquilinoActual')
            ->orderBy('codigo')
            ->get();

        $grupos = [];

        foreach ($unidades as $unidad) {
            // getRelation(): usa la RELACIÓN real, no el accesor sombra
            foreach ($unidad->getRelation('inquilinoActual') as $inquilino) {
                $grupos[$inquilino->id] ??= [
                    'inquilino' => $inquilino,
                    'unidades'  => collect(),
                ];

                $grupos[$inquilino->id]['unidades']->push((object) [
                    'unidad'     => $unidad,
                    'autorizado' => (bool) ($inquilino->pivot->portal_autorizado ?? false),
                ]);
            }
        }

        return collect($grupos)
            ->map(fn ($g) => (object) [
                'inquilino'   => $g['inquilino'],
                'unidades'    => $g['unidades']->sortBy(fn ($f) => $f->unidad->codigo)->values(),
                // Autorizado = TODAS sus unidades lo están
                'autorizado'  => $g['unidades']->every(fn ($f) => $f->autorizado),
                'alguno'      => $g['unidades']->contains(fn ($f) => $f->autorizado),
            ])
            ->sortBy(fn ($g) => $g->inquilino->nombre_completo)
            ->values();
    }

    /**
     * Autorizar / revocar al inquilino en TODAS sus unidades asignadas
     * dentro de las propiedades del propietario autenticado.
     */
    public function toggleAutorizacionGrupo(int $inquilinoId): void
    {
        $user = $this->user;
        $persona = $user->persona;

        if (!$persona || $user->tipo !== 'propietario') {
            abort(403);
        }

        // ANTI-IDOR: unidades del propietario autenticado con ese inquilino
        $unidadesIds = $persona->unidadesActuales()
            ->whereHas('inquilinos', function ($q) use ($inquilinoId) {
                $q->where('inquilinos.id', $inquilinoId)
                  ->where('inquilino_unidad.es_inquilino_actual', true);
            })
            ->pluck('unidades.id');

        if ($unidadesIds->isEmpty()) {
            $this->dispatch('toast-error', message: 'Inquilino no encontrado entre tus propiedades');
            return;
        }

        // Estado destino: si todas autorizadas → revocar; si no → autorizar
        $pivotes = DB::table('inquilino_unidad')
            ->where('inquilino_id', $inquilinoId)
            ->whereIn('unidad_id', $unidadesIds)
            ->where('es_inquilino_actual', true)
            ->get();

        $nuevoEstado = ! $pivotes->every(fn ($p) => (bool) $p->portal_autorizado);

        DB::table('inquilino_unidad')
            ->where('inquilino_id', $inquilinoId)
            ->whereIn('unidad_id', $unidadesIds)
            ->where('es_inquilino_actual', true)
            ->update([
                'portal_autorizado' => $nuevoEstado,
                'autorizado_en'     => $nuevoEstado ? now() : null,
            ]);

        try {
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => $nuevoEstado ? 'portal_inquilino_autorizado' : 'portal_inquilino_revocado',
                'model_type' => \App\Models\Inquilino::class,
                'model_id' => $inquilinoId,
                'description' => ($nuevoEstado ? 'Autorización OTORGADA' : 'Autorización REVOCADA')
                    . " al inquilino #{$inquilinoId} para {$unidadesIds->count()} unidad(es)",
                'properties' => [
                    'unidades' => $unidadesIds->all(),
                    'portal_autorizado' => $nuevoEstado,
                ],
            ]);
        } catch (\Throwable $e) {
            logger()->error('Error auditando autorización portal', ['error' => $e->getMessage()]);
        }

        // Refrescar el computed
        unset($this->grupos);

        $this->dispatch('toast-success', message: $nuevoEstado
            ? 'Inquilino autorizado en todas sus propiedades. Ya puede usar el portal.'
            : 'Autorización revocada en todas sus propiedades.');
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
