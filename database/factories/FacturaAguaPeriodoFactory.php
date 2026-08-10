<?php

namespace Database\Factories;

use App\Models\FacturaAguaPeriodo;
use App\Models\PeriodoExpensa;
use Illuminate\Database\Eloquent\Factories\Factory;

class FacturaAguaPeriodoFactory extends Factory
{
    protected $model = FacturaAguaPeriodo::class;

    public function definition(): array
    {
        return [
            'periodo_id' => PeriodoExpensa::factory(),
            'factura_agua_config_id' => null,
            'numero_factura' => $this->faker->unique()->numerify('F-########'),
            'categoria' => $this->faker->randomElement(['domiciliaria', 'comercial']),
            'importe' => $this->faker->randomFloat(2, 500, 5000),
            'consumo_m3' => $this->faker->randomFloat(2, 100, 1000),
            'fecha_factura' => $this->faker->dateTimeBetween('-2 months', '-1 month'),
            'observaciones' => $this->faker->optional(0.3)->sentence(),
            'registrado_por' => 1,
        ];
    }

    /**
     * Factura domiciliaria
     */
    public function domiciliaria(float $importe = 1500, float $consumo = 300): static
    {
        return $this->state(fn (array $attributes) => [
            'categoria' => 'domiciliaria',
            'importe' => $importe,
            'consumo_m3' => $consumo,
        ]);
    }

    /**
     * Factura comercial
     */
    public function comercial(float $importe = 2500, float $consumo = 400): static
    {
        return $this->state(fn (array $attributes) => [
            'categoria' => 'comercial',
            'importe' => $importe,
            'consumo_m3' => $consumo,
        ]);
    }

    /**
     * Factura para un período específico
     */
    public function paraPeriodo(int $periodoId): static
    {
        return $this->state(fn (array $attributes) => [
            'periodo_id' => $periodoId,
        ]);
    }

    /**
     * Factura con importe y consumo específicos
     */
    public function conImporteYConsumo(float $importe, float $consumo): static
    {
        return $this->state(fn (array $attributes) => [
            'importe' => $importe,
            'consumo_m3' => $consumo,
        ]);
    }
}
