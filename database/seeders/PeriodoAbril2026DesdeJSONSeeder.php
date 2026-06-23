<?php

namespace Database\Seeders;

use App\Models\PeriodoExpensa;
use App\Models\FacturaAguaConfig;
use App\Models\FacturaAguaPeriodo;
use App\Models\LecturaMedidor;
use App\Models\Unidad;
use App\Models\Expensa;
use App\Models\User;
use App\Services\ExpensaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PeriodoAbril2026DesdeJSONSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * SOLO GENERA EXPENSAS - SIN PAGOS
     */
    public function run(): void
    {
        $this->command->info('====================================================');
        $this->command->info('🚀 GENERANDO EXPENSAS - ABRIL 2026');
        $this->command->info('   (Arquitectura Descentralizada v2.0)');
        $this->command->info('   ⚠️  SOLO EXPENSAS - Pagos en seeder separado');
        $this->command->info('====================================================');

        DB::beginTransaction();

        try {
            $adminUserId = User::where('email', 'admin@admin.com')->first()?->id ?? 1;

            // 1. 📊 VERIFICAR FACTORES (mantienen los de marzo: 2.4/4.2)
            $this->verificarFactores();

            // 2. 🏢 CREAR PERÍODO
            $periodo = $this->crearPeriodo($adminUserId);

            // 3. 💧 REGISTRAR FACTURAS EPSAS
            $this->registrarFacturasAgua($periodo, $adminUserId);

            // 4. 📏 IMPORTAR LECTURAS DESDE JSON
            $this->importarLecturasDesdeJSON($periodo, $adminUserId);

            // 5. 💰 GENERAR EXPENSAS
            $this->generarExpensasCompletas($periodo);

            // 6. 🔄 SOBRESCRIBIR MONTOS DE EMISIÓN DESDE JSON
            $this->sobrescribirMontosEmisionDesdeJSON($periodo);

            // 7. 🔒 CERRAR PERÍODO
            $this->cerrarPeriodo($periodo);

            DB::commit();

            $this->command->info('====================================================');
            $this->command->info('✅ EXPENSAS DE ABRIL 2026 GENERADAS CON ÉXITO');
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
        $this->command->info('🏢 Creando período Abril 2026...');
        $periodo = PeriodoExpensa::updateOrCreate(
            ['mes' => 4, 'año' => 2026],
            [
                'fecha_apertura' => '2026-04-01',
                'fecha_cierre' => '2026-04-30',
                'estado' => 'abierto',
                'factor_domiciliario' => 4.484381779,
                'factor_comercial' => 25.821875,
                'factores_calculados_at' => now(),
                'factores_calculados_por' => $adminUserId,
            ]
        );
        $this->command->info("  ✓ Período creado (ID: {$periodo->id})");
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
            ['numero_factura' => '0B22TS000518', 'categoria' => 'domiciliaria', 'importe' => 2055.10, 'consumo_m3' => 458.00, 'fecha_factura' => '2026-05-01', 'registrado_por' => $adminUserId]
        );

        FacturaAguaPeriodo::updateOrCreate(
            ['periodo_id' => $periodo->id, 'factura_agua_config_id' => $configDom2->id],
            ['numero_factura' => '16498', 'categoria' => 'domiciliaria', 'importe' => 12.20, 'consumo_m3' => 3.00, 'fecha_factura' => '2026-05-01', 'registrado_por' => $adminUserId]
        );

        FacturaAguaPeriodo::updateOrCreate(
            ['periodo_id' => $periodo->id, 'factura_agua_config_id' => $configCom->id],
            ['numero_factura' => '00B16S002189', 'categoria' => 'comercial', 'importe' => 826.30, 'consumo_m3' => 32.00, 'fecha_factura' => '2026-05-01', 'registrado_por' => $adminUserId]
        );

        $this->command->info('  ✓ 3 facturas EPSAS registradas');
    }

    private function importarLecturasDesdeJSON($periodo, $adminUserId)
    {
        $this->command->info('📏 Importando lecturas desde JSON...');
        $jsonPath = base_path('EXCELS/LECTURAS AGUA/ABRIL2026.json');

        if (!file_exists($jsonPath)) {
            $this->command->error("  ✗ No se encontró: {$jsonPath}");
            return;
        }

        $jsonData = json_decode(file_get_contents($jsonPath), true);
        $lecturas = $jsonData['lecturas'];
        $totalImportadas = 0;

        foreach ($lecturas as $item) {
            $codigoUnidad = $this->mapearCodigoUnidad($item['codigo_unidad']);

            if ($codigoUnidad === null) continue;

            $unidad = Unidad::where('codigo', $codigoUnidad)->first();
            if ($unidad) {
                LecturaMedidor::updateOrCreate(
                    ['periodo_id' => $periodo->id, 'unidad_id' => $unidad->id],
                    [
                        'lectura_anterior' => $item['lectura_anterior'],
                        'lectura_actual' => $item['lectura_actual'],
                        'consumo_m3' => $item['consumo_m3'] ?? 0,
                        'medidor_grupo_id' => null,
                        'registrado_por' => $adminUserId,
                    ]
                );
                $totalImportadas++;
            }
        }

        $this->command->info("  ✓ Lecturas importadas: {$totalImportadas} unidades");
    }

    private function generarExpensasCompletas($periodo)
    {
        $this->command->info('💰 Generando expensas para TODAS las unidades...');
        $expensaService = app(ExpensaService::class);
        $resultados = $expensaService->generarExpensas($periodo->id);
        $this->command->info("  ✓ Expensas generadas: {$resultados['exitosas']} unidades");
    }

    /**
     * 6. Sobrescribir montos de emisión desde JSON
     * NOTA: Solo actualiza montos de emisión, NO saldos pendientes
     */
    private function sobrescribirMontosEmisionDesdeJSON($periodo)
    {
        $this->command->info('🔄 Sobrescribiendo montos de emisión desde JSON...');
        $jsonPath = base_path('EXCELS/INGRESOS/INGRESOS-ABRIL.json');

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
                // Actualizar montos de emisión para TODAS las unidades (con o sin medidor)
                $montoExpensaJson = $expensaData['monto_expensa'];
                $montoAguaJson = $expensaData['monto_agua'] ?? 0;
                $montoTotalJson = $montoExpensaJson + $montoAguaJson;

                $actualizado = Expensa::where('periodo_id', $periodo->id)
                              ->where('unidad_id', $unidad->id)
                              ->update([
                                  'monto_expensa' => $montoExpensaJson,
                                  'monto_agua' => $montoAguaJson,
                                  'monto_total' => $montoTotalJson,
                                  'expensa_pendiente' => $montoExpensaJson,
                                  'agua_pendiente' => $montoAguaJson,
                              ]);

                if ($actualizado > 0) {
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

    private function mapearCodigoUnidad($codigoJson)
    {
        $mapeosEspeciales = [
            'LOC-12-13' => 'LOC-12',
            'DEP-25-C' => 'DEP-25-C-T',
            'PARQ-128' => null,
            'PARQUEO-128' => null,
        ];

        if (isset($mapeosEspeciales[$codigoJson])) {
            return $mapeosEspeciales[$codigoJson];
        }

        return $codigoJson;
    }
}
