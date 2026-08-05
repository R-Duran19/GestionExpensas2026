<?php

namespace Database\Seeders;

use App\Models\PeriodoExpensa;
use App\Models\FacturaAguaConfig;
use App\Models\FacturaAguaPeriodo;
use App\Models\Unidad;
use App\Models\Expensa;
use App\Models\User;
use App\Services\ExpensaService;
use Database\Seeders\Concerns\ImportaLecturasConGrupos;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder para Junio 2026.
 *
 * Facturas EPSAS:
 *   Domiciliaria 1 (0B22TS000518): 2344.40 Bs, 487 m³
 *   Domiciliaria 2 (16498):           18.30 Bs,   2 m³
 *   Comercial      (00B16S002189):  1375.20 Bs,  62 m³
 *
 * Factores del período (calculados por EPSAS):
 *   Domiciliario: (2344.40 + 18.30) / (487 + 2) = 2362.70 / 489 = 4.831697342
 *   Comercial:    1375.20 / 62 = 22.18064516
 */
class PeriodoJunio2026DesdeJSONSeeder extends Seeder
{
    use ImportaLecturasConGrupos;

    public function run(): void
    {
        $this->command->info('====================================================');
        $this->command->info('🚀 GENERANDO EXPENSAS - JUNIO 2026');
        $this->command->info('   (Arquitectura Descentralizada v2.0)');
        $this->command->info('   ⚠️  SOLO EXPENSAS - Pagos en seeder separado');
        $this->command->info('====================================================');

        DB::beginTransaction();

        try {
            $adminUserId = User::where('email', 'admin@admin.com')->first()?->id ?? 1;

            // 1. Verificar factores (mismos de marzo: 2.4/4.2)
            $this->verificarFactores();

            // 2. Crear período
            $periodo = $this->crearPeriodo($adminUserId);

            // 3. Registrar facturas EPSAS
            $this->registrarFacturasAgua($periodo, $adminUserId);

            // 4. Importar lecturas desde JSON (con grupos)
            $this->importarLecturasDesdeJSON($periodo, $adminUserId);

            // 5. Generar expensas
            $this->generarExpensasCompletas($periodo);

            // 6. Sobrescribir montos de agua desde JSON
            $this->sobrescribirMontosAguaDesdeJSON($periodo);

            // 7. Cerrar período
            $this->cerrarPeriodo($periodo);

            DB::commit();

            $this->command->info('====================================================');
            $this->command->info('✅ EXPENSAS DE JUNIO 2026 GENERADAS CON ÉXITO');
            $this->command->info('ℹ️  Para importar pagos, ejecuta: PagosExpensasSeeder');
            $this->command->info('====================================================');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("✗ ERROR: " . $e->getMessage());
            throw $e;
        }
    }

    private function verificarFactores()
    {
        $this->command->info('📊 Verificando factores de prorrateo...');
        $factores = \App\Models\FactorProrrateo::where('vigencia_desde', '2026-03-01')->where('activo', true)->count();
        if ($factores >= 2) {
            $this->command->info('  ✓ Factores vigentes: Domiciliario 2.4, Comercial 4.2');
        } else {
            throw new \Exception("No se encuentran los factores vigentes. Ejecute primero PeriodoMarzo2026DesdeJSONSeeder");
        }
    }

    private function crearPeriodo($adminUserId)
    {
        $this->command->info('🏢 Creando período Junio 2026...');
        $periodo = PeriodoExpensa::updateOrCreate(
            ['mes' => 6, 'año' => 2026],
            [
                'fecha_apertura' => '2026-06-01',
                'fecha_cierre' => '2026-06-30',
                'estado' => 'abierto',
                'factor_domiciliario' => 4.831697342,
                'factor_comercial' => 22.18064516,
                'factores_calculados_at' => now(),
                'factores_calculados_por' => $adminUserId,
            ]
        );
        $this->command->info("  ✓ Período creado (ID: {$periodo->id})");
        $this->command->info("  ✓ Factores de agua: Domiciliario 4.83, Comercial 22.18");
        return $periodo;
    }

    private function registrarFacturasAgua($periodo, $adminUserId)
    {
        $this->command->info('💧 Registrando facturas EPSAS...');
        $configDom1 = FacturaAguaConfig::where('tipo', 'domiciliaria_1')->first();
        $configDom2 = FacturaAguaConfig::where('tipo', 'domiciliaria_2')->first();
        $configCom = FacturaAguaConfig::where('tipo', 'comercial')->first();

        if (!$configDom1 || !$configDom2 || !$configCom) {
            throw new \Exception("No se encontraron configuraciones de facturas EPSAS");
        }

        FacturaAguaPeriodo::updateOrCreate(
            ['periodo_id' => $periodo->id, 'factura_agua_config_id' => $configDom1->id],
            ['numero_factura' => '0B22TS000518', 'categoria' => 'domiciliaria', 'importe' => 2344.40, 'consumo_m3' => 487.00, 'fecha_factura' => '2026-07-01', 'registrado_por' => $adminUserId]
        );

        FacturaAguaPeriodo::updateOrCreate(
            ['periodo_id' => $periodo->id, 'factura_agua_config_id' => $configDom2->id],
            ['numero_factura' => '16498', 'categoria' => 'domiciliaria', 'importe' => 18.30, 'consumo_m3' => 2.00, 'fecha_factura' => '2026-07-01', 'registrado_por' => $adminUserId]
        );

        FacturaAguaPeriodo::updateOrCreate(
            ['periodo_id' => $periodo->id, 'factura_agua_config_id' => $configCom->id],
            ['numero_factura' => '00B16S002189', 'categoria' => 'comercial', 'importe' => 1375.20, 'consumo_m3' => 62.00, 'fecha_factura' => '2026-07-01', 'registrado_por' => $adminUserId]
        );

        $this->command->info('  ✓ 3 facturas EPSAS registradas');
    }

    private function importarLecturasDesdeJSON($periodo, $adminUserId)
    {
        $this->command->info('📏 Importando lecturas desde JSON (con grupos)...');
        $jsonPath = base_path('EXCELS/LECTURAS AGUA/JUNIO.json');

        $this->importarLecturasConGrupos(
            $periodo->id,
            $adminUserId,
            $jsonPath,
            $this->command
        );
    }

    private function generarExpensasCompletas($periodo)
    {
        $this->command->info('💰 Generando expensas para TODAS las unidades...');
        $expensaService = app(ExpensaService::class);
        $resultados = $expensaService->generarExpensas($periodo->id);
        $this->command->info("  ✓ Expensas generadas: {$resultados['exitosas']} unidades");
    }

    private function sobrescribirMontosAguaDesdeJSON($periodo)
    {
        $this->command->info('💧 Sobrescribiendo SOLO montos de AGUA desde JSON...');
        $jsonPath = base_path('EXCELS/INGRESOS/INGRESOS-JUNIO.json');

        if (!file_exists($jsonPath)) {
            $this->command->warn("  ⚠️ No se encontró archivo: {$jsonPath}");
            return;
        }

        $jsonData = json_decode(file_get_contents($jsonPath), true);
        $expensasHistoricas = $jsonData['expensas'];

        $this->command->info("  ✓ Montos a procesar: " . count($expensasHistoricas) . " unidades");

        $totalSobrescritas = 0;
        $unidadesNoEncontradas = [];

        foreach ($expensasHistoricas as $expensaData) {
            $codigoUnidad = $this->mapearCodigoUnidad($expensaData['codigo_unidad']);

            if ($codigoUnidad === null) continue;

            $unidad = Unidad::where('codigo', $codigoUnidad)->first();

            if ($unidad) {
                $montoAguaJson = $expensaData['monto_agua'] ?? 0;

                $expensa = Expensa::where('periodo_id', $periodo->id)
                          ->where('unidad_id', $unidad->id)
                          ->first();

                if ($expensa) {
                    $montoTotal = $expensa->monto_expensa + $montoAguaJson;

                    $expensa->update([
                        'monto_agua' => $montoAguaJson,
                        'monto_total' => $montoTotal,
                        'agua_pendiente' => $montoAguaJson,
                    ]);

                    $totalSobrescritas++;
                }
            } else {
                $unidadesNoEncontradas[] = $codigoUnidad;
            }
        }

        if (!empty($unidadesNoEncontradas)) {
            $this->command->warn("  ⚠️ Unidades no encontradas: " . implode(', ', array_unique($unidadesNoEncontradas)));
        }

        $this->command->info("  ✓ Montos de emisión sobrescritos: {$totalSobrescritas} unidades");
    }

    private function cerrarPeriodo($periodo)
    {
        $this->command->info('🔒 Cerrando período...');
        Expensa::where('periodo_id', $periodo->id)->update(['periodo_cerrado' => true]);
        $periodo->update(['estado' => 'cerrado']);
        $this->command->info("  ✓ Período cerrado correctamente");
    }
}
