<?php

namespace App\Services;

use App\Models\Ingreso;
use App\Models\SecuenciaCaja;
use App\Models\CajaTotal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Exception;

class IngresoService
{
    protected ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new GdDriver());
    }

    /**
     * Crear un nuevo ingreso
     */
    public function crear(array $datos, $archivoComprobante = null): Ingreso
    {
        return DB::transaction(function () use ($datos, $archivoComprobante) {
            // Generar número de recibo
            $fecha = \Carbon\Carbon::parse($datos['fecha']);
            $numeroRecibo = SecuenciaCaja::generarNumeroRecibo(
                $fecha->year,
                $fecha->month
            );

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
                // Eliminar comprobante anterior si existe
                if ($ingreso->comprobante_foto && Storage::exists($ingreso->comprobante_foto)) {
                    Storage::delete($ingreso->comprobante_foto);
                }

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

            // Verificar si es un pago de expensa
            $esPagoExpensa = !is_null($ingreso->expensa_total_id);
            $expensaTotalId = $ingreso->expensa_total_id;

            // Eliminar archivo de comprobante si existe
            if ($ingreso->comprobante_foto && Storage::exists($ingreso->comprobante_foto)) {
                Storage::delete($ingreso->comprobante_foto);
            }

            // Soft delete
            $ingreso->delete();

            // Si es pago de expensa, recalcular expensa_total
            if ($esPagoExpensa) {
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
     * Restaurar un ingreso eliminado
     */
    public function restaurar(int $id): Ingreso
    {
        return DB::transaction(function () use ($id) {
            $ingreso = Ingreso::withTrashed()->findOrFail($id);

            // Verificar si es un pago de expensa
            $esPagoExpensa = !is_null($ingreso->expensa_total_id);
            $expensaTotalId = $ingreso->expensa_total_id;

            $ingreso->restore();

            // Si es pago de expensa, recalcular expensa_total
            if ($esPagoExpensa) {
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
     * Procesar imagen y convertirla a WebP
     */
    protected function procesarImagenWebP($archivo, string $tipo, $fecha): string
    {
        try {
            // Leer la imagen (v4 usa decode() en lugar de read())
            $image = $this->imageManager->decode($archivo);

            // Redimensionar si es muy grande (max 1920x1080)
            $image->scaleDown(1920, 1080);

            // Generar nombre único
            $nombre = uniqid('comprobante_') . '_' . time() . '.webp';

            // Ruta organizada por año/mes
            $ruta = sprintf(
                'caja/%s/%s/%s/%s',
                $tipo,
                $fecha->format('Y'),
                $fecha->format('m'),
                $nombre
            );

            // Crear directorio si no existe
            $directorio = storage_path('app/public/' . dirname($ruta));
            if (!file_exists($directorio)) {
                mkdir($directorio, 0755, true);
            }

            // Guardar como WebP (la extensión .webp indica el formato automáticamente)
            $image->save(storage_path('app/public/' . $ruta));

            return $ruta;
        } catch (Exception $e) {
            throw new Exception('Error al procesar la imagen: ' . $e->getMessage());
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
