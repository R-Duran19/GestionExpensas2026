<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LimpiarUtf8Command extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'limpiar:utf8';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar caracteres UTF-8 mal codificados de la base de datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== LIMPIEZA UTF-8 DE BASE DE DATOS ===');
        $this->newLine();

        $limpiarUtf8 = function ($string) {
            if (empty($string) || !is_string($string)) {
                return $string;
            }

            // Remover BOM
            $string = preg_replace('/\xEF\xBB\xBF/', '', $string);

            // Convertir a UTF-8 válido
            $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');

            // Remover caracteres UTF-8 inválidos
            $string = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $string);

            // Remover caracteres de control
            $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $string);

            // Reemplazos específicos
            $reemplazos = [
                ['«', '»', '"', '"', '"', '"', ''', ''', '"', '"', '–', '—', '…', ' ', ' '],
                ['"', '"', '"', '"', '"', '"', '"', '-', '-', '...', ' ', ' '],
            ];

            return str_replace($reemplazos[0], $reemplazos[1], $string);
        };

        try {
            // Limpiar ingresos
            $this->info('Limpiando tabla ingresos...');
            $ingresos = DB::table('ingresos')->get(['id', 'descripcion', 'observaciones', 'referencia_bancaria']);
            $countIngresos = 0;

            foreach ($ingresos as $ingreso) {
                $update = [];
                foreach (['descripcion', 'observaciones', 'referencia_bancaria'] as $campo) {
                    if (!empty($ingreso->$campo) && is_string($ingreso->$campo)) {
                        $clean = $limpiarUtf8($ingreso->$campo);
                        if ($clean !== $ingreso->$campo) {
                            $update[$campo] = $clean;
                        }
                    }
                }
                if (!empty($update)) {
                    DB::table('ingresos')->where('id', $ingreso->id)->update($update);
                    $countIngresos++;
                }
            }
            $this->info("✅ Ingresos limpiados: {$countIngresos}");
            $this->newLine();

            // Limpiar egresos
            $this->info('Limpiando tabla egresos...');
            $egresos = DB::table('egresos')->get(['id', 'concepto', 'descripcion', 'observaciones', 'proveedor']);
            $countEgresos = 0;

            foreach ($egresos as $egreso) {
                $update = [];
                foreach (['concepto', 'descripcion', 'observaciones', 'proveedor'] as $campo) {
                    if (!empty($egreso->$campo) && is_string($egreso->$campo)) {
                        $clean = $limpiarUtf8($egreso->$campo);
                        if ($clean !== $egreso->$campo) {
                            $update[$campo] = $clean;
                        }
                    }
                }
                if (!empty($update)) {
                    DB::table('egresos')->where('id', $egreso->id)->update($update);
                    $countEgresos++;
                }
            }
            $this->info("✅ Egresos limpiados: {$countEgresos}");
            $this->newLine();

            // Limpiar propietarios
            $this->info('Limpiando tabla propietarios...');
            $propietarios = DB::table('propietarios')->get(['id', 'nombre', 'apellido', 'email', 'observaciones']);
            $countPropietarios = 0;

            foreach ($propietarios as $propietario) {
                $update = [];
                foreach (['nombre', 'apellido', 'email', 'observaciones'] as $campo) {
                    if (!empty($propietario->$campo) && is_string($propietario->$campo)) {
                        $clean = $limpiarUtf8($propietario->$campo);
                        if ($clean !== $propietario->$campo) {
                            $update[$campo] = $clean;
                        }
                    }
                }
                if (!empty($update)) {
                    DB::table('propietarios')->where('id', $propietario->id)->update($update);
                    $countPropietarios++;
                }
            }
            $this->info("✅ Propietarios limpiados: {$countPropietarios}");
            $this->newLine();

            // Limpiar categorías
            $this->info('Limpiando categorías...');
            $categoriasIngresos = DB::table('categorias_ingresos')->get(['id', 'nombre', 'descripcion']);
            $countCategorias = 0;

            foreach ($categoriasIngresos as $categoria) {
                $update = [];
                foreach (['nombre', 'descripcion'] as $campo) {
                    if (!empty($categoria->$campo) && is_string($categoria->$campo)) {
                        $clean = $limpiarUtf8($categoria->$campo);
                        if ($clean !== $categoria->$campo) {
                            $update[$campo] = $clean;
                        }
                    }
                }
                if (!empty($update)) {
                    DB::table('categorias_ingresos')->where('id', $categoria->id)->update($update);
                    $countCategorias++;
                }
            }
            $this->info("✅ Categorías limpiadas: {$countCategorias}");
            $this->newLine();

            // Limpiar configuración
            $config = DB::table('configuracion_edificio')->first();
            if ($config) {
                $this->info('Limpiando configuración del edificio...');
                $update = [];
                foreach (['nombre_edificio', 'direccion', 'email', 'informacion_adicional'] as $campo) {
                    if (!empty($config->$campo) && is_string($config->$campo)) {
                        $clean = $limpiarUtf8($config->$campo);
                        if ($clean !== $config->$campo) {
                            $update[$campo] = $clean;
                        }
                    }
                }
                if (!empty($update)) {
                    DB::table('configuracion_edificio')->where('id', $config->id)->update($update);
                    $this->info("✅ Configuración limpiada");
                } else {
                    $this->info("ℹ️  Configuración no requiere limpieza");
                }
                $this->newLine();
            }

            $total = $countIngresos + $countEgresos + $countPropietarios + $countCategorias;
            $this->newLine();
            $this->info("=== LIMPIEZA COMPLETADA ===");
            $this->info("Total registros procesados: {$total}");
            $this->info("✅ Recarga la página para aplicar los cambios");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
