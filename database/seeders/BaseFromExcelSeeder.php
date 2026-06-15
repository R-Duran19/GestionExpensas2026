<?php

namespace Database\Seeders;

use App\Models\Propietario;
use App\Models\Unidad;
use App\Enums\TipoUnidad;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\ConsoleOutput;

abstract class BaseFromExcelSeeder extends Seeder
{
    protected $console;
    protected $ciCounter = 1;

    protected $stats = [
        'unidades_existen' => 0,
        'unidades_no_encontradas' => 0,
        'propietarios_creados' => 0,
        'propietarios_existen' => 0,
        'relaciones_creadas' => 0,
        'relaciones_actualizadas' => 0,
        'filas_omitidas' => 0,
        'errores' => [],
    ];

    public function __construct()
    {
        $this->console = new ConsoleOutput();
        $this->ciCounter = (DB::table('propietarios')->max('id') ?? 0) + 1;
    }

    protected function mapearTipoUnidad(string $tipoExcel): ?TipoUnidad
    {
        $tipoNormalizado = strtolower(trim($tipoExcel));

        if ($tipoNormalizado === 'parqueo baulera' || $tipoNormalizado === 'baulera parqueo') {
            return TipoUnidad::BAULERA; // Dummy for now, actual handling in child class
        }

        return match (true) {
            str_contains($tipoNormalizado, 'departamento') => TipoUnidad::DEPARTAMENTO,
            str_contains($tipoNormalizado, 'oficina') => TipoUnidad::OFICINA,
            str_contains($tipoNormalizado, 'local') => TipoUnidad::LOCAL,
            str_contains($tipoNormalizado, 'baulera') => TipoUnidad::BAULERA,
            str_contains($tipoNormalizado, 'parqueo') => TipoUnidad::PARQUEO,
            default => null,
        };
    }

    protected function separarNombreApellido(string $nombreCompleto): array
    {
        $nombreCompleto = trim($nombreCompleto);
        $partes = preg_split('/\s+/', $nombreCompleto);

        if (count($partes) === 1) {
            return ['nombre' => $partes[0], 'apellido' => ''];
        }
        if (count($partes) === 2) {
            return ['nombre' => $partes[0], 'apellido' => $partes[1]];
        }

        $nombre = $partes[0];
        $apellido = implode(' ', array_slice($partes, 1));

        return ['nombre' => $nombre, 'apellido' => $apellido];
    }

    protected function generarCodigoSistema(string $codigoExcel, TipoUnidad $tipoUnidad, ?string $ubicacion): string
    {
        $codigoExcel = trim($codigoExcel);
        $prefijos = [
            TipoUnidad::DEPARTAMENTO->value => 'DEP',
            TipoUnidad::OFICINA->value => 'OF',
            TipoUnidad::LOCAL->value => 'LOC',
            TipoUnidad::PARQUEO->value => 'P',
            TipoUnidad::BAULERA->value => 'B',
        ];

        $prefijo = $prefijos[$tipoUnidad->value];

        $numeroPlanta = null;
        if ($ubicacion) {
            if (preg_match('/planta\s*(\d+)\s*-\s*(?:planta\s*)?(\d+)/i', $ubicacion, $matches)) {
                $numeroPlanta = $matches[1] . '-' . $matches[2];
            } elseif (preg_match('/planta\s*(\d+)/i', $ubicacion, $matches)) {
                $numeroPlanta = $matches[1];
            } elseif (preg_match('/sotano\s*(\d+)/i', $ubicacion, $matches)) {
                $numeroPlanta = 'S' . $matches[1];
            } elseif (preg_match('/piso\s*(\d+)/i', $ubicacion, $matches)) {
                $numeroPlanta = $matches[1];
            }
        }

        if ($tipoUnidad === TipoUnidad::DEPARTAMENTO && $numeroPlanta) {
            return "{$prefijo}-{$numeroPlanta}-{$codigoExcel}";
        }

        if ($tipoUnidad === TipoUnidad::OFICINA) {
            if ($numeroPlanta && preg_match('/^' . preg_quote($numeroPlanta) . '/i', $codigoExcel)) {
                return "{$prefijo}-{$codigoExcel}";
            }
            if ($numeroPlanta) {
                return "{$prefijo}-{$numeroPlanta}-{$codigoExcel}";
            }
            return "{$prefijo}-{$codigoExcel}";
        }

        if ($tipoUnidad === TipoUnidad::LOCAL) {
            return "{$prefijo}-{$codigoExcel}";
        }

        return "{$prefijo}-{$codigoExcel}";
    }

    protected function buscarUnidad(string $codigo, TipoUnidad $tipoUnidad, ?string $ubicacion): ?Unidad
    {
        $codigo = trim($codigo);
        $codigoSistema = $this->generarCodigoSistema($codigo, $tipoUnidad, $ubicacion);

        $unidad = Unidad::where('codigo', $codigoSistema)->first();

        if ($unidad) {
            if (!$unidad->activo) {
                $unidad->activo = true;
                $unidad->save();
            }
            $this->stats['unidades_existen']++;
            return $unidad;
        }

        $unidad = Unidad::where('codigo', $codigo)
            ->where('tipo_unidad', $tipoUnidad->value)
            ->first();

        if ($unidad) {
            if (!$unidad->activo) {
                $unidad->activo = true;
                $unidad->save();
            }
            $this->stats['unidades_existen']++;
            return $unidad;
        }

        // Para departamentos, intentar formato simplificado DEP-{numero}
        if ($tipoUnidad === TipoUnidad::DEPARTAMENTO) {
            $prefijos = [
                TipoUnidad::DEPARTAMENTO->value => 'DEP',
                TipoUnidad::OFICINA->value => 'OF',
                TipoUnidad::LOCAL->value => 'LOC',
                TipoUnidad::PARQUEO->value => 'P',
                TipoUnidad::BAULERA->value => 'B',
            ];
            $prefijo = $prefijos[$tipoUnidad->value];
            $codigoSimplificado = "{$prefijo}-{$codigo}";

            $unidad = Unidad::where('codigo', $codigoSimplificado)
                ->where('tipo_unidad', $tipoUnidad->value)
                ->first();

            if ($unidad) {
                if (!$unidad->activo) {
                    $unidad->activo = true;
                    $unidad->save();
                }
                $this->stats['unidades_existen']++;
                return $unidad;
            }
        }

        $this->stats['unidades_no_encontradas']++;
        return null;
    }

    protected function buscarOCrearPropietario(string $nombreCompleto): Propietario
    {
        $nombreCompleto = trim($nombreCompleto);
        $partes = $this->separarNombreApellido($nombreCompleto);

        // 1. Búsqueda exacta
        $propietario = Propietario::where('nombre', $partes['nombre'])
            ->where('apellido', $partes['apellido'])
            ->first();

        if ($propietario) {
            $this->stats['propietarios_existen']++;
            return $propietario;
        }

        // 2. Si solo hay un nombre (sin apellido), búsqueda exacta sin apellido
        if (empty($partes['apellido'])) {
            $propietario = Propietario::where('nombre', $partes['nombre'])
                ->where(function ($q) {
                    $q->whereNull('apellido')->orWhere('apellido', '');
                })
                ->first();

            if ($propietario) {
                $this->stats['propietarios_existen']++;
                return $propietario;
            }
        }

        // 3. Búsqueda por similitud concatenada (ignora espacios extra y permite nombres parciales)
        $busqueda = '%' . str_replace(' ', '%', $nombreCompleto) . '%';
        $propietario = Propietario::whereRaw("CONCAT(nombre, ' ', COALESCE(apellido, '')) LIKE ?", [$busqueda])
            ->orWhereRaw("CONCAT(COALESCE(apellido, ''), ' ', nombre) LIKE ?", [$busqueda])
            ->first();

        if ($propietario) {
            $this->console->writeln("  <comment>⚠ Propietario encontrado por similitud:</comment> '{$nombreCompleto}' => '{$propietario->nombre} {$propietario->apellido}'");
            $this->stats['propietarios_existen']++;
            return $propietario;
        }

        // 4. Búsqueda invertida (apellido en el campo nombre y viceversa)
        if (!empty($partes['apellido'])) {
            $propietario = Propietario::where('nombre', $partes['apellido'])
                ->where('apellido', $partes['nombre'])
                ->first();

            if ($propietario) {
                $this->console->writeln("  <comment>⚠ Propietario encontrado invertido:</comment> '{$nombreCompleto}' => '{$propietario->nombre} {$propietario->apellido}'");
                $this->stats['propietarios_existen']++;
                return $propietario;
            }
        }

        // 5. Si de verdad no existe después de todo eso, lo creamos
        $ciAuto = str_pad($this->ciCounter++, 8, '0', STR_PAD_LEFT);

        $propietario = Propietario::create([
            'nombre' => $partes['nombre'],
            'apellido' => $partes['apellido'],
            'ci' => $ciAuto,
            'telefono' => '',
            'activo' => true,
        ]);

        $this->stats['propietarios_creados']++;
        $this->console->writeln("  <info>✓ Propietario nuevo creado:</info> {$nombreCompleto} (CI: {$ciAuto})");

        return $propietario;
    }

    protected function crearRelacion(Propietario $propietario, Unidad $unidad): void
    {
        $relacionExistente = DB::table('propietario_unidad')
            ->where('propietario_id', $propietario->id)
            ->where('unidad_id', $unidad->id)
            ->where('es_propietario_actual', true)
            ->first();

        if ($relacionExistente) {
            $this->stats['relaciones_actualizadas']++;
            return;
        }

        DB::table('propietario_unidad')
            ->where('unidad_id', $unidad->id)
            ->where('es_propietario_actual', true)
            ->update([
                'es_propietario_actual' => false,
                'fecha_fin' => now()->toDateString(),
            ]);

        DB::table('propietario_unidad')->insert([
            'propietario_id' => $propietario->id,
            'unidad_id' => $unidad->id,
            'fecha_inicio' => now()->toDateString(),
            'es_propietario_actual' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->stats['relaciones_creadas']++;
    }

    protected function obtenerMapaColumnas($worksheet, int $headerRow): ?array
    {
        $columnas = [
            'unidad_funcional' => null,
            'numero' => null,
            'ubicacion' => null,
            'total_m2' => null,
            'propietario' => null,
        ];

        $highestColumn = $worksheet->getHighestColumn();

        for ($col = 'A'; $col <= $highestColumn; $col++) {
            $valor = trim($worksheet->getCell($col . $headerRow)->getValue());
            if (empty($valor)) continue;

            if (stripos($valor, 'unidad funcional') !== false) {
                $columnas['unidad_funcional'] = $col;
            } elseif (stripos($valor, 'N°') === 0 || $valor === 'N°') {
                $columnas['numero'] = $col;
            } elseif (stripos($valor, 'Ubicación') !== false) {
                $columnas['ubicacion'] = $col;
            } elseif (stripos($valor, 'Total m2') !== false) {
                $columnas['total_m2'] = $col;
            } elseif (stripos($valor, 'NOMBRE DE PROPIETARIOS') !== false) {
                $columnas['propietario'] = $col;
            }
        }

        if (!$columnas['unidad_funcional'] || (!$columnas['numero']) || !$columnas['propietario']) {
            return null;
        }

        return $columnas;
    }

    protected function procesarExcel(): void
    {
        $path = base_path('FAITH RICARDO.xlsx');
        if (!file_exists($path)) {
            $this->console->writeln("<error>✗ Archivo no encontrado:</error> {$path}");
            throw new \Exception("Archivo Excel no encontrado: {$path}");
        }

        $this->console->writeln("<info> Leyendo archivo Excel...</info>");
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $worksheet = $spreadsheet->getSheetByName('MARZO');

        if (!$worksheet) {
            throw new \Exception("Hoja 'MARZO' no encontrada en el archivo Excel");
        }

        $highestRow = $worksheet->getHighestRow();
        $this->console->writeln("<info> Filas totales:</info> {$highestRow}");

        $headerRow = 10;
        $columnas = $this->obtenerMapaColumnas($worksheet, $headerRow);

        if ($columnas === null) {
            throw new \Exception("No se pudieron identificar las columnas necesarias");
        }

        $this->console->writeln("<comment> Procesando filas...</comment>");
        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $this->procesarFila($worksheet, $row, $columnas);
        }
    }

    protected function imprimirResumen(): void
    {
        $this->console->writeln("");
        $this->console->writeln("<info>========================================</info>");
        $this->console->writeln("<info>  RESUMEN  </info>");
        $this->console->writeln("<info>========================================</info>");
        $this->console->writeln("  <comment>Unidades encontradas:</comment>    {$this->stats['unidades_existen']}");
        $this->console->writeln("  <error>Unidades no encontradas:</error> {$this->stats['unidades_no_encontradas']}");
        $this->console->writeln("  <info>Propietarios creados:</info>      {$this->stats['propietarios_creados']}");
        $this->console->writeln("  <comment>Propietarios existentes:</comment>   {$this->stats['propietarios_existen']}");
        $this->console->writeln("  <info>Relaciones creadas:</info>        {$this->stats['relaciones_creadas']}");
        $this->console->writeln("  <comment>Relaciones existentes:</comment>     {$this->stats['relaciones_actualizadas']}");
        $this->console->writeln("  <comment>Filas omitidas:</comment>            {$this->stats['filas_omitidas']}");

        if (!empty($this->stats['errores'])) {
            $this->console->writeln("");
            $this->console->writeln("<error>========================================</error>");
            $this->console->writeln("<error>  UNIDADES NO ENCONTRADAS O ERRORES (" . count($this->stats['errores']) . ")  </error>");
            $this->console->writeln("<error>========================================</error>");
            foreach ($this->stats['errores'] as $error) {
                $this->console->writeln("  <error>Fila {$error['fila']}: {$error['error']}</error>");
            }
        }
        $this->console->writeln("");
    }

    abstract protected function procesarFila($worksheet, int $row, array $columnas): void;
}
