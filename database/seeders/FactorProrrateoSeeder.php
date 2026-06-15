<?php

namespace Database\Seeders;

use App\Models\FactorProrrateo;
use Illuminate\Database\Seeder;

class FactorProrrateoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el usuario admin (asumimos que ya existe)
        $adminUserId = \App\Models\User::where('email', 'admin@admin.com')->first()?->id ?? 2;

        // Crear factor domiciliario (2.1)
        FactorProrrateo::firstOrCreate(
            [
                'categoria' => 'domiciliaria',
                'activo' => true,
            ],
            [
                'factor' => 2.40,
                'vigencia_desde' => now(),
                'creado_por' => $adminUserId,
            ]
        );

        // Crear factor comercial (3.5)
        FactorProrrateo::firstOrCreate(
            [
                'categoria' => 'comercial',
                'activo' => true,
            ],
            [
                'factor' => 4.20,
                'vigencia_desde' => now(),
                'creado_por' => $adminUserId,
            ]
        );

        $this->command->info('✅ Factores de prorrateo creados correctamente:');
        $this->command->info('   - Domiciliario: 2.10');
        $this->command->info('   - Comercial: 3.50');
    }
}
