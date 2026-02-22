<?php

namespace Database\Seeders;

use App\Models\Propietario;
use Illuminate\Database\Seeder;

class PropietarioSeeder extends Seeder
{
    /**
     * Crear propietarios de prueba
     */
    public function run(): void
    {
        $propietarios = [
            [
                'nombre' => 'Juan Carlos',
                'apellido' => 'Pérez López',
                'ci' => '1234567',
                'telefono' => '71234567',
                'email' => 'juan.perez@email.com',
                'observaciones' => 'Propietario de múltiples unidades',
            ],
            [
                'nombre' => 'María Elena',
                'apellido' => 'González Sánchez',
                'ci' => '2345678',
                'telefono' => '72234568',
                'email' => 'maria.gonzalez@email.com',
                'observaciones' => null,
            ],
            [
                'nombre' => 'Carlos Roberto',
                'apellido' => 'López Mercado',
                'ci' => '3456789',
                'telefono' => '73234569',
                'email' => 'carlos.lopez@email.com',
                'observaciones' => 'Prefiere contacto por teléfono',
            ],
            [
                'nombre' => 'Ana Patricia',
                'apellido' => 'Ramírez Torrez',
                'ci' => '4567890',
                'telefono' => '74234570',
                'email' => 'ana.ramirez@email.com',
                'observaciones' => null,
            ],
            [
                'nombre' => 'Luis Fernando',
                'apellido' => 'Suárez Vaca',
                'ci' => '5678901',
                'telefono' => '75234571',
                'email' => 'luis.suarez@email.com',
                'observaciones' => 'Propietario desde 2020',
            ],
            [
                'nombre' => 'Carmen Isabel',
                'apellido' => 'Mendoza Roca',
                'ci' => '6789012',
                'telefono' => '76234572',
                'email' => 'carmen.mendoza@email.com',
                'observaciones' => null,
            ],
            [
                'nombre' => 'Jorge Luis',
                'apellido' => 'Fernando Castro',
                'ci' => '7890123',
                'telefono' => '77234573',
                'email' => 'jorge.fernando@email.com',
                'observaciones' => 'Vive en el exterior',
            ],
            [
                'nombre' => 'Rosa María',
                'apellido' => 'Dávila Urioste',
                'ci' => '8901234',
                'telefono' => '78234574',
                'email' => 'rosa.davila@email.com',
                'observaciones' => 'Contacto prioritario: email',
            ],
            [
                'nombre' => 'Miguel Ángel',
                'apellido' => 'Ortiz Pando',
                'ci' => '9012345',
                'telefono' => '79234575',
                'email' => 'miguel.ortiz@email.com',
                'observaciones' => null,
            ],
            [
                'nombre' => 'Patricia Elizabeth',
                'apellido' => 'Rivas Bascope',
                'ci' => '1023456',
                'telefono' => '70234576',
                'email' => 'patricia.rivas@email.com',
                'observaciones' => 'Representante legal de varias unidades',
            ],
        ];

        foreach ($propietarios as $propietario) {
            Propietario::create([
                'nombre' => $propietario['nombre'],
                'apellido' => $propietario['apellido'],
                'ci' => $propietario['ci'],
                'telefono' => $propietario['telefono'],
                'email' => $propietario['email'],
                'activo' => true,
                'observaciones' => $propietario['observaciones'],
            ]);
        }

        $this->command->info('✓ Propietarios de prueba creados: ' . count($propietarios));
    }
}
