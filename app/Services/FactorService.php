<?php

namespace App\Services;

use App\Models\FactorHistorial;
use App\Models\FactorProrrateo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        return DB::transaction(function () use ($categoria, $nuevoFactor, $motivo, $userId) {
            // Obtener factor anterior vigente
            $anterior = $this->obtenerFactorVigente($categoria);

            if (!$anterior) {
                throw new \Exception("No existe un factor vigente para la categoría {$categoria}. Debe crear uno primero.");
            }

            // Desactivar factor anterior
            $anterior->activo = false;
            $anterior->save();

            // Crear nuevo factor
            $nuevo = FactorProrrateo::create([
                'categoria' => $categoria,
                'factor' => $nuevoFactor,
                'vigencia_desde' => now(),
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
                'vigencia_desde' => now(),
            ]);

            // Registrar en log de actividad
            if (class_exists('App\Models\ActivityLog')) {
                \App\Models\ActivityLog::create([
                    'user_id' => $userId,
                    'action' => 'factor.actualizado',
                    'model_type' => FactorProrrateo::class,
                    'model_id' => $nuevo->id,
                    'description' => "Factor {$categoria} actualizado de {$anterior->factor} a {$nuevoFactor}",
                    'properties' => [
                        'categoria' => $categoria,
                        'factor_anterior' => $anterior->factor,
                        'factor_nuevo' => $nuevoFactor,
                        'motivo' => $motivo,
                    ],
                ]);
            }

            Log::info("Factor actualizado", [
                'categoria' => $categoria,
                'anterior' => $anterior->factor,
                'nuevo' => $nuevoFactor,
                'usuario' => $userId,
            ]);

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
            'vigencia_desde' => now(),
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
