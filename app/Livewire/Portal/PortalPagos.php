<?php

namespace App\Livewire\Portal;

use App\Models\Ingreso;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * [PORTAL / Fase 2.4 — HU-43] Historial de pagos propios.
 *
 * Lista los ingresos donde el pagador es la persona del usuario autenticado
 * (pagador_tipo/pagador_id — ADR 2026-08-04). ANTI-IDOR: la query SIEMPRE
 * filtra por el usuario autenticado.
 */
#[Title('Mis Pagos')]
class PortalPagos extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $filtroMetodo = '';

    #[Computed]
    public function user(): \App\Models\User
    {
        return auth()->user();
    }

    #[Computed]
    public function pagos()
    {
        return Ingreso::query()
            ->where('pagador_tipo', $this->user->tipo)
            ->where('pagador_id', $this->user->persona_id)
            ->when($this->filtroMetodo !== '', fn ($q) => $q->where('metodo_pago', $this->filtroMetodo))
            ->with('unidad')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(10);
    }

    #[Computed]
    public function totalPagado(): float
    {
        return (float) Ingreso::query()
            ->where('pagador_tipo', $this->user->tipo)
            ->where('pagador_id', $this->user->persona_id)
            ->sum('monto_total');
    }

    public function updatedFiltroMetodo(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.portal.portal-pagos')
            ->layout('layouts.portal');
    }
}
