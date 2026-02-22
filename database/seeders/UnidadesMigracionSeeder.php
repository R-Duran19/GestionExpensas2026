<?php

namespace Database\Seeders;

use App\Models\Unidad;
use App\Enums\TipoUnidad;
use App\Enums\CategoriaUnidad;
use Illuminate\Database\Seeder;

class UnidadesMigracionSeeder extends Seeder
{
    /**
     * Migrar todas las unidades del sistema anterior
     */
    public function run(): void
    {
        $this->command->info('Migrando unidades desde sistema anterior...');

        $totalDepartamentos = $this->migrarDepartamentos();
        $this->command->info("✓ Departamentos: {$totalDepartamentos}");

        $totalOficinas = $this->migrarOficinas();
        $this->command->info("✓ Oficinas: {$totalOficinas}");

        $totalLocales = $this->migrarLocales();
        $this->command->info("✓ Locales: {$totalLocales}");

        $totalParqueosBauleras = $this->migrarParqueosBauleras();
        $this->command->info("✓ Parqueos y Bauleras: {$totalParqueosBauleras}");

        $total = Unidad::count();
        $this->command->info("========================================");
        $this->command->info("TOTAL UNIDADES MIGRADAS: {$total}");
        $this->command->info("========================================");
    }

    /**
     * Migrar departamentos (Plantas 11-29)
     */
    private function migrarDepartamentos(): int
    {
        $departamentos = [
            // PLANTA 11
            ['codigo' => 'DEP-11-A', 'piso' => 'Planta 11', 'm2' => 100.64],
            ['codigo' => 'DEP-11-B', 'piso' => 'Planta 11', 'm2' => 100.63],
            ['codigo' => 'DEP-11-C', 'piso' => 'Planta 11', 'm2' => 89.28],
            ['codigo' => 'DEP-11-D', 'piso' => 'Planta 11', 'm2' => 89.22],

            // PLANTA 12
            ['codigo' => 'DEP-12-A', 'piso' => 'Planta 12', 'm2' => 100.46],
            ['codigo' => 'DEP-12-B', 'piso' => 'Planta 12', 'm2' => 100.46],
            ['codigo' => 'DEP-12-C', 'piso' => 'Planta 12', 'm2' => 91.09],
            ['codigo' => 'DEP-12-D', 'piso' => 'Planta 12', 'm2' => 91.09],

            // PLANTA 13
            ['codigo' => 'DEP-13-A', 'piso' => 'Planta 13', 'm2' => 154.03],
            ['codigo' => 'DEP-13-B', 'piso' => 'Planta 13', 'm2' => 153.95],
            ['codigo' => 'DEP-13-C', 'piso' => 'Planta 13', 'm2' => 42.07],
            ['codigo' => 'DEP-13-D', 'piso' => 'Planta 13', 'm2' => 41.94],

            // PLANTA 14
            ['codigo' => 'DEP-14-A', 'piso' => 'Planta 14', 'm2' => 154.03],
            ['codigo' => 'DEP-14-B', 'piso' => 'Planta 14', 'm2' => 153.95],
            ['codigo' => 'DEP-14-C', 'piso' => 'Planta 14', 'm2' => 42.07],
            ['codigo' => 'DEP-14-D', 'piso' => 'Planta 14', 'm2' => 41.94],

            // PLANTA 15
            ['codigo' => 'DEP-15-A', 'piso' => 'Planta 15', 'm2' => 153.87],
            ['codigo' => 'DEP-15-B', 'piso' => 'Planta 15', 'm2' => 147.97],
            ['codigo' => 'DEP-15-C', 'piso' => 'Planta 15', 'm2' => 47.92],
            ['codigo' => 'DEP-15-D', 'piso' => 'Planta 15', 'm2' => 41.95],

            // PLANTA 16
            ['codigo' => 'DEP-16-A', 'piso' => 'Planta 16', 'm2' => 154.03],
            ['codigo' => 'DEP-16-B', 'piso' => 'Planta 16', 'm2' => 153.95],
            ['codigo' => 'DEP-16-C', 'piso' => 'Planta 16', 'm2' => 42.07],
            ['codigo' => 'DEP-16-D', 'piso' => 'Planta 16', 'm2' => 41.94],

            // PLANTA 17
            ['codigo' => 'DEP-17-A', 'piso' => 'Planta 17', 'm2' => 153.87],
            ['codigo' => 'DEP-17-B', 'piso' => 'Planta 17', 'm2' => 147.97],
            ['codigo' => 'DEP-17-C', 'piso' => 'Planta 17', 'm2' => 47.92],
            ['codigo' => 'DEP-17-D', 'piso' => 'Planta 17', 'm2' => 41.95],

            // PLANTA 18
            ['codigo' => 'DEP-18-A', 'piso' => 'Planta 18', 'm2' => 153.87],
            ['codigo' => 'DEP-18-B', 'piso' => 'Planta 18', 'm2' => 147.97],
            ['codigo' => 'DEP-18-C', 'piso' => 'Planta 18', 'm2' => 47.92],
            ['codigo' => 'DEP-18-D', 'piso' => 'Planta 18', 'm2' => 41.95],

            // PLANTA 19
            ['codigo' => 'DEP-19-A', 'piso' => 'Planta 19', 'm2' => 148.04],
            ['codigo' => 'DEP-19-B', 'piso' => 'Planta 19', 'm2' => 147.97],
            ['codigo' => 'DEP-19-C', 'piso' => 'Planta 19', 'm2' => 47.92],
            ['codigo' => 'DEP-19-D', 'piso' => 'Planta 19', 'm2' => 47.78],

            // PLANTA 20
            ['codigo' => 'DEP-20-A', 'piso' => 'Planta 20', 'm2' => 153.87],
            ['codigo' => 'DEP-20-B', 'piso' => 'Planta 20', 'm2' => 147.97],
            ['codigo' => 'DEP-20-C', 'piso' => 'Planta 20', 'm2' => 47.92],
            ['codigo' => 'DEP-20-D', 'piso' => 'Planta 20', 'm2' => 41.95],

            // PLANTA 21
            ['codigo' => 'DEP-21-A', 'piso' => 'Planta 21', 'm2' => 148.04],
            ['codigo' => 'DEP-21-B', 'piso' => 'Planta 21', 'm2' => 147.97],
            ['codigo' => 'DEP-21-C', 'piso' => 'Planta 21', 'm2' => 47.92],
            ['codigo' => 'DEP-21-D', 'piso' => 'Planta 21', 'm2' => 47.78],

            // PLANTA 22
            ['codigo' => 'DEP-22-A', 'piso' => 'Planta 22', 'm2' => 153.87],
            ['codigo' => 'DEP-22-B', 'piso' => 'Planta 22', 'm2' => 147.97],
            ['codigo' => 'DEP-22-C', 'piso' => 'Planta 22', 'm2' => 47.92],
            ['codigo' => 'DEP-22-D', 'piso' => 'Planta 22', 'm2' => 41.95],

            // PLANTA 23
            ['codigo' => 'DEP-23-A', 'piso' => 'Planta 23', 'm2' => 148.04],
            ['codigo' => 'DEP-23-B', 'piso' => 'Planta 23', 'm2' => 147.97],
            ['codigo' => 'DEP-23-C', 'piso' => 'Planta 23', 'm2' => 47.92],
            ['codigo' => 'DEP-23-D', 'piso' => 'Planta 23', 'm2' => 47.78],

            // PLANTA 24
            ['codigo' => 'DEP-24-A', 'piso' => 'Planta 24', 'm2' => 154.01],
            ['codigo' => 'DEP-24-B', 'piso' => 'Planta 24', 'm2' => 153.93],
            ['codigo' => 'DEP-24-C', 'piso' => 'Planta 24', 'm2' => 42.05],
            ['codigo' => 'DEP-24-D', 'piso' => 'Planta 24', 'm2' => 41.92],

            // PLANTA 25
            ['codigo' => 'DEP-25-A', 'piso' => 'Planta 25', 'm2' => 148.04],
            ['codigo' => 'DEP-25-B', 'piso' => 'Planta 25', 'm2' => 147.97],
            ['codigo' => 'DEP-25-C-T', 'piso' => 'Planta 25 Terraza', 'm2' => 108.92],
            ['codigo' => 'DEP-25-D', 'piso' => 'Planta 25', 'm2' => 47.78],

            // PLANTA 26
            ['codigo' => 'DEP-26-A', 'piso' => 'Planta 26', 'm2' => 100.46],
            ['codigo' => 'DEP-26-B', 'piso' => 'Planta 26', 'm2' => 100.46],
            ['codigo' => 'DEP-26-C', 'piso' => 'Planta 26', 'm2' => 91.09],
            ['codigo' => 'DEP-26-D', 'piso' => 'Planta 26', 'm2' => 91.09],

            // PLANTA 27
            ['codigo' => 'DEP-27-A', 'piso' => 'Planta 27', 'm2' => 210.88],
            ['codigo' => 'DEP-27-B', 'piso' => 'Planta 27', 'm2' => 91.19],
            ['codigo' => 'DEP-27-C', 'piso' => 'Planta 27', 'm2' => 90.81],

            // PLANTA 28-29 (Duplex)
            ['codigo' => 'DEP-28-29-A', 'piso' => 'Planta 28-29', 'm2' => 260.96],
            ['codigo' => 'DEP-28-29-B', 'piso' => 'Planta 28-29', 'm2' => 254.76],
        ];

        foreach ($departamentos as $depto) {
            Unidad::create([
                'codigo' => $depto['codigo'],
                'tipo_unidad' => TipoUnidad::DEPARTAMENTO,
                'piso' => $depto['piso'],
                'metros_cuadrados' => $depto['m2'],
                'categoria_unidad' => CategoriaUnidad::DOMICILIARIA,
                'tiene_medidor' => true,
                'activo' => false, // Sin propietario inicial
            ]);
        }

        return count($departamentos);
    }

    /**
     * Migrar oficinas (Plantas 3-10)
     */
    private function migrarOficinas(): int
    {
        $oficinas = [
            // PLANTA 3
            ['codigo' => 'OF-3-A', 'piso' => 'Planta 3', 'm2' => 50.36],
            ['codigo' => 'OF-3-B', 'piso' => 'Planta 3', 'm2' => 50.26],
            ['codigo' => 'OF-3-C', 'piso' => 'Planta 3', 'm2' => 54.14],
            ['codigo' => 'OF-3-D', 'piso' => 'Planta 3', 'm2' => 52.59],

            // PLANTA 4
            ['codigo' => 'OF-4-A', 'piso' => 'Planta 4', 'm2' => 52.57],
            ['codigo' => 'OF-4-B', 'piso' => 'Planta 4', 'm2' => 100.66],
            ['codigo' => 'OF-4-C', 'piso' => 'Planta 4', 'm2' => 46.95],

            // PLANTA 5
            ['codigo' => 'OF-5-A', 'piso' => 'Planta 5', 'm2' => 52.56],
            ['codigo' => 'OF-5-B', 'piso' => 'Planta 5', 'm2' => 44.96],
            ['codigo' => 'OF-5-C', 'piso' => 'Planta 5', 'm2' => 52.56],
            ['codigo' => 'OF-5-D', 'piso' => 'Planta 5', 'm2' => 51.59],
            ['codigo' => 'OF-5-E', 'piso' => 'Planta 5', 'm2' => 40.47],
            ['codigo' => 'OF-5-F', 'piso' => 'Planta 5', 'm2' => 40.47],
            ['codigo' => 'OF-5-G', 'piso' => 'Planta 5', 'm2' => 52.88],

            // PLANTA 6
            ['codigo' => 'OF-6-A', 'piso' => 'Planta 6', 'm2' => 52.46],
            ['codigo' => 'OF-6-B', 'piso' => 'Planta 6', 'm2' => 44.96],
            ['codigo' => 'OF-6-C', 'piso' => 'Planta 6', 'm2' => 52.56],
            ['codigo' => 'OF-6-D', 'piso' => 'Planta 6', 'm2' => 51.59],
            ['codigo' => 'OF-6-E', 'piso' => 'Planta 6', 'm2' => 40.47],
            ['codigo' => 'OF-6-F', 'piso' => 'Planta 6', 'm2' => 40.47],
            ['codigo' => 'OF-6-G', 'piso' => 'Planta 6', 'm2' => 52.88],

            // PLANTA 7
            ['codigo' => 'OF-7-A', 'piso' => 'Planta 7', 'm2' => 52.46],
            ['codigo' => 'OF-7-B', 'piso' => 'Planta 7', 'm2' => 44.96],
            ['codigo' => 'OF-7-C', 'piso' => 'Planta 7', 'm2' => 52.56],
            ['codigo' => 'OF-7-D', 'piso' => 'Planta 7', 'm2' => 51.59],
            ['codigo' => 'OF-7-E', 'piso' => 'Planta 7', 'm2' => 40.47],
            ['codigo' => 'OF-7-F', 'piso' => 'Planta 7', 'm2' => 40.47],
            ['codigo' => 'OF-7-G', 'piso' => 'Planta 7', 'm2' => 52.88],

            // PLANTA 8
            ['codigo' => 'OF-8-A', 'piso' => 'Planta 8', 'm2' => 52.46],
            ['codigo' => 'OF-8-B', 'piso' => 'Planta 8', 'm2' => 44.96],
            ['codigo' => 'OF-8-C', 'piso' => 'Planta 8', 'm2' => 52.56],
            ['codigo' => 'OF-8-D', 'piso' => 'Planta 8', 'm2' => 51.59],
            ['codigo' => 'OF-8-E', 'piso' => 'Planta 8', 'm2' => 40.47],
            ['codigo' => 'OF-8-F', 'piso' => 'Planta 8', 'm2' => 40.47],
            ['codigo' => 'OF-8-G', 'piso' => 'Planta 8', 'm2' => 52.88],

            // PLANTA 9
            ['codigo' => 'OF-9-A', 'piso' => 'Planta 9', 'm2' => 52.46],
            ['codigo' => 'OF-9-B', 'piso' => 'Planta 9', 'm2' => 44.96],
            ['codigo' => 'OF-9-C', 'piso' => 'Planta 9', 'm2' => 52.56],
            ['codigo' => 'OF-9-D', 'piso' => 'Planta 9', 'm2' => 51.59],
            ['codigo' => 'OF-9-E', 'piso' => 'Planta 9', 'm2' => 40.47],
            ['codigo' => 'OF-9-F', 'piso' => 'Planta 9', 'm2' => 40.47],
            ['codigo' => 'OF-9-G', 'piso' => 'Planta 9', 'm2' => 52.88],

            // PLANTA 10
            ['codigo' => 'OF-10-A', 'piso' => 'Planta 10', 'm2' => 52.46],
            ['codigo' => 'OF-10-B', 'piso' => 'Planta 10', 'm2' => 42.54],
            ['codigo' => 'OF-10-C', 'piso' => 'Planta 10', 'm2' => 52.56],
            ['codigo' => 'OF-10-D', 'piso' => 'Planta 10', 'm2' => 51.59],
            ['codigo' => 'OF-10-E', 'piso' => 'Planta 10', 'm2' => 40.47],
            ['codigo' => 'OF-10-F', 'piso' => 'Planta 10', 'm2' => 40.47],
            ['codigo' => 'OF-10-G', 'piso' => 'Planta 10', 'm2' => 52.88],
        ];

        foreach ($oficinas as $ofi) {
            Unidad::create([
                'codigo' => $ofi['codigo'],
                'tipo_unidad' => TipoUnidad::OFICINA,
                'piso' => $ofi['piso'],
                'metros_cuadrados' => $ofi['m2'],
                'categoria_unidad' => CategoriaUnidad::COMERCIAL,
                'tiene_medidor' => true,
                'activo' => false,
            ]);
        }

        return count($oficinas);
    }

    /**
     * Migrar locales (Plantas 1-2)
     */
    private function migrarLocales(): int
    {
        $locales = [
            // PLANTA 1
            ['codigo' => 'LOC-1', 'piso' => 'Planta 1', 'm2' => 18.25],
            ['codigo' => 'LOC-2', 'piso' => 'Planta 1', 'm2' => 19.92],
            ['codigo' => 'LOC-3', 'piso' => 'Planta 1', 'm2' => 29.92],
            ['codigo' => 'LOC-4', 'piso' => 'Planta 1', 'm2' => 28.84],
            ['codigo' => 'LOC-5', 'piso' => 'Planta 1', 'm2' => 28.82],
            ['codigo' => 'LOC-6', 'piso' => 'Planta 1', 'm2' => 30.77],
            ['codigo' => 'LOC-7', 'piso' => 'Planta 1', 'm2' => 21.13],
            ['codigo' => 'LOC-8', 'piso' => 'Planta 1', 'm2' => 19.37],
            ['codigo' => 'LOC-9', 'piso' => 'Planta 1', 'm2' => 21.44],
            ['codigo' => 'LOC-10', 'piso' => 'Planta 1', 'm2' => 23.35],
            ['codigo' => 'LOC-11', 'piso' => 'Planta 1', 'm2' => 19.78],

            // PLANTA 2
            ['codigo' => 'LOC-12', 'piso' => 'Planta 2', 'm2' => 100.05],
            ['codigo' => 'LOC-14', 'piso' => 'Planta 2', 'm2' => 27.66],
            ['codigo' => 'LOC-15', 'piso' => 'Planta 2', 'm2' => 51.61],
            ['codigo' => 'LOC-16', 'piso' => 'Planta 2', 'm2' => 19.26],
            ['codigo' => 'LOC-17', 'piso' => 'Planta 2', 'm2' => 20.09],
            ['codigo' => 'LOC-18', 'piso' => 'Planta 2', 'm2' => 38.21],
            ['codigo' => 'LOC-19', 'piso' => 'Planta 2', 'm2' => 23.54],
        ];

        foreach ($locales as $local) {
            Unidad::create([
                'codigo' => $local['codigo'],
                'tipo_unidad' => TipoUnidad::LOCAL,
                'piso' => $local['piso'],
                'metros_cuadrados' => $local['m2'],
                'categoria_unidad' => CategoriaUnidad::COMERCIAL,
                'tiene_medidor' => true,
                'activo' => false,
            ]);
        }

        return count($locales);
    }

    /**
     * Migrar parqueos y bauleras (Sótanos 1-4, Planta 1)
     */
    private function migrarParqueosBauleras(): int
    {
        $contador = 0;

        // SOTANO 4 - Parqueos
        $parqueos_s4 = [
            ['codigo' => 'P-125-118', 'm2' => 100.00],
            ['codigo' => 'P-114', 'm2' => 12.50],
            ['codigo' => 'P-115', 'm2' => 12.50],
            ['codigo' => 'P-116', 'm2' => 12.50],
            ['codigo' => 'P-117', 'm2' => 12.50],
            ['codigo' => 'P-113', 'm2' => 12.50],
            ['codigo' => 'P-112', 'm2' => 12.50],
            ['codigo' => 'P-111', 'm2' => 12.50],
            ['codigo' => 'P-110', 'm2' => 12.50],
            ['codigo' => 'P-109', 'm2' => 12.50],
            ['codigo' => 'P-108', 'm2' => 12.50],
            ['codigo' => 'P-107', 'm2' => 12.50],
            ['codigo' => 'P-106', 'm2' => 12.50],
            ['codigo' => 'PB-104-76', 'm2' => 18.31],
            ['codigo' => 'P-103', 'm2' => 12.50],
            ['codigo' => 'P-102', 'm2' => 12.50],
            ['codigo' => 'PB-101-68', 'm2' => 36.44],
            ['codigo' => 'P-100', 'm2' => 12.50],
            ['codigo' => 'PB-99-57', 'm2' => 19.66],
            ['codigo' => 'PB-98-95', 'm2' => 19.34],
            ['codigo' => 'PB-97-94', 'm2' => 32.01],
            ['codigo' => 'P-96', 'm2' => 12.50],
            ['codigo' => 'P-95', 'm2' => 12.50],
            ['codigo' => 'P-94', 'm2' => 12.50],
        ];

        foreach ($parqueos_s4 as $parqueo) {
            Unidad::create([
                'codigo' => $parqueo['codigo'],
                'tipo_unidad' => TipoUnidad::PARQUEO,
                'piso' => 'Sótano 4',
                'metros_cuadrados' => $parqueo['m2'],
                'categoria_unidad' => CategoriaUnidad::DOMICILIARIA,
                'tiene_medidor' => false,
                'activo' => false,
            ]);
            $contador++;
        }

        // SOTANO 4 - Bauleras
        $bauleras_s4 = ['B-93', 'B-92', 'B-91', 'B-90', 'B-89', 'B-88', 'B-87', 'B-86', 'B-85', 'B-84', 'B-83', 'B-82', 'B-81', 'B-80', 'B-79', 'B-78', 'B-77', 'B-75', 'B-74', 'B-73', 'B-72', 'B-71', 'B-70', 'B-69', 'B-67', 'B-66', 'B-65', 'B-64', 'B-63', 'B-62', 'B-61', 'B-60', 'B-59', 'B-58'];
        $m2_bauleras = [4.50, 5.53, 5.76, 5.76, 5.53, 5.53, 5.53, 5.76, 5.99, 6.14, 5.53, 5.53, 5.53, 7.96, 5.02, 5.01, 5.68, 4.74, 4.32, 4.32, 4.74, 7.20, 4.70, 5.34, 9.10, 4.23, 4.86, 4.77, 6.50, 8.56, 5.51, 5.50, 5.49, 5.84];

        foreach ($bauleras_s4 as $i => $codigo) {
            Unidad::create([
                'codigo' => $codigo,
                'tipo_unidad' => TipoUnidad::BAULERA,
                'piso' => 'Sótano 4',
                'metros_cuadrados' => $m2_bauleras[$i],
                'categoria_unidad' => CategoriaUnidad::DOMICILIARIA,
                'tiene_medidor' => false,
                'activo' => false,
            ]);
            $contador++;
        }

        // SOTANO 3 - Parqueos
        $parqueos_s3 = ['P-93', 'P-92', 'P-91', 'P-90', 'P-89', 'P-88', 'P-87', 'P-86', 'P-85', 'P-84', 'P-83', 'P-82', 'P-81', 'P-80', 'P-79', 'P-78', 'P-77', 'P-76', 'P-75', 'P-74', 'P-73-300', 'P-72', 'P-71', 'P-70', 'P-69', 'P-68', 'P-67', 'P-66', 'P-65', 'P-64', 'P-63'];
        $m2_parqueos_s3 = [12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 25.00, 12.50, 12.50, 12.50, 12.50, 12.50, 24.10, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50];

        foreach ($parqueos_s3 as $i => $codigo) {
            Unidad::create([
                'codigo' => $codigo,
                'tipo_unidad' => TipoUnidad::PARQUEO,
                'piso' => 'Sótano 3',
                'metros_cuadrados' => $m2_parqueos_s3[$i],
                'categoria_unidad' => CategoriaUnidad::DOMICILIARIA,
                'tiene_medidor' => false,
                'activo' => false,
            ]);
            $contador++;
        }

        // SOTANO 3 - Bauleras (parcial)
        $bauleras_s3 = ['B-56', 'B-55', 'B-54', 'B-53', 'B-52', 'B-51', 'B-50', 'B-49', 'B-48', 'B-47', 'B-46', 'B-45', 'B-44', 'B-43', 'B-42', 'B-301', 'B-40', 'B-41'];
        $m2_bauleras_s3 = [5.57, 5.57, 5.57, 5.57, 5.51, 5.62, 5.57, 5.57, 5.57, 5.51, 5.57, 18.78, 7.38, 7.62, 7.62, 10.67, 5.52, 5.62];

        foreach ($bauleras_s3 as $i => $codigo) {
            Unidad::create([
                'codigo' => $codigo,
                'tipo_unidad' => TipoUnidad::BAULERA,
                'piso' => 'Sótano 3',
                'metros_cuadrados' => $m2_bauleras_s3[$i],
                'categoria_unidad' => CategoriaUnidad::DOMICILIARIA,
                'tiene_medidor' => false,
                'activo' => false,
            ]);
            $contador++;
        }

        // SOTANO 2 - Parqueos (parcial)
        $parqueos_s2 = ['P-62', 'P-61', 'P-60', 'P-59', 'P-58', 'P-57', 'P-56', 'P-55', 'P-54', 'P-53', 'P-52', 'P-51', 'P-50', 'P-49', 'P-48', 'P-47', 'P-46', 'P-45', 'P-44', 'P-43-11', 'P-42-12', 'P-41-13', 'P-40-14', 'P-39-15', 'P-38', 'P-37', 'P-36', 'P-35', 'P-34', 'P-33', 'P-32'];
        $m2_parqueos_s2 = [12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 12.50, 20.27, 20.61, 20.40, 20.67, 23.39, 12.50, 12.50, 25.00, 12.50, 12.50, 12.50, 12.50, 12.50];

        foreach ($parqueos_s2 as $i => $codigo) {
            Unidad::create([
                'codigo' => $codigo,
                'tipo_unidad' => TipoUnidad::PARQUEO,
                'piso' => 'Sótano 2',
                'metros_cuadrados' => $m2_parqueos_s2[$i],
                'categoria_unidad' => CategoriaUnidad::DOMICILIARIA,
                'tiene_medidor' => false,
                'activo' => false,
            ]);
            $contador++;
        }

        // SOTANO 2 - Bauleras (parcial)
        $bauleras_s2 = ['B-39', 'B-38', 'B-37', 'B-36', 'B-35', 'B-34', 'B-33', 'B-32', 'B-31', 'B-30', 'B-29', 'B-28', 'B-27', 'B-26', 'B-25', 'B-24', 'B-23', 'B-22', 'B-21', 'B-20', 'B-19', 'B-18', 'B-17', 'B-16', 'B-10'];
        $m2_bauleras_s2 = [5.91, 5.80, 21.17, 5.27, 4.82, 4.74, 4.32, 4.74, 4.32, 5.34, 4.70, 7.80, 9.28, 4.15, 4.76, 4.03, 4.19, 4.88, 4.70, 6.13, 5.51, 5.50, 5.49, 5.84, 9.35];

        foreach ($bauleras_s2 as $i => $codigo) {
            Unidad::create([
                'codigo' => $codigo,
                'tipo_unidad' => TipoUnidad::BAULERA,
                'piso' => 'Sótano 2',
                'metros_cuadrados' => $m2_bauleras_s2[$i],
                'categoria_unidad' => CategoriaUnidad::DOMICILIARIA,
                'tiene_medidor' => false,
                'activo' => false,
            ]);
            $contador++;
        }

        // SOTANO 1 - Parqueo Público
        Unidad::create([
            'codigo' => 'P-1',
            'tipo_unidad' => TipoUnidad::PARQUEO,
            'piso' => 'Sótano 1',
            'metros_cuadrados' => 899.10,
            'categoria_unidad' => CategoriaUnidad::DOMICILIARIA,
            'tiene_medidor' => false,
            'activo' => false,
        ]);
        $contador++;

        // PLANTA 1 - Parqueos
        $parqueos_p1 = ['P-5', 'P-6', 'P-13', 'P-126', 'P-127', 'P-128', 'P-129', 'P-130'];
        foreach ($parqueos_p1 as $codigo) {
            Unidad::create([
                'codigo' => $codigo,
                'tipo_unidad' => TipoUnidad::PARQUEO,
                'piso' => 'Planta 1',
                'metros_cuadrados' => 12.50,
                'categoria_unidad' => CategoriaUnidad::DOMICILIARIA,
                'tiene_medidor' => false,
                'activo' => false,
            ]);
            $contador++;
        }

        return $contador;
    }
}
