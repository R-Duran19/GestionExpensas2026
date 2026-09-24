<?php

namespace App\Livewire\Portal;

use App\Models\Expensa;
use App\Models\SaldoAFavor;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * [PORTAL / Fase 2.3 — HU-42] Estado de cuenta del portal.
 *
 * Muestra el historial de expensas por unidad (períodos, montos, pagos,
 * pendientes) calculado AL VUELO (ADR "deuda al vuelo").
 *
 * ANTI-IDOR: la unidad se valida contra las unidades visibles del usuario
 * antes de cualquier query. Nunca se confía en el ID recibido.
 */
#[Title('Estado de Cuenta')]
class PortalEstadoCuenta extends Component
{
    /** Unidad seleccionada (ID validado contra unidades propias). */
    public ?int $unidadId = null;

    use WithPagination;

    protected $paginationTheme = 'tailwind';

    #[Computed]
    public function user(): \App\Models\User
    {
        return auth()->user();
    }

    /**
     * Unidades visibles (regla maestra + autorización para inquilinos).
     */
    #[Computed]
    public function unidades(): Collection
    {
        $persona = $this->user->persona;

        if (!$persona) {
            return collect();
        }

        if ($this->user->tipo === 'propietario') {
            return $persona->unidadesActuales()
                ->doesntHave('inquilinoActual')
                ->orderBy('codigo')
                ->get();
        }

        return $persona->unidadesAutorizadasPortal()
            ->orderBy('codigo')
            ->get();
    }

    public function mount(): void
    {
        // Default: primera unidad visible
        $this->unidadId = $this->unidades->first()?->id;
    }

    /**
     * La unidad seleccionada SOLO si pertenece al usuario.
     */
    #[Computed]
    public function unidad(): ?\App\Models\Unidad
    {
        return $this->unidades->firstWhere('id', $this->unidadId);
    }

    /**
     * Expensas de la unidad seleccionada, de más reciente a más antigua.
     */
    #[Computed]
    public function expensas()
    {
        $unidad = $this->unidad;

        if (!$unidad) {
            return Expensa::query()->whereRaw('1 = 0')->paginate(12);
        }

        return Expensa::query()
            ->where('unidad_id', $unidad->id)
            ->with('periodo')
            ->join('periodos_expensa as pe', 'pe.id', '=', 'expensas.periodo_id')
            ->orderByDesc('pe.año')
            ->orderByDesc('pe.mes')
            ->select('expensas.*')
            ->paginate(12);
    }

    /**
     * Saldo a favor disponible de la unidad seleccionada.
     */
    #[Computed]
    public function saldoFavor(): float
    {
        $unidad = $this->unidad;

        if (!$unidad) {
            return 0.0;
        }

        return (float) SaldoAFavor::where('unidad_id', $unidad->id)
            ->where('monto_saldo', '>', 0)
            ->sum('monto_saldo');
    }

    /**
     * Totales del historial completo de la unidad (no solo la página).
     */
    #[Computed]
    public function totales(): array
    {
        $unidad = $this->unidad;

        if (!$unidad) {
            return ['total_generado' => 0, 'total_pagado' => 0, 'total_pendiente' => 0, 'pendiente_expensa' => 0, 'pendiente_agua' => 0];
        }

        $row = Expensa::query()
            ->where('unidad_id', $unidad->id)
            ->selectRaw('
                SUM(monto_total) as total_generado,
                SUM(monto_total - expensa_pendiente - agua_pendiente) as total_pagado,
                SUM(expensa_pendiente + agua_pendiente) as total_pendiente,
                SUM(expensa_pendiente) as pendiente_expensa,
                SUM(agua_pendiente) as pendiente_agua
            ')
            ->first();

        return [
            'total_generado'    => (float) ($row->total_generado ?? 0),
            'total_pagado'      => (float) ($row->total_pagado ?? 0),
            'total_pendiente'   => (float) ($row->total_pendiente ?? 0),
            'pendiente_expensa' => (float) ($row->pendiente_expensa ?? 0),
            'pendiente_agua'    => (float) ($row->pendiente_agua ?? 0),
        ];
    }

    public function render()
    {
        return view('livewire.portal.portal-estado-cuenta')
            ->layout('layouts.portal');
    }
}
