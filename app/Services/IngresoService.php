<?php

namespace App\Services;

use App\Models\Ingreso;
use App\Models\SecuenciaCaja;
use App\Models\CajaTotal;
use App\Traits\ComprobanteStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Exception;

class IngresoService
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
     * Crear un nuevo ingreso
     */
    public function crear(array $datos, $archivoComprobante = null): Ingreso
    {
        return DB::transaction(function () use ($datos, $archivoComprobante) {
            // Generar número de recibo automático o usar manual si se proporciona
            $fecha = \Carbon\Carbon::parse($datos['fecha']);

            if (isset($datos['numero_recibo']) && !empty($datos['numero_recibo'])) {
                // Usar número manual proporcionado
                $numeroRecibo = $datos['numero_recibo'];
            } else {
                // Generar número de recibo automático
                $numeroRecibo = SecuenciaCaja::generarNumeroRecibo(
                    $fecha->year,
                    $fecha->month
                );
            }

            // Procesar comprobante si se proporciona
            $rutaComprobante = null;
            if ($archivoComprobante) {
                $rutaComprobante = $this->procesarImagenWebP($archivoComprobante, 'ingresos', $fecha);
            }

            // Crear ingreso
            $ingreso = Ingreso::create([
                'numero_recibo' => $numeroRecibo,
                'fecha' => $datos['fecha'],
                'categoria_ingreso_id' => $datos['categoria_ingreso_id'],
                'importe' => $datos['importe'],
                'metodo_pago' => $datos['metodo_pago'] ?? 'efectivo',
                'descripcion' => $datos['descripcion'] ?? null,
                'comprobante_foto' => $rutaComprobante,
                'referencia_bancaria' => $datos['referencia_bancaria'] ?? null,
                'unidad_id' => $datos['unidad_id'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
                'activo' => true,
                'creado_por' => Auth::id(),
                'actualizado_por' => Auth::id(),
            ]);

            // Actualizar totales del día
            $this->actualizarTotalesDia($datos['fecha']);

            // Registrar en Activity Log
            $this->registrarActividad('ingreso.creado', $ingreso);

            return $ingreso;
        });
    }

    /**
     * Actualizar un ingreso existente
     */
    public function actualizar(Ingreso $ingreso, array $datos, $archivoComprobante = null): Ingreso
    {
        return DB::transaction(function () use ($ingreso, $datos, $archivoComprobante) {
            $fechaOriginal = $ingreso->fecha->format('Y-m-d');
            $fechaNueva = \Carbon\Carbon::parse($datos['fecha'])->format('Y-m-d');
            $montoOriginal = $ingreso->importe;

            // Verificar si es un pago de expensa
            $esPagoExpensa = !is_null($ingreso->expensa_total_id);
            $expensaTotalId = $ingreso->expensa_total_id;

            // Procesar nuevo comprobante si se proporciona
            if ($archivoComprobante) {
                // Eliminar comprobante anterior si existe (private o public)
                $this->borrarComprobante($ingreso->comprobante_foto);

                $fecha = \Carbon\Carbon::parse($datos['fecha']);
                $datos['comprobante_foto'] = $this->procesarImagenWebP($archivoComprobante, 'ingresos', $fecha);
            }

            $datos['actualizado_por'] = Auth::id();

            // Actualizar ingreso
            $ingreso->update($datos);

            // Si es pago de expensa y cambió el monto, recalcular expensa_total
            if ($esPagoExpensa && isset($datos['importe']) && $datos['importe'] != $montoOriginal) {
                $this->recalcularExpensaTotal($expensaTotalId);
            }

            // Actualizar totales de ambas fechas si cambió la fecha
            if ($fechaOriginal !== $fechaNueva) {
                $this->actualizarTotalesDia($fechaOriginal);
            }
            $this->actualizarTotalesDia($fechaNueva);

            // Registrar en Activity Log
            $this->registrarActividad('ingreso.actualizado', $ingreso);

            return $ingreso->fresh();
        });
    }

    /**
     * Eliminar un ingreso (soft delete)
     */
    public function eliminar(Ingreso $ingreso): bool
    {
        return DB::transaction(function () use ($ingreso) {
            $fecha = $ingreso->fecha->format('Y-m-d');

            // Verificar si es un pago de expensa (nueva arquitectura)
            if ($ingreso->tipo_ingreso === 'expensa') {
                // Revertir pagos aplicados a expensas
                $this->revertirPagoExpensa($ingreso);
            }

            // Verificar arquitectura antigua (legacy)
            $esPagoExpensaLegacy = !is_null($ingreso->expensa_total_id);
            $expensaTotalId = $ingreso->expensa_total_id;

            // Eliminar archivo de comprobante si existe (private o public)
            $this->borrarComprobante($ingreso->comprobante_foto);

            // Soft delete
            $ingreso->delete();

            // Si es pago de expensa (arquitectura antigua), recalcular expensa_total
            if ($esPagoExpensaLegacy) {
                $this->recalcularExpensaTotal($expensaTotalId);
            }

            // Actualizar totales del día
            $this->actualizarTotalesDia($fecha);

            // Registrar en Activity Log
            $this->registrarActividad('ingreso.eliminado', $ingreso);

            return true;
        });
    }

    /**
     * Revertir pagos aplicados a expensas (nueva arquitectura)
     * Restaura las deudas pendientes de las expensas cuando se anula un pago
     */
    protected function revertirPagoExpensa(Ingreso $ingreso): void
    {
        // Cargar detalles con expensas
        $detalles = $ingreso->detalles()
            ->with('expensa')
            ->get();

        foreach ($detalles as $detalle) {
            if (!$detalle->expensa) continue;

            // Bloquear expensa para actualizar
            $expensa = \App\Models\Expensa::lockForUpdate()->findOrFail($detalle->expensa_id);

            // Restaurar las deudas pendientes
            $expensa->update([
                'expensa_pendiente' => $expensa->expensa_pendiente + $detalle->monto_expensa_aplicado,
                'agua_pendiente' => $expensa->agua_pendiente + $detalle->monto_agua_aplicado,
            ]);

            // Log para auditoría
            \Log::info('Pago de expensa anulado - deudas restauradas', [
                'ingreso_id' => $ingreso->id,
                'numero_recibo' => $ingreso->numero_recibo,
                'expensa_id' => $expensa->id,
                'monto_expensa_devuelto' => $detalle->monto_expensa_aplicado,
                'monto_agua_devuelto' => $detalle->monto_agua_aplicado,
            ]);
        }

        // Eliminar los saldos a favor generados por este ingreso
        \App\Models\SaldoAFavor::where('ingreso_id', $ingreso->id)->delete();
    }

    /**
     * Restaurar un ingreso eliminado
     */
    public function restaurar(int $id): Ingreso
    {
        return DB::transaction(function () use ($id) {
            $ingreso = Ingreso::withTrashed()->findOrFail($id);

            // Si es pago de expensa (nueva arquitectura), reaplicar pagos
            if ($ingreso->tipo_ingreso === 'expensa') {
                $this->reaplicarPagoExpensa($ingreso);
            }

            // Verificar arquitectura antigua (legacy)
            $esPagoExpensaLegacy = !is_null($ingreso->expensa_total_id);
            $expensaTotalId = $ingreso->expensa_total_id;

            $ingreso->restore();

            // Si es pago de expensa (arquitectura antigua), recalcular expensa_total
            if ($esPagoExpensaLegacy) {
                $this->recalcularExpensaTotal($expensaTotalId);
            }

            // Actualizar totales del día
            $this->actualizarTotalesDia($ingreso->fecha->format('Y-m-d'));

            // Registrar en Activity Log
            $this->registrarActividad('ingreso.restaurado', $ingreso);

            return $ingreso;
        });
    }

    /**
     * Reaplicar pagos a expensas (nueva arquitectura)
     * Vuelve a aplicar los montos cuando se restaura un pago anulado
     */
    protected function reaplicarPagoExpensa(Ingreso $ingreso): void
    {
        // Cargar detalles con expensas
        $detalles = $ingreso->detalles()
            ->with('expensa')
            ->get();

        foreach ($detalles as $detalle) {
            if (!$detalle->expensa) continue;

            // Bloquear expensa para actualizar
            $expensa = \App\Models\Expensa::lockForUpdate()->findOrFail($detalle->expensa_id);

            // Verificar que haya deuda pendiente para aplicar
            $nuevaExpensaPendiente = $expensa->expensa_pendiente - $detalle->monto_expensa_aplicado;
            $nuevaAguaPendiente = $expensa->agua_pendiente - $detalle->monto_agua_aplicado;

            // No permitir que quede negativo (indica inconsistencia)
            if ($nuevaExpensaPendiente < 0 || $nuevaAguaPendiente < 0) {
                \Log::warning('Inconsistencia al reaplicar pago - saldo insuficiente', [
                    'ingreso_id' => $ingreso->id,
                    'numero_recibo' => $ingreso->numero_recibo,
                    'expensa_id' => $expensa->id,
                    'expensa_pendiente_actual' => $expensa->expensa_pendiente,
                    'monto_expensa_a_aplicar' => $detalle->monto_expensa_aplicado,
                    'agua_pendiente_actual' => $expensa->agua_pendiente,
                    'monto_agua_a_aplicar' => $detalle->monto_agua_aplicado,
                ]);
                // Aplicar hasta 0 como máximo
                $nuevaExpensaPendiente = max(0, $nuevaExpensaPendiente);
                $nuevaAguaPendiente = max(0, $nuevaAguaPendiente);
            }

            // Aplicar los pagos
            $expensa->update([
                'expensa_pendiente' => $nuevaExpensaPendiente,
                'agua_pendiente' => $nuevaAguaPendiente,
            ]);

            // Log para auditoría
            \Log::info('Pago de expensa restaurado - pagos reaplicados', [
                'ingreso_id' => $ingreso->id,
                'numero_recibo' => $ingreso->numero_recibo,
                'expensa_id' => $expensa->id,
                'monto_expensa_aplicado' => $detalle->monto_expensa_aplicado,
                'monto_agua_aplicado' => $detalle->monto_agua_aplicado,
            ]);
        }

        // Nota: No recreamos saldos a favor porque si se borraron al anular,
        // podría haber inconsistencias. Si se necesitan, deben recrearse manualmente.
    }

    /**
     * Procesar imagen y convertirla a WebP
     */
    protected function procesarImagenWebP($archivo, string $tipo, $fecha): string
    {
        $driverUsado = extension_loaded('imagick') ? 'imagick' : 'gd';
        $path = method_exists($archivo, 'getRealPath') ? $archivo->getRealPath() : $archivo;

        try {
            // Leer la imagen (Intervention Image v4.0.0 usa decode(), no read())
            $image = $this->imageManager->decode($path);

            // Redimensionar si es muy grande (max 1600x1200 — suficiente para comprobantes)
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

            // Guardar como WebP con quality 70 (buen balance calidad/tamaño para comprobantes)
            $image->save(storage_path('app/private/' . $ruta), quality: 70);

            return $ruta;
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al procesar imagen WebP', [
                'service' => 'IngresoService',
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
     */
    protected function registrarActividad(string $accion, Ingreso $ingreso): void
    {
        if (class_exists(\App\Models\ActivityLog::class)) {
            \App\Models\ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => $accion,
                'model_type' => Ingreso::class,
                'model_id' => $ingreso->id,
                'description' => match($accion) {
                    'ingreso.creado' => "Ingreso {$ingreso->numero_recibo} creado",
                    'ingreso.actualizado' => "Ingreso {$ingreso->numero_recibo} actualizado",
                    'ingreso.eliminado' => "Ingreso {$ingreso->numero_recibo} eliminado",
                    'ingreso.restaurado' => "Ingreso {$ingreso->numero_recibo} restaurado",
                    default => "Acción {$accion} en ingreso {$ingreso->numero_recibo}",
                },
                'properties' => [
                    'numero_recibo' => $ingreso->numero_recibo,
                    'importe' => $ingreso->importe,
                    'metodo_pago' => $ingreso->metodo_pago,
                    'categoria' => $ingreso->categoria?->nombre,
                    'fecha' => $ingreso->fecha->format('Y-m-d'),
                ],
            ]);
        }
    }

    /**
     * Recalcular expensa_total después de editar/eliminar un pago
     *
     * @param int $expensaTotalId
     * @return void
     */
    protected function recalcularExpensaTotal(int $expensaTotalId): void
    {
        $expensaTotal = \App\Models\ExpensaTotal::find($expensaTotalId);

        if (!$expensaTotal) {
            return;
        }

        // Sumar todos los pagos (ingresos) activos de este expensa_total
        $montoPagado = Ingreso::where('expensa_total_id', $expensaTotalId)
            ->activos()
            ->sum('importe');

        // Calcular nuevo monto pendiente
        $montoPendiente = $expensaTotal->monto_total - $montoPagado;

        // Calcular saldo a favor si sobra
        $saldoFavor = 0;
        if ($montoPendiente < 0) {
            $saldoFavor = abs($montoPendiente);
            $montoPendiente = 0;
        }

        // Determinar nuevo estado
        $nuevoEstado = $expensaTotal->estado;
        if ($montoPendiente == 0 && $saldoFavor == 0) {
            $nuevoEstado = 'pagado';
        } elseif ($montoPagado > 0 && $montoPendiente > 0) {
            $nuevoEstado = 'parcial';
        } elseif ($montoPagado == 0) {
            $nuevoEstado = 'pendiente';
        }

        // Actualizar expensa_total
        $expensaTotal->update([
            'monto_pagado' => $montoPagado,
            'monto_pendiente' => $montoPendiente,
            'saldo_a_favor' => $saldoFavor,
            'estado' => $nuevoEstado,
        ]);

        // Registrar en Activity Log
        if (class_exists(\App\Models\ActivityLog::class)) {
            \App\Models\ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'expensa_total.recalculada',
                'model_type' => \App\Models\ExpensaTotal::class,
                'model_id' => $expensaTotal->id,
                'description' => "Expensa total recalculada automáticamente",
                'properties' => [
                    'expensa_total_id' => $expensaTotal->id,
                    'propietario' => $expensaTotal->propietario->nombre . ' ' . $expensaTotal->propietario->apellido,
                    'periodo' => $expensaTotal->periodo->nombre_periodo,
                    'monto_pagado' => $montoPagado,
                    'monto_pendiente' => $montoPendiente,
                    'saldo_a_favor' => $saldoFavor,
                    'estado' => $nuevoEstado,
                ],
            ]);
        }
    }
}
