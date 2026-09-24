<?php

namespace App\Livewire\Portal;

use App\Models\Expensa;
use App\Models\SaldoAFavor;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * [PORTAL / Fase 1.3 + 2.2] Dashboard del portal.
 *
 * ANTI-IDOR: las unidades se resuelven SIEMPRE desde el usuario autenticado
 * (su persona → unidades actuales según regla maestra). Nunca desde parámetros.
 */
class PortalDashboard extends Component
{
    #[Computed]
    public function user(): \App\Models\User
    {
        return auth()->user();
    }

    /**
     * Unidades visibles según regla maestra (ADR 2026-08-11):
     * - Propietario: sus unidades actuales EXCEPTO las con inquilino actual.
     * - Inquilino: las unidades donde es inquilino actual.
     */
    #[Computed]
    public function unidades(): Collection
    {
        $persona = $this->user->persona;

        if (!$persona) {
            return collect();
        }

        $query = $persona->unidadesActuales();

        if ($this->user->tipo === 'propietario') {
            $query->doesntHave('inquilinoActual');
        }

        return $query->orderBy('codigo')->get();
    }

    /**
     * Deuda total (expensa + agua) por unidad_id, calculada al vuelo (ADR
     * "deuda al vuelo"): SUM(pendientes) de sus expensas.
     *
     * @return array<int, float>
     */
    #[Computed]
    public function deudasPorUnidad(): array
    {
        $ids = $this->unidades->pluck('id');

        if ($ids->isEmpty()) {
            return [];
        }

        return Expensa::query()
            ->selectRaw('unidad_id, SUM(expensa_pendiente + agua_pendiente) as total')
            ->whereIn('unidad_id', $ids)
            ->groupBy('unidad_id')
            ->pluck('total', 'unidad_id')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /**
     * Saldo a favor disponible por unidad_id.
     *
     * @return array<int, float>
     */
    #[Computed]
    public function saldosPorUnidad(): array
    {
        $ids = $this->unidades->pluck('id');

        if ($ids->isEmpty()) {
            return [];
        }

        return SaldoAFavor::query()
            ->selectRaw('unidad_id, SUM(monto_saldo) as total')
            ->whereIn('unidad_id', $ids)
            ->where('monto_saldo', '>', 0)
            ->groupBy('unidad_id')
            ->pluck('total', 'unidad_id')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    #[Computed]
    public function deudaTotal(): float
    {
        return array_sum($this->deudasPorUnidad());
    }

    #[Computed]
    public function saldoTotal(): float
    {
        return array_sum($this->saldosPorUnidad());
    }

    public function render()
    {
        return view('livewire.portal.portal-dashboard')
            ->layout('layouts.portal');
    }
}
