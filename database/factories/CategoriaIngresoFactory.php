<?php

namespace Database\Factories;

use App\Models\CategoriaIngreso;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoriaIngresoFactory extends Factory
{
    protected $model = CategoriaIngreso::class;

    public function definition(): array
    {
        $nombre = $this->faker->randomElement(['Expensas', 'Cuota Extraordinaria', 'Intereses', 'Otros Ingresos']);

        return [
            'nombre' => $nombre,
            'activo' => true,
        ];
    }

    /**
     * Categoría de expensas
     */
    public function expensas(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombre' => 'Expensas',
        ]);
    }
}
