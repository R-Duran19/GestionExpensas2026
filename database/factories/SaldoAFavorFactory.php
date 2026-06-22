<?php

namespace Database\Factories;

use App\Models\Ingreso;
use App\Models\SaldoAFavor;
use App\Models\Unidad;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaldoAFavorFactory extends Factory
{
    protected $model = SaldoAFavor::class;

    public function definition(): array
    {
        $montoOriginal = $this->faker->randomFloat(2, 10, 500);
        $montoAplicado = $this->faker->randomFloat(2, 0, $montoOriginal);

        return [
            'unidad_id' => Unidad::factory(),
            'ingreso_id' => Ingreso::factory(),
            'monto_original' => $montoOriginal,
            'monto_aplicado' => $montoAplicado,
            'monto_saldo' => $montoOriginal - $montoAplicado,
            'estado' => $montoAplicado > 0 ? 'parcialmente_consumido' : 'disponible',
            'consumido_totalmente_en' => $montoAplicado >= $montoOriginal ? now() : null,
        ];
    }

    /**
     * Saldo disponible (sin aplicar)
     */
    public function disponible(): static
    {
        $montoOriginal = $this->faker->randomFloat(2, 10, 500);

        return $this->state(fn (array $attributes) => [
            'monto_original' => $montoOriginal,
            'monto_aplicado' => 0,
            'monto_saldo' => $montoOriginal,
            'estado' => 'disponible',
            'consumido_totalmente_en' => null,
        ]);
    }

    /**
     * Saldo parcialmente consumido
     */
    public function parcialmenteConsumido(): static
    {
        $montoOriginal = $this->faker->randomFloat(2, 50, 500);
        $montoAplicado = $this->faker->randomFloat(2, 10, $montoOriginal - 5);

        return $this->state(fn (array $attributes) => [
            'monto_original' => $montoOriginal,
            'monto_aplicado' => $montoAplicado,
            'monto_saldo' => $montoOriginal - $montoAplicado,
            'estado' => 'parcialmente_consumido',
            'consumido_totalmente_en' => null,
        ]);
    }

    /**
     * Saldo totalmente consumido
     */
    public function consumido(): static
    {
        $montoOriginal = $this->faker->randomFloat(2, 10, 500);

        return $this->state(fn (array $attributes) => [
            'monto_original' => $montoOriginal,
            'monto_aplicado' => $montoOriginal,
            'monto_saldo' => 0,
            'estado' => 'consumido',
            'consumido_totalmente_en' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Saldo con monto específico
     */
    public function conMonto(float $monto): static
    {
        return $this->state(fn (array $attributes) => [
            'monto_original' => $monto,
            'monto_aplicado' => 0,
            'monto_saldo' => $monto,
            'estado' => 'disponible',
            'consumido_totalmente_en' => null,
        ]);
    }
}
