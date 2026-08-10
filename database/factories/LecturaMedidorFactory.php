<?php

namespace Database\Factories;

use App\Models\LecturaMedidor;
use App\Models\PeriodoExpensa;
use App\Models\Unidad;
use Illuminate\Database\Eloquent\Factories\Factory;

class LecturaMedidorFactory extends Factory
{
    protected $model = LecturaMedidor::class;

    public function definition(): array
    {
        $lecturaAnterior = $this->faker->randomFloat(2, 100, 500);
        $lecturaActual = $lecturaAnterior + $this->faker->randomFloat(2, 10, 50);
        $consumo = $lecturaActual - $lecturaAnterior;

        return [
            'periodo_id' => PeriodoExpensa::factory(),
            'unidad_id' => Unidad::factory(),
            'medidor_grupo_id' => null,
            'lectura_anterior' => $lecturaAnterior,
            'lectura_actual' => $lecturaActual,
            'consumo_m3' => $consumo,
            'observaciones' => $this->faker->optional(0.2)->sentence(),
            'registrado_por' => 1,
        ];
    }

    /**
     * Lectura para una unidad específica
     */
    public function paraUnidad(int $unidadId): static
    {
        return $this->state(fn (array $attributes) => [
            'unidad_id' => $unidadId,
        ]);
    }

    /**
     * Lectura para un período específico
     */
    public function paraPeriodo(int $periodoId): static
    {
        return $this->state(fn (array $attributes) => [
            'periodo_id' => $periodoId,
        ]);
    }

    /**
     * Lectura con consumo específico
     */
    public function conConsumo(float $consumo, float $lecturaBase = 200): static
    {
        $lecturaAnterior = $lecturaBase;
        $lecturaActual = $lecturaBase + $consumo;

        return $this->state(fn (array $attributes) => [
            'lectura_anterior' => $lecturaAnterior,
            'lectura_actual' => $lecturaActual,
            'consumo_m3' => $consumo,
        ]);
    }

    /**
     * Lectura de medidor compartido (grupo)
     */
    public function compartido(): static
    {
        return $this->state(fn (array $attributes) => [
            'medidor_grupo_id' => 1,
        ]);
    }
}
