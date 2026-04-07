<?php

/**
 * Script de limpieza UTF-8 para la base de datos
 * Ejecutar: php limpiar_bd_utf8.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

echo "=== LIMPIEZA UTF-8 DE BASE DE DATOS ===\n\n";

try {
    // Conectar a la base de datos
    $app = require_once __DIR__ . '/bootstrap/app.php';

    // Función para limpiar strings UTF-8
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
        $replacements = [
            "\xC2\xAB" => '"', "\xC2\xBB" => '"',
            "\xC2\x84" => '"', "\xC2\x85" => '"',
            "\xE2\x80\x98" => "'", "\xE2\x80\x99" => "'",
            "\xE2\x80\x9C" => '"', "\xE2\x80\x9D" => '"',
            "\xE2\x80\x93" => '-', "\xC2\xA0" => ' ',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $string);
    };

    // Limpiar tabla de ingresos
    echo "Limpiando tabla ingresos...\n";
    $ingresos = DB::table('ingresos')->get();
    $countIngresos = 0;

    foreach ($ingresos as $ingreso) {
        $update = [];

        if (!empty($ingreso->descripcion) && is_string($ingreso->descripcion)) {
            $clean = $limpiarUtf8($ingreso->descripcion);
            if ($clean !== $ingreso->descripcion) {
                $update['descripcion'] = $clean;
            }
        }

        if (!empty($ingreso->observaciones) && is_string($ingreso->observaciones)) {
            $clean = $limpiarUtf8($ingreso->observaciones);
            if ($clean !== $ingreso->observaciones) {
                $update['observaciones'] = $clean;
            }
        }

        if (!empty($ingreso->referencia_bancaria) && is_string($ingreso->referencia_bancaria)) {
            $clean = $limpiarUtf8($ingreso->referencia_bancaria);
            if ($clean !== $ingreso->referencia_bancaria) {
                $update['referencia_bancaria'] = $clean;
            }
        }

        if (!empty($update)) {
            DB::table('ingresos')->where('id', $ingreso->id)->update($update);
            $countIngresos++;
        }
    }

    echo "✅ Ingresos limpiados: {$countIngresos}\n";

    // Limpiar tabla de egresos
    echo "\nLimpiando tabla egresos...\n";
    $egresos = DB::table('egresos')->get();
    $countEgresos = 0;

    foreach ($egresos as $egreso) {
        $update = [];

        if (!empty($egreso->concepto) && is_string($egreso->concepto)) {
            $clean = $limpiarUtf8($egreso->concepto);
            if ($clean !== $egreso->concepto) {
                $update['concepto'] = $clean;
            }
        }

        if (!empty($egreso->descripcion) && is_string($egreso->descripcion)) {
            $clean = $limpiarUtf8($egreso->descripcion);
            if ($clean !== $egreso->descripcion) {
                $update['descripcion'] = $clean;
            }
        }

        if (!empty($egreso->observaciones) && is_string($egreso->observaciones)) {
            $clean = $limpiarUtf8($egreso->observaciones);
            if ($clean !== $egreso->observaciones) {
                $update['observaciones'] = $clean;
            }
        }

        if (!empty($egreso->proveedor) && is_string($egreso->proveedor)) {
            $clean = $limpiarUtf8($egreso->proveedor);
            if ($clean !== $egreso->proveedor) {
                $update['proveedor'] = $clean;
            }
        }

        if (!empty($update)) {
            DB::table('egresos')->where('id', $egreso->id)->update($update);
            $countEgresos++;
        }
    }

    echo "✅ Egresos limpiados: {$countEgresos}\n";

    // Limpiar tabla de propietarios
    echo "\nLimpiando tabla propietarios...\n";
    $propietarios = DB::table('propietarios')->get();
    $countPropietarios = 0;

    foreach ($propietarios as $propietario) {
        $update = [];

        if (!empty($propietario->nombre) && is_string($propietario->nombre)) {
            $clean = $limpiarUtf8($propietario->nombre);
            if ($clean !== $propietario->nombre) {
                $update['nombre'] = $clean;
            }
        }

        if (!empty($propietario->apellido) && is_string($propietario->apellido)) {
            $clean = $limpiarUtf8($propietario->apellido);
            if ($clean !== $propietario->apellido) {
                $update['apellido'] = $clean;
            }
        }

        if (!empty($propietario->email) && is_string($propietario->email)) {
            $clean = $limpiarUtf8($propietario->email);
            if ($clean !== $propietario->email) {
                $update['email'] = $clean;
            }
        }

        if (!empty($propietario->observaciones) && is_string($propietario->observaciones)) {
            $clean = $limpiarUtf8($propietario->observaciones);
            if ($clean !== $propietario->observaciones) {
                $update['observaciones'] = $clean;
            }
        }

        if (!empty($update)) {
            DB::table('propietarios')->where('id', $propietario->id)->update($update);
            $countPropietarios++;
        }
    }

    echo "✅ Propietarios limpiados: {$countPropietarios}\n";

    // Limpiar categorías
    echo "\nLimpiando categorías...\n";
    $categoriasIngresos = DB::table('categorias_ingresos')->get();
    $countCategorias = 0;

    foreach ($categoriasIngresos as $categoria) {
        $update = [];

        if (!empty($categoria->nombre) && is_string($categoria->nombre)) {
            $clean = $limpiarUtf8($categoria->nombre);
            if ($clean !== $categoria->nombre) {
                $update['nombre'] = $clean;
            }
        }

        if (!empty($categoria->descripcion) && is_string($categoria->descripcion)) {
            $clean = $limpiarUtf8($categoria->descripcion);
            if ($clean !== $categoria->descripcion) {
                $update['descripcion'] = $clean;
            }
        }

        if (!empty($update)) {
            DB::table('categorias_ingresos')->where('id', $categoria->id)->update($update);
            $countCategorias++;
        }
    }

    echo "✅ Categorías limpiadas: {$countCategorias}\n";

    // Limpiar configuración
    echo "\nLimpiando configuración...\n";
    $config = DB::table('configuracion_edificio')->first();

    if ($config) {
        $update = [];

        foreach (['nombre_edificio', 'direccion', 'email', 'informacion_adicional', 'ciudad', 'provincia'] as $campo) {
            if (!empty($config->$campo) && is_string($config->$campo)) {
                $clean = $limpiarUtf8($config->$campo);
                if ($clean !== $config->$campo) {
                    $update[$campo] = $clean;
                }
            }
        }

        if (!empty($update)) {
            DB::table('configuracion_edificio')->where('id', $config->id)->update($update);
            echo "✅ Configuración limpiada\n";
        } else {
            echo "ℹ️  Configuración no requiere limpieza\n";
        }
    }

    echo "\n=== LIMPIEZA COMPLETADA ===\n";
    echo "Total registros procesados: " . ($countIngresos + $countEgresos + $countPropietarios + $countCategorias) . "\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
