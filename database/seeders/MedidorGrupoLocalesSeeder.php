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
    /**
     * Definición de los grupos de medidores compartidos del edificio.
     *
     * Cada grupo define: nombre, observación descriptiva y lista de códigos
     * de las unidades que comparten el medidor físico.
     *
     * Distribución:
     * - LOCALES 14-19: distribución equitativa (partes iguales).
     * - LOCALES 2-7-8-11: el cálculo es equitativo pero los montos reales
     *   se sobrescriben luego desde el JSON de ingresos (adaptaciones del
     *   administrador; LOC-11 paga menos que las demás).
     */
    private const GRUPOS = [
        [
            'nombre' => 'LOCALES 14-19',
            'observaciones' => 'Medidor compartido entre los locales 14, 15, 16, 17 y 19. Distribución equitativa.',
            'codigos' => ['LOC-14', 'LOC-15', 'LOC-16', 'LOC-17', 'LOC-19'],
        ],
        [
            'nombre' => 'LOCALES 2-7-8-11',
            'observaciones' => 'Medidor compartido entre los locales 2, 7, 8 y 11. El medidor físico está en LOC-2. Distribución equitativa del consumo; los montos finales se ajustan desde el JSON de ingresos (LOC-11 paga una cuota menor).',
            'codigos' => ['LOC-2', 'LOC-7', 'LOC-8', 'LOC-11'],
        ],
    ];

    public function run(): void
    {
        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('  MedidorGrupoLocalesSeeder');
        $this->command->info('========================================');

        $adminUserId = User::where('email', 'admin@admin.com')->first()?->id ?? 1;

        DB::beginTransaction();
        try {
            foreach (self::GRUPOS as $def) {
                $this->crearOActualizarGrupo($def, $adminUserId);
            }

            DB::commit();

            $this->command->info('');
            $this->command->info('  Resumen:');
            foreach (self::GRUPOS as $def) {
                $grupo = MedidorGrupo::where('nombre', $def['nombre'])->first();
                if ($grupo) {
                    $this->command->info("    - {$grupo->nombre} (id={$grupo->id}): {$grupo->unidades()->count()} unidades, 1/" . count($def['codigos']) . " c/u");
                }
            }
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

    /**
     * Crea (o actualiza) un grupo y asigna sus unidades.
     *
     * Si el grupo ya existe, no falla: reutiliza y asegura que todas las
     * unidades del catálogo estén asignadas activamente. Esto hace al
     * seeder idempotente (se puede correr varias veces sin romper).
     */
    private function crearOActualizarGrupo(array $def, int $adminUserId): void
    {
        $grupo = MedidorGrupo::firstOrCreate(
            ['nombre' => $def['nombre']],
            [
                'tipo_distribucion' => 'equitativa',
                'activo' => true,
                'observaciones' => $def['observaciones'],
                'creado_por' => $adminUserId,
            ]
        );

        if ($grupo->wasRecentlyCreated) {
            $this->command->info("  ✓ Grupo creado: {$grupo->nombre} (id={$grupo->id})");
        } else {
            $this->command->info("  → Grupo ya existía: {$grupo->nombre} (id={$grupo->id})");
            // Actualizar observación por si cambió
            $grupo->update(['observaciones' => $def['observaciones']]);
        }

        $unidades = Unidad::whereIn('codigo', $def['codigos'])->get()->keyBy('codigo');
        $faltantes = array_diff($def['codigos'], $unidades->keys()->toArray());

        if (!empty($faltantes)) {
            throw new \RuntimeException(
                'Unidades no encontradas en BD para grupo ' . $def['nombre'] . ': ' . implode(', ', $faltantes)
            );
        }

        foreach ($def['codigos'] as $codigo) {
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
                $this->command->info("    ✓ {$codigo} asignada al grupo");
            } else {
                $this->command->info("    → {$codigo} ya estaba asignada");
            }
        }
    }
}
