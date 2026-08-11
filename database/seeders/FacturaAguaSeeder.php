<?php

namespace Database\Seeders;

use App\Models\FacturaAguaConfig;
use Illuminate\Database\Seeder;

class FacturaAguaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el usuario admin (creado por AdminRoleSeeder). Si no existe,
        // usar el primer usuario disponible, o 1 como último recurso.
        $adminUserId = \App\Models\User::where('email', 'admin@admin.com')->first()?->id
            ?? \App\Models\User::first()?->id
            ?? 1;

        // Crear las 3 facturas de agua principales

        // Factura Domiciliaria 1
        FacturaAguaConfig::firstOrCreate(
            [
                'tipo' => 'domiciliaria_1',
            ],
            [
                'numero_factura' => 'DOM-001-0B22TS000518',
                'categoria' => 'domiciliaria',
                'activo' => true,
                'creado_por' => $adminUserId,
            ]
        );

        // Factura Domiciliaria 2
        FacturaAguaConfig::firstOrCreate(
            [
                'tipo' => 'domiciliaria_2',
            ],
            [
                'numero_factura' => 'DOM-002-16498',
                'categoria' => 'domiciliaria',
                'activo' => true,
                'creado_por' => $adminUserId,
            ]
        );

        // Factura Comercial
        FacturaAguaConfig::firstOrCreate(
            [
                'tipo' => 'comercial',
            ],
            [
                'numero_factura' => 'COM-00B16S002189',
                'categoria' => 'comercial',
                'activo' => true,
                'creado_por' => $adminUserId,
            ]
        );

        $this->command->info('✅ Facturas de agua EPSAS creadas correctamente:');
        $this->command->info('   - Domiciliaria 1: DOM-001');
        $this->command->info('   - Domiciliaria 2: DOM-002');
        $this->command->info('   - Comercial: COM-001');
    }
}
