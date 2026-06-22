<?php

namespace Database\Factories;

use App\Models\Expensa;
use App\Models\PeriodoExpensa;
use App\Models\Unidad;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpensaFactory extends Factory
{
    protected $model = Expensa::class;

    public function definition(): array
    {
        // Valores por defecto que serán calculados por el servicio
        return [
            'periodo_id' => PeriodoExpensa::factory(),
            'unidad_id' => Unidad::factory(),
            'monto_expensa' => 150.00,
            'monto_agua' => 50.00,
            'expensa_pendiente' => 150.00,
            'agua_pendiente' => 50.00,
            'periodo_cerrado' => false,
            'monto_total' => 200.00,
        ];
    }

    /**
     * Expensa pagada (sin deuda)
     */
    public function pagada(): static
    {
        return $this->state(fn (array $attributes) => [
            'expensa_pendiente' => 0,
            'agua_pendiente' => 0,
        ]);
    }

    /**
     * Expensa con deuda parcial
     */
    public function parcial(): static
    {
        return $this->state(fn (array $attributes) => [
            'expensa_pendiente' => $attributes['monto_expensa'] / 2,
            'agua_pendiente' => $attributes['monto_agua'] / 2,
        ]);
    }

    /**
     * Expensa de período cerrado (inmutable)
     */
    public function periodoCerrado(): static
    {
        return $this->state(fn (array $attributes) => [
            'periodo_cerrado' => true,
        ]);
    }

    /**
     * Expensa sin deuda de agua (unidad sin medidor)
     */
    public function sinAgua(): static
    {
        return $this->state(fn (array $attributes) => [
            'monto_agua' => 0,
            'agua_pendiente' => 0,
            'monto_total' => $attributes['monto_expensa'],
        ]);
    }

    /**
     * Expensa con montos específicos
     */
    public function conMontos(float $expensa, float $agua = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'monto_expensa' => $expensa,
            'monto_agua' => $agua,
            'expensa_pendiente' => $expensa,
            'agua_pendiente' => $agua,
            'monto_total' => $expensa + $agua,
        ]);
    }

    /**
     * Expensa con pendientes específicos
     */
    public function conPendientes(float $expensaPendiente, float $aguaPendiente): static
    {
        return $this->state(fn (array $attributes) => [
            'expensa_pendiente' => $expensaPendiente,
            'agua_pendiente' => $aguaPendiente,
        ]);
    }
}
