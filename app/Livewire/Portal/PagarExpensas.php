<?php

namespace App\Livewire\Portal;

use App\Models\Expensa;
use App\Models\PagoQr;
use App\Models\SaldoAFavor;
use App\Services\QrBanco\PagoQrService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * [PORTAL QR / Fase 4.3 — HU-50/52, RF-49] Pagar expensas con QR.
 *
 * Pago FLEXIBLE estilo Caja (decisión 9 del plan):
 * - Usuario elige unidad → ve deudas pendientes (antigua → reciente).
 * - Dos modos: seleccionar períodos (total sugerido) o monto libre.
 * - UN QR activo por usuario: recargar/re-entrar devuelve el mismo QR.
 * - Polling cada 15s + botón manual "Verificar ahora".
 * - El monto se acredita FIFO con PagoExpensaService; excedente → SaldoAFavor.
 */
#[Title('Pagar Expensas')]
class PagarExpensas extends Component
{
    public ?int $unidadId = null;

    /** IDs de expensas marcadas (modo sugerido). */
    public array $seleccionadas = [];

    /** Modo de monto: 'seleccion' | 'libre'. */
    public string $modo = 'seleccion';

    /** Monto libre elegido por el usuario. */
    public $montoLibre = null;

    /** QR activo (id) para UI — el objeto vive en el service. */
    #[Locked]
    public ?int $qrActivoId = null;

    /** Mensaje de éxito con el recibo. */
    public bool $pagoExitoso = false;
    public ?int $ingresoId = null;

    protected $listeners = [];

    #[Computed]
    public function user(): \App\Models\User
    {
        return auth()->user();
    }

    /**
     * Unidades pagables del usuario (regla maestra + autorización).
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

        return $persona->unidadesAutorizadasPortal()->orderBy('codigo')->get();
    }

    public function mount(): void
    {
        $this->unidadId = $this->unidades->first()?->id;

        // Re-enganchar QR activo existente (consistencia: recargar no genera otro)
        $activo = $this->service()->obtenerActivo($this->user);

        if ($activo) {
            $this->qrActivoId = $activo->id;
        }

        // Si el QR activo YA fue pagado (ej. pagó desde el celular y volvió
        // a entrar), mostrar directamente la pantalla de éxito con el recibo.
        if ($activo && $activo->estado === PagoQr::ESTADO_PAGADO) {
            $this->pagoExitoso = true;
            $this->ingresoId = $activo->ingreso_id;
        }
    }

    protected function service(): PagoQrService
    {
        return app(PagoQrService::class);
    }

    /**
     * La unidad seleccionada SOLO si es del usuario (anti-IDOR).
     */
    #[Computed]
    public function unidad(): ?\App\Models\Unidad
    {
        return $this->unidades->firstWhere('id', $this->unidadId);
    }

    /**
     * Expensas pendientes de la unidad, de más antigua a más reciente (FIFO).
     */
    #[Computed]
    public function pendientes(): Collection
    {
        $unidad = $this->unidad;

        if (!$unidad) {
            return collect();
        }

        return Expensa::query()
            ->where('unidad_id', $unidad->id)
            ->where(function ($q) {
                $q->where('expensa_pendiente', '>', 0)
                  ->orWhere('agua_pendiente', '>', 0);
            })
            ->with('periodo')
            ->join('periodos_expensa as pe', 'pe.id', '=', 'expensas.periodo_id')
            ->orderBy('pe.año')
            ->orderBy('pe.mes')
            ->select('expensas.*')
            ->get();
    }

    #[Computed]
    public function deudaTotalUnidad(): float
    {
        return round($this->pendientes->sum(fn ($e) => (float) $e->expensa_pendiente + (float) $e->agua_pendiente), 2);
    }

    #[Computed]
    public function saldoFavorUnidad(): float
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
     * Total de la selección actual (checkboxes marcados).
     */
    #[Computed]
    public function totalSeleccion(): float
    {
        return round(
            $this->pendientes
                ->whereIn('id', $this->seleccionadas)
                ->sum(fn ($e) => (float) $e->expensa_pendiente + (float) $e->agua_pendiente),
            2
        );
    }

    /**
     * Monto final a pagar según modo.
     */
    #[Computed]
    public function montoAPagar(): float
    {
        if ($this->modo === 'libre') {
            return round((float) str_replace(',', '.', (string) $this->montoLibre), 2);
        }

        return $this->totalSeleccion;
    }

    public function updatedUnidadId(): void
    {
        $this->seleccionadas = [];
        $this->montoLibre = null;
    }

    public function marcarTodo(): void
    {
        $this->seleccionadas = $this->pendientes->pluck('id')->all();
    }

    protected function rules(): array
    {
        return [
            'unidadId' => ['required', 'integer'],
            'montoLibre' => ['nullable', 'numeric', 'min:1', 'max:100000'],
        ];
    }

    protected $validationAttributes = [
        'montoLibre' => 'monto a pagar',
    ];

    /**
     * Generar (o devolver) el QR del pago.
     */
    public function generarQr(): void
    {
        $this->validate();

        if (!$this->unidad) {
            $this->dispatch('toast-error', message: 'Unidad no encontrada');
            return;
        }

        $monto = $this->montoAPagar();

        if ($monto <= 0) {
            $this->dispatch('toast-error', message: 'El monto a pagar debe ser mayor a cero');
            return;
        }

        // Validación de negocio: monto libre no mayor a deuda total
        // (pagar de más se convertiría en saldo a favor, pero en el portal
        // evitamos confusiones — el admin puede registrar excedentes).
        if ($this->modo === 'libre' && $monto > $this->deudaTotalUnidad()) {
            $this->addError('montoLibre', 'El monto no puede superar tu deuda total (Bs ' . number_format($this->deudaTotalUnidad(), 2) . ').');
            return;
        }

        if ($this->modo === 'seleccion' && empty($this->seleccionadas)) {
            $this->dispatch('toast-error', message: 'Selecciona al menos una expensa a pagar');
            return;
        }

        // Selección informativa + glosa
        $seleccion = [
            'unidad_codigo' => $this->unidad->codigo,
            'expensas' => $this->pendientes
                ->whereIn('id', $this->seleccionadas)
                ->map(fn ($e) => [
                    'expensa_id' => $e->id,
                    'periodo' => $e->periodo->nombre_periodo ?? null,
                    'pendiente' => (float) $e->expensa_pendiente + (float) $e->agua_pendiente,
                ])->values()->all(),
            'glosa' => 'Expensas unidad ' . $this->unidad->codigo,
        ];

        try {
            $qr = $this->service()->crearIntencion(
                $this->user,
                $this->unidad->id,
                $monto,
                $seleccion
            );

            $this->qrActivoId = $qr->id;
            $this->pagoExitoso = false;
            $this->ingresoId = null;

            $this->dispatch('toast-success', message: 'QR generado. Escanéalo con tu app bancaria.');
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('toast-error', message: 'No se pudo generar el QR: ' . $e->getMessage());
        }
    }

    /**
     * QR activo resuelto (comprobando propiedad).
     */
    #[Computed]
    public function qrActivo(): ?PagoQr
    {
        if (!$this->qrActivoId) {
            return null;
        }

        return PagoQr::where('id', $this->qrActivoId)
            ->where('user_id', $this->user->id)
            ->first();
    }

    /**
     * Verificar estado en el banco.
     *
     * Polling automático (15s): usa cache de 5s en el service.
     * Botón manual "Verificar ahora": fuerza consulta real al banco.
     */
    public function verificar(bool $forzar = false): void
    {
        $qr = $this->qrActivo;

        if (!$qr || !$qr->estaPendiente()) {
            return;
        }

        try {
            $resultado = $this->service()->verificarYAcreditar($qr, $forzar);
        } catch (\Throwable $e) {
            // Banco caído: el fallback mantiene el estado local (como tickets)
            report($e);
            return;
        }

        if ($resultado['pago_procesado']) {
            $qr->refresh();

            if ($qr->estado === PagoQr::ESTADO_PAGADO) {
                $this->pagoExitoso = true;
                $this->ingresoId = $qr->ingreso_id;
                $this->dispatch('toast-success', message: '¡Pago confirmado! Tu recibo ya está disponible.');
            } else {
                $this->dispatch('toast-error', message: 'El pago requiere revisión del administrador.');
            }
        }
    }

    /**
     * Cancelar el QR activo y volver al formulario.
     */
    public function cancelarQr(): void
    {
        $qr = $this->qrActivo;

        if ($qr && $qr->estaPendiente()) {
            $this->service()->cancelar($qr);
        }

        $this->qrActivoId = null;
    }

    public function render()
    {
        return view('livewire.portal.pagar-expensas')
            ->layout('layouts.portal');
    }
}
