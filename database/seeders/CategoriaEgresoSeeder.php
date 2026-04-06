<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoriaEgresoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            [
                'nombre' => 'Mantenimiento',
                'descripcion' => 'Mantenimiento general del edificio',
                'color' => '#EF4444',
                'icono' => 'wrench',
                'activo' => true,
                'creado_por' => 1, // Admin
                'actualizado_por' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Servicios Públicos',
                'descripcion' => 'Agua, luz, gas, internet, etc.',
                'color' => '#F59E0B',
                'icono' => 'bolt',
                'activo' => true,
                'creado_por' => 1, // Admin
                'actualizado_por' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Limpieza',
                'descripcion' => 'Servicios de limpieza del edificio',
                'color' => '#3B82F6',
                'icono' => 'broom',
                'activo' => true,
                'creado_por' => 1, // Admin
                'actualizado_por' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Seguridad',
                'descripcion' => 'Servicios de seguridad y vigilancia',
                'color' => '#8B5CF6',
                'icono' => 'shield',
                'activo' => true,
                'creado_por' => 1, // Admin
                'actualizado_por' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Administración',
                'descripcion' => 'Gastos administrativos y generales',
                'color' => '#6B7280',
                'icono' => 'briefcase',
                'activo' => true,
                'creado_por' => 1, // Admin
                'actualizado_por' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        \DB::table('categorias_egresos')->insert($categorias);
    }
}
