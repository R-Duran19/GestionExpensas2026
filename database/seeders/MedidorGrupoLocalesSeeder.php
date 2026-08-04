<?php

namespace Database\Seeders;

use App\Models\MedidorGrupo;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MedidorGrupoLocalesSeeder extends Seeder
{
    private const NOMBRE_GRUPO = 'LOCALES 14-19';

    private const CODIGOS_UNIDADES = ['LOC-14', 'LOC-15', 'LOC-16', 'LOC-17', 'LOC-19'];

    public function run(): void
    {
        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('  MedidorGrupoLocalesSeeder');
        $this->command->info('========================================');

        $adminUserId = User::where('email', 'admin@admin.com')->first()?->id ?? 1;

        DB::beginTransaction();
        try {
            $grupo = MedidorGrupo::firstOrCreate(
                ['nombre' => self::NOMBRE_GRUPO],
                [
                    'tipo_distribucion' => 'equitativa',
                    'activo' => true,
                    'observaciones' => 'Medidor compartido entre los locales 14, 15, 16, 17 y 19. Distribución equitativa.',
                    'creado_por' => $adminUserId,
                ]
            );

            if ($grupo->wasRecentlyCreated) {
                $this->command->info("  ✓ Grupo creado: {$grupo->nombre} (id={$grupo->id})");
            } else {
                $this->command->info("  → Grupo ya existía: {$grupo->nombre} (id={$grupo->id})");
            }

            $unidades = Unidad::whereIn('codigo', self::CODIGOS_UNIDADES)->get()->keyBy('codigo');
            $faltantes = array_diff(self::CODIGOS_UNIDADES, $unidades->keys()->toArray());

            if (!empty($faltantes)) {
                throw new \RuntimeException(
                    'Unidades no encontradas en BD: ' . implode(', ', $faltantes)
                );
            }

            foreach (self::CODIGOS_UNIDADES as $codigo) {
                $unidad = $unidades->get($codigo);

                $unidad->update([
                    'tiene_medidor' => true,
                    'tipo_medidor' => 'compartido',
                    'medidor_grupo_id' => $grupo->id,
                ]);

                $yaAsignada = DB::table('medidor_grupo_unidades')
                    ->where('medidor_grupo_id', $grupo->id)
                    ->where('unidad_id', $unidad->id)
                    ->whereNull('fecha_retiro')
                    ->exists();

                if (!$yaAsignada) {
                    $grupo->unidades()->attach($unidad->id, [
                        'fecha_asignacion' => now(),
                    ]);
                    $this->command->info("  ✓ {$codigo} asignada al grupo");
                } else {
                    $this->command->info("  → {$codigo} ya estaba asignada");
                }
            }

            DB::commit();

            $this->command->info('');
            $this->command->info('  Resumen:');
            $this->command->info("    - Grupo: {$grupo->nombre}");
            $this->command->info('    - Tipo distribución: equitativa (partes iguales)');
            $this->command->info('    - Unidades activas en grupo: ' . $grupo->unidades()->count());
            $this->command->info('    - Cada unidad paga 1/' . count(self::CODIGOS_UNIDADES) . ' del consumo total');
            $this->command->info('');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('  ✗ Error: ' . $e->getMessage());
            Log::error('MedidorGrupoLocalesSeeder falló', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
