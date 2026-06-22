<?php

namespace Database\Factories;

use App\Models\PeriodoExpensa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class PeriodoExpensaFactory extends Factory
{
    protected $model = PeriodoExpensa::class;

    public function definition(): array
    {
        // Asegurar que existe un usuario para las foreign keys
        $user = \App\Models\User::first();
        if (!$user) {
            $user = \App\Models\User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        return [
            'mes' => $this->faker->numberBetween(1, 12),
            'año' => 2026,
            'fecha_apertura' => Carbon::now()->subMonths(2),
            'fecha_cierre' => null,
            'estado' => 'abierto',
            'motivo_reapertura' => null,
            'cerrado_por' => null,
            'factor_domiciliario' => null,
            'factor_comercial' => null,
            'factores_calculados_at' => null,
            'factores_calculados_por' => null,
        ];
    }

    /**
     * Período cerrado
     */
    public function cerrado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'cerrado',
            'fecha_cierre' => Carbon::now(),
            'cerrado_por' => User::first()?->id ?? 1,
        ]);
    }

    /**
     * Período abierto con factores calculados
     */
    public function conFactoresCalculados(float $domiciliario = 2.40, float $comercial = 4.20): static
    {
        return $this->state(fn (array $attributes) => [
            'factor_domiciliario' => $domiciliario,
            'factor_comercial' => $comercial,
            'factores_calculados_at' => Carbon::now(),
            'factores_calculados_por' => User::first()?->id ?? 1,
        ]);
    }

    /**
     * Período específico (mes, año)
     */
    public function paraPeriodo(int $mes, int $año = 2026): static
    {
        return $this->state(fn (array $attributes) => [
            'mes' => $mes,
            'año' => $año,
            'fecha_apertura' => Carbon::create($año, $mes, 1)->startOfMonth(),
        ]);
    }

    /**
     * Período enero 2026
     */
    public function enero2026(): static
    {
        return $this->paraPeriodo(1, 2026);
    }

    /**
     * Período febrero 2026
     */
    public function febrero2026(): static
    {
        return $this->paraPeriodo(2, 2026);
    }

    /**
     * Período marzo 2026
     */
    public function marzo2026(): static
    {
        return $this->paraPeriodo(3, 2026);
    }

    /**
     * Período abril 2026
     */
    public function abril2026(): static
    {
        return $this->paraPeriodo(4, 2026);
    }

    /**
     * Período mayo 2026
     */
    public function mayo2026(): static
    {
        return $this->paraPeriodo(5, 2026);
    }

    /**
     * Período junio 2026
     */
    public function junio2026(): static
    {
        return $this->paraPeriodo(6, 2026);
    }

    /**
     * Período julio 2026
     */
    public function julio2026(): static
    {
        return $this->paraPeriodo(7, 2026);
    }

    /**
     * Período corriente (mes actual)
     */
    public function corriente(): static
    {
        $now = Carbon::now();
        return $this->paraPeriodo($now->month, $now->year);
    }

    /**
     * Período diciembre 2025
     */
    public function diciembre2025(): static
    {
        return $this->paraPeriodo(12, 2025);
    }

    /**
     * Período noviembre 2025
     */
    public function noviembre2025(): static
    {
        return $this->paraPeriodo(11, 2025);
    }
}
