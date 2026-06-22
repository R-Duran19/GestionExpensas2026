<?php

namespace Database\Factories;

use App\Models\Propietario;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropietarioFactory extends Factory
{
    protected $model = Propietario::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->firstName(),
            'apellido' => $this->faker->lastName(),
            'ci' => $this->faker->unique()->numerify('########'),
            'telefono' => $this->faker->phoneNumber(),
            'email' => $this->faker->safeEmail(),
            'activo' => true,
            'observaciones' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    /**
     * Estado inactivo
     */
    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }
}
