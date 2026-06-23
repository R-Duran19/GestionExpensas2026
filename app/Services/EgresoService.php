<?php

namespace App\Services;

use App\Models\Egreso;
use App\Models\SecuenciaCaja;
use App\Models\CajaTotal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Exception;

class EgresoService
{
    protected ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new GdDriver());
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
                // Eliminar comprobante anterior si existe
                if ($egreso->comprobante_foto && Storage::exists($egreso->comprobante_foto)) {
                    Storage::delete($egreso->comprobante_foto);
                }

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

            // Eliminar archivo de comprobante si existe
            if ($egreso->comprobante_foto && Storage::exists($egreso->comprobante_foto)) {
                Storage::delete($egreso->comprobante_foto);
            }

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
        try {
            // Leer la imagen (Intervention Image v4 usa read())
            // Soporta: UploadedFile, string path, binary data, or resource
            $path = method_exists($archivo, 'getRealPath') ? $archivo->getRealPath() : $archivo;
            $image = $this->imageManager->read($path);

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
            $directorio = dirname(storage_path('app/public/' . $ruta));
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
