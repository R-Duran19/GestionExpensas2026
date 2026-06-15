<?php

namespace Database\Seeders;

use App\Models\PeriodoExpensa;
use App\Models\FacturaAguaConfig;
use App\Models\FacturaAguaPeriodo;
use App\Models\LecturaMedidor;
use App\Models\Unidad;
use App\Models\Expensa;
use App\Models\Ingreso;
use App\Models\User;
use App\Services\ExpensaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PeriodoMayo2026DesdeJSONSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('====================================================');
        $this->command->info('🚀 INICIANDO IMPORTACIÓN COMPLETA - MAYO 2026');
        $this->command->info('   (Leyendo datos desde JSON)');
        $this->command->info('====================================================');

        DB::beginTransaction();

        try {
            $adminUserId = User::where('email', 'admin@admin.com')->first()?->id ?? 1;

            // 1. 📊 VERIFICAR FACTORES (mantienen los de marzo: 2.4/4.2)
            $this->command->info('📊 Verificando factores de prorrateo...');
            $factores = \App\Models\FactorProrrateo::where('vigencia_desde', '2026-03-01')->where('activo', true)->count();
            if ($factores >= 2) {
                $this->command->info('  ✓ Factores vigentes: Domiciliario 2.4, Comercial 4.2');
            } else {
                throw new \Exception("No se encuentran los factores vigentes. Ejecute primero PeriodoMarzo2026DesdeJSONSeeder");
            }

            // 2. 🏢 CREAR PERÍODO
            $periodo = $this->crearPeriodo($adminUserId);

            // 3. 💧 REGISTRAR FACTURAS EPSAS
            $this->registrarFacturasAgua($periodo, $adminUserId);

            // 4. 📏 IMPORTAR LECTURAS DESDE JSON
            $this->importarLecturasDesdeJSON($periodo, $adminUserId);

            // 5. 💰 GENERAR EXPENSAS
            $this->generarExpensasCompletas($periodo);

            // 6. 🔒 CERRAR PERÍODO
            $periodo->update(['estado' => 'cerrado']);

            // 7. 🔄 SOBRESCRIBIR EXPENSAS DESDE JSON
            $this->sobrescribirExpensasDesdeJSON($periodo);

            // 7.5. ✅ VALIDAR DEUDAS ARRASTRADAS VS JSON
            $this->validarDeudasArrastradas($periodo);

            // 8. 💳 IMPORTAR PAGOS DESDE JSON
            $this->importarPagosDesdeJSON($periodo, $adminUserId);

            // 9. 🔧 RECALCULAR CAMPOS DERIVADOS Y TOTALES
            $this->recalcularDatosFinales($periodo);

            DB::commit();

            $this->command->info('====================================================');
            $this->command->info('✅ IMPORTACIÓN DE MAYO 2026 FINALIZADA');
            $this->command->info('====================================================');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("✗ ERROR: " . $e->getMessage());
            throw $e;
        }
    }

    private function crearPeriodo($adminUserId)
    {
        $this->command->info('🏢 Creando período Mayo 2026...');
        $periodo = PeriodoExpensa::updateOrCreate(
            ['mes' => 5, 'año' => 2026],
            [
                'fecha_apertura' => '2026-05-01',
                'fecha_cierre' => '2026-05-31',
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
        $configDom1 = \App\Models\FacturaAguaConfig::where('tipo', 'domiciliaria_1')->first();
        $configDom2 = \App\Models\FacturaAguaConfig::where('tipo', 'domiciliaria_2')->first();
        $configCom = \App\Models\FacturaAguaConfig::where('tipo', 'comercial')->first();

        if (!$configDom1 || !$configDom2 || !$configCom) {
            throw new \Exception("No se encontraron configuraciones de facturas EPSAS");
        }

        \App\Models\FacturaAguaPeriodo::updateOrCreate(
            ['periodo_id' => $periodo->id, 'factura_agua_config_id' => $configDom1->id],
            ['numero_factura' => '0B22TS000518', 'categoria' => 'domiciliaria', 'importe' => 2055.10, 'consumo_m3' => 458.00, 'fecha_factura' => '2026-06-01', 'registrado_por' => $adminUserId]
        );

        \App\Models\FacturaAguaPeriodo::updateOrCreate(
            ['periodo_id' => $periodo->id, 'factura_agua_config_id' => $configDom2->id],
            ['numero_factura' => '16498', 'categoria' => 'domiciliaria', 'importe' => 12.20, 'consumo_m3' => 3.00, 'fecha_factura' => '2026-06-01', 'registrado_por' => $adminUserId]
        );

        \App\Models\FacturaAguaPeriodo::updateOrCreate(
            ['periodo_id' => $periodo->id, 'factura_agua_config_id' => $configCom->id],
            ['numero_factura' => '00B16S002189', 'categoria' => 'comercial', 'importe' => 826.30, 'consumo_m3' => 32.00, 'fecha_factura' => '2026-06-01', 'registrado_por' => $adminUserId]
        );

        $this->command->info('  ✓ 3 facturas EPSAS registradas');
    }

    private function importarLecturasDesdeJSON($periodo, $adminUserId)
    {
        $this->command->info('📏 Importando lecturas desde JSON...');
        $jsonPath = base_path('EXCELS/LECTURAS AGUA/MAYO2026.json');

        if (!file_exists($jsonPath)) {
            $this->command->error("  ✗ No se encontró: {$jsonPath}");
            return;
        }

        $jsonData = json_decode(file_get_contents($jsonPath), true);
        $totalImportadas = 0;

        foreach ($jsonData['lecturas'] as $item) {
            $codigoUnidad = $item['codigo_unidad'];
            $codigoUnidad = $this->mapearCodigoUnidad($codigoUnidad);

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
        $this->command->info('💰 Generando expensas...');
        $expensaService = app(ExpensaService::class);
        $resultados = $expensaService->generarExpensas($periodo->id);
        $this->command->info("  ✓ Expensas generadas: {$resultados['exitosas']} unidades");
    }

    private function sobrescribirExpensasDesdeJSON($periodo)
    {
        $this->command->info('🔄 Sobrescribiendo expensas desde JSON...');
        $jsonPath = base_path('EXCELS/INGRESOS/INGRESOS-MAYO.json');

        if (!file_exists($jsonPath)) {
            $this->command->error("  ✗ No se encontró: {$jsonPath}");
            return;
        }

        $jsonData = json_decode(file_get_contents($jsonPath), true);
        $totalSobrescritas = 0;
        $unidadesSinExpensa = [];
        $unidadesConMontoCero = [];

        foreach ($jsonData['expensas'] as $expensaData) {
            $codigoUnidad = $expensaData['codigo_unidad'];
            $codigoUnidad = $this->mapearCodigoUnidad($codigoUnidad);

            if ($codigoUnidad === null) continue;

            $unidad = Unidad::where('codigo', $codigoUnidad)->first();
            if ($unidad && $unidad->tiene_medidor) {
                // Verificar si monto_expensa es null o 0
                if ($expensaData['monto_expensa'] === null || $expensaData['monto_expensa'] === '') {
                    $unidadesSinExpensa[] = [
                        'codigo' => $codigoUnidad,
                        'id' => $unidad->id,
                        'razon' => 'monto_expensa es null o vacío',
                    ];
                    $this->command->warn("  ⚠️ Unidad {$codigoUnidad} (ID: {$unidad->id}) sin monto_expensa - OMITIDA");
                    continue;
                }

                // Calcular deuda_anterior basándonos en el JSON
                $deudaAcumuladaJson = $expensaData['deuda_acumulada'] ?? 0;
                $montoExpensaJson = floatval($expensaData['monto_expensa']);
                $montoAguaJson = floatval($expensaData['monto_agua'] ?? 0);
                $deudaAnteriorJson = $deudaAcumuladaJson - $montoExpensaJson - $montoAguaJson;

                // Verificar si el monto es 0
                if ($montoExpensaJson == 0) {
                    $unidadesConMontoCero[] = [
                        'codigo' => $codigoUnidad,
                        'id' => $unidad->id,
                        'monto_expensa' => $montoExpensaJson,
                        'monto_agua' => $montoAguaJson,
                        'deuda_acumulada' => $deudaAcumuladaJson,
                    ];
                    $this->command->warn("  ⚠️ Unidad {$codigoUnidad} con monto_expensa = 0 - OMITIDA");
                    continue;
                }

                Expensa::where('periodo_id', $periodo->id)->where('unidad_id', $unidad->id)->update([
                    'monto_expensa' => $montoExpensaJson,
                    'monto_agua' => $montoAguaJson,
                    'monto_total' => $deudaAcumuladaJson,
                    'deuda_anterior' => $deudaAnteriorJson,
                ]);
                $totalSobrescritas++;
            }
        }

        $this->command->info("  ✓ Expensas sobrescritas: {$totalSobrescritas} unidades");

        // Reporte final de unidades omitidas
        if (!empty($unidadesSinExpensa)) {
            $this->command->warn("  ⚠️ Unidades sin expensa (null/vacío): " . count($unidadesSinExpensa));
            foreach ($unidadesSinExpensa as $unidad) {
                $this->command->warn("    - {$unidad['codigo']} (ID: {$unidad['id']}): {$unidad['razon']}");
            }
        }

        if (!empty($unidadesConMontoCero)) {
            $this->command->warn("  ⚠️ Unidades con monto_expensa = 0: " . count($unidadesConMontoCero));
            foreach ($unidadesConMontoCero as $unidad) {
                $this->command->warn("    - {$unidad['codigo']} (ID: {$unidad['id']}): Expensa={$unidad['monto_expensa']}, Agua={$unidad['monto_agua']}, Total={$unidad['deuda_acumulada']}");
            }
        }

        if (!empty($unidadesSinExpensa) || !empty($unidadesConMontoCero)) {
            $this->command->warn("  ⚠️ TOTAL DE UNIDADES OMITIDAS: " . (count($unidadesSinExpensa) + count($unidadesConMontoCero)));
            $this->command->warn("  💡 Estas unidades necesitan revisión manual en el JSON de Mayo");
        }
    }

    private function importarPagosDesdeJSON($periodo, $adminUserId)
    {
        $this->command->info('💳 Importando pagos desde JSON...');
        $jsonPath = base_path('EXCELS/INGRESOS/INGRESOS-MAYO.json');

        if (!file_exists($jsonPath)) {
            $this->command->error("  ✗ No se encontró: {$jsonPath}");
            return;
        }

        $jsonData = json_decode(file_get_contents($jsonPath), true);
        $totalImportados = 0;
        $categoriaExpensas = \DB::table('categorias_ingresos')->where('nombre', 'Expensas')->first();

        foreach ($jsonData['expensas'] as $expensaData) {
            $codigoUnidad = $expensaData['codigo_unidad'];
            $codigoUnidad = $this->mapearCodigoUnidad($codigoUnidad);

            if ($codigoUnidad === null) continue;

            $unidad = Unidad::where('codigo', $codigoUnidad)->first();
            if ($unidad) {
                $expensa = Expensa::where('periodo_id', $periodo->id)->where('unidad_id', $unidad->id)->first();
                if ($expensa && $expensaData['estado_pago'] === 'pagado') {
                    // Generar número de recibo único
                    $numeroRecibo = $expensaData['recibo'] ?? 'N/A';
                    if ($numeroRecibo === 'N/A' || $numeroRecibo === null) {
                        $numeroRecibo = 'N/A-' . $unidad->codigo . '-' . $periodo->mes . '-' . $periodo->año;
                    }

                    // Verificar si ya existe un ingreso con este número de recibo (en toda la BD)
                    $ingresoExistente = Ingreso::where('numero_recibo', $numeroRecibo)->first();

                    if (!$ingresoExistente) {
                        Ingreso::create([
                            'numero_recibo' => $numeroRecibo,
                            'fecha' => '2026-05-15',
                            'categoria_ingreso_id' => $categoriaExpensas->id,
                            'importe' => $expensaData['monto_pagado'],
                            'metodo_pago' => 'efectivo',
                            'descripcion' => 'Pago expensa ' . $periodo->mes . '-' . $periodo->año . ' - ' . $unidad->codigo,
                            'unidad_id' => $unidad->id,
                            'expensa_id' => $expensa->id,
                            'referencia_bancaria' => $expensaData['recibo'] ?? 'N/A',
                            'activo' => true,
                            'creado_por' => $adminUserId,
                        ]);

                        $totalImportados++;
                    }

                    // Solo actualizar expensa si no está pagada
                    if ($expensa->estado !== 'pagado') {
                        $expensa->update([
                            'estado' => 'pagado',
                            'monto_pagado' => $expensaData['monto_pagado'],
                            'monto_pendiente' => 0,
                            'fecha_pago' => '2026-05-15',
                        ]);
                    }
                }
            }
        }

        $this->command->info("  ✓ Pagos importados: {$totalImportados} registros");
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

        if (in_array($codigoJson, ['PARQ-128', 'PARQUEO-128'])) {
            return null;
        }

        return $codigoJson;
    }

    private function validarDeudasArrastradas($periodo)
    {
        $this->command->info('✅ Validando deudas arrastradas vs datos del JSON...');

        $jsonPath = base_path('EXCELS/INGRESOS/INGRESOS-MAYO.json');

        if (!file_exists($jsonPath)) {
            $this->command->warn("  ⚠️ No se encontró JSON para validar deudas");
            return;
        }

        $jsonData = json_decode(file_get_contents($jsonPath), true);
        $expensasJson = $jsonData['expensas'];

        $validaciones = [];

        foreach ($expensasJson as $expensaData) {
            $codigoUnidad = $expensaData['codigo_unidad'];
            $codigoUnidad = $this->mapearCodigoUnidad($codigoUnidad);

            if ($codigoUnidad === null) continue;

            $unidad = Unidad::where('codigo', $codigoUnidad)->first();
            if (!$unidad) continue;

            $expensa = Expensa::where('periodo_id', $periodo->id)
                              ->where('unidad_id', $unidad->id)
                              ->first();

            if ($expensa) {
                // Comparar deuda_anterior (sistema) vs deuda_anterior calculada del JSON
                $deudaAnteriorSistema = floatval($expensa->deuda_anterior);
                $deudaAcumuladaJson = floatval($expensaData['deuda_acumulada'] ?? 0);
                $montoExpensaJson = floatval($expensaData['monto_expensa']);
                $montoAguaJson = floatval($expensaData['monto_agua'] ?? 0);
                $deudaAnteriorJson = $deudaAcumuladaJson - $montoExpensaJson - $montoAguaJson;

                $diferencia = abs($deudaAnteriorSistema - $deudaAnteriorJson);

                if ($diferencia > 1.0) {
                    $validaciones[] = [
                        'unidad' => $codigoUnidad,
                        'sistema' => $deudaAnteriorSistema,
                        'json' => $deudaAnteriorJson,
                        'diferencia' => $diferencia,
                    ];
                }
            }
        }

        if (!empty($validaciones)) {
            $this->command->warn("  ⚠️ Se encontraron " . count($validaciones) . " unidades con diferencias de deuda:");
            foreach ($validaciones as $val) {
                $this->command->warn("    - {$val['unidad']}: Sistema {$val['sistema']} vs JSON {$val['json']} (diff: {$val['diferencia']} Bs)");
            }
        } else {
            $this->command->info("  ✓ Todas las deudas coinciden correctamente");
        }
    }

    /**
     * 9. Recalcular campos derivados y totales
     */
    private function recalcularDatosFinales($periodo)
    {
        $this->command->info('🔧 Recalculando campos derivados y totales...');

        $recalculoService = new \App\Services\ExpensaRecalculoService();
        $resultados = $recalculoService->recalcularPeriodo($periodo->id);

        $this->command->info("  ✓ Expensas individuales recalculadas: {$resultados['expensas_recalculadas']}");
        $this->command->info("  ✓ Expensas totales por propietario: {$resultados['totales_recalculados']}");
    }
}
