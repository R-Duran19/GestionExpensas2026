<?php

namespace App\Services;

use App\Models\CategoriaIngreso;
use App\Models\CategoriaIngresoHistorial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CategoriaIngresoService
{
    /**
     * Crear una nueva categoría de ingreso
     */
    public function crear(array $datos): CategoriaIngreso
    {
        $categoria = CategoriaIngreso::create([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'color' => $datos['color'] ?? '#10B981',
            'icono' => $datos['icono'] ?? 'dollar-sign',
            'activo' => true,
            'creado_por' => Auth::id(),
            'actualizado_por' => Auth::id(),
        ]);

        // Registrar en Activity Log
        $this->registrarActividad('categoria_ingreso.creada', $categoria);

        return $categoria;
    }

    /**
     * Actualizar una categoría existente
     */
    public function actualizar(CategoriaIngreso $categoria, array $datos, string $motivo): CategoriaIngreso
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
            CategoriaIngresoHistorial::create([
                'categoria_ingreso_id' => $categoria->id,
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
            $this->registrarActividad('categoria_ingreso.actualizada', $categoria, [
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
    public function eliminar(CategoriaIngreso $categoria): bool
    {
        // Verificar si tiene ingresos asociados
        if ($categoria->tieneIngresos()) {
            throw new \Exception('No se puede eliminar la categoría porque tiene ingresos asociados.');
        }

        return DB::transaction(function () use ($categoria) {
            $categoria->delete();

            // Registrar en Activity Log
            $this->registrarActividad('categoria_ingreso.eliminada', $categoria);

            return true;
        });
    }

    /**
     * Activar/desactivar una categoría
     */
    public function toggleActivo(CategoriaIngreso $categoria): CategoriaIngreso
    {
        $categoria->update([
            'activo' => !$categoria->activo,
            'actualizado_por' => Auth::id(),
        ]);

        $accion = $categoria->activo ? 'activada' : 'desactivada';

        // Registrar en Activity Log
        $this->registrarActividad("categoria_ingreso.{$accion}", $categoria);

        return $categoria->fresh();
    }

    /**
     * Obtener historial de cambios de una categoría
     */
    public function obtenerHistorial(CategoriaIngreso $categoria): \Illuminate\Database\Eloquent\Collection
    {
        return $categoria->historial()->recientes()->get();
    }

    /**
     * Registrar actividad en el log
     */
    protected function registrarActividad(string $accion, CategoriaIngreso $categoria, array $propiedadesAdicionales = []): void
    {
        if (class_exists(\App\Models\ActivityLog::class)) {
            \App\Models\ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => $accion,
                'model_type' => CategoriaIngreso::class,
                'model_id' => $categoria->id,
                'description' => match($accion) {
                    'categoria_ingreso.creada' => "Categoría de ingreso '{$categoria->nombre}' creada",
                    'categoria_ingreso.actualizada' => "Categoría de ingreso '{$categoria->nombre}' actualizada",
                    'categoria_ingreso.eliminada' => "Categoría de ingreso '{$categoria->nombre}' eliminada",
                    'categoria_ingreso.activada' => "Categoría de ingreso '{$categoria->nombre}' activada",
                    'categoria_ingreso.desactivada' => "Categoría de ingreso '{$categoria->nombre}' desactivada",
                    default => "Acción {$accion} en categoría de ingreso '{$categoria->nombre}'",
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
