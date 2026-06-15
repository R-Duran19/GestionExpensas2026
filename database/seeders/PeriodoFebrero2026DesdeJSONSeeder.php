<?php

namespace Database\Seeders;

use App\Models\PeriodoExpensa;
use App\Models\FacturaAguaConfig;
use App\Models\FacturaAguaPeriodo;
use App\Models\LecturaMedidor;
use App\Models\Unidad;
use App\Models\Expensa;
use App\Models\Ingreso;
use App\Models\FactorProrrateo;
use App\Models\User;
use App\Services\ExpensaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PeriodoFebrero2026DesdeJSONSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('====================================================');
        $this->command->info('🚀 INICIANDO IMPORTACIÓN COMPLETA - FEBRERO 2026');
        $this->command->info('   (Leyendo datos desde JSON)');
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

            // 6. 🔒 CERRAR PERÍODO
            $this->cerrarPeriodo($periodo);

            // 7. 🔄 SOBRESCRIBIR EXPENSAS HISTÓRICAS DESDE JSON
            $this->sobrescribirExpensasHistoricasDesdeJSON($periodo);

            // 7.5. ✅ VALIDAR DEUDAS ARRASTRADAS VS JSON
            $this->validarDeudasArrastradas($periodo);

            // 8. 💳 IMPORTAR PAGOS HISTÓRICOS DESDE JSON
            $this->importarPagosDesdeJSON($periodo, $adminUserId);

            // 9. 🔧 RECALCULAR CAMPOS DERIVADOS Y TOTALES
            $this->recalcularDatosFinales($periodo);

            DB::commit();

            $this->command->info('====================================================');
            $this->command->info('✅ IMPORTACIÓN DE FEBRERO 2026 FINALIZADA CON ÉXITO');
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
     * 4. Importar lecturas de medidor desde JSON
     */
    private function importarLecturasDesdeJSON($periodo, $adminUserId)
    {
        $this->command->info('📏 Importando lecturas de medidor desde JSON...');

        $jsonPath = base_path('EXCELS/LECTURAS AGUA/FEBRERO2026.json');

        if (!file_exists($jsonPath)) {
            $this->command->error("  ✗ No se encontró archivo: {$jsonPath}");
            return;
        }

        $jsonData = json_decode(file_get_contents($jsonPath), true);
        $lecturas = $jsonData['lecturas'];

        $this->command->info("  ✓ Lecturas a importar: " . count($lecturas) . " unidades");

        $totalImportadas = 0;
        $unidadesNoEncontradas = [];

        foreach ($lecturas as $item) {
            $codigoUnidad = $item['codigo_unidad'];
            $codigoUnidad = $this->mapearCodigoUnidad($codigoUnidad);

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
     * 6. Cerrar período
     */
    private function cerrarPeriodo($periodo)
    {
        $this->command->info('🔒 Cerrando período...');

        $periodo->update([
            'estado' => 'cerrado',
        ]);

        $this->command->info("  ✓ Período cerrado correctamente");
    }

    /**
     * 7. Sobrescribir expensas históricas desde JSON
     */
    private function sobrescribirExpensasHistoricasDesdeJSON($periodo)
    {
        $this->command->info('🔄 Sobrescribiendo expensas históricas desde JSON...');

        $jsonPath = base_path('EXCELS/INGRESOS/INGRESOS-FEBRERO.json');

        if (!file_exists($jsonPath)) {
            $this->command->error("  ✗ No se encontró archivo: {$jsonPath}");
            return;
        }

        $jsonData = json_decode(file_get_contents($jsonPath), true);
        $expensasHistoricas = $jsonData['expensas'];

        $this->command->info("  ✓ Expensas históricas a procesar: " . count($expensasHistoricas) . " unidades");

        $totalSobrescritas = 0;

        foreach ($expensasHistoricas as $expensaData) {
            $codigoUnidad = $expensaData['codigo_unidad'];
            $codigoUnidad = $this->mapearCodigoUnidad($codigoUnidad);

            if ($codigoUnidad === null) continue;

            $unidad = Unidad::where('codigo', $codigoUnidad)->first();

            if ($unidad && $unidad->tiene_medidor) {
                // Calcular deuda_anterior basándonos en el JSON
                $deudaAcumuladaJson = $expensaData['deuda_acumulada'];
                $montoExpensaJson = $expensaData['monto_expensa'];
                $montoAguaJson = $expensaData['monto_agua'] ?? 0;
                $deudaAnteriorJson = $deudaAcumuladaJson - $montoExpensaJson - $montoAguaJson;

                Expensa::where('periodo_id', $periodo->id)
                      ->where('unidad_id', $unidad->id)
                      ->update([
                          'monto_expensa' => $montoExpensaJson,
                          'monto_agua' => $montoAguaJson,
                          'monto_total' => $deudaAcumuladaJson,
                          'deuda_anterior' => $deudaAnteriorJson,
                      ]);
                $totalSobrescritas++;
            }
        }

        $this->command->info("  ✓ Expensas históricas sobrescritas: {$totalSobrescritas} unidades");
    }

    /**
     * 8. Importar pagos históricos desde JSON
     */
    private function importarPagosDesdeJSON($periodo, $adminUserId)
    {
        $this->command->info('💳 Importando pagos históricos desde JSON...');

        $jsonPath = base_path('EXCELS/INGRESOS/INGRESOS-FEBRERO.json');

        if (!file_exists($jsonPath)) {
            $this->command->error("  ✗ No se encontró archivo: {$jsonPath}");
            return;
        }

        $jsonData = json_decode(file_get_contents($jsonPath), true);
        $expensasData = $jsonData['expensas'];

        $totalImportados = 0;
        $categoriaExpensas = \DB::table('categorias_ingresos')->where('nombre', 'Expensas')->first();

        foreach ($expensasData as $expensaData) {
            $codigoUnidad = $expensaData['codigo_unidad'];
            $codigoUnidad = $this->mapearCodigoUnidad($codigoUnidad);

            if ($codigoUnidad === null) continue;

            $unidad = Unidad::where('codigo', $codigoUnidad)->first();

            if ($unidad) {
                $expensa = Expensa::where('periodo_id', $periodo->id)
                                   ->where('unidad_id', $unidad->id)
                                   ->first();

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
                            'fecha' => '2026-02-15',
                            'categoria_ingreso_id' => $categoriaExpensas->id,
                            'importe' => $expensaData['monto_pagado'],
                            'metodo_pago' => 'efectivo',
                            'descripcion' => 'Pago de expensa ' . $periodo->mes . '-' . $periodo->año . ' - ' . $unidad->codigo,
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
                            'fecha_pago' => '2026-02-15',
                        ]);
                    }
                }
            }
        }

        $this->command->info("  ✓ Pagos históricos importados: {$totalImportados} registros");
    }

    /**
     * Mapear códigos de unidades del JSON a códigos de BD
     */
    private function mapearCodigoUnidad($codigoJson)
    {
        // Mapeos específicos para unidades con códigos diferentes
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

    /**
     * 5.5. Validar deudas arrastradas automáticamente vs datos del JSON
     */
    private function validarDeudasArrastradas($periodo)
    {
        $this->command->info('✅ Validando deudas arrastradas vs datos del JSON...');

        $jsonPath = base_path('EXCELS/INGRESOS/INGRESOS-FEBRERO.json');

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