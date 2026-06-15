<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Propietario;
use App\Models\Unidad;
use Illuminate\Support\Facades\Auth;

class PropietarioService
{
    /**
     * Buscar propietario por CI
     */
    public function buscarPorCI(string $ci): ?Propietario
    {
        return Propietario::where('ci', $ci)->first();
    }

    /**
     * Verificar si CI existe
     */
    public function ciExiste(string $ci, ?int $excluirId = null): bool
    {
        $query = Propietario::where('ci', $ci);

        if ($excluirId) {
            $query->where('id', '!=', $excluirId);
        }

        return $query->exists();
    }

    /**
     * Obtener unidades activas de un propietario
     */
    public function obtenerUnidadesActivas(Propietario $propietario): \Illuminate\Database\Eloquent\Collection
    {
        return $propietario->unidadesActuales;
    }

    /**
     * Obtener historial completo de unidades de un propietario
     */
    public function obtenerHistorialUnidades(Propietario $propietario): \Illuminate\Database\Eloquent\Collection
    {
        return $propietario->historialUnidades;
    }

    /**
     * Buscar propietarios por diferentes criterios
     */
    public function buscar(array $filtros): \Illuminate\Database\Eloquent\Collection
    {
        $query = Propietario::query();

        // Filtro por búsqueda de texto
        if (!empty($filtros['busqueda'])) {
            $query->buscar($filtros['busqueda']);
        }

        // Filtro por estado activo
        if (isset($filtros['activo'])) {
            if ($filtros['activo'] === 'true') {
                $query->activos();
            } elseif ($filtros['activo'] === 'false') {
                $query->inactivos();
            }
        }

        return $query->orderBy('apellido')
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Obtener estadísticas de propietarios
     */
    public function obtenerEstadisticas(): array
    {
        $total = Propietario::count();
        $activos = Propietario::activos()->count();
        $inactivos = Propietario::inactivos()->count();

        // Propietarios con múltiples unidades
        $conMultiplesUnidades = 0;
        $totalUnidadesAsignadas = 0;

        foreach (Propietario::activos()->get() as $propietario) {
            $unidades = $this->obtenerUnidadesActivas($propietario);
            $totalUnidadesAsignadas += $unidades->count();

            if ($unidades->count() > 1) {
                $conMultiplesUnidades++;
            }
        }

        return [
            'total' => $total,
            'activos' => $activos,
            'inactivos' => $inactivos,
            'con_multiples_unidades' => $conMultiplesUnidades,
            'total_unidades_asignadas' => $totalUnidadesAsignadas,
            'promedio_unidades_por_propietario' => $activos > 0
                ? round($totalUnidadesAsignadas / $activos, 1)
                : 0,
        ];
    }

    /**
     * Activar propietario
     */
    public function activar(Propietario $propietario): Propietario
    {
        $propietario->activo = true;
        $propietario->save();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'propietario.activado',
            'model_type' => Propietario::class,
            'model_id' => $propietario->id,
            'description' => "Propietario {$propietario->nombre_completo} activado",
            'properties' => [
                'propietario_nombre' => $propietario->nombre_completo,
                'ci' => $propietario->ci,
            ],
        ]);

        return $propietario;
    }

    /**
     * Desactivar propietario
     */
    public function desactivar(Propietario $propietario): Propietario
    {
        // Verificar si tiene unidades activas
        $unidadesActivas = $this->obtenerUnidadesActivas($propietario);

        if ($unidadesActivas->count() > 0) {
            throw new \Exception(
                "No se puede desactivar el propietario porque tiene {$unidadesActivas->count()} unidad(es) activa(s). " .
                "Primero debe reasignar las unidades a otro propietario."
            );
        }

        $propietario->activo = false;
        $propietario->save();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'propietario.desactivado',
            'model_type' => Propietario::class,
            'model_id' => $propietario->id,
            'description' => "Propietario {$propietario->nombre_completo} desactivado",
            'properties' => [
                'propietario_nombre' => $propietario->nombre_completo,
                'ci' => $propietario->ci,
            ],
        ]);

        return $propietario;
    }

    /**
     * Obtener propietarios disponibles para asignar a una unidad
     */
    public function obtenerPropietariosDisponibles(): \Illuminate\Database\Eloquent\Collection
    {
        return Propietario::activos()
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Obtener unidades disponibles (sin propietario actual)
     */
    public function obtenerUnidadesDisponibles(): \Illuminate\Database\Eloquent\Collection
    {
        return Unidad::inactivos()
            ->orderBy('tipo_unidad')
            ->orderByRaw('CAST(piso AS UNSIGNED)')
            ->orderByRaw("SUBSTRING_INDEX(codigo, '-', -1) + 0")
            ->get();
    }
}
