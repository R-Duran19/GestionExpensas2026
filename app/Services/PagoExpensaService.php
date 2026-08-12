<?php

namespace App\Services;

use App\Models\Ingreso;
use App\Models\IngresoDetalle;
use App\Models\Expensa;
use App\Models\SaldoAFavor;
use App\Models\CategoriaIngreso;
use App\Models\SecuenciaCaja;
use App\Models\CajaTotal;
use App\Traits\ComprobanteStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Exception;
use Carbon\Carbon;

class PagoExpensaService
{
    // [SEGURIDAD-09 / Fase 1.2] Helpers para borrar/verificar comprobantes
    // en disco privado (local) con fallback a publico (public).
    use ComprobanteStorage;

    protected ImageManager $imageManager;

    public function __construct()
    {
        // Usa Imagick si está disponible (mucho más rápido para WebP), si no GD como fallback.
        $driver = extension_loaded('imagick') ? new ImagickDriver() : new GdDriver();
        $this->imageManager = new ImageManager($driver);
    }

    /**
     * Procesar un recibo de pago con distribución granular
     *
     * @param array $datosPago
     * @param mixed $archivoComprobante
     * @return array{ingreso: Ingreso, detalles: \Illuminate\Support\Collection, saldos_favor: \Illuminate\Support\Collection}
     * @throws Exception
     */
    public function procesarRecibo(array $datosPago, $archivoComprobante = null): array
    {
        return DB::transaction(function () use ($datosPago, $archivoComprobante) {
            // 1. Validar que haya expensas seleccionadas
            if (empty($datosPago['expensas'])) {
                throw new Exception('Debe seleccionar al menos una expensa para pagar');
            }

            // 2. Calcular monto total del pago
            $montoTotalPago = collect($datosPago['expensas'])->sum('monto_aplicar');
            if ($montoTotalPago <= 0) {
                throw new Exception('El monto total debe ser mayor a cero');
            }

            // 3. Obtener o crear categoría "Expensas"
            $categoriaExpensas = $this->obtenerOCrearCategoriaExpensas();

            // 4. Generar número de recibo automático o usar manual si se proporciona
            $fecha = Carbon::parse($datosPago['fecha_pago'] ?? now());

            if (isset($datosPago['numero_recibo']) && !empty($datosPago['numero_recibo'])) {
                // Usar número manual proporcionado
                $numeroRecibo = $datosPago['numero_recibo'];
            } else {
                // Generar número de recibo automático
                $numeroRecibo = SecuenciaCaja::generarNumeroRecibo(
                    $fecha->year,
                    $fecha->month
                );
            }

            // 5. Procesar comprobante si se proporciona
            $rutaComprobante = null;
            if ($archivoComprobante) {
                $rutaComprobante = $this->procesarImagenWebP($archivoComprobante, 'pagos-expensas', $fecha);
            }

            // 6. Crear ingreso cabecera
            $ingreso = Ingreso::create([
                'numero_recibo' => $numeroRecibo,
                'fecha' => $fecha,
                'categoria_ingreso_id' => $categoriaExpensas->id,
                'importe' => $montoTotalPago,
                'monto_total' => $montoTotalPago,
                'tipo_ingreso' => 'expensa',
                'metodo_pago' => $datosPago['metodo_pago'] ?? 'efectivo',
                'descripcion' => $datosPago['descripcion'] ?? null,
                'comprobante_foto' => $rutaComprobante,
                'referencia_bancaria' => $datosPago['referencia_bancaria'] ?? null,
                'unidad_id' => null, // Se obtiene de los detalles
                'pagador_tipo' => $datosPago['pagador_tipo'] ?? null,
                'pagador_id' => $datosPago['pagador_id'] ?? null,
                'observaciones' => $datosPago['observaciones'] ?? null,
                'activo' => true,
                'creado_por' => Auth::id(),
                'actualizado_por' => Auth::id(),
            ]);

            // 7. Procesar cada expensa con bloqueo pesimista
            $detalles = collect();
            $saldosFavor = collect();

            foreach ($datosPago['expensas'] as $pagoExpensa) {
                $resultado = $this->procesarPagoExpensa(
                    $ingreso,
                    $pagoExpensa['expensa_id'],
                    $pagoExpensa['monto_aplicar'],
                    $pagoExpensa['concepto'] ?? 'ambos'
                );

                $detalles->push($resultado['detalle']);
                if ($resultado['saldo_favor']) {
                    $saldosFavor->push($resultado['saldo_favor']);
                }
            }

            // 8. Actualizar unidad_id en el ingreso (primera unidad del primer detalle)
            if ($detalles->isNotEmpty()) {
                $primeraUnidadId = $detalles->first()->expensa->unidad_id;
                $ingreso->update(['unidad_id' => $primeraUnidadId]);
            }

            // 9. Actualizar totales del día
            $this->actualizarTotalesDia($fecha->format('Y-m-d'));

            // 10. Registrar en Activity Log
            $this->registrarActividad('pago.expensa.procesado', $ingreso, $detalles);

            return [
                'ingreso' => $ingreso,
                'detalles' => $detalles,
                'saldos_favor' => $saldosFavor,
            ];
        });
    }

    /**
     * Actualizar un recibo de pago existente
     *
     * @param Ingreso $ingreso
     * @param array $datosPago
     * @param mixed $archivoComprobante
     * @return array{ingreso: Ingreso, detalles: \Illuminate\Support\Collection, saldos_favor: \Illuminate\Support\Collection}
     * @throws Exception
     */
    public function actualizarRecibo(Ingreso $ingreso, array $datosPago, $archivoComprobante = null): array
    {
        return DB::transaction(function () use ($ingreso, $datosPago, $archivoComprobante) {
            // 1. Validar que sea un pago de expensas
            if ($ingreso->tipo_ingreso !== 'expensa') {
                throw new Exception('Solo se pueden actualizar pagos de expensas');
            }

            // 2. Validar que haya expensas seleccionadas
            if (empty($datosPago['expensas'])) {
                throw new Exception('Debe seleccionar al menos una expensa para pagar');
            }

            // 3. Calcular monto total del pago
            $montoTotalPago = collect($datosPago['expensas'])->sum('monto_aplicar');
            if ($montoTotalPago <= 0) {
                throw new Exception('El monto total debe ser mayor a cero');
            }

            // 4. Revertir los pagos anteriores
            $this->revertirPagoAnterior($ingreso);

            // 5. Procesar comprobante si se proporciona
            $rutaComprobante = $ingreso->comprobante_foto; // Mantener el anterior si no se proporciona uno nuevo
            if ($archivoComprobante) {
                // Eliminar comprobante anterior si existe (private o public)
                // [SEGURIDAD-09 / Fase 1.2] Antes usaba Storage::exists('public/' . $ruta)
                // que apuntaba mal al disco default; ahora usa el trait con fallback.
                $this->borrarComprobante($ingreso->comprobante_foto);

                $fecha = Carbon::parse($datosPago['fecha_pago'] ?? $ingreso->fecha);
                $rutaComprobante = $this->procesarImagenWebP($archivoComprobante, 'pagos-expensas', $fecha);
            }

            // 6. Actualizar ingreso cabecera
            $fecha = Carbon::parse($datosPago['fecha_pago'] ?? $ingreso->fecha);
            $ingreso->update([
                'fecha' => $fecha,
                'importe' => $montoTotalPago,
                'monto_total' => $montoTotalPago,
                'metodo_pago' => $datosPago['metodo_pago'] ?? $ingreso->metodo_pago,
                'descripcion' => $datosPago['descripcion'] ?? $ingreso->descripcion,
                'comprobante_foto' => $rutaComprobante,
                'referencia_bancaria' => $datosPago['referencia_bancaria'] ?? $ingreso->referencia_bancaria,
                'pagador_tipo' => $datosPago['pagador_tipo'] ?? $ingreso->pagador_tipo,
                'pagador_id' => $datosPago['pagador_id'] ?? $ingreso->pagador_id,
                'observaciones' => $datosPago['observaciones'] ?? $ingreso->observaciones,
                'actualizado_por' => Auth::id(),
            ]);

            // 7. Procesar cada expensa con bloqueo pesimista
            $detalles = collect();
            $saldosFavor = collect();

            foreach ($datosPago['expensas'] as $pagoExpensa) {
                $resultado = $this->procesarPagoExpensa(
                    $ingreso,
                    $pagoExpensa['expensa_id'],
                    $pagoExpensa['monto_aplicar'],
                    $pagoExpensa['concepto'] ?? 'ambos',
                    $pagoExpensa['monto_expensa'] ?? 0,
                    $pagoExpensa['monto_agua'] ?? 0
                );

                $detalles->push($resultado['detalle']);
                if ($resultado['saldo_favor']) {
                    $saldosFavor->push($resultado['saldo_favor']);
                }
            }

            // 8. Actualizar unidad_id en el ingreso (primera unidad del primer detalle)
            if ($detalles->isNotEmpty()) {
                $primeraUnidadId = $detalles->first()->expensa->unidad_id;
                $ingreso->update(['unidad_id' => $primeraUnidadId]);
            }

            // 9. Actualizar totales del día (para ambas fechas si cambió)
            $fechaAnterior = $ingreso->getOriginal('fecha');
            if ($fechaAnterior != $fecha->format('Y-m-d')) {
                $this->actualizarTotalesDia($fechaAnterior);
            }
            $this->actualizarTotalesDia($fecha->format('Y-m-d'));

            // 10. Registrar en Activity Log
            $this->registrarActividad('pago.expensa.actualizado', $ingreso, $detalles);

            return [
                'ingreso' => $ingreso,
                'detalles' => $detalles,
                'saldos_favor' => $saldosFavor,
            ];
        });
    }

    /**
     * Revertir un pago anterior (restaurar deudas pendientes y eliminar detalles)
     *
     * @param Ingreso $ingreso
     * @return void
     * @throws Exception
     */
    protected function revertirPagoAnterior(Ingreso $ingreso): void
    {
        // Cargar detalles con expensas
        $detalles = $ingreso->detalles()
            ->with('expensa')
            ->get();

        foreach ($detalles as $detalle) {
            if (!$detalle->expensa) continue;

            // Bloquear expensa para actualizar
            $expensa = Expensa::lockForUpdate()->findOrFail($detalle->expensa_id);

            // Restaurar las deudas pendientes
            $expensa->update([
                'expensa_pendiente' => $expensa->expensa_pendiente + $detalle->monto_expensa_aplicado,
                'agua_pendiente' => $expensa->agua_pendiente + $detalle->monto_agua_aplicado,
            ]);

            // Log para auditoría
            \Log::info('Pago de expensa revertido', [
                'ingreso_id' => $ingreso->id,
                'expensa_id' => $expensa->id,
                'monto_expensa_devuelto' => $detalle->monto_expensa_aplicado,
                'monto_agua_devuelto' => $detalle->monto_agua_aplicado,
            ]);
        }

        // Eliminar los detalles del ingreso
        $ingreso->detalles()->delete();

        // Eliminar los saldos a favor generados por este ingreso
        SaldoAFavor::where('ingreso_id', $ingreso->id)->delete();
    }

    /**
     * Procesar pago de expensa
     *
     * @param Ingreso $ingreso
     * @param int $expensaId
     * @param float $montoAplicar
     * @param string $concepto
     * @return array{detalle: IngresoDetalle, saldo_favor: SaldoAFavor|null}
     * @throws Exception
     */
    protected function procesarPagoExpensa(
        Ingreso $ingreso,
        int $expensaId,
        float $montoAplicar,
        string $concepto
    ): array {
        // Bloqueo pesimista para evitar condiciones de carrera
        $expensa = Expensa::lockForUpdate()->findOrFail($expensaId);

        // Obtener saldos pendientes actuales
        $expensaPendiente = $expensa->expensa_pendiente;
        $aguaPendiente = $expensa->agua_pendiente;

        // Calcular distribución según concepto
        $montoExpensaAplicado = 0;
        $montoAguaAplicado = 0;

        switch ($concepto) {
            case 'expensa':
                $montoExpensaAplicado = min($montoAplicar, $expensaPendiente);
                break;
            case 'agua':
                $montoAguaAplicado = min($montoAplicar, $aguaPendiente);
                break;
            case 'ambos':
                // Aplicar primero a expensa, luego a agua
                $montoExpensaAplicado = min($montoAplicar, $expensaPendiente);
                $restante = $montoAplicar - $montoExpensaAplicado;
                $montoAguaAplicado = min($restante, $aguaPendiente);
                break;
            default:
                throw new Exception("Concepto de pago no válido: {$concepto}");
        }

        // Actualizar saldos pendientes de la expensa
        $expensa->update([
            'expensa_pendiente' => $expensaPendiente - $montoExpensaAplicado,
            'agua_pendiente' => $aguaPendiente - $montoAguaAplicado,
        ]);

        // Crear detalle del pago
        $detalle = IngresoDetalle::create([
            'ingreso_id' => $ingreso->id,
            'expensa_id' => $expensa->id,
            'concepto' => $concepto,
            'monto_aplicado' => $montoAplicar,
            'monto_expensa_aplicado' => $montoExpensaAplicado,
            'monto_agua_aplicado' => $montoAguaAplicado,
        ]);

        // Verificar si generó saldo a favor
        $saldoFavor = null;
        $montoTotalAplicado = $montoExpensaAplicado + $montoAguaAplicado;
        $sobrante = $montoAplicar - $montoTotalAplicado;

        if ($sobrante > 0.01) { // Tolerancia de 0.01 para errores de redondeo
            $saldoFavor = $this->crearSaldoAFavor(
                $expensa->unidad_id,
                $ingreso->id,
                $montoAplicar,
                $montoTotalAplicado,
                $sobrante
            );
        }

        // Log para auditoría
        \Log::info('Pago de expensa procesado', [
            'ingreso_id' => $ingreso->id,
            'expensa_id' => $expensa->id,
            'concepto' => $concepto,
            'monto_aplicado' => $montoAplicar,
            'monto_expensa_aplicado' => $montoExpensaAplicado,
            'monto_agua_aplicado' => $montoAguaAplicado,
            'saldo_favor_generado' => $saldoFavor ? $saldoFavor->monto_saldo : 0,
        ]);

        return [
            'detalle' => $detalle,
            'saldo_favor' => $saldoFavor,
        ];
    }

    /**
     * Crear un saldo a favor
     *
     * @param int $unidadId
     * @param int $ingresoId
     * @param float $montoOriginal
     * @param float $montoAplicado
     * @param float $montoSobrante
     * @return SaldoAFavor
     */
    protected function crearSaldoAFavor(
        int $unidadId,
        int $ingresoId,
        float $montoOriginal,
        float $montoAplicado,
        float $montoSobrante
    ): SaldoAFavor {
        return SaldoAFavor::create([
            'unidad_id' => $unidadId,
            'ingreso_id' => $ingresoId,
            'monto_original' => $montoOriginal,
            'monto_aplicado' => $montoAplicado,
            'monto_saldo' => $montoSobrante,
            'estado' => 'disponible',
            'creado_por' => Auth::id(),
        ]);
    }

    /**
     * Obtener deuda de una unidad para pagos
     *
     * @param int $unidadId
     * @param int|null $periodoId
     * @return array
     */
    public function obtenerDeudaUnidad(int $unidadId, ?int $periodoId = null): array
    {
        $query = Expensa::where('unidad_id', $unidadId);

        if ($periodoId) {
            $query->where('periodo_id', $periodoId);
        }

        $expensas = $query->with('periodo')->get();

        $detalles = [];
        $totalDeuda = 0;

        foreach ($expensas as $expensa) {
            $deudaExpensa = $expensa->expensa_pendiente;
            $deudaAgua = $expensa->agua_pendiente;
            $deudaTotal = $deudaExpensa + $deudaAgua;

            if ($deudaTotal > 0) {
                $detalles[] = [
                    'expensa_id' => $expensa->id,
                    'periodo' => $expensa->periodo->nombre_periodo,
                    'periodo_id' => $expensa->periodo->id,
                    'deuda_expensa' => $deudaExpensa,
                    'deuda_agua' => $deudaAgua,
                    'deuda_total' => $deudaTotal,
                    'periodo_cerrado' => $expensa->periodo_cerrado,
                ];
                $totalDeuda += $deudaTotal;
            }
        }

        // Obtener saldo a favor disponible
        $saldoAFavor = SaldoAFavor::deUnidad($unidadId)
            ->disponibles()
            ->sum('monto_saldo');

        return [
            'unidad_id' => $unidadId,
            'deuda_total' => $totalDeuda,
            'deuda_neta' => max(0, $totalDeuda - $saldoAFavor),
            'saldo_a_favor' => $saldoAFavor,
            'detalles' => $detalles,
        ];
    }

    /**
     * Obtener o crear categoría "Expensas"
     *
     * @return CategoriaIngreso
     */
    protected function obtenerOCrearCategoriaExpensas(): CategoriaIngreso
    {
        $categoria = CategoriaIngreso::where('nombre', 'Expensas')->first();

        if (!$categoria) {
            $categoria = CategoriaIngreso::create([
                'nombre' => 'Expensas',
                'descripcion' => 'Pagos de expensas comunes',
                'icono' => '💰',
                'color' => '#10B981',
                'activo' => true,
                'creado_por' => Auth::id(),
                'actualizado_por' => Auth::id(),
            ]);
        }

        return $categoria;
    }

    /**
     * Procesar imagen y convertirla a WebP
     *
     * @param mixed $archivo
     * @param string $tipo
     * @param Carbon $fecha
     * @return string
     * @throws Exception
     */
    protected function procesarImagenWebP($archivo, string $tipo, Carbon $fecha): string
    {
        $driverUsado = extension_loaded('imagick') ? 'imagick' : 'gd';
        $path = method_exists($archivo, 'getRealPath') ? $archivo->getRealPath() : $archivo;

        try {
            // Leer la imagen (Intervention Image v4.0.0 usa decode(), no read())
            $image = $this->imageManager->decode($path);

            // Redimensionar si es muy grande (max 1600x1200)
            $image->scaleDown(1600, 1200);

            // Generar nombre único (aleatorio, no predecible)
            // [SEGURIDAD-09 / Fase 1.6] uniqid() deriva de microtime del server -> predecible.
            $nombre = Str::random(40) . '.webp';

            // Ruta organizada por año/mes
            $ruta = sprintf(
                'caja/%s/%s/%s/%s',
                $tipo,
                $fecha->format('Y'),
                $fecha->format('m'),
                $nombre
            );

            // Crear directorio si no existe
            // [SEGURIDAD-09 / Fase 1.2] Escribir a disco privado (storage/app/private)
            $directorio = storage_path('app/private/' . dirname($ruta));
            if (!file_exists($directorio)) {
                mkdir($directorio, 0775, true);
            }

            // Guardar como WebP con quality 70
            $image->save(storage_path('app/private/' . $ruta), quality: 70);

            return $ruta;
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al procesar imagen WebP', [
                'service' => 'PagoExpensaService',
                'driver' => $driverUsado,
                'tipo' => $tipo,
                'archivo_path' => $path,
                'archivo_existe' => $path ? file_exists($path) : false,
                'archivo_tamano' => $path && file_exists($path) ? filesize($path) : null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new Exception('Error al procesar la imagen: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Actualizar los totales de una fecha específica
     *
     * @param string $fecha
     * @return void
     */
    protected function actualizarTotalesDia(string $fecha): void
    {
        $totales = CajaTotal::obtenerPorFecha($fecha);

        // Calcular totales de ingresos del día
        $ingresosQuery = Ingreso::porFecha($fecha)->activos();

        $totales->total_ingresos_efectivo = (clone $ingresosQuery)->porMetodoPago('efectivo')->sum('importe');
        $totales->total_ingresos_transferencia = (clone $ingresosQuery)->porMetodoPago('transferencia')->sum('importe');
        $totales->total_ingresos_qr = (clone $ingresosQuery)->porMetodoPago('qr')->sum('importe');
        $totales->total_ingresos_otro = (clone $ingresosQuery)->porMetodoPago('otro')->sum('importe');
        $totales->total_ingresos = (clone $ingresosQuery)->sum('importe');
        $totales->cantidad_ingresos = (clone $ingresosQuery)->count();

        // Calcular totales de egresos del día
        $egresosDia = \App\Models\Egreso::porFecha($fecha)->activos()->get();
        $totales->total_egresos = $egresosDia->sum('importe');
        $totales->cantidad_egresos = $egresosDia->count();

        // Calcular balance neto
        $totales->balance_neto = $totales->total_ingresos - $totales->total_egresos;

        $totales->actualizado_por = Auth::id();
        $totales->save();
    }

    /**
     * Registrar actividad en el log
     *
     * @param string $accion
     * @param Ingreso $ingreso
     * @param \Illuminate\Support\Collection $detalles
     * @return void
     */
    protected function registrarActividad(string $accion, Ingreso $ingreso, $detalles): void
    {
        if (class_exists(\App\Models\ActivityLog::class)) {
            $unidades = $detalles->map(fn($d) => $d->expensa->unidad->nombre_completo)->unique()->implode(', ');
            $periodos = $detalles->map(fn($d) => $d->expensa->periodo->nombre_periodo)->unique()->implode(', ');

            \App\Models\ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => $accion,
                'model_type' => Ingreso::class,
                'model_id' => $ingreso->id,
                'description' => "Recibo {$ingreso->numero_recibo} procesado - Unidades: {$unidades} - Períodos: {$periodos}",
                'properties' => [
                    'numero_recibo' => $ingreso->numero_recibo,
                    'monto_total' => $ingreso->monto_total,
                    'metodo_pago' => $ingreso->metodo_pago,
                    'cantidad_detalles' => $detalles->count(),
                    'unidades' => $unidades,
                    'periodos' => $periodos,
                    'fecha' => $ingreso->fecha->format('Y-m-d'),
                ],
            ]);
        }
    }

    /**
     * Obtener resumen de pagos de una unidad
     *
     * @param int $unidadId
     * @param array $filtros
     * @return array
     */
    public function obtenerResumenPagosUnidad(int $unidadId, array $filtros = []): array
    {
        $query = Ingreso::with(['detalles.expensa.periodo', 'saldosFavor'])
            ->whereHas('detalles.expensa', function ($q) use ($unidadId) {
                $q->where('unidad_id', $unidadId);
            })
            ->pagosExpensas();

        // Aplicar filtros de fecha
        if (isset($filtros['fecha_desde'])) {
            $query->whereDate('fecha', '>=', $filtros['fecha_desde']);
        }
        if (isset($filtros['fecha_hasta'])) {
            $query->whereDate('fecha', '<=', $filtros['fecha_hasta']);
        }

        $ingresos = $query->orderBy('fecha', 'desc')->get();

        $totalPagado = 0;
        $totalSaldosFavor = 0;
        $detalles = [];

        foreach ($ingresos as $ingreso) {
            // Calcular total aplicado a esta unidad
            $montoAplicadoUnidad = $ingreso->detalles
                ->filter(fn($d) => $d->expensa->unidad_id == $unidadId)
                ->sum('monto_aplicado');

            // Calcular saldos a favor para esta unidad
            $saldoFavorUnidad = $ingreso->saldosFavor
                ->filter(fn($s) => $s->unidad_id == $unidadId)
                ->sum('monto_saldo');

            $totalPagado += $montoAplicadoUnidad;
            $totalSaldosFavor += $saldoFavorUnidad;

            $detalles[] = [
                'recibo' => $ingreso->numero_recibo,
                'fecha' => $ingreso->fecha->format('Y-m-d'),
                'monto_aplicado' => $montoAplicadoUnidad,
                'saldo_favor' => $saldoFavorUnidad,
                'metodo_pago' => $ingreso->metodo_pago,
                'periodos' => $ingreso->detalles
                    ->filter(fn($d) => $d->expensa->unidad_id == $unidadId)
                    ->map(fn($d) => $d->expensa->periodo->nombre_periodo)
                    ->unique()
                    ->implode(', '),
            ];
        }

        return [
            'unidad_id' => $unidadId,
            'total_pagado' => $totalPagado,
            'total_saldos_favor' => $totalSaldosFavor,
            'cantidad_recibos' => $ingresos->count(),
            'detalles' => $detalles,
        ];
    }
}
