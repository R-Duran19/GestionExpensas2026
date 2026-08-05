<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Propietario;
use App\Models\Unidad;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UnidadService
{
    /**
     * Activar una unidad
     */
    public function activar(Unidad $unidad): Unidad
    {
        $unidad->activo = true;
        $unidad->save();

        // Log
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'unidad.activada',
            'model_type' => Unidad::class,
            'model_id' => $unidad->id,
            'description' => "Unidad {$unidad->codigo} activada",
            'properties' => ['codigo' => $unidad->codigo],
        ]);

        return $unidad;
    }

    /**
     * Desactivar una unidad
     */
    public function desactivar(Unidad $unidad): Unidad
    {
        $unidad->activo = false;
        $unidad->save();

        // Log
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'unidad.desactivada',
            'model_type' => Unidad::class,
            'model_id' => $unidad->id,
            'description' => "Unidad {$unidad->codigo} desactivada",
            'properties' => ['codigo' => $unidad->codigo],
        ]);

        return $unidad;
    }

    /**
     * Asignar un propietario a una unidad
     *
     * @param Unidad $unidad
     * @param Propietario $propietario
     * @param string|null $fechaInicio
     * @param string|null $motivo
     * @return void
     */
    public function asignarPropietario(
        Unidad $unidad,
        Propietario $propietario,
        ?string $fechaInicio = null,
        ?string $motivo = null
    ): void {
        DB::transaction(function () use ($unidad, $propietario, $fechaInicio, $motivo) {
            // 0. Verificar si el propietario YA es el actual de esta unidad
            $asignacionActual = DB::table('propietario_unidad')
                ->where('unidad_id', $unidad->id)
                ->where('propietario_id', $propietario->id)
                ->where('es_propietario_actual', true)
                ->first();

            if ($asignacionActual) {
                throw new \Exception(
                    "El propietario {$propietario->nombre_completo} ya está asignado como propietario actual de la unidad {$unidad->codigo}"
                );
            }

            // 0.5 Verificar si ya existe un registro con la misma fecha (para reactivar)
            $fechaInicioFormat = $fechaInicio ? date('Y-m-d', strtotime($fechaInicio)) : now()->format('Y-m-d');
            $existeMismaFecha = DB::table('propietario_unidad')
                ->where('unidad_id', $unidad->id)
                ->where('propietario_id', $propietario->id)
                ->where('fecha_inicio', $fechaInicioFormat)
                ->first();

            if ($existeMismaFecha) {
                // Si el registro existe pero NO es el actual, reactivarlo
                if (!$existeMismaFecha->es_propietario_actual) {
                    DB::table('propietario_unidad')
                        ->where('id', $existeMismaFecha->id)
                        ->update([
                            'es_propietario_actual' => true,
                            'fecha_fin' => null,
                            'updated_at' => now(),
                        ]);

                    // Activar la unidad
                    if (!$unidad->activo) {
                        $unidad->activo = true;
                        $unidad->save();
                    }

                    // Log de reactivación
                    ActivityLog::create([
                        'user_id' => Auth::id(),
                        'action' => 'propietario.reactivado',
                        'model_type' => Unidad::class,
                        'model_id' => $unidad->id,
                        'description' => "Propietario {$propietario->nombre_completo} reactivado para unidad {$unidad->codigo}",
                        'properties' => [
                            'unidad_codigo' => $unidad->codigo,
                            'propietario_nombre' => $propietario->nombre_completo,
                            'fecha_inicio' => $fechaInicioFormat,
                        ],
                    ]);

                    return; // Salir, ya reactivamos
                } else {
                    // Si ya es el actual, lanzar error
                    throw new \Exception(
                        "El propietario {$propietario->nombre_completo} ya está asignado como propietario actual de la unidad {$unidad->codigo}"
                    );
                }
            }

            // 1. Buscar propietario anterior de esta unidad
            $asignacionAnterior = DB::table('propietario_unidad')
                ->where('unidad_id', $unidad->id)
                ->where('es_propietario_actual', true)
                ->first();

            $nombrePropietarioAnterior = null;

            // 2. Si existe propietario anterior, marcarlo como inactivo
            if ($asignacionAnterior) {
                $propietarioAnterior = Propietario::find($asignacionAnterior->propietario_id);
                $nombrePropietarioAnterior = $propietarioAnterior ? $propietarioAnterior->nombre_completo : 'Desconocido';

                DB::table('propietario_unidad')
                    ->where('id', $asignacionAnterior->id)
                    ->update([
                        'es_propietario_actual' => false,
                        'fecha_fin' => now(),
                        'updated_at' => now(),
                    ]);

                // Log del cambio
                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'propietario.cambiado',
                    'model_type' => Unidad::class,
                    'model_id' => $unidad->id,
                    'description' => "Unidad {$unidad->codigo}: propietario cambiado de {$nombrePropietarioAnterior} a {$propietario->nombre_completo}",
                    'properties' => [
                        'unidad_codigo' => $unidad->codigo,
                        'propietario_anterior' => $nombrePropietarioAnterior,
                        'propietario_nuevo' => $propietario->nombre_completo,
                        'motivo' => $motivo,
                    ],
                ]);
            }

            // 3. Crear nueva asignación
            DB::table('propietario_unidad')->insert([
                'propietario_id' => $propietario->id,
                'unidad_id' => $unidad->id,
                'fecha_inicio' => $fechaInicio ? date('Y-m-d', strtotime($fechaInicio)) : now(),
                'fecha_fin' => null,
                'es_propietario_actual' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 4. Activar la unidad si estaba inactiva
            if (!$unidad->activo) {
                $unidad->activo = true;
                $unidad->save();
            }

            // 5. Log de la nueva asignación
            if (!$asignacionAnterior) {
                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'propietario.asignado',
                    'model_type' => Unidad::class,
                    'model_id' => $unidad->id,
                    'description' => "Propietario {$propietario->nombre_completo} asignado a unidad {$unidad->codigo}",
                    'properties' => [
                        'unidad_codigo' => $unidad->codigo,
                        'propietario_nombre' => $propietario->nombre_completo,
                        'propietario_ci' => $propietario->ci,
                        'motivo' => $motivo,
                    ],
                ]);
            }
        });
    }

    /**
     * Obtener historial de propietarios de una unidad
     */
    public function obtenerHistorialPropietarios(Unidad $unidad): array
    {
        return $unidad->propietarios()
            ->orderBy('pivot_fecha_inicio', 'desc')
            ->get()
            ->map(function ($propietario) {
                return [
                    'propietario' => $propietario->nombre_completo,
                    'ci' => $propietario->ci,
                    'fecha_inicio' => $propietario->pivot->fecha_inicio->format('d/m/Y'),
                    'fecha_fin' => $propietario->pivot->fecha_fin ? $propietario->pivot->fecha_fin->format('d/m/Y') : 'Actual',
                    'es_actual' => $propietario->pivot->es_propietario_actual,
                ];
            })
            ->toArray();
    }

    /**
     * Buscar unidades por diferentes criterios
     */
    public function buscar(array $filtros): \Illuminate\Database\Eloquent\Collection
    {
        $query = Unidad::query();

        // Filtro por búsqueda de texto
        if (!empty($filtros['busqueda'])) {
            $query->buscar($filtros['busqueda']);
        }

        // Filtro por tipo
        if (!empty($filtros['tipo_unidad'])) {
            $query->where('tipo_unidad', $filtros['tipo_unidad']);
        }

        // Filtro por categoría
        if (!empty($filtros['categoria_unidad'])) {
            $query->where('categoria_unidad', $filtros['categoria_unidad']);
        }

        // Filtro por estado activo
        if (isset($filtros['activo'])) {
            if ($filtros['activo'] === 'true') {
                $query->activos();
            } elseif ($filtros['activo'] === 'false') {
                $query->inactivos();
            }
        }

        // Filtro por tiene medidor
        if (isset($filtros['tiene_medidor'])) {
            $query->where('tiene_medidor', $filtros['tiene_medidor']);
        }

        // Ordenamiento natural unificado (tipo_unidad -> piso -> código natural)
        return $query->orderByRaw(Unidad::ordenPorTipoUnidadRaw('tipo_unidad'))
            ->orderByRaw('CAST(piso AS UNSIGNED)')
            ->orderByRaw(Unidad::ordenPorCodigoRaw('codigo', 'ASC'))
            ->get();
    }

    /**
     * Obtener estadísticas de unidades
     */
    public function obtenerEstadisticas(): array
    {
        $total = Unidad::count();
        $activas = Unidad::activos()->count();
        $inactivas = Unidad::inactivos()->count();
        $conMedidor = Unidad::conMedidor()->count();

        $porTipo = [
            'departamento' => Unidad::deTipo(\App\Enums\TipoUnidad::DEPARTAMENTO)->count(),
            'oficina' => Unidad::deTipo(\App\Enums\TipoUnidad::OFICINA)->count(),
            'local' => Unidad::deTipo(\App\Enums\TipoUnidad::LOCAL)->count(),
            'parqueo' => Unidad::deTipo(\App\Enums\TipoUnidad::PARQUEO)->count(),
            'baulera' => Unidad::deTipo(\App\Enums\TipoUnidad::BAULERA)->count(),
        ];

        $porCategoria = [
            'domiciliaria' => Unidad::deCategoria(\App\Enums\CategoriaUnidad::DOMICILIARIA)->count(),
            'comercial' => Unidad::deCategoria(\App\Enums\CategoriaUnidad::COMERCIAL)->count(),
        ];

        return [
            'total' => $total,
            'activas' => $activas,
            'inactivas' => $inactivas,
            'con_medidor' => $conMedidor,
            'porcentaje_activas' => $total > 0 ? round(($activas / $total) * 100, 1) : 0,
            'por_tipo' => $porTipo,
            'por_categoria' => $porCategoria,
        ];
    }

    /**
     * Validar que una unidad pueda ser eliminada
     */
    public function puedeEliminar(Unidad $unidad): bool
    {
        // No eliminar si tiene propietario actual
        if ($unidad->tienePropietarioActual()) {
            return false;
        }

        // En el futuro, validar si tiene expensas asociadas
        // if ($unidad->expensas()->exists()) {
        //     return false;
        // }

        return true;
    }
}
