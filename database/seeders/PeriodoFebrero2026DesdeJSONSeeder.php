<?php

namespace Database\Seeders;

use App\Models\PeriodoExpensa;
use App\Models\FacturaAguaConfig;
use App\Models\FacturaAguaPeriodo;
use App\Models\Expensa;
use App\Models\FactorProrrateo;
use App\Models\Unidad;
use App\Models\User;
use App\Services\ExpensaService;
use Database\Seeders\Concerns\ImportaLecturasConGrupos;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PeriodoFebrero2026DesdeJSONSeeder extends Seeder
{
    use ImportaLecturasConGrupos;

    /**
     * Run the database seeds.
     * SOLO GENERA EXPENSAS - SIN PAGOS
     */
    public function run(): void
    {
        $this->command->info('====================================================');
        $this->command->info('🚀 GENERANDO EXPENSAS - FEBRERO 2026');
        $this->command->info('   (Arquitectura Descentralizada v2.0)');
        $this->command->info('   ⚠️  SOLO EXPENSAS - Pagos en seeder separado');
        $this->command->info('====================================================');

        DB::beginTransaction();

        try {
            $adminUserId = User::where('email', 'admin@admin.com')->first()?->id ?? 1;

            // 1. 📊 VERIFICAR FACTORES DE PRORRATEO (ya existen de enero)
            $this->verificarFactoresProrrateo();

            // 2. 🏢 CREAR PERÍODO CON FACTORES DE AGUA
            $periodo = $this->crearPeriodo($adminUserId);

            // 3. 💧 REGISTRAR FACTURAS EPSAS
            $this->registrarFacturasAgua($periodo, $adminUserId);

            // 4. 📏 IMPORTAR LECTURAS DE MEDIDOR DESDE JSON
            $this->importarLecturasDesdeJSON($periodo, $adminUserId);

            // 5. 💰 GENERAR EXPENSAS PARA TODAS LAS UNIDADES
            $this->generarExpensasCompletas($periodo);

            // 6. 💧 SOBRESCRIBIR SOLO MONTOS DE AGUA DESDE JSON (NO toca monto_expensa del sistema)
            $this->sobrescribirMontosAguaDesdeJSON($periodo);

            // 7. 🔒 CERRAR PERÍODO
            $this->cerrarPeriodo($periodo);

            DB::commit();

            $this->command->info('====================================================');
            $this->command->info('✅ EXPENSAS DE FEBRERO 2026 GENERADAS CON ÉXITO');
            $this->command->info('ℹ️  Para importar pagos, ejecuta: PagosExpensasSeeder');
            $this->command->info('====================================================');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("✗ ERROR FATAL AL IMPORTAR: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 1. Verificar factores de prorrateo (ya existen de enero)
     */
    private function verificarFactoresProrrateo()
    {
        $this->command->info('📊 Verificando factores de prorrateo...');

        $factoresExist = FactorProrrateo::where('vigencia_desde', '2026-01-01')
                                      ->where('activo', true)
                                      ->count();

        if ($factoresExist >= 2) {
            $this->command->info('  ✓ Factores vigentes: Domiciliario 2.1, Comercial 3.5');
        } else {
            throw new \Exception("No se encuentran los factores de prorrateo vigentes. Ejecute primero PeriodoEnero2026DesdeJSONSeeder");
        }
    }

    /**
     * 2. Crear período con factores de agua
     */
    private function crearPeriodo($adminUserId)
    {
        $this->command->info('🏢 Creando período de Febrero 2026...');

        $periodo = PeriodoExpensa::updateOrCreate(
            [
                'mes' => 2,
                'año' => 2026,
            ],
            [
                'fecha_apertura' => '2026-02-01',
                'fecha_cierre' => '2026-02-28',
                'estado' => 'abierto',
                'factor_domiciliario' => 4.931670282,
                'factor_comercial' => 22.428125,
                'factores_calculados_at' => now(),
                'factores_calculados_por' => $adminUserId,
            ]
        );

        $this->command->info("  ✓ Período creado (ID: {$periodo->id})");
        $this->command->info("  ✓ Factores de agua: Domiciliario 4.93, Comercial 22.43");

        return $periodo;
    }

    /**
     * 3. Registrar facturas EPSAS
     */
    private function registrarFacturasAgua($periodo, $adminUserId)
    {
        $this->command->info('💧 Registrando facturas EPSAS...');

        $configDom1 = FacturaAguaConfig::where('tipo', 'domiciliaria_1')->first();
        $configDom2 = FacturaAguaConfig::where('tipo', 'domiciliaria_2')->first();
        $configCom = FacturaAguaConfig::where('tipo', 'comercial')->first();

        if (!$configDom1 || !$configDom2 || !$configCom) {
            throw new \Exception("No se encontraron todas las configuraciones de facturas EPSAS");
        }

        // Factura Domiciliaria 1 (principal)
        FacturaAguaPeriodo::updateOrCreate(
            [
                'periodo_id' => $periodo->id,
                'factura_agua_config_id' => $configDom1->id,
            ],
            [
                'numero_factura' => '0B22TS000518',
                'categoria' => 'domiciliaria',
                'importe' => 2255.80,
                'consumo_m3' => 458.00,
                'fecha_factura' => '2026-03-01',
                'registrado_por' => $adminUserId,
            ]
        );

        // Factura Domiciliaria 2
        FacturaAguaPeriodo::updateOrCreate(
            [
                'periodo_id' => $periodo->id,
                'factura_agua_config_id' => $configDom2->id,
            ],
            [
                'numero_factura' => '16498',
                'categoria' => 'domiciliaria',
                'importe' => 17.70,
                'consumo_m3' => 3.00,
                'fecha_factura' => '2026-03-01',
                'registrado_por' => $adminUserId,
            ]
        );

        // Factura Comercial
        FacturaAguaPeriodo::updateOrCreate(
            [
                'periodo_id' => $periodo->id,
                'factura_agua_config_id' => $configCom->id,
            ],
            [
                'numero_factura' => '00B16S002189',
                'categoria' => 'comercial',
                'importe' => 717.70,
                'consumo_m3' => 32.00,
                'fecha_factura' => '2026-03-01',
                'registrado_por' => $adminUserId,
            ]
        );

        $this->command->info('  ✓ 3 facturas EPSAS registradas');
    }

    /**
     * 4. Importar lecturas de medidor desde JSON (reconoce grupos compartidos)
     */
    private function importarLecturasDesdeJSON($periodo, $adminUserId)
    {
        $this->command->info('📏 Importando lecturas de medidor desde JSON (con grupos)...');

        $jsonPath = base_path('EXCELS/LECTURAS AGUA/FEBRERO2026.json');

        $this->importarLecturasConGrupos(
            $periodo->id,
            $adminUserId,
            $jsonPath,
            $this->command
        );
    }

    /**
     * 5. Generar expensas para TODAS las unidades
     */
    private function generarExpensasCompletas($periodo)
    {
        $this->command->info('💰 Generando expensas para TODAS las unidades...');

        $expensaService = app(ExpensaService::class);
        $resultados = $expensaService->generarExpensas($periodo->id);

        $this->command->info("  ✓ Expensas generadas: {$resultados['exitosas']} unidades");

        if (!empty($resultados['errores'])) {
            $this->command->warn("  ⚠️ Errores: " . count($resultados['errores']));
        }
    }

    /**
     * 6. Sobrescribir SOLO montos de AGUA desde JSON
     * NOTA: NO modifica monto_expensa ni expensa_pendiente (los deja como los calculó el sistema)
     */
    private function sobrescribirMontosAguaDesdeJSON($periodo)
    {
        $this->command->info('💧 Sobrescribiendo SOLO montos de AGUA desde JSON...');

        $jsonPath = base_path('EXCELS/INGRESOS/INGRESOS-FEBRERO.json');

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
                // SOLO sobrescribir montos de AGUA desde JSON
                // NO tocar monto_expensa ni expensa_pendiente (quedan como los calculó el sistema)
                $montoAguaJson = $expensaData['monto_agua'] ?? 0;

                $expensa = Expensa::where('periodo_id', $periodo->id)
                          ->where('unidad_id', $unidad->id)
                          ->first();

                if ($expensa) {
                    // Recalcular monto_total conservando el monto_expensa calculado por el sistema
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

    /**
     * 7. Cerrar período
     */
    private function cerrarPeriodo($periodo)
    {
        $this->command->info('🔒 Cerrando período...');

        Expensa::where('periodo_id', $periodo->id)
            ->update(['periodo_cerrado' => true]);

        $periodo->update([
            'estado' => 'cerrado',
        ]);

        $this->command->info("  ✓ Período cerrado correctamente");
    }
}
