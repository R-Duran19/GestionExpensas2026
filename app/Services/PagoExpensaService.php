<?php

namespace App\Services;

use App\Models\Ingreso;
use App\Models\ExpensaTotal;
use App\Models\CategoriaIngreso;
use App\Models\SecuenciaCaja;
use App\Models\CajaTotal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Exception;
use Carbon\Carbon;

class PagoExpensaService
{
    protected ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new GdDriver());
    }

    /**
     * Registrar un pago de expensa
     *
     * @param int $expensaTotalId
     * @param array $datosPago
     * @param mixed $archivoComprobante
     * @return Ingreso
     * @throws Exception
     */
    public function registrarPago(int $expensaTotalId, array $datosPago, $archivoComprobante = null): Ingreso
    {
        return DB::transaction(function () use ($expensaTotalId, $datosPago, $archivoComprobante) {
            // Obtener expensa total
            $expensaTotal = ExpensaTotal::with(['periodo', 'propietario'])
                ->findOrFail($expensaTotalId);

            // Validar monto del pago
            $this->validarMontoPago($datosPago['monto_pago'], $expensaTotal->monto_pendiente);

            // Obtener o crear categoría "Expensas"
            $categoriaExpensas = $this->obtenerOCrearCategoriaExpensas();

            // Generar número de recibo
            $fecha = Carbon::parse($datosPago['fecha_pago']);
            $numeroRecibo = SecuenciaCaja::generarNumeroRecibo(
                $fecha->year,
                $fecha->month
            );

            // Procesar comprobante si se proporciona
            $rutaComprobante = null;
            if ($archivoComprobante) {
                $rutaComprobante = $this->procesarImagenWebP($archivoComprobante, 'pagos-expensas', $fecha);
            }

            // Crear ingreso
            $ingreso = Ingreso::create([
                'numero_recibo' => $numeroRecibo,
                'fecha' => $datosPago['fecha_pago'],
                'categoria_ingreso_id' => $categoriaExpensas->id,
                'importe' => $datosPago['monto_pago'],
                'metodo_pago' => $datosPago['metodo_pago'],
                'descripcion' => $this->generarDescripcion($expensaTotal),
                'comprobante_foto' => $rutaComprobante,
                'referencia_bancaria' => $datosPago['referencia_bancaria'] ?? null,
                'unidad_id' => null, // Los pagos de expensas no se asocian a una unidad específica
                'expensa_total_id' => $expensaTotal->id,
                'propietario_id' => $expensaTotal->propietario_id,
                'observaciones' => $datosPago['observaciones'] ?? null,
                'activo' => true,
                'creado_por' => Auth::id(),
                'actualizado_por' => Auth::id(),
            ]);

            // Log para debug
            if ($rutaComprobante) {
                \Log::info('Pago de expensa creado con comprobante', [
                    'ingreso_id' => $ingreso->id,
                    'comprobante_foto_bd' => $ingreso->comprobante_foto,
                    'archivo_existe' => Storage::disk('public')->exists($ingreso->comprobante_foto),
                    'storage_url' => Storage::disk('public')->url($ingreso->comprobante_foto),
                ]);
            }

            // Actualizar expensa_total
            $this->actualizarExpensaTotal($expensaTotal, $datosPago['monto_pago']);

            // Actualizar totales del día
            $this->actualizarTotalesDia($datosPago['fecha_pago']);

            // Registrar en Activity Log
            $this->registrarActividad('pago.expensa.registrado', $ingreso, $expensaTotal);

            return $ingreso;
        });
    }

    /**
     * Actualizar expensa_total después de un pago
     *
     * @param ExpensaTotal $expensaTotal
     * @param float $montoPago
     * @return void
     */
    protected function actualizarExpensaTotal(ExpensaTotal $expensaTotal, float $montoPago): void
    {
        // Sumar al monto pagado
        $nuevoMontoPagado = $expensaTotal->monto_pagado + $montoPago;

        // Calcular nuevo monto pendiente
        $nuevoMontoPendiente = $expensaTotal->monto_total - $nuevoMontoPagado;

        // Calcular saldo a favor si sobra
        $saldoFavor = 0;
        if ($nuevoMontoPendiente < 0) {
            $saldoFavor = abs($nuevoMontoPendiente);
            $nuevoMontoPendiente = 0;
        }

        // Determinar nuevo estado
        $nuevoEstado = $expensaTotal->estado;
        if ($nuevoMontoPendiente == 0 && $saldoFavor == 0) {
            $nuevoEstado = 'pagado';
        } elseif ($nuevoMontoPagado > 0 && $nuevoMontoPendiente > 0) {
            $nuevoEstado = 'parcial';
        }

        // Actualizar expensa_total
        $expensaTotal->update([
            'monto_pagado' => $nuevoMontoPagado,
            'monto_pendiente' => $nuevoMontoPendiente,
            'saldo_a_favor' => $saldoFavor,
            'estado' => $nuevoEstado,
        ]);
    }

    /**
     * Validar que el monto del pago sea válido
     *
     * @param float $montoPago
     * @param float $montoPendiente
     * @return void
     * @throws Exception
     */
    protected function validarMontoPago(float $montoPago, float $montoPendiente): void
    {
        if ($montoPago <= 0) {
            throw new Exception('El monto del pago debe ser mayor a cero');
        }

        // Permitir pagos parciales, pero advertir si es mayor al pendiente
        if ($montoPago > $montoPendiente) {
            // Se permite pago en exceso (genera saldo a favor)
            // pero podríamos lanzar una advertencia si es necesario
        }
    }

    /**
     * Obtener deuda de un propietario para un período
     *
     * @param int $propietarioId
     * @param int $periodoId
     * @return array
     */
    public function obtenerDeudaPropietario(int $propietarioId, int $periodoId): array
    {
        $expensaTotal = ExpensaTotal::where('periodo_id', $periodoId)
            ->where('propietario_id', $propietarioId)
            ->first();

        if (!$expensaTotal) {
            return [
                'existe' => false,
                'monto_total' => 0,
                'monto_pagado' => 0,
                'monto_pendiente' => 0,
                'saldo_a_favor' => 0,
                'estado' => 'pendiente',
            ];
        }

        return [
            'existe' => true,
            'monto_total' => $expensaTotal->monto_total,
            'monto_pagado' => $expensaTotal->monto_pagado,
            'monto_pendiente' => $expensaTotal->monto_pendiente,
            'saldo_a_favor' => $expensaTotal->saldo_a_favor,
            'estado' => $expensaTotal->estado,
            'expensa_total_id' => $expensaTotal->id,
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
                'descripcion' => 'Pagos de expensas de propietarios',
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
     * Generar descripción automática para el pago
     *
     * @param ExpensaTotal $expensaTotal
     * @return string
     */
    protected function generarDescripcion(ExpensaTotal $expensaTotal): string
    {
        $propietario = $expensaTotal->propietario;
        $periodo = $expensaTotal->periodo;

        return "Pago de expensas - {$propietario->nombre} {$propietario->apellido} - {$periodo->nombre_periodo}";
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
        try {
            // Leer la imagen
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

            // Guardar como WebP
            $image->save(storage_path('app/public/' . $ruta));

            return $ruta;
        } catch (Exception $e) {
            throw new Exception('Error al procesar la imagen: ' . $e->getMessage());
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
     * @param ExpensaTotal $expensaTotal
     * @return void
     */
    protected function registrarActividad(string $accion, Ingreso $ingreso, ExpensaTotal $expensaTotal): void
    {
        if (class_exists(\App\Models\ActivityLog::class)) {
            \App\Models\ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => $accion,
                'model_type' => Ingreso::class,
                'model_id' => $ingreso->id,
                'description' => "Pago de expensa {$ingreso->numero_recibo} registrado - {$expensaTotal->propietario->nombre} {$expensaTotal->propietario->apellido} - {$expensaTotal->periodo->nombre_periodo}",
                'properties' => [
                    'numero_recibo' => $ingreso->numero_recibo,
                    'importe' => $ingreso->importe,
                    'metodo_pago' => $ingreso->metodo_pago,
                    'expensa_total_id' => $expensaTotal->id,
                    'propietario' => $expensaTotal->propietario->nombre . ' ' . $expensaTotal->propietario->apellido,
                    'periodo' => $expensaTotal->periodo->nombre_periodo,
                    'fecha' => $ingreso->fecha->format('Y-m-d'),
                ],
            ]);
        }
    }
}
