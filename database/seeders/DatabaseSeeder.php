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
            // \Database\Seeders\PropietarioSeeder::class,
            // \Database\Seeders\PropietarioUnidadFromExcelSeeder::class,
            \Database\Seeders\DepartamentosFromExcelSeeder::class,
            \Database\Seeders\OficinasFromExcelSeeder::class,
            \Database\Seeders\LocalesFromExcelSeeder::class,
            \Database\Seeders\ParqueosBaulerasFromExcelSeeder::class,
            // \Database\Seeders\CrearOficinas4D4ESeeder::class,
        ]);

        // FASE 1.5: Configuración del Sistema
        $this->call([
            FactorProrrateoSeeder::class,
            FacturaAguaSeeder::class,
            \Database\Seeders\MedidorGrupoLocalesSeeder::class,
        ]);

        // FASE 1.6: Categorías de Caja
        $this->call([
            CategoriaIngresoSeeder::class,
            CategoriaEgresoSeeder::class,
        ]);

        // FASE 2: Histórico de Expensas (Enero - Julio 2026)
        $this->call([
            \Database\Seeders\PeriodoEnero2026DesdeJSONSeeder::class,
            \Database\Seeders\PeriodoFebrero2026DesdeJSONSeeder::class,
            \Database\Seeders\PeriodoMarzo2026DesdeJSONSeeder::class,
            \Database\Seeders\PeriodoAbril2026DesdeJSONSeeder::class,
            \Database\Seeders\PeriodoMayo2026DesdeJSONSeeder::class,
            \Database\Seeders\PeriodoJunio2026DesdeJSONSeeder::class,
            \Database\Seeders\PeriodoJulio2026DesdeJSONSeeder::class,
        ]);
    }
}
