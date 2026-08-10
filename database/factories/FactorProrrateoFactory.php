<?php

namespace Database\Factories;

use App\Models\FactorProrrateo;
use Illuminate\Database\Eloquent\Factories\Factory;

class FactorProrrateoFactory extends Factory
{
    protected $model = FactorProrrateo::class;

    public function definition(): array
    {
        return [
            'categoria' => $this->faker->randomElement(['domiciliaria', 'comercial']),
            'factor' => $this->faker->randomFloat(2, 2.0, 5.0),
            'vigencia_desde' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'activo' => true,
            'creado_por' => 1,
        ];
    }

    /**
     * Factor domiciliario (uso residencial)
     */
    public function domiciliario(float $factor = 2.40): static
    {
        return $this->state(fn (array $attributes) => [
            'categoria' => 'domiciliaria',
            'factor' => $factor,
        ]);
    }

    /**
     * Factor comercial (uso comercial)
     */
    public function comercial(float $factor = 4.20): static
    {
        return $this->state(fn (array $attributes) => [
            'categoria' => 'comercial',
            'factor' => $factor,
        ]);
    }

    /**
     * Factor con vigencia desde una fecha específica
     */
    public function vigenteDesde(string $fecha): static
    {
        return $this->state(fn (array $attributes) => [
            'vigencia_desde' => $fecha,
        ]);
    }

    /**
     * Factor inactivo
     */
    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }

    /**
     * Factor con factor específico
     */
    public function conFactor(float $factor): static
    {
        return $this->state(fn (array $attributes) => [
            'factor' => $factor,
        ]);
    }
}
