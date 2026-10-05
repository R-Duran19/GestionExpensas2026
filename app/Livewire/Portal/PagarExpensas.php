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
 * [REDESIGN 2026-09-24] Réplica EXACTA del selector de deudas de
 * Caja → Ingresos (IngresoForm): deudas agrupadas POR PERÍODO de TODAS las
 * propiedades, checkbox + monto editable por concepto (expensa/agua),
 * seleccionar/deseleccionar por período, totales en tiempo real.
 *
 * El monto del QR es SIEMPRE el total seleccionado (el banco cobra monto
 * exacto); la acreditación aplica EXACTAMENTE esa distribución (guardada en
 * `seleccion.distribucion`) con FIFO/excedente→SaldoAFavor de PagoExpensaService.
 */
#[Title('Pagar Expensas')]
class PagarExpensas extends Component
{
    /** Deudas agrupadas por período: [{periodo_nombre, expensas: [[...]]}] */
    public array $deudasPendientes = [];

    /** Totales (caja-style: recalculados con calcularTotales()) */
    public $totalDeuda = 0;
    public $totalAPagar = 0;

    /** QR activo (id) para UI. */
    #[Locked]
    public ?int $qrActivoId = null;

    public bool $pagoExitoso = false;
    public ?int $ingresoId = null;

    public function mount(): void
    {
        $this->cargarDeudas();

        // Re-enganchar QR activo (consistencia: recargar no genera otro)
        $activo = $this->service()->obtenerActivo($this->user);

        if ($activo) {
            $this->qrActivoId = $activo->id;

            // Si ya fue pagado (pagó desde el celular y volvió), pantalla de éxito
            if ($activo->estado === PagoQr::ESTADO_PAGADO) {
                $this->pagoExitoso = true;
                $this->ingresoId = $activo->ingreso_id;
            }
        }
    }

    protected function service(): PagoQrService
    {
        return app(PagoQrService::class);
    }

    #[Computed]
    public function user(): \App\Models\User
    {
        return auth()->user();
    }

    /**
     * Unidades visibles (regla maestra / autorización) — para el QR y el resumen.
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

    /**
     * Saldo a favor disponible de TODAS las unidades (como Caja → Ingresos).
     */
    #[Computed]
    public function saldoFavorDisponible(): float
    {
        $ids = $this->unidades->pluck('id');

        return $ids->isEmpty() ? 0.0 : (float) SaldoAFavor::whereIn('unidad_id', $ids)
            ->where('monto_saldo', '>', 0)
            ->sum('monto_saldo');
    }

    /**
     * [como cargarDeudasPropietario de Caja] Deudas de TODAS las unidades,
     * agrupadas por período (más antiguas primero para el orden de la tabla).
     */
    public function cargarDeudas(): void
    {
        $unidadIds = $this->unidades->pluck('id');
        $this->deudasPendientes = [];

        if ($unidadIds->isEmpty()) {
            $this->calcularTotales();
            return;
        }

        $expensas = Expensa::whereIn('unidad_id', $unidadIds)
            ->where(function ($q) {
                $q->where('expensa_pendiente', '>', 0)
                  ->orWhere('agua_pendiente', '>', 0);
            })
            ->with(['periodo', 'unidad'])
            ->join('periodos_expensa as pe', 'pe.id', '=', 'expensas.periodo_id')
            ->orderBy('pe.año')
            ->orderBy('pe.mes')
            ->select('expensas.*')
            ->get();

        // Agrupar por período (nombre), como Caja
        $agrupadas = [];
        foreach ($expensas as $expensa) {
            $periodoNombre = $expensa->periodo->nombre_periodo;
            $tieneMedidor = $expensa->unidad->tiene_medidor ?? false;

            $agrupadas[$periodoNombre][] = [
                'expensa_id'      => $expensa->id,
                'unidad_id'       => $expensa->unidad_id,
                'unidad_codigo'   => $expensa->unidad->codigo,
                'periodo'         => $periodoNombre,
                'tiene_medidor'   => $tieneMedidor,
                'deuda_expensa'   => (float) $expensa->expensa_pendiente,
                'deuda_agua'      => $tieneMedidor ? (float) $expensa->agua_pendiente : 0,
                'deuda_total'     => (float) $expensa->expensa_pendiente + ($tieneMedidor ? (float) $expensa->agua_pendiente : 0),
                'pagar_expensa'   => false,
                'pagar_agua'      => false,
                'monto_expensa'   => 0,
                'monto_agua'      => 0,
            ];
        }

        foreach ($agrupadas as $periodoNombre => $filas) {
            $this->deudasPendientes[] = [
                'periodo_nombre' => $periodoNombre,
                'expensas' => $filas,
            ];
        }

        $this->calcularTotales();
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos idénticos a Caja → Ingresos (IngresoForm)
    |--------------------------------------------------------------------------
    */

    public function seleccionarTodasDeudas(): void
    {
        foreach ($this->deudasPendientes as &$periodoData) {
            foreach ($periodoData['expensas'] as &$expensa) {
                $expensa['pagar_expensa'] = $expensa['deuda_expensa'] > 0;
                $expensa['pagar_agua'] = $expensa['deuda_agua'] > 0;
                $expensa['monto_expensa'] = $expensa['deuda_expensa'];
                $expensa['monto_agua'] = $expensa['deuda_agua'];
            }
        }
        $this->calcularTotales();
    }

    public function deseleccionarTodasDeudas(): void
    {
        foreach ($this->deudasPendientes as &$periodoData) {
            foreach ($periodoData['expensas'] as &$expensa) {
                $expensa['pagar_expensa'] = false;
                $expensa['pagar_agua'] = false;
                $expensa['monto_expensa'] = 0;
                $expensa['monto_agua'] = 0;
            }
        }
        $this->calcularTotales();
    }

    public function seleccionarPeriodo($periodoIndex): void
    {
        if (!isset($this->deudasPendientes[$periodoIndex])) {
            return;
        }

        foreach ($this->deudasPendientes[$periodoIndex]['expensas'] as &$expensa) {
            $expensa['pagar_expensa'] = $expensa['deuda_expensa'] > 0;
            $expensa['pagar_agua'] = $expensa['deuda_agua'] > 0;
            $expensa['monto_expensa'] = $expensa['deuda_expensa'];
            $expensa['monto_agua'] = $expensa['deuda_agua'];
        }
        $this->calcularTotales();
    }

    public function deseleccionarPeriodo($periodoIndex): void
    {
        if (!isset($this->deudasPendientes[$periodoIndex])) {
            return;
        }

        foreach ($this->deudasPendientes[$periodoIndex]['expensas'] as &$expensa) {
            $expensa['pagar_expensa'] = false;
            $expensa['pagar_agua'] = false;
            $expensa['monto_expensa'] = 0;
            $expensa['monto_agua'] = 0;
        }
        $this->calcularTotales();
    }

    public function actualizarMontoExpensa($periodoIndex, $expensaIndex): void
    {
        if (!isset($this->deudasPendientes[$periodoIndex]['expensas'][$expensaIndex])) {
            return;
        }

        $expensa = &$this->deudasPendientes[$periodoIndex]['expensas'][$expensaIndex];

        if ($expensa['pagar_expensa']) {
            $monto = floatval($expensa['monto_expensa'] ?? 0);
            if ($monto == 0) {
                $expensa['monto_expensa'] = $expensa['deuda_expensa'];
            }
        } else {
            $expensa['monto_expensa'] = 0;
        }

        $this->calcularTotales();
    }

    public function actualizarMontoAgua($periodoIndex, $expensaIndex): void
    {
        if (!isset($this->deudasPendientes[$periodoIndex]['expensas'][$expensaIndex])) {
            return;
        }

        $expensa = &$this->deudasPendientes[$periodoIndex]['expensas'][$expensaIndex];

        if ($expensa['pagar_agua']) {
            $monto = floatval($expensa['monto_agua'] ?? 0);
            if ($monto == 0) {
                $expensa['monto_agua'] = $expensa['deuda_agua'];
            }
        } else {
            $expensa['monto_agua'] = 0;
        }

        $this->calcularTotales();
    }

    public function actualizarDesdeInputExpensa($periodoIndex, $expensaIndex): void
    {
        if (!isset($this->deudasPendientes[$periodoIndex]['expensas'][$expensaIndex])) {
            return;
        }

        $expensa = &$this->deudasPendientes[$periodoIndex]['expensas'][$expensaIndex];
        $monto = floatval($expensa['monto_expensa'] ?? 0);
        $expensa['pagar_expensa'] = $monto > 0;

        $this->calcularTotales();
    }

    public function actualizarDesdeInputAgua($periodoIndex, $expensaIndex): void
    {
        if (!isset($this->deudasPendientes[$periodoIndex]['expensas'][$expensaIndex])) {
            return;
        }

        $expensa = &$this->deudasPendientes[$periodoIndex]['expensas'][$expensaIndex];
        $monto = floatval($expensa['monto_agua'] ?? 0);
        $expensa['pagar_agua'] = $monto > 0;

        $this->calcularTotales();
    }

    public function updatedDeudasPendientes(): void
    {
        $this->calcularTotales();
    }

    public function periodoTieneSeleccionados($periodoIndex): bool
    {
        if (!isset($this->deudasPendientes[$periodoIndex])) {
            return false;
        }

        foreach ($this->deudasPendientes[$periodoIndex]['expensas'] as $expensa) {
            if (($expensa['pagar_expensa'] && floatval($expensa['monto_expensa'] ?? 0) > 0) ||
                ($expensa['pagar_agua'] && floatval($expensa['monto_agua'] ?? 0) > 0)) {
                return true;
            }
        }

        return false;
    }

    /**
     * [como obtenerExpensasAPagar de Caja] Lista de expensas con concepto
     * y monto exacto para la acreditación.
     */
    protected function obtenerExpensasAPagar(): array
    {
        $expensasAProcesar = [];

        foreach ($this->deudasPendientes as $periodoData) {
            foreach ($periodoData['expensas'] as $expensa) {
                $tieneExpensa = $expensa['pagar_expensa'] && floatval($expensa['monto_expensa'] ?? 0) > 0;
                $tieneAgua = $expensa['pagar_agua'] && floatval($expensa['monto_agua'] ?? 0) > 0;

                if ($tieneExpensa || $tieneAgua) {
                    if ($tieneExpensa && $tieneAgua) {
                        $concepto = 'ambos';
                        $montoTotal = floatval($expensa['monto_expensa']) + floatval($expensa['monto_agua']);
                    } elseif ($tieneExpensa) {
                        $concepto = 'expensa';
                        $montoTotal = floatval($expensa['monto_expensa']);
                    } else {
                        $concepto = 'agua';
                        $montoTotal = floatval($expensa['monto_agua']);
                    }

                    $expensasAProcesar[] = [
                        'expensa_id'    => $expensa['expensa_id'],
                        'unidad_codigo' => $expensa['unidad_codigo'],
                        'monto_aplicar' => round($montoTotal, 2),
                        'concepto'      => $concepto,
                        'monto_expensa' => round(floatval($expensa['monto_expensa'] ?? 0), 2),
                        'monto_agua'    => round(floatval($expensa['monto_agua'] ?? 0), 2),
                    ];
                }
            }
        }

        return $expensasAProcesar;
    }

    /**
     * [como calcularTotales de Caja]
     */
    public function calcularTotales(): void
    {
        $todas = collect($this->deudasPendientes)->pluck('expensas')->flatten(1);

        $this->totalDeuda = $todas->sum('deuda_total');

        $this->totalAPagar = round($todas->sum(function ($e) {
            $total = 0;
            if ($e['pagar_expensa']) {
                $total += floatval($e['monto_expensa'] ?? 0);
            }
            if ($e['pagar_agua']) {
                $total += floatval($e['monto_agua'] ?? 0);
            }
            return $total;
        }), 2);
    }

    /*
    |--------------------------------------------------------------------------
    | QR
    |--------------------------------------------------------------------------
    */

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
     * Generar (o devolver) el QR del pago con la selección actual.
     */
    public function generarQr(): void
    {
        $distribucion = $this->obtenerExpensasAPagar();

        if (empty($distribucion)) {
            $this->dispatch('toast-error', message: 'Selecciona al menos una expensa o agua a pagar');
            return;
        }

        $monto = round(collect($distribucion)->sum('monto_aplicar'), 2);

        if ($monto <= 0) {
            $this->dispatch('toast-error', message: 'El monto a pagar debe ser mayor a cero');
            return;
        }

        $unidades = collect($distribucion)->pluck('unidad_codigo')->unique();
        $glosa = $unidades->count() > 1
            ? "Expensas {$unidades->count()} unidades"
            : "Expensas unidad {$unidades->first()}";

        $seleccion = [
            'glosa' => $glosa,
            'unidades' => $unidades->values()->all(),
            // Distribución EXACTA elegida por el usuario (la acreditación la
            // aplica tal cual, con caps por si cambió la deuda mientras tanto)
            'distribucion' => $distribucion,
        ];

        try {
            // Unidad de referencia = la del primer ítem de la distribución
            $primeraUnidadId = Expensa::find($distribucion[0]['expensa_id'])?->unidad_id
                ?? $this->unidades->first()?->id;

            $qr = $this->service()->crearIntencion(
                $this->user,
                (int) $primeraUnidadId,
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
     * Verificar estado en el banco.
     * Polling (15s) usa cache de 5s; el botón manual fuerza consulta real.
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
