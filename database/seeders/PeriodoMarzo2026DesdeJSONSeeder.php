<?php

namespace Database\Seeders;

use App\Models\PeriodoExpensa;
use App\Models\FacturaAguaConfig;
use App\Models\FacturaAguaPeriodo;
use App\Models\LecturaMedidor;
use App\Models\Unidad;
use App\Models\Expensa;
use App\Models\FactorProrrateo;
use App\Models\User;
use App\Services\ExpensaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PeriodoMarzo2026DesdeJSONSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * SOLO GENERA EXPENSAS - SIN PAGOS
     */
    public function run(): void
    {
        $this->command->info('====================================================');
        $this->command->info('🚀 GENERANDO EXPENSAS - MARZO 2026');
        $this->command->info('   ⚠️  CAMBIO DE FACTORES: 2.1/3.5 → 2.4/4.2');
        $this->command->info('   (Arquitectura Descentralizada v2.0)');
        $this->command->info('   ⚠️  SOLO EXPENSAS - Pagos en seeder separado');
        $this->command->info('====================================================');

        DB::beginTransaction();

        try {
            $adminUserId = User::where('email', 'admin@admin.com')->first()?->id ?? 1;

            // 1. 📊 ACTUALIZAR FACTORES DE PRORRATEO (cambio a 2.4/4.2)
            $this->actualizarFactoresProrrateo($adminUserId);

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
            $this->command->info('✅ EXPENSAS DE MARZO 2026 GENERADAS CON ÉXITO');
            $this->command->info('ℹ️  Para importar pagos, ejecuta: PagosExpensasSeeder');
            $this->command->info('====================================================');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("✗ ERROR FATAL AL IMPORTAR: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 1. Actualizar factores de prorrateo (cambio a 2.4/4.2)
     */
    private function actualizarFactoresProrrateo($adminUserId)
    {
        $this->command->info('📊 Actualizando factores de prorrateo para Marzo 2026...');
        $this->command->info('  ⚠️  CAMBIO DE FACTORES: 2.1/3.5 → 2.4/4.2');

        // Desactivar factores anteriores
        FactorProrrateo::where('activo', true)->update(['activo' => false]);

        // Crear nuevos factores para Marzo-Mayo 2026
        FactorProrrateo::create([
            'categoria' => 'domiciliaria',
            'factor' => 2.4,
            'vigencia_desde' => '2026-03-01',
            'activo' => true,
            'creado_por' => $adminUserId,
        ]);

        FactorProrrateo::create([
            'categoria' => 'comercial',
            'factor' => 4.2,
            'vigencia_desde' => '2026-03-01',
            'activo' => true,
            'creado_por' => $adminUserId,
        ]);

        $this->command->info('  ✓ Nuevos factores: Domiciliario 2.4, Comercial 4.2');
    }

    /**
     * 2. Crear período con factores de agua
     */
    private function crearPeriodo($adminUserId)
    {
        $this->command->info('🏢 Creando período de Marzo 2026...');

        $periodo = PeriodoExpensa::updateOrCreate(
            [
                'mes' => 3,
                'año' => 2026,
            ],
            [
                'fecha_apertura' => '2026-03-01',
                'fecha_cierre' => '2026-03-31',
                'estado' => 'abierto',
                'factor_domiciliario' => 4.628131021,
                'factor_comercial' => 20.35,
                'factores_calculados_at' => now(),
                'factores_calculados_por' => $adminUserId,
            ]
        );

        $this->command->info("  ✓ Período creado (ID: {$periodo->id})");
        $this->command->info("  ✓ Factores de agua: Domiciliario 4.63, Comercial 20.35");

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
                'importe' => 2389.60,
                'consumo_m3' => 516.00,
                'fecha_factura' => '2026-04-01',
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
                'importe' => 12.40,
                'consumo_m3' => 3.00,
                'fecha_factura' => '2026-04-01',
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
                'importe' => 976.80,
                'consumo_m3' => 48.00,
                'fecha_factura' => '2026-04-01',
                'registrado_por' => $adminUserId,
            ]
        );

        $this->command->info('  ✓ 3 facturas EPSAS registradas');
    }

    /**
     * 4. Importar lecturas de medidor desde JSON
     */
    private function importarLecturasDesdeJSON($periodo, $adminUserId)
    {
        $this->command->info('📏 Importando lecturas de medidor desde JSON...');

        $jsonPath = base_path('EXCELS/LECTURAS AGUA/MARZO2026.json');

        if (!file_exists($jsonPath)) {
            $this->command->warn("  ⚠️ No se encontró archivo: {$jsonPath}");
            return;
        }

        $jsonData = json_decode(file_get_contents($jsonPath), true);
        $lecturas = $jsonData['lecturas'];

        $this->command->info("  ✓ Lecturas a importar: " . count($lecturas) . " unidades");

        $totalImportadas = 0;
        $unidadesNoEncontradas = [];

        foreach ($lecturas as $item) {
            $codigoUnidad = $this->mapearCodigoUnidad($item['codigo_unidad']);

            if ($codigoUnidad === null) continue;

            $unidad = Unidad::where('codigo', $codigoUnidad)->first();

            if ($unidad) {
                LecturaMedidor::updateOrCreate(
                    [
                        'periodo_id' => $periodo->id,
                        'unidad_id' => $unidad->id,
                    ],
                    [
                        'lectura_anterior' => $item['lectura_anterior'],
                        'lectura_actual' => $item['lectura_actual'],
                        'consumo_m3' => $item['consumo_m3'] ?? 0,
                        'medidor_grupo_id' => null,
                        'registrado_por' => $adminUserId,
                    ]
                );
                $totalImportadas++;
            } else {
                $unidadesNoEncontradas[] = $codigoUnidad;
            }
        }

        if (!empty($unidadesNoEncontradas)) {
            $this->command->warn("  ⚠️ Unidades no encontradas: " . implode(', ', array_unique($unidadesNoEncontradas)));
        }

        $this->command->info("  ✓ Lecturas importadas: {$totalImportadas} unidades");
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

        $jsonPath = base_path('EXCELS/INGRESOS/INGRESOS-MARZO.json');

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

    /**
     * Mapear códigos de unidades del JSON a códigos de BD
     */
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
