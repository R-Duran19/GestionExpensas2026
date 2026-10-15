<?php

namespace App\Services;

use App\Models\Inquilino;
use App\Models\Unidad;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InquilinoService
{
    /**
     * Asignar un inquilino a una unidad (alquilar)
     * Un inquilino puede tener múltiples unidades simultáneas
     */
    public function asignarInquilino(
        Unidad $unidad,
        Inquilino $inquilino,
        ?string $fechaInicio = null,
        ?float $montoAlquiler = null,
        ?string $observaciones = null
    ): void {
        DB::transaction(function () use ($unidad, $inquilino, $fechaInicio, $montoAlquiler, $observaciones) {
            // [REMEDIACIÓN-2.4] Serializar asignaciones sobre la unidad (guard
            // sin lock + insert = dos inquilinos actuales por carrera).
            $unidad = Unidad::lockForUpdate()->findOrFail($unidad->id);

            // Verificar que NO hay ya un inquilito actual en esta unidad
            $inquilinoActual = DB::table('inquilino_unidad')
                ->where('unidad_id', $unidad->id)
                ->where('es_inquilino_actual', true)
                ->lockForUpdate()
                ->first();

            if ($inquilinoActual) {
                $inquilinoExistente = Inquilino::find($inquilinoActual->inquilino_id);
                throw new \Exception(
                    "La unidad {$unidad->codigo} ya tiene un inquilino actual: {$inquilinoExistente->nombre_completo}"
                );
            }

            // Crear nueva asignación (sin finalizar anteriores - un inquilino puede tener múltiples unidades)
            DB::table('inquilino_unidad')->insert([
                'inquilino_id' => $inquilino->id,
                'unidad_id' => $unidad->id,
                'fecha_inicio' => $fechaInicio ? date('Y-m-d', strtotime($fechaInicio)) : now(),
                'fecha_fin' => null,
                'es_inquilino_actual' => true,
                'monto_alquiler' => $montoAlquiler,
                'observaciones' => $observaciones,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'inquilino.asignado',
                'model_type' => Unidad::class,
                'model_id' => $unidad->id,
                'description' => "Inquilino {$inquilino->nombre_completo} asignado a unidad {$unidad->codigo}",
            ]);
        });
    }

    /**
     * Asignar múltiples unidades a un inquilino
     */
    public function asignarMultiplesUnidades(
        Inquilino $inquilino,
        array $unidadIds,
        ?string $fechaInicio = null,
        ?float $montoAlquiler = null,
        ?string $observaciones = null
    ): array {
        $unidadesAsignadas = [];
        $errores = [];

        foreach ($unidadIds as $unidadId) {
            try {
                $unidad = Unidad::findOrFail($unidadId);
                $this->asignarInquilino(
                    $unidad,
                    $inquilino,
                    $fechaInicio,
                    $montoAlquiler,
                    $observaciones
                );
                $unidadesAsignadas[] = $unidad->codigo;
            } catch (\Exception $e) {
                $errores[] = "Unidad ID {$unidadId}: " . $e->getMessage();
            }
        }

        return [
            'asignadas' => $unidadesAsignadas,
            'errores' => $errores,
        ];
    }

    /**
     * Desasignar una unidad de un inquilino
     */
    public function desasignarUnidad(
        Unidad $unidad,
        Inquilino $inquilino,
        ?string $motivo = null
    ): void {
        DB::transaction(function () use ($unidad, $inquilino, $motivo) {
            $alquiler = DB::table('inquilino_unidad')
                ->where('unidad_id', $unidad->id)
                ->where('inquilino_id', $inquilino->id)
                ->where('es_inquilino_actual', true)
                ->first();

            if (!$alquiler) {
                throw new \Exception("El inquilino {$inquilino->nombre_completo} no tiene asignada la unidad {$unidad->codigo}");
            }

            // Marcar como inactivo (finalizar alquiler)
            DB::table('inquilino_unidad')
                ->where('id', $alquiler->id)
                ->update([
                    'es_inquilino_actual' => false,
                    'fecha_fin' => now(),
                    'updated_at' => now(),
                ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'inquilino.desasignado',
                'model_type' => Unidad::class,
                'model_id' => $unidad->id,
                'description' => "Desasignado {$inquilino->nombre_completo} de unidad {$unidad->codigo}",
                'properties' => ['motivo' => $motivo],
            ]);
        });
    }

    /**
     * Finalizar alquiler (desalojar) - deprecated, usar desasignarUnidad
     */
    public function finalizarAlquiler(
        Unidad $unidad,
        ?string $fechaFin = null,
        ?string $motivo = null
    ): void {
        $alquilerActual = DB::table('inquilino_unidad')
            ->where('unidad_id', $unidad->id)
            ->where('es_inquilino_actual', true)
            ->first();

        if (!$alquilerActual) {
            throw new \Exception("La unidad {$unidad->codigo} no tiene inquilino actual");
        }

        $inquilino = Inquilino::find($alquilerActual->inquilino_id);
        $this->desasignarUnidad($unidad, $inquilino, $motivo);
    }

    /**
     * Activar inquilino
     */
    public function activar(Inquilino $inquilino): Inquilino
    {
        $inquilino->activo = true;
        $inquilino->save();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'inquilino.activado',
            'model_type' => Inquilino::class,
            'model_id' => $inquilino->id,
            'description' => "Inquilino {$inquilino->nombre_completo} activado",
        ]);

        return $inquilino;
    }

    /**
     * Desactivar inquilino
     */
    public function desactivar(Inquilino $inquilino): Inquilino
    {
        $inquilino->activo = false;
        $inquilino->save();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'inquilino.desactivado',
            'model_type' => Inquilino::class,
            'model_id' => $inquilino->id,
            'description' => "Inquilino {$inquilino->nombre_completo} desactivado",
        ]);

        return $inquilino;
    }
}
