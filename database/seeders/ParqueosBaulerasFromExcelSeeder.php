<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use App\Enums\TipoUnidad;

class ParqueosBaulerasFromExcelSeeder extends BaseFromExcelSeeder
{
    public function run(): void
    {
        $this->console->writeln("<info>========================================</info>");
        $this->console->writeln("<info>  ParqueosBaulerasFromExcelSeeder  </info>");
        $this->console->writeln("<info>========================================</info>");
        $this->console->writeln("");

        DB::beginTransaction();
        try {
            $this->procesarExcel();
            DB::commit();
            $this->imprimirResumen();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->console->writeln("");
            $this->console->writeln("<error>✗ Error fatal:</error> {$e->getMessage()}");
            throw $e;
        }
    }

    protected function procesarFila($worksheet, int $row, array $columnas): void
    {
        $tipoUnidadStr = trim($worksheet->getCell($columnas['unidad_funcional'] . $row)->getValue() ?? '');
        $numero = trim($worksheet->getCell($columnas['numero'] . $row)->getValue() ?? '');
        $propietarioNombre = trim($worksheet->getCell($columnas['propietario'] . $row)->getValue() ?? '');

        if (empty($numero) || empty($propietarioNombre)) {
            $this->stats['filas_omitidas']++;
            return;
        }

        if (in_array(strtolower($tipoUnidadStr), ['total m2', 'total bolivianos al mes', 'total', 'subtotal'])) {
            $this->stats['filas_omitidas']++;
            return;
        }

        $tipoNormalizado = strtolower($tipoUnidadStr);



        $esParqueoPublico = (str_contains($tipoNormalizado, 'publico') || str_contains($tipoNormalizado, 'público'));
        $esParqueoBaulera = ($tipoNormalizado === 'parqueo baulera' || $tipoNormalizado === 'baulera parqueo');
        $tipoUnidad = $this->mapearTipoUnidad($tipoUnidadStr);

        // Solo procesamos parqueos, bauleras o parqueos-bauleras
        if ($tipoUnidad !== TipoUnidad::PARQUEO && $tipoUnidad !== TipoUnidad::BAULERA && !$esParqueoBaulera) {
            $this->stats['filas_omitidas']++;
            return;
        }

        $ubicacion = $columnas['ubicacion'] ? trim($worksheet->getCell($columnas['ubicacion'] . $row)->getValue() ?? '') : null;

        try {
            $propietario = $this->buscarOCrearPropietario($propietarioNombre);

            if ($esParqueoPublico && $numero === '1') {
                for ($i = 1; $i <= 21; $i++) {
                    $codigoSub = "P-1-{$i}";
                    $unidad = \App\Models\Unidad::where('codigo', $codigoSub)->first();
                    
                    if (!$unidad) {
                        $this->stats['errores'][] = [
                            'fila' => $row,
                            'error' => "Subdivisión no encontrada en BD. Código buscado: {$codigoSub}",
                        ];
                        $this->stats['unidades_no_encontradas']++;
                        $this->console->writeln("  <comment>⚠ Subdivisión no encontrada:</comment> {$codigoSub} (fila {$row})");
                    } else {
                        if (!$unidad->activo) {
                            $unidad->activo = true;
                            $unidad->save();
                        }
                        $this->stats['unidades_existen']++;
                        $this->crearRelacion($propietario, $unidad);
                    }
                }
            } elseif ($esParqueoBaulera) {
                // Buscar directamente como PB-{numero} sin separar
                $codigoSistema = "PB-{$numero}";
                
                $unidad = \App\Models\Unidad::where('codigo', $codigoSistema)->first();
                
                if (!$unidad) {
                    $unidad = \App\Models\Unidad::where('codigo', $numero)->first();
                }

                if (!$unidad) {
                    $this->stats['errores'][] = [
                        'fila' => $row,
                        'error' => "Unidad no encontrada en BD. Tipo: Parqueo Baulera, Código buscado: {$codigoSistema}",
                    ];
                    $this->stats['unidades_no_encontradas']++;
                    $this->console->writeln("  <comment>⚠ Unidad no encontrada:</comment> {$codigoSistema} (fila {$row})");
                } else {
                    if (!$unidad->activo) {
                        $unidad->activo = true;
                        $unidad->save();
                    }
                    $this->stats['unidades_existen']++;
                    $this->crearRelacion($propietario, $unidad);
                }
            } else {
                // Caso normal (solo un parqueo o una baulera, busca P- o B-)
                $this->asignarUnidad($numero, $tipoUnidad, $ubicacion, $propietario, $row, $tipoUnidadStr);
            }

        } catch (\Exception $e) {
            $this->stats['errores'][] = [
                'fila' => $row,
                'error' => $e->getMessage(),
            ];
            $this->console->writeln("  <error>✗ Error fila {$row}:</error> {$e->getMessage()}");
        }
    }

    private function asignarUnidad(string $numero, TipoUnidad $tipoUnidad, ?string $ubicacion, $propietario, int $row, string $tipoUnidadStr): void
    {
        $unidad = $this->buscarUnidad($numero, $tipoUnidad, $ubicacion);

        if (!$unidad) {
            $this->stats['errores'][] = [
                'fila' => $row,
                'error' => "Unidad no encontrada en BD. Tipo esperado: {$tipoUnidad->value} (desde {$tipoUnidadStr}), N°: {$numero}",
            ];
            $this->console->writeln("  <comment>⚠ Unidad no encontrada:</comment> {$numero} (fila {$row})");
            return;
        }

        $this->crearRelacion($propietario, $unidad);
    }
}
