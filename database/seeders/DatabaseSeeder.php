<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        // FASE 1.1: Seguridad y Roles
        $this->call([
            AdminRoleSeeder::class,
        ]);

        // FASE 1.2: Propiedades y Propietarios
        $this->call([
            \Database\Seeders\UnidadesMigracionSeeder::class,
            \Database\Seeders\PropietarioSeeder::class,
        ]);

        // FASE 1.5: Configuración del Sistema
        $this->call([
            FactorProrrateoSeeder::class,
            FacturaAguaSeeder::class,
        ]);

        // FASE 1.6: Categorías de Caja
        $this->call([
            CategoriaIngresoSeeder::class,
            CategoriaEgresoSeeder::class,
        ]);
    }
}
