<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use App\Enums\TipoUnidad;

class DepartamentosFromExcelSeeder extends BaseFromExcelSeeder
{
    public function run(): void
    {
        $this->console->writeln("<info>========================================</info>");
        $this->console->writeln("<info>  DepartamentosFromExcelSeeder  </info>");
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

        $tipoUnidad = $this->mapearTipoUnidad($tipoUnidadStr);

        // Solo procesamos departamentos
        if ($tipoUnidad !== TipoUnidad::DEPARTAMENTO) {
            $this->stats['filas_omitidas']++;
            return;
        }

        $ubicacion = $columnas['ubicacion'] ? trim($worksheet->getCell($columnas['ubicacion'] . $row)->getValue() ?? '') : null;

        try {
            $unidad = $this->buscarUnidad($numero, $tipoUnidad, $ubicacion);

            if (!$unidad) {
                $this->stats['errores'][] = [
                    'fila' => $row,
                    'error' => "Unidad no encontrada en BD. Tipo: {$tipoUnidadStr}, N°: {$numero}",
                ];
                $this->console->writeln("  <comment>⚠ Unidad no encontrada:</comment> {$numero} (fila {$row})");
                return;
            }

            $propietario = $this->buscarOCrearPropietario($propietarioNombre);
            $this->crearRelacion($propietario, $unidad);

        } catch (\Exception $e) {
            $this->stats['errores'][] = [
                'fila' => $row,
                'error' => $e->getMessage(),
            ];
            $this->console->writeln("  <error>✗ Error fila {$row}:</error> {$e->getMessage()}");
        }
    }
}
