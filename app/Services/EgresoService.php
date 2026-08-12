<?php

namespace App\Services;

use App\Models\Egreso;
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

class EgresoService
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
     * Crear un nuevo egreso
     */
    public function crear(array $datos, $archivoComprobante): Egreso
    {
        return DB::transaction(function () use ($datos, $archivoComprobante) {
            // Generar número de egreso
            $numeroEgreso = SecuenciaCaja::generarNumeroEgreso();

            // Procesar comprobante (REQUERIDO)
            $fecha = \Carbon\Carbon::parse($datos['fecha']);
            $rutaComprobante = $this->procesarImagenWebP($archivoComprobante, 'egresos', $fecha);

            // Crear egreso
            $egreso = Egreso::create([
                'numero_egreso' => $numeroEgreso,
                'fecha' => $datos['fecha'],
                'categoria_egreso_id' => $datos['categoria_egreso_id'],
                'concepto' => $datos['concepto'],
                'numero_comprobante' => $datos['numero_comprobante'] ?? null,
                'importe' => $datos['importe'],
                'descripcion' => $datos['descripcion'] ?? null,
                'comprobante_foto' => $rutaComprobante,
                'proveedor' => $datos['proveedor'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
                'activo' => true,
                'creado_por' => Auth::id(),
                'actualizado_por' => Auth::id(),
            ]);

            // Actualizar totales del día
            $this->actualizarTotalesDia($datos['fecha']);

            // Registrar en Activity Log
            $this->registrarActividad('egreso.creado', $egreso);

            return $egreso;
        });
    }

    /**
     * Actualizar un egreso existente
     */
    public function actualizar(Egreso $egreso, array $datos, $archivoComprobante = null): Egreso
    {
        return DB::transaction(function () use ($egreso, $datos, $archivoComprobante) {
            $fechaOriginal = $egreso->fecha->format('Y-m-d');
            $fechaNueva = \Carbon\Carbon::parse($datos['fecha'])->format('Y-m-d');

            // Procesar nuevo comprobante si se proporciona
            if ($archivoComprobante) {
                // Eliminar comprobante anterior si existe (private o public)
                $this->borrarComprobante($egreso->comprobante_foto);

                $fecha = \Carbon\Carbon::parse($datos['fecha']);
                $datos['comprobante_foto'] = $this->procesarImagenWebP($archivoComprobante, 'egresos', $fecha);
            }

            $datos['actualizado_por'] = Auth::id();

            // Actualizar egreso
            $egreso->update($datos);

            // Actualizar totales de ambas fechas si cambió la fecha
            if ($fechaOriginal !== $fechaNueva) {
                $this->actualizarTotalesDia($fechaOriginal);
            }
            $this->actualizarTotalesDia($fechaNueva);

            // Registrar en Activity Log
            $this->registrarActividad('egreso.actualizado', $egreso);

            return $egreso->fresh();
        });
    }

    /**
     * Eliminar un egreso (soft delete)
     */
    public function eliminar(Egreso $egreso): bool
    {
        return DB::transaction(function () use ($egreso) {
            $fecha = $egreso->fecha->format('Y-m-d');

            // Eliminar archivo de comprobante si existe (private o public)
            $this->borrarComprobante($egreso->comprobante_foto);

            // Soft delete
            $egreso->delete();

            // Actualizar totales del día
            $this->actualizarTotalesDia($fecha);

            // Registrar en Activity Log
            $this->registrarActividad('egreso.eliminado', $egreso);

            return true;
        });
    }

    /**
     * Restaurar un egreso eliminado
     */
    public function restaurar(int $id): Egreso
    {
        return DB::transaction(function () use ($id) {
            $egreso = Egreso::withTrashed()->findOrFail($id);
            $egreso->restore();

            // Actualizar totales del día
            $this->actualizarTotalesDia($egreso->fecha->format('Y-m-d'));

            // Registrar en Activity Log
            $this->registrarActividad('egreso.restaurado', $egreso);

            return $egreso;
        });
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
            $directorio = dirname(storage_path('app/private/' . $ruta));
            if (!file_exists($directorio)) {
                mkdir($directorio, 0775, true);
            }

            // Guardar como WebP con quality 70
            $image->save(storage_path('app/private/' . $ruta), quality: 70);

            return $ruta;
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al procesar imagen WebP', [
                'service' => 'EgresoService',
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
        $ingresosQuery = \App\Models\Ingreso::porFecha($fecha)->activos();

        $totales->total_ingresos_efectivo = (clone $ingresosQuery)->porMetodoPago('efectivo')->sum('importe');
        $totales->total_ingresos_transferencia = (clone $ingresosQuery)->porMetodoPago('transferencia')->sum('importe');
        $totales->total_ingresos_qr = (clone $ingresosQuery)->porMetodoPago('qr')->sum('importe');
        $totales->total_ingresos_otro = (clone $ingresosQuery)->porMetodoPago('otro')->sum('importe');
        $totales->total_ingresos = (clone $ingresosQuery)->sum('importe');
        $totales->cantidad_ingresos = (clone $ingresosQuery)->count();

        // Calcular totales de egresos del día
        $egresosQuery = Egreso::porFecha($fecha)->activos();
        $totales->total_egresos = (clone $egresosQuery)->sum('importe');
        $totales->cantidad_egresos = (clone $egresosQuery)->count();

        // Calcular balance neto
        $totales->balance_neto = $totales->total_ingresos - $totales->total_egresos;

        $totales->actualizado_por = Auth::id();
        $totales->save();
    }

    /**
     * Registrar actividad en el log
     */
    protected function registrarActividad(string $accion, Egreso $egreso): void
    {
        if (class_exists(\App\Models\ActivityLog::class)) {
            \App\Models\ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => $accion,
                'model_type' => Egreso::class,
                'model_id' => $egreso->id,
                'description' => match($accion) {
                    'egreso.creado' => "Egreso {$egreso->numero_egreso} creado",
                    'egreso.actualizado' => "Egreso {$egreso->numero_egreso} actualizado",
                    'egreso.eliminado' => "Egreso {$egreso->numero_egreso} eliminado",
                    'egreso.restaurado' => "Egreso {$egreso->numero_egreso} restaurado",
                    default => "Acción {$accion} en egreso {$egreso->numero_egreso}",
                },
                'properties' => [
                    'numero_egreso' => $egreso->numero_egreso,
                    'importe' => $egreso->importe,
                    'concepto' => $egreso->concepto,
                    'categoria' => $egreso->categoria?->nombre,
                    'proveedor' => $egreso->proveedor,
                    'fecha' => $egreso->fecha->format('Y-m-d'),
                ],
            ]);
        }
    }
}
