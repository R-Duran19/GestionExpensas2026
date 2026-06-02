<?php

namespace App\Services;

use App\Models\FactorHistorial;
use App\Models\FactorProrrateo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class FactorService
{
    /**
     * Obtener el factor vigente para una categoría.
     *
     * @param string $categoria 'domiciliaria' o 'comercial'
     * @return FactorProrrateo|null
     */
    public function obtenerFactorVigente(string $categoria): ?FactorProrrateo
    {
        return FactorProrrateo::porCategoria($categoria)
            ->vigente($categoria)
            ->first();
    }

    /**
     * Obtener todos los factores vigentes (domiciliario y comercial).
     *
     * @return array
     */
    public function obtenerFactoresVigentes(): array
    {
        return [
            'domiciliaria' => $this->obtenerFactorVigente('domiciliaria'),
            'comercial' => $this->obtenerFactorVigente('comercial'),
        ];
    }

    /**
     * Actualizar el factor de una categoría con motivo obligatorio.
     *
     * @param string $categoria
     * @param float $nuevoFactor
     * @param string $motivo
     * @return FactorProrrateo
     * @throws \Exception
     */
    public function actualizarFactor(string $categoria, float $nuevoFactor, string $motivo): FactorProrrateo
    {
        // Validaciones
        if ($nuevoFactor <= 0) {
            throw new \Exception('El factor debe ser mayor a 0');
        }

        if (empty(trim($motivo))) {
            throw new \Exception('El motivo del cambio es obligatorio');
        }

        $categoria = strtolower($categoria);
        if (!in_array($categoria, ['domiciliaria', 'comercial'])) {
            throw new \Exception('Categoría inválida. Use "domiciliaria" o "comercial"');
        }

        $userId = auth()->id();
        $hoy = now()->startOfDay();

        return DB::transaction(function () use ($categoria, $nuevoFactor, $motivo, $userId, $hoy) {
            // Obtener factor anterior vigente
            $anterior = $this->obtenerFactorVigente($categoria);

            if (!$anterior) {
                throw new \Exception("No existe un factor vigente para la categoría {$categoria}. Debe crear uno primero.");
            }

            // Verificar si ya existe un registro creado hoy
            $factorHoy = FactorProrrateo::where('categoria', $categoria)
                ->whereDate('vigencia_desde', $hoy)
                ->first();

            if ($factorHoy) {
                // Ya existe un registro creado hoy, actualizarlo
                $factorAnteriorValor = $factorHoy->factor;
                $factorHoy->factor = $nuevoFactor;
                $factorHoy->activo = true;
                $factorHoy->save();

                $nuevo = $factorHoy;

                // Guardar en historial con el valor anterior
                FactorHistorial::create([
                    'factor_prorrateo_id' => $nuevo->id,
                    'factor_anterior' => $factorAnteriorValor,
                    'factor_nuevo' => $nuevoFactor,
                    'cambiado_por' => $userId,
                    'motivo' => $motivo,
                    'vigencia_desde' => $hoy,
                ]);

                Log::info("Factor actualizado (registro existente hoy)", [
                    'categoria' => $categoria,
                    'anterior' => $factorAnteriorValor,
                    'nuevo' => $nuevoFactor,
                    'usuario' => $userId,
                ]);
            } else {
                // No existe registro hoy, desactivar anterior y crear nuevo
                $anterior->activo = false;
                $anterior->save();

                try {
                    // Crear nuevo factor
                    $nuevo = FactorProrrateo::create([
                        'categoria' => $categoria,
                        'factor' => $nuevoFactor,
                        'vigencia_desde' => $hoy,
                        'activo' => true,
                        'creado_por' => $userId,
                    ]);

                    // Guardar en historial
                    FactorHistorial::create([
                        'factor_prorrateo_id' => $nuevo->id,
                        'factor_anterior' => $anterior->factor,
                        'factor_nuevo' => $nuevoFactor,
                        'cambiado_por' => $userId,
                        'motivo' => $motivo,
                        'vigencia_desde' => $hoy,
                    ]);

                    Log::info("Factor actualizado (nuevo registro)", [
                        'categoria' => $categoria,
                        'anterior' => $anterior->factor,
                        'nuevo' => $nuevoFactor,
                        'usuario' => $userId,
                    ]);
                } catch (QueryException $e) {
                    // Capturar error de duplicado y dar mensaje claro
                    if ($e->getCode() == 23000) {
                        throw new \Exception(
                            "Ya existe un factor para la categoría {$categoria} creado hoy. " .
                            "Espere hasta mañana para crear uno nuevo o actualice el existente."
                        );
                    }
                    throw $e;
                }
            }

            // Registrar en log de actividad
            if (class_exists('App\Models\ActivityLog')) {
                \App\Models\ActivityLog::create([
                    'user_id' => $userId,
                    'action' => 'factor.actualizado',
                    'model_type' => FactorProrrateo::class,
                    'model_id' => $nuevo->id,
                    'description' => "Factor {$categoria} actualizado a {$nuevoFactor}",
                    'properties' => [
                        'categoria' => $categoria,
                        'factor_anterior' => isset($factorAnteriorValor) ? $factorAnteriorValor : $anterior->factor,
                        'factor_nuevo' => $nuevoFactor,
                        'motivo' => $motivo,
                    ],
                ]);
            }

            return $nuevo;
        });
    }

    /**
     * Crear un factor inicial (para seeders).
     *
     * @param string $categoria
     * @param float $factor
     * @return FactorProrrateo
     */
    public function crearFactorInicial(string $categoria, float $factor): FactorProrrateo
    {
        return FactorProrrateo::create([
            'categoria' => $categoria,
            'factor' => $factor,
            'vigencia_desde' => now()->startOfDay(),
            'activo' => true,
            'creado_por' => auth()->id() ?? 1,
        ]);
    }

    /**
     * Obtener historial de cambios de factores.
     *
     * @param int|null $factorId
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerHistorial(?int $factorId = null, int $limit = 50)
    {
        $query = FactorHistorial::with(['factorProrrateo', 'cambiadoPor'])
            ->orderBy('created_at', 'desc');

        if ($factorId) {
            $query->where('factor_prorrateo_id', $factorId);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Verificar si existen factores vigentes para todas las categorías.
     *
     * @return bool
     */
    public function verificarFactoresConfigurados(): bool
    {
        $factores = $this->obtenerFactoresVigentes();

        return $factores['domiciliaria'] !== null && $factores['comercial'] !== null;
    }
}
