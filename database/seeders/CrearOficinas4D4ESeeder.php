<?php

namespace Database\Seeders;

use App\Models\Unidad;
use App\Models\Propietario;
use App\Enums\TipoUnidad;
use App\Enums\CategoriaUnidad;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CrearOficinas4D4ESeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('========================================');
        $this->command->info('  Crear Oficinas 4D y 4E  ');
        $this->command->info('========================================');
        $this->command->info('');

        DB::beginTransaction();
        try {
            // 1. Crear o buscar el propietario ADMINISTRACION
            $propietario = Propietario::firstOrCreate(
                ['nombre' => 'ADMINISTRACION'],
                [
                    'apellido' => '',
                    'ci' => $this->generarCIRandom(),
                    'telefono' => $this->generarTelefonoRandom(),
                    'email' => $this->generarEmailRandom(),
                    'activo' => true,
                    'observaciones' => 'Cuenta administrativa para oficinas comunales'
                ]
            );

            $this->command->info("✓ Propietario: {$propietario->nombre} (CI: {$propietario->ci})");

            // 2. Crear las oficinas 4D y 4E
            $oficinas = ['D', 'E'];

            foreach ($oficinas as $letra) {
                $codigo = "OF-4-{$letra}";

                // Verificar si la unidad ya existe
                $unidadExistente = Unidad::where('codigo', $codigo)
                    ->where('tipo_unidad', TipoUnidad::OFICINA)
                    ->first();

                if ($unidadExistente) {
                    $this->command->warn("⚠ La oficina {$codigo} ya existe, actualizando propietario...");
                    $this->asignarPropietarioAUnidad($propietario, $unidadExistente);
                    continue;
                }

                // Crear la nueva unidad
                $unidad = Unidad::create([
                    'codigo' => $codigo,
                    'tipo_unidad' => TipoUnidad::OFICINA,
                    'piso' => '4',
                    'metros_cuadrados' => 1.00,
                    'categoria_unidad' => CategoriaUnidad::COMERCIAL,
                    'tiene_medidor' => true,  // Las oficinas tienen medidor
                    'tipo_medidor' => 'individual',
                    'activo' => true,
                    'observaciones' => 'Oficina creada automáticamente para administración'
                ]);

                $this->command->info("✓ Oficina creada: {$unidad->codigo} (1 m²)");

                // Asignar el propietario
                $this->asignarPropietarioAUnidad($propietario, $unidad);
            }

            DB::commit();
            $this->command->info('');
            $this->command->info('========================================');
            $this->command->info('  ✓ Proceso completado exitosamente  ');
            $this->command->info('========================================');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("✗ Error: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Asignar un propietario a una unidad
     */
    private function asignarPropietarioAUnidad(Propietario $propietario, Unidad $unidad): void
    {
        // Verificar si ya existe relación actual
        $relacionExistente = DB::table('propietario_unidad')
            ->where('propietario_id', $propietario->id)
            ->where('unidad_id', $unidad->id)
            ->where('es_propietario_actual', true)
            ->first();

        if ($relacionExistente) {
            $this->command->info("  La relación ya existe");
            return;
        }

        // Desactivar relaciones anteriores de esta unidad
        DB::table('propietario_unidad')
            ->where('unidad_id', $unidad->id)
            ->where('es_propietario_actual', true)
            ->update([
                'es_propietario_actual' => false,
                'fecha_fin' => now()->toDateString(),
            ]);

        // Crear nueva relación
        DB::table('propietario_unidad')->insert([
            'propietario_id' => $propietario->id,
            'unidad_id' => $unidad->id,
            'fecha_inicio' => now()->toDateString(),
            'es_propietario_actual' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info("  ✓ Propietario asignado: {$propietario->nombre}");
    }

    /**
     * Generar un CI random de 8 dígitos
     */
    private function generarCIRandom(): string
    {
        return str_pad(rand(1, 99999999), 8, '0', STR_PAD_LEFT);
    }

    /**
     * Generar un teléfono random
     */
    private function generarTelefonoRandom(): string
    {
        return '+' . rand(100, 999) . rand(100000000, 999999999);
    }

    /**
     * Generar un email random
     */
    private function generarEmailRandom(): string
    {
        $dominios = ['gmail.com', 'hotmail.com', 'yahoo.com', 'outlook.com'];
        $usuario = 'admin' . rand(100, 999);
        $dominio = $dominios[array_rand($dominios)];
        return $usuario . '@' . $dominio;
    }
}
