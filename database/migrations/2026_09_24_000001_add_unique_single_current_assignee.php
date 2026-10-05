<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * [REMEDIACIÓN-2.4] Garantía a nivel de esquema: UN SOLO dueño/inquilino
 * actual por unidad.
 *
 * El servicio hacía check-then-insert sin lock y la rama de reactivación
 * dejaba dos filas con es_*_actual=true. MySQL/MariaDB no tienen índices
 * únicos parciales nativos, así que se emula con una columna GENERADA que
 * vale unidad_id sólo cuando el flag está activo (NULL en caso contrario,
 * y los NULL no colisionan en índices únicos).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------- propietario_unidad ----------
        Schema::table('propietario_unidad', function (Blueprint $table) {
            $table->unsignedBigInteger('unidad_actual_key')
                ->nullable()
                ->storedAs('CASE WHEN es_propietario_actual = 1 THEN unidad_id END');
        });

        try {
            Schema::table('propietario_unidad', function (Blueprint $table) {
                $table->unique('unidad_actual_key', 'uniq_prop_unidad_un_solo_actual');
            });
        } catch (\Throwable $e) {
            // Datos existentes con doble dueño actual: abortar con guía clara
            // en vez de dejar la migración a medias.
            Schema::table('propietario_unidad', function (Blueprint $table) {
                $table->dropColumn('unidad_actual_key');
            });

            $duplicados = DB::table('propietario_unidad')
                ->select('unidad_id', DB::raw('COUNT(*) AS total'))
                ->where('es_propietario_actual', 1)
                ->groupBy('unidad_id')
                ->having('total', '>', 1)
                ->pluck('unidad_id')
                ->implode(', ');

            throw new \RuntimeException(
                "propietario_unidad tiene unidades con MÁS DE UN propietario actual (unidad_id: {$duplicados}). " .
                'Corrige esos registros (es_propietario_actual=0 para todos menos uno) y vuelve a ejecutar la migración.'
            );
        }

        // ---------- inquilino_unidad ----------
        Schema::table('inquilino_unidad', function (Blueprint $table) {
            $table->unsignedBigInteger('unidad_actual_key')
                ->nullable()
                ->storedAs('CASE WHEN es_inquilino_actual = 1 THEN unidad_id END');
        });

        try {
            Schema::table('inquilino_unidad', function (Blueprint $table) {
                $table->unique('unidad_actual_key', 'uniq_inq_unidad_un_solo_actual');
            });
        } catch (\Throwable $e) {
            Schema::table('inquilino_unidad', function (Blueprint $table) {
                $table->dropColumn('unidad_actual_key');
            });

            $duplicados = DB::table('inquilino_unidad')
                ->select('unidad_id', DB::raw('COUNT(*) AS total'))
                ->where('es_inquilino_actual', 1)
                ->groupBy('unidad_id')
                ->having('total', '>', 1)
                ->pluck('unidad_id')
                ->implode(', ');

            throw new \RuntimeException(
                "inquilino_unidad tiene unidades con MÁS DE UN inquilino actual (unidad_id: {$duplicados}). " .
                'Corrige esos registros (es_inquilino_actual=0 para todos menos uno) y vuelve a ejecutar la migración.'
            );
        }
    }

    public function down(): void
    {
        Schema::table('propietario_unidad', function (Blueprint $table) {
            $table->dropUnique('uniq_prop_unidad_un_solo_actual');
            $table->dropColumn('unidad_actual_key');
        });

        Schema::table('inquilino_unidad', function (Blueprint $table) {
            $table->dropUnique('uniq_inq_unidad_un_solo_actual');
            $table->dropColumn('unidad_actual_key');
        });
    }
};
