<?php

namespace App\Services;

use App\Models\MedidorGrupo;
use App\Models\Unidad;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MedidorGrupoService
{
    /**
     * Obtener todos los grupos de medidores.
     *
     * @param bool $soloActivos
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerGrupos(bool $soloActivos = true)
    {
        $query = MedidorGrupo::with(['creadoPor', 'unidades']);

        if ($soloActivos) {
            $query->activos();
        }

        return $query->orderBy('nombre')->get();
    }

    /**
     * Obtener un grupo por ID con sus unidades.
     *
     * @param int $grupoId
     * @return MedidorGrupo|null
     */
    public function obtenerGrupoConUnidades(int $grupoId): ?MedidorGrupo
    {
        return MedidorGrupo::with(['unidades', 'creadoPor'])
            ->find($grupoId);
    }

    /**
     * Crear un nuevo grupo de medidores.
     *
     * @param array $data
     * @return MedidorGrupo
     * @throws \Exception
     */
    public function crearGrupo(array $data): MedidorGrupo
    {
        $data['nombre'] = trim($data['nombre']);
        $data['tipo_distribucion'] = $data['tipo_distribucion'] ?? 'equitativa';

        if (empty($data['nombre'])) {
            throw new \Exception('El nombre del grupo es obligatorio');
        }

        // Verificar que no exista un grupo con el mismo nombre
        $existe = MedidorGrupo::where('nombre', $data['nombre'])
            ->whereNull('deleted_at')
            ->exists();

        if ($existe) {
            throw new \Exception('Ya existe un grupo con ese nombre');
        }

        return DB::transaction(function () use ($data) {
            $grupo = MedidorGrupo::create([
                'nombre' => $data['nombre'],
                'tipo_distribucion' => $data['tipo_distribucion'],
                'activo' => $data['activo'] ?? true,
                'observaciones' => $data['observaciones'] ?? null,
                'creado_por' => auth()->id(),
            ]);

            // Registrar en log de actividad
            if (class_exists('App\Models\ActivityLog')) {
                \App\Models\ActivityLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'medidor_grupo.creado',
                    'model_type' => MedidorGrupo::class,
                    'model_id' => $grupo->id,
                    'description' => "Grupo de medidor '{$grupo->nombre}' creado",
                    'properties' => $data,
                ]);
            }

            Log::info("Grupo de medidor creado", [
                'grupo_id' => $grupo->id,
                'nombre' => $grupo->nombre,
                'usuario' => auth()->id(),
            ]);

            return $grupo;
        });
    }

    /**
     * Actualizar un grupo de medidores.
     *
     * @param int $grupoId
     * @param array $data
     * @return MedidorGrupo
     * @throws \Exception
     */
    public function actualizarGrupo(int $grupoId, array $data): MedidorGrupo
    {
        $grupo = MedidorGrupo::find($grupoId);
        if (!$grupo) {
            throw new \Exception('Grupo no encontrado');
        }

        if (!empty($data['nombre'])) {
            $data['nombre'] = trim($data['nombre']);

            // Verificar que no exista otro grupo con el mismo nombre
            $existe = MedidorGrupo::where('nombre', $data['nombre'])
                ->where('id', '!=', $grupoId)
                ->whereNull('deleted_at')
                ->exists();

            if ($existe) {
                throw new \Exception('Ya existe otro grupo con ese nombre');
            }
        }

        $grupo->update($data);

        // Registrar en log de actividad
        if (class_exists('App\Models\ActivityLog')) {
            \App\Models\ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'medidor_grupo.actualizado',
                'model_type' => MedidorGrupo::class,
                'model_id' => $grupo->id,
                'description' => "Grupo de medidor '{$grupo->nombre}' actualizado",
                'properties' => $data,
            ]);
        }

        Log::info("Grupo de medidor actualizado", [
            'grupo_id' => $grupo->id,
            'usuario' => auth()->id(),
        ]);

        return $grupo->fresh();
    }

    /**
     * Eliminar un grupo de medidores (soft delete).
     *
     * @param int $grupoId
     * @return bool
     * @throws \Exception
     */
    public function eliminarGrupo(int $grupoId): bool
    {
        $grupo = MedidorGrupo::find($grupoId);
        if (!$grupo) {
            throw new \Exception('Grupo no encontrado');
        }

        // Verificar que no tenga unidades asignadas activas
        if ($grupo->unidades()->count() > 0) {
            throw new \Exception('No se puede eliminar el grupo porque tiene unidades asignadas. Primero desasigne las unidades.');
        }

        $grupo->delete();

        // Registrar en log de actividad
        if (class_exists('App\Models\ActivityLog')) {
            \App\Models\ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'medidor_grupo.eliminado',
                'model_type' => MedidorGrupo::class,
                'model_id' => $grupo->id,
                'description' => "Grupo de medidor '{$grupo->nombre}' eliminado",
            ]);
        }

        Log::info("Grupo de medidor eliminado", [
            'grupo_id' => $grupo->id,
            'usuario' => auth()->id(),
        ]);

        return true;
    }

    /**
     * Asignar una unidad a un grupo de medidores.
     *
     * @param int $grupoId
     * @param int $unidadId
     * @return bool
     * @throws \Exception
     */
    public function asignarUnidad(int $grupoId, int $unidadId): bool
    {
        $grupo = MedidorGrupo::find($grupoId);
        if (!$grupo) {
            throw new \Exception('Grupo no encontrado');
        }

        $unidad = Unidad::find($unidadId);
        if (!$unidad) {
            throw new \Exception('Unidad no encontrada');
        }

        // Verificar que la unidad no pertenezca ya a un grupo
        if ($unidad->tieneMedidorCompartido()) {
            throw new \Exception('La unidad ya pertenece a un grupo de medidor. Primero desasigne la unidad del grupo actual.');
        }

        return DB::transaction(function () use ($grupo, $unidad) {
            // Asignar unidad al grupo
            $grupo->unidades()->attach($unidad->id, [
                'fecha_asignacion' => now(),
            ]);

            // Registrar en log de actividad
            if (class_exists('App\Models\ActivityLog')) {
                \App\Models\ActivityLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'medidor_grupo.unidad_asignada',
                    'model_type' => MedidorGrupo::class,
                    'model_id' => $grupo->id,
                    'description' => "Unidad {$unidad->nombre_completo} asignada al grupo '{$grupo->nombre}'",
                    'properties' => [
                        'unidad_id' => $unidad->id,
                        'unidad_nombre' => $unidad->nombre_completo,
                    ],
                ]);
            }

            Log::info("Unidad asignada a grupo de medidor", [
                'grupo_id' => $grupo->id,
                'unidad_id' => $unidad->id,
                'usuario' => auth()->id(),
            ]);

            return true;
        });
    }

    /**
     * Desasignar una unidad de un grupo de medidores.
     *
     * @param int $grupoId
     * @param int $unidadId
     * @return bool
     * @throws \Exception
     */
    public function desasignarUnidad(int $grupoId, int $unidadId): bool
    {
        $grupo = MedidorGrupo::find($grupoId);
        if (!$grupo) {
            throw new \Exception('Grupo no encontrado');
        }

        $unidad = Unidad::find($unidadId);
        if (!$unidad) {
            throw new \Exception('Unidad no encontrada');
        }

        return DB::transaction(function () use ($grupo, $unidad) {
            // Desasignar unidad del grupo (marcar fecha de retiro)
            $pivot = $grupo->unidades()->where('unidad_id', $unidad->id)->first()?->pivot;

            if ($pivot) {
                $pivot->update([
                    'fecha_retiro' => now(),
                ]);

                // Sincronizar para remover de la relación activa
                $grupo->unidades()->detach($unidad->id);
            }

            // Registrar en log de actividad
            if (class_exists('App\Models\ActivityLog')) {
                \App\Models\ActivityLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'medidor_grupo.unidad_desasignada',
                    'model_type' => MedidorGrupo::class,
                    'model_id' => $grupo->id,
                    'description' => "Unidad {$unidad->nombre_completo} desasignada del grupo '{$grupo->nombre}'",
                    'properties' => [
                        'unidad_id' => $unidad->id,
                        'unidad_nombre' => $unidad->nombre_completo,
                    ],
                ]);
            }

            Log::info("Unidad desasignada de grupo de medidor", [
                'grupo_id' => $grupo->id,
                'unidad_id' => $unidad->id,
                'usuario' => auth()->id(),
            ]);

            return true;
        });
    }

    /**
     * Activar/desactivar un grupo.
     *
     * @param int $grupoId
     * @return MedidorGrupo
     * @throws \Exception
     */
    public function toggleEstado(int $grupoId): MedidorGrupo
    {
        $grupo = MedidorGrupo::find($grupoId);
        if (!$grupo) {
            throw new \Exception('Grupo no encontrado');
        }

        $grupo->activo = !$grupo->activo;
        $grupo->save();

        $estado = $grupo->activo ? 'activado' : 'desactivado';

        // Registrar en log de actividad
        if (class_exists('App\Models\ActivityLog')) {
            \App\Models\ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'medidor_grupo.cambio_estado',
                'model_type' => MedidorGrupo::class,
                'model_id' => $grupo->id,
                'description' => "Grupo '{$grupo->nombre}' {$estado}",
            ]);
        }

        Log::info("Grupo de medidor {$estado}", [
            'grupo_id' => $grupo->id,
            'activo' => $grupo->activo,
            'usuario' => auth()->id(),
        ]);

        return $grupo;
    }

    /**
     * Obtener unidades disponibles para asignar a un grupo.
     *
     * @param int|null $grupoId (para excluir unidades ya asignadas a este grupo)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerUnidadesDisponibles(?int $grupoId = null)
    {
        $query = Unidad::activos()
            ->conMedidor()
            ->whereDoesntHave('medidorGrupo');

        if ($grupoId) {
            // Incluir unidades que ya están en este grupo (para el mismo grupo)
            $query->orWhereHas('medidorGrupo', function ($q) use ($grupoId) {
                $q->where('medidor_grupos.id', $grupoId);
            });
        }

        return $query->get();
    }
}
