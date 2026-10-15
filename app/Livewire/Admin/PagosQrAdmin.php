<?php

namespace App\Livewire\Admin;

use App\Models\PagoQr;
use App\Services\QrBanco\PagoQrService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * [PORTAL QR / Fase 4 — HU-55] Lista de pagos QR para conciliación del admin.
 *
 * Acciones:
 * - "Verificar con banco": consulta estadoTransaccion y acredita si PAGADO.
 * - "Revisar" (divergentes): marca pagado tras revisión con motivo auditado.
 */
#[Title('Pagos QR')]
class PagosQrAdmin extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $filtroEstado = '';

    protected function service(): PagoQrService
    {
        return app(PagoQrService::class);
    }

    public function pagos()
    {
        return PagoQr::query()
            ->when($this->filtroEstado !== '', fn ($q) => $q->where('estado', $this->filtroEstado))
            ->with(['user', 'unidad', 'ingreso'])
            ->orderByDesc('id')
            ->paginate(15);
    }

    public function updatedFiltroEstado(): void
    {
        $this->resetPage();
    }

    /**
     * Verificar con el banco y acreditar si corresponde.
     */
    public function verificarConBanco(int $pagoQrId): void
    {
        $qr = PagoQr::findOrFail($pagoQrId);

        try {
            $resultado = $this->service()->verificarYAcreditar($qr);

            $this->dispatch('toast-success', message: $resultado['pago_procesado']
                ? 'Pago acreditado correctamente'
                : "Estado en banco: {$resultado['estado']}");
        } catch (\Throwable $e) {
            $this->dispatch('toast-error', message: 'Error consultando al banco: ' . $e->getMessage());
        }
    }

    /**
     * Acreditar manualmente un divergente tras revisión (con motivo).
     */
    public function acreditarDivergente(int $pagoQrId, string $motivo): void
    {
        if (trim($motivo) === '') {
            $this->dispatch('toast-error', message: 'Indica el motivo de la revisión');
            return;
        }

        $qr = PagoQr::findOrFail($pagoQrId);

        if ($qr->estado !== PagoQr::ESTADO_DIVERGENTE) {
            $this->dispatch('toast-error', message: 'Solo los pagos divergentes se acreditan aquí');
            return;
        }

        try {
            // [REMEDIACIÓN-2.9] Flip + acreditación atómicos y con lock: el
            // update suelto (:83 original) podía pisar un estado final escrito
            // concurrentemente y abrir ventana a un segundo ingreso.
            $resultado = DB::transaction(function () use ($qr) {
                $qr = PagoQr::lockForUpdate()->findOrFail($qr->id);

                if ($qr->estado !== PagoQr::ESTADO_DIVERGENTE) {
                    // Alguien más lo procesó mientras tanto
                    return ['pago_qr' => $qr, 'procesado_ahora' => false];
                }

                $qr->update(['estado' => PagoQr::ESTADO_PENDIENTE]);

                return $this->service()->acreditar($qr, ['monto' => $qr->monto]);
            });

            $final = $resultado['pago_qr']->refresh();

            // [REMEDIACIÓN-2.9] Verificar el RESULTADO antes de auditar: la
            // rama "sin deuda aplicable" de acreditar() vuelve a divergente con
            // ingreso_id null y ANTES se registraba como "acreditado" (auditoría
            // falsa) con toast de éxito — y la fila quedaba incerrable.
            if ($final->estado !== PagoQr::ESTADO_PAGADO || !$final->ingreso_id) {
                $this->dispatch('toast-error', message: 'Sin deuda pendiente en la unidad: el pago queda en divergente para revisión manual. No se acreditó nada.');
                return;
            }

            \App\Models\ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'pago_qr_divergente_acreditado',
                'model_type' => PagoQr::class,
                'model_id' => $qr->id,
                'description' => "QR divergente {$qr->alias} acreditado tras revisión admin. Motivo: {$motivo}",
                'properties' => [
                    'alias' => $qr->alias,
                    'monto' => (float) $qr->monto,
                    'ingreso_id' => $final->ingreso_id,
                ],
            ]);

            $this->dispatch('toast-success', message: 'Pago divergente acreditado tras revisión');
        } catch (\Throwable $e) {
            $qr->refresh()->update(['estado' => PagoQr::ESTADO_DIVERGENTE]);
            $this->dispatch('toast-error', message: 'Error acreditando: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.pagos-qr-admin')
            ->layout('layouts.app');
    }
}
