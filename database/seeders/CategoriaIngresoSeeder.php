<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoriaIngresoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            [
                'nombre' => 'Expensas',
                'descripcion' => 'Pagos de expensas de los propietarios',
                'color' => '#10B981',
                'icono' => 'building',
                'activo' => true,
                'creado_por' => 1, // Admin
                'actualizado_por' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Cuota Extraordinaria',
                'descripcion' => 'Cuotas extraordinarias o especiales',
                'color' => '#F59E0B',
                'icono' => 'dollar-sign',
                'activo' => true,
                'creado_por' => 1, // Admin
                'actualizado_por' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Intereses',
                'descripcion' => 'Intereses por pagos fuera de término',
                'color' => '#8B5CF6',
                'icono' => 'percent',
                'activo' => true,
                'creado_por' => 1, // Admin
                'actualizado_por' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Otros Ingresos',
                'descripcion' => 'Otros ingresos no categorizados',
                'color' => '#6B7280',
                'icono' => 'plus-circle',
                'activo' => true,
                'creado_por' => 1, // Admin
                'actualizado_por' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        \DB::table('categorias_ingresos')->insert($categorias);
    }
}
