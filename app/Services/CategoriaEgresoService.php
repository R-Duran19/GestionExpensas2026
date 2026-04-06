<?php

namespace App\Services;

use App\Models\CategoriaEgreso;
use App\Models\CategoriaEgresoHistorial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CategoriaEgresoService
{
    /**
     * Crear una nueva categoría de egreso
     */
    public function crear(array $datos): CategoriaEgreso
    {
        $categoria = CategoriaEgreso::create([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'color' => $datos['color'] ?? '#EF4444',
            'icono' => $datos['icono'] ?? 'arrow-down-circle',
            'activo' => true,
            'creado_por' => Auth::id(),
            'actualizado_por' => Auth::id(),
        ]);

        // Registrar en Activity Log
        $this->registrarActividad('categoria_egreso.creada', $categoria);

        return $categoria;
    }

    /**
     * Actualizar una categoría existente
     */
    public function actualizar(CategoriaEgreso $categoria, array $datos, string $motivo): CategoriaEgreso
    {
        return DB::transaction(function () use ($categoria, $datos, $motivo) {
            // Guardar valores anteriores
            $valoresAnteriores = [
                'nombre' => $categoria->nombre,
                'descripcion' => $categoria->descripcion,
                'color' => $categoria->color,
            ];

            // Actualizar categoría
            $categoria->update([
                'nombre' => $datos['nombre'] ?? $categoria->nombre,
                'descripcion' => $datos['descripcion'] ?? $categoria->descripcion,
                'color' => $datos['color'] ?? $categoria->color,
                'icono' => $datos['icono'] ?? $categoria->icono,
                'actualizado_por' => Auth::id(),
            ]);

            // Guardar en historial
            CategoriaEgresoHistorial::create([
                'categoria_egreso_id' => $categoria->id,
                'nombre_anterior' => $valoresAnteriores['nombre'],
                'nombre_nuevo' => $categoria->nombre,
                'descripcion_anterior' => $valoresAnteriores['descripcion'],
                'descripcion_nueva' => $categoria->descripcion,
                'color_anterior' => $valoresAnteriores['color'],
                'color_nuevo' => $categoria->color,
                'motivo' => $motivo,
                'cambiado_por' => Auth::id(),
            ]);

            // Registrar en Activity Log
            $this->registrarActividad('categoria_egreso.actualizada', $categoria, [
                'motivo' => $motivo,
                'cambios' => [
                    'nombre' => $valoresAnteriores['nombre'] . ' → ' . $categoria->nombre,
                    'color' => $valoresAnteriores['color'] . ' → ' . $categoria->color,
                ],
            ]);

            return $categoria->fresh();
        });
    }

    /**
     * Eliminar una categoría (soft delete)
     */
    public function eliminar(CategoriaEgreso $categoria): bool
    {
        // Verificar si tiene egresos asociados
        if ($categoria->tieneEgresos()) {
            throw new \Exception('No se puede eliminar la categoría porque tiene egresos asociados.');
        }

        return DB::transaction(function () use ($categoria) {
            $categoria->delete();

            // Registrar en Activity Log
            $this->registrarActividad('categoria_egreso.eliminada', $categoria);

            return true;
        });
    }

    /**
     * Activar/desactivar una categoría
     */
    public function toggleActivo(CategoriaEgreso $categoria): CategoriaEgreso
    {
        $categoria->update([
            'activo' => !$categoria->activo,
            'actualizado_por' => Auth::id(),
        ]);

        $accion = $categoria->activo ? 'activada' : 'desactivada';

        // Registrar en Activity Log
        $this->registrarActividad("categoria_egreso.{$accion}", $categoria);

        return $categoria->fresh();
    }

    /**
     * Obtener historial de cambios de una categoría
     */
    public function obtenerHistorial(CategoriaEgreso $categoria): \Illuminate\Database\Eloquent\Collection
    {
        return $categoria->historial()->recientes()->get();
    }

    /**
     * Registrar actividad en el log
     */
    protected function registrarActividad(string $accion, CategoriaEgreso $categoria, array $propiedadesAdicionales = []): void
    {
        if (class_exists(\App\Models\ActivityLog::class)) {
            \App\Models\ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => $accion,
                'model_type' => CategoriaEgreso::class,
                'model_id' => $categoria->id,
                'description' => match($accion) {
                    'categoria_egreso.creada' => "Categoría de egreso '{$categoria->nombre}' creada",
                    'categoria_egreso.actualizada' => "Categoría de egreso '{$categoria->nombre}' actualizada",
                    'categoria_egreso.eliminada' => "Categoría de egreso '{$categoria->nombre}' eliminada",
                    'categoria_egreso.activada' => "Categoría de egreso '{$categoria->nombre}' activada",
                    'categoria_egreso.desactivada' => "Categoría de egreso '{$categoria->nombre}' desactivada",
                    default => "Acción {$accion} en categoría de egreso '{$categoria->nombre}'",
                },
                'properties' => array_merge([
                    'nombre' => $categoria->nombre,
                    'color' => $categoria->color,
                    'icono' => $categoria->icono,
                ], $propiedadesAdicionales),
            ]);
        }
    }
}
