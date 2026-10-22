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

    /** Bloqueo anti-spam del botón "Verificar ahora" (10 s tras cada chequeo). */
    public $cooldownHasta = null;

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
                // [UX 2026-10-15] aviso visible cuando el usuario intenta pagar
                // de más en un mes que no es el último (se clampea a la deuda)
                'aviso_expensa'   => false,
                'aviso_agua'      => false,
            ];
        }

        foreach ($agrupadas as $periodoNombre => $filas) {
            $this->deudasPendientes[] = [
                'periodo_nombre' => $periodoNombre,
                'expensas' => $filas,
            ];
        }

        // [REGLA SALDO A FAVOR 2026-10-15 — OPCIÓN 3, POR UNIDAD] Solo se
        // puede sobrepagar (excedente → SaldoAFavor) en la fila cuya unidad
        // NO tiene OTRAS deudas pendientes en el selector: primero pagas lo
        // viejo, recién adelantas. Los saldos a favor se crean por unidad,
        // así que la regla también es por unidad.
        $filasPorUnidad = [];
        foreach ($this->deudasPendientes as $p) {
            foreach ($p['expensas'] as $f) {
                $filasPorUnidad[$f['unidad_id']] = ($filasPorUnidad[$f['unidad_id']] ?? 0) + 1;
            }
        }
        foreach ($this->deudasPendientes as &$p) {
            foreach ($p['expensas'] as &$f) {
                $f['permite_sobrepago'] = ($filasPorUnidad[$f['unidad_id']] ?? 0) === 1;
            }
            unset($f);
        }
        unset($p);

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

    /**
     * [REGLA SALDO A FAVOR 2026-10-15 — OPCIÓN 3] Clamp del monto a la deuda
     * del mes, SALVO en la fila cuya unidad no tiene otras deudas pendientes
     * (ahí el excedente genera SaldoAFavor al acreditar). Si clampea, deja
     * aviso_expensa/aviso_agua en true para el feedback "Máx. Bs X".
     */
    protected function clampMontoSegunRegla(array &$expensa, string $campoMonto, string $campoDeuda, string $campoAviso): void
    {
        $permiteSobrepago = $expensa['permite_sobrepago'] ?? false;

        $max = floatval($expensa[$campoDeuda] ?? 0);
        $monto = floatval($expensa[$campoMonto] ?? 0);

        if (!$permiteSobrepago && $monto > $max) {
            $expensa[$campoMonto] = $max;
            $expensa[$campoAviso] = true;
        } else {
            $expensa[$campoAviso] = false;
        }
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
            $this->clampMontoSegunRegla($expensa, 'monto_expensa', 'deuda_expensa', 'aviso_expensa');
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
            $this->clampMontoSegunRegla($expensa, 'monto_agua', 'deuda_agua', 'aviso_agua');
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
        $this->clampMontoSegunRegla($expensa, 'monto_expensa', 'deuda_expensa', 'aviso_expensa');

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
        $this->clampMontoSegunRegla($expensa, 'monto_agua', 'deuda_agua', 'aviso_agua');

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
                // [REGLA SALDO A FAVOR — OPCIÓN 3] Clamp de servidor: filas
                // cuya unidad tiene otras deudas NUNCA pagan más que su
                // deuda, aunque la petición llegue manipulada. Filas de
                // unidad al día (única deuda) conservan el excedente.
                $permiteSobrepago = $expensa['permite_sobrepago'] ?? false;

                $montoExpensa = floatval($expensa['monto_expensa'] ?? 0);
                $montoAgua = floatval($expensa['monto_agua'] ?? 0);

                if (!$permiteSobrepago) {
                    $montoExpensa = min($montoExpensa, floatval($expensa['deuda_expensa'] ?? 0));
                    $montoAgua = min($montoAgua, floatval($expensa['deuda_agua'] ?? 0));
                }

                $montoExpensa = max(0, round($montoExpensa, 2));
                $montoAgua = max(0, round($montoAgua, 2));

                $tieneExpensa = $expensa['pagar_expensa'] && $montoExpensa > 0;
                $tieneAgua = $expensa['pagar_agua'] && $montoAgua > 0;

                if ($tieneExpensa || $tieneAgua) {
                    if ($tieneExpensa && $tieneAgua) {
                        $concepto = 'ambos';
                        $montoTotal = $montoExpensa + $montoAgua;
                    } elseif ($tieneExpensa) {
                        $concepto = 'expensa';
                        $montoTotal = $montoExpensa;
                    } else {
                        $concepto = 'agua';
                        $montoTotal = $montoAgua;
                    }

                    $expensasAProcesar[] = [
                        'expensa_id'    => $expensa['expensa_id'],
                        'unidad_codigo' => $expensa['unidad_codigo'],
                        'monto_aplicar' => round($montoTotal, 2),
                        'concepto'      => $concepto,
                        'monto_expensa' => $montoExpensa,
                        'monto_agua'    => $montoAgua,
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
     *
     * - Polling (15s) usa cache de 5s; el botón manual fuerza consulta real.
     * - Anti-spam: tras cada verificación el botón queda bloqueado 10 s
     *   (cooldownHasta) y SIEMPRE muestra un diálogo con el resultado.
     */
    public function verificar(bool $forzar = false): void
    {
        // Anti-spam SOLO para el botón manual: el polling automático de 15 s
        // no debe dejar el botón bloqueado casi permanentemente.
        if ($forzar) {
            $this->cooldownHasta = now()->addSeconds(10);
        }

        $qr = $this->qrActivo;

        if (!$qr || !$qr->estaPendiente()) {
            return; // sin QR pendiente no hay nada que avisar (el polling pasa por aquí)
        }

        try {
            $resultado = $this->service()->verificarYAcreditar($qr, $forzar);
        } catch (\Throwable $e) {
            report($e);
            // Banco caído: el estado local se mantiene (fallback como tickets).
            // El polling falla en silencio (report() ya lo logueó); solo el
            // click manual muestra el error.
            if ($forzar) {
                $this->dispatch('alerta', [
                    'tipo' => 'error',
                    'titulo' => 'No se pudo verificar',
                    'mensaje' => 'El banco no respondió en este momento. Tu QR sigue activo; intenta de nuevo en unos segundos.',
                ]);
            }
            return;
        }

        $qr->refresh();

        if ($resultado['pago_procesado']) {
            if ($qr->estado === PagoQr::ESTADO_PAGADO) {
                $this->pagoExitoso = true;
                $this->ingresoId = $qr->ingreso_id;
                $this->dispatch('alerta', [
                    'tipo' => 'success',
                    'titulo' => '¡Pago confirmado!',
                    'mensaje' => 'Tu pago fue acreditado correctamente. Ya puedes descargar tu recibo.',
                ]);
            } else {
                $this->dispatch('alerta', [
                    'tipo' => 'warning',
                    'titulo' => 'Pago con monto diferente',
                    'mensaje' => 'El banco reporta un pago con un monto distinto al del QR. El administrador lo revisará.',
                ]);
            }
            return;
        }

        // Sin procesamiento nuevo: el aviso es SOLO para el click manual.
        // El polling de 15 s verifica en silencio (no spamea diálogos) y
        // acreditará solito cuando el banco registre el pago.
        if (!$forzar) {
            return;
        }

        $mensajes = [
            PagoQr::ESTADO_PENDIENTE => ['info', 'Aún no pagado', 'Verificado con el banco: el QR todavía no ha sido pagado. Te sigue esperando; también puedes esperar la verificación automática.'],
            PagoQr::ESTADO_PAGADO    => ['info', 'Ya estaba confirmado', 'Este QR ya había sido pagado y acreditado. Descarga tu recibo desde Mis Pagos.'],
            PagoQr::ESTADO_EXPIRADO  => ['warning', 'El QR venció', 'El QR superó su tiempo de validez (1 hora) sin recibir el pago. Genera uno nuevo con tu selección.'],
            PagoQr::ESTADO_CANCELADO => ['warning', 'QR cancelado', 'Este QR fue cancelado. Genera uno nuevo con tu selección.'],
        ];

        [$tipo, $titulo, $mensaje] = $mensajes[$qr->estado] ?? ['info', 'Sin cambios', 'El estado del QR no ha cambiado.'];

        if ($qr->estado === PagoQr::ESTADO_PAGADO) {
            $this->pagoExitoso = true;
            $this->ingresoId = $qr->ingreso_id;
        }

        $this->dispatch('alerta', ['tipo' => $tipo, 'titulo' => $titulo, 'mensaje' => $mensaje]);
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
