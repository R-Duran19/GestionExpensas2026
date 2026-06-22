<?php

namespace Database\Factories;

use App\Enums\CategoriaUnidad;
use App\Enums\TipoUnidad;
use App\Models\Unidad;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnidadFactory extends Factory
{
    protected $model = Unidad::class;

    public function definition(): array
    {
        // Por defecto, departamento con medidor
        $tipo = TipoUnidad::DEPARTAMENTO;
        $categoria = $tipo->getCategoriaPermitida();

        return [
            'codigo' => strtoupper($this->faker->bothify('??-##')),
            'tipo_unidad' => $tipo,
            'piso' => $this->faker->numberBetween(1, 10),
            'metros_cuadrados' => $this->faker->randomElement([45.50, 60.00, 75.50, 85.00, 100.00, 120.50]),
            'categoria_unidad' => $categoria,
            'tiene_medidor' => true,
            'tipo_medidor' => 'individual',
            'numero_medidor' => $this->faker->unique()->numerify('#######'),
            'medidor_grupo_id' => null,
            'activo' => true,
            'observaciones' => $this->faker->optional(0.2)->sentence(),
        ];
    }

    /**
     * Unidad de tipo departamento
     */
    public function departamento(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_unidad' => TipoUnidad::DEPARTAMENTO,
            'categoria_unidad' => CategoriaUnidad::DOMICILIARIA,
            'tiene_medidor' => true,
        ]);
    }

    /**
     * Unidad de tipo oficina
     */
    public function oficina(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_unidad' => TipoUnidad::OFICINA,
            'categoria_unidad' => CategoriaUnidad::COMERCIAL,
            'tiene_medidor' => true,
        ]);
    }

    /**
     * Unidad de tipo local comercial
     */
    public function local(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_unidad' => TipoUnidad::LOCAL,
            'categoria_unidad' => CategoriaUnidad::COMERCIAL,
            'tiene_medidor' => true,
        ]);
    }

    /**
     * Unidad de tipo parqueo (sin medidor)
     */
    public function parqueo(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_unidad' => TipoUnidad::PARQUEO,
            'categoria_unidad' => CategoriaUnidad::DOMICILIARIA,
            'tiene_medidor' => false,
            'tipo_medidor' => null,
            'numero_medidor' => null,
        ]);
    }

    /**
     * Unidad de tipo baulera (sin medidor)
     */
    public function baulera(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_unidad' => TipoUnidad::BAULERA,
            'categoria_unidad' => CategoriaUnidad::DOMICILIARIA,
            'tiene_medidor' => false,
            'tipo_medidor' => null,
            'numero_medidor' => null,
        ]);
    }

    /**
     * Unidad sin medidor de agua
     */
    public function sinMedidor(): static
    {
        return $this->state(fn (array $attributes) => [
            'tiene_medidor' => false,
            'tipo_medidor' => null,
            'numero_medidor' => null,
        ]);
    }

    /**
     * Unidad inactiva
     */
    public function inactiva(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }

    /**
     * Unidad con medidor compartido (grupo)
     */
    public function conMedidorCompartido(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_medidor' => 'compartido',
            'numero_medidor' => null,
        ]);
    }

    /**
     * Unidad domiciliaria (departamento, parqueo, baulera)
     */
    public function domiciliaria(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_unidad' => $this->faker->randomElement([
                TipoUnidad::DEPARTAMENTO,
                TipoUnidad::PARQUEO,
                TipoUnidad::BAULERA,
            ]),
            'categoria_unidad' => CategoriaUnidad::DOMICILIARIA,
        ]);
    }

    /**
     * Unidad comercial (oficina, local)
     */
    public function comercial(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_unidad' => $this->faker->randomElement([
                TipoUnidad::OFICINA,
                TipoUnidad::LOCAL,
            ]),
            'categoria_unidad' => CategoriaUnidad::COMERCIAL,
        ]);
    }
}
