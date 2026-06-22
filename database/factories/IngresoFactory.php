<?php

namespace Database\Factories;

use App\Models\Ingreso;
use Illuminate\Database\Eloquent\Factories\Factory;

class IngresoFactory extends Factory
{
    protected $model = Ingreso::class;

    public function definition(): array
    {
        // Asegurar que existe una categoría
        $categoria = \App\Models\CategoriaIngreso::first();
        if (!$categoria) {
            $categoria = \App\Models\CategoriaIngreso::factory()->create();
        }

        return [
            'numero_recibo' => $this->faker->unique()->numerify('REC-########'),
            'fecha' => $this->faker->dateBetween('-1 month', 'now'),
            'categoria_ingreso_id' => $categoria->id,
            'tipo_ingreso' => $this->faker->randomElement(['expensa', 'extraordinario']),
            'importe' => $this->faker->randomFloat(2, 50, 500),
            'monto_total' => fn (array $attributes) => $attributes['importe'],
            'metodo_pago' => $this->faker->randomElement(['efectivo', 'transferencia', 'qr', 'otro']),
            'comprobante_foto' => null,
            'referencia_bancaria' => $this->faker->optional(0.5)->numerify('REF-########'),
            'unidad_id' => null,
            'activo' => true,
        ];
    }

    /**
     * Ingreso para una unidad específica
     */
    public function paraUnidad(int $unidadId): static
    {
        return $this->state(fn (array $attributes) => [
            'unidad_id' => $unidadId,
        ]);
    }

    /**
     * Ingreso de expensa
     */
    public function expensa(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_ingreso' => 'expensa',
        ]);
    }

    /**
     * Ingreso extraordinario
     */
    public function extraordinario(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_ingreso' => 'extraordinario',
        ]);
    }

    /**
     * Ingreso con monto específico
     */
    public function conMonto(float $monto): static
    {
        return $this->state(fn (array $attributes) => [
            'importe' => $monto,
            'monto_total' => $monto,
        ]);
    }
}
