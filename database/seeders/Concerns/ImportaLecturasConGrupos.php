<?php

namespace Database\Seeders\Concerns;

use App\Models\LecturaGrupoPeriodo;
use App\Models\LecturaMedidor;
use App\Models\MedidorGrupo;
use App\Models\Unidad;
use Illuminate\Support\Facades\DB;

/**
 * Trait ImportaLecturasConGrupos
 *
 * Reemplaza la importación naive de lecturas desde JSON por una versión
 * que reconoce unidades que pertenecen a un grupo de medidor compartido.
 *
 * - Unidades individuales: se importan como antes (lectura + consumo propios).
 * - Unidades en grupo: se procesan por grupo, creando 1 registro en
 *   lectura_grupos_periodos (con la lectura real del medidor físico) y
 *   N filas en lecturas_medidor (una por unidad) con consumo distribuido
 *   equitativamente y medidor_grupo_id seteado.
 *
 * El item del JSON que aporta los datos del medidor físico del grupo es
 * el primero que tenga lectura_actual no null (ej: LOC-14 para el grupo
 * "LOCALES 14-19", LOC-2 para "LOCALES 2-7-8-11"). Los demás items del
 * grupo suelen venir con null y nota "medidor combinado".
 */
trait ImportaLecturasConGrupos
{
    /**
     * Mapeo de códigos especiales del JSON a códigos de BD.
     * LOC-12-13 → LOC-12 (individual; no existe LOC-13 en BD).
     * PARQ-128 → null (parqueo sin lectura de agua).
     */
    protected function mapearCodigoUnidad(string $codigoJson): ?string
    {
        $mapeos = [
            'LOC-12-13' => 'LOC-12',
            'DEP-25-C' => 'DEP-25-C-T',
            'PARQ-128' => null,
            'PARQUEO-128' => null,
        ];

        return $mapeos[$codigoJson] ?? $codigoJson;
    }

    /**
     * Importa lecturas desde un JSON respetando los grupos de medidor.
     *
     * @param int    $periodoId  ID del período
     * @param int    $adminUserId ID del usuario admin
     * @param string $jsonPath   Ruta absoluta al JSON de lecturas
     * @param object $command    Instancia de Command para info/warn (opcional)
     * @return array{importadas: int, grupos: int, no_encontradas: string[]}
     */
    protected function importarLecturasConGrupos(
        int $periodoId,
        int $adminUserId,
        string $jsonPath,
        $command = null
    ): array {
        if (!file_exists($jsonPath)) {
            if ($command) {
                $command->warn("  ⚠️ No se encontró archivo: {$jsonPath}");
            }
            return ['importadas' => 0, 'grupos' => 0, 'no_encontradas' => []];
        }

        $jsonData = json_decode(file_get_contents($jsonPath), true);
        $lecturas = $jsonData['lecturas'] ?? [];

        if ($command) {
            $command->info("  ✓ Lecturas a procesar: " . count($lecturas) . " items");
        }

        // Precargar grupos activos con sus unidades (clave: codigo_unidad)
        $gruposActivos = MedidorGrupo::with('unidades')
            ->where('activo', true)
            ->get();

        // Map codigo_unidad (BD) => ['grupo_id' => X, 'unidades_ids' => [...]]
        $unidadesEnGrupo = [];
        $gruposPorId = [];
        foreach ($gruposActivos as $grupo) {
            $gruposPorId[$grupo->id] = $grupo;
            foreach ($grupo->unidades as $u) {
                $unidadesEnGrupo[$u->codigo] = $grupo->id;
            }
        }

        $totalImportadas = 0;
        $totalGrupos = 0;
        $unidadesNoEncontradas = [];

        // Datos del medidor físico por grupo_id (el primer item con datos)
        // Estructura: [grupo_id => ['lectura_anterior' => X, 'lectura_actual' => Y, 'consumo_total' => Z]]
        $datosGrupos = [];
        // Items individuales a procesar al final
        $itemsIndividuales = [];
        // Items cuya unidad NO está en BD (para reportería)
        $itemsGrupoSinDatos = [];

        foreach ($lecturas as $item) {
            $codigoJson = $item['codigo_unidad'];
            $codigoBD = $this->mapearCodigoUnidad($codigoJson);

            if ($codigoBD === null) {
                continue; // Parqueo sin lectura, ignorar
            }

            $unidad = Unidad::where('codigo', $codigoBD)->first();

            if (!$unidad) {
                $unidadesNoEncontradas[] = $codigoBD;
                continue;
            }

            // ¿La unidad pertenece a un grupo?
            $grupoId = $unidadesEnGrupo[$codigoBD] ?? null;

            if ($grupoId === null) {
                // Individual: procesar directo
                $itemsIndividuales[] = [
                    'unidad' => $unidad,
                    'item' => $item,
                ];
                $totalImportadas++;
            } else {
                // De grupo: solo registramos los datos del medidor físico
                // si este item los trae (lectura_actual no null). Los
                // registros por unidad se crean después.
                $tieneDatos = $item['lectura_actual'] !== null
                    || (isset($item['consumo_m3']) && $item['consumo_m3'] !== null && $item['consumo_m3'] > 0);

                if ($tieneDatos && !isset($datosGrupos[$grupoId])) {
                    $datosGrupos[$grupoId] = [
                        'lectura_anterior' => $item['lectura_anterior'],
                        'lectura_actual' => $item['lectura_actual'],
                        'consumo_total' => $item['consumo_m3'] ?? 0,
                    ];
                }
                $itemsGrupoSinDatos[] = $codigoBD;
            }
        }

        // 1) Procesar items individuales
        foreach ($itemsIndividuales as $entry) {
            $unidad = $entry['unidad'];
            $item = $entry['item'];

            LecturaMedidor::updateOrCreate(
                [
                    'periodo_id' => $periodoId,
                    'unidad_id' => $unidad->id,
                ],
                [
                    'lectura_anterior' => $item['lectura_anterior'] ?? null,
                    'lectura_actual' => $item['lectura_actual'] ?? null,
                    'consumo_m3' => $item['consumo_m3'] ?? 0,
                    'medidor_grupo_id' => null,
                    'registrado_por' => $adminUserId,
                ]
            );
        }

        // 2) Procesar cada grupo activo (tenga o no datos en este JSON)
        foreach ($gruposPorId as $grupoId => $grupo) {
            $unidadesGrupo = $grupo->unidades;
            if ($unidadesGrupo->isEmpty()) {
                if ($command) {
                    $command->warn("  ⚠️ Grupo '{$grupo->nombre}' sin unidades activas, saltando.");
                }
                continue;
            }

            $datos = $datosGrupos[$grupoId] ?? null;

            // Si no se encontraron datos del medidor en el JSON, usar 0
            // (mantiene compatibilidad con períodos donde el JSON está truncado)
            $lecturaAnterior = $datos['lectura_anterior'] ?? 0;
            $lecturaActual = $datos['lectura_actual'] ?? 0;
            $consumoTotal = $datos['consumo_total'] ?? 0;

            // Crear/actualizar el registro del grupo en lectura_grupos_periodos
            LecturaGrupoPeriodo::updateOrCreate(
                [
                    'periodo_id' => $periodoId,
                    'medidor_grupo_id' => $grupoId,
                ],
                [
                    'lectura_anterior' => $lecturaAnterior,
                    'lectura_actual' => $lecturaActual,
                    'consumo_total' => $consumoTotal,
                    'observaciones' => $datos === null
                        ? 'Sin datos en JSON fuente; valores en 0'
                        : null,
                    'registrado_por' => $adminUserId,
                ]
            );

            // Crear/actualizar N filas en lecturas_medidor (una por unidad)
            // con consumo equitativo y medidor_grupo_id seteado.
            $n = $unidadesGrupo->count();
            $consumoPorUnidad = $n > 0
                ? round($consumoTotal / $n, 2)
                : 0;

            // Ajustar residuo a la última unidad (suma exacta)
            $ajuste = round($consumoTotal - ($consumoPorUnidad * $n), 2);

            $ultimaUnidad = $unidadesGrupo->last();
            foreach ($unidadesGrupo as $unidad) {
                $consumoAsignado = $consumoPorUnidad;
                if ($unidad->id === $ultimaUnidad->id) {
                    $consumoAsignado = round($consumoPorUnidad + $ajuste, 2);
                }

                LecturaMedidor::updateOrCreate(
                    [
                        'periodo_id' => $periodoId,
                        'unidad_id' => $unidad->id,
                    ],
                    [
                        'medidor_grupo_id' => $grupoId,
                        'lectura_anterior' => null,
                        'lectura_actual' => null,
                        'consumo_m3' => max(0, $consumoAsignado),
                        'observaciones' => "Consumo distribuido del grupo '{$grupo->nombre}' (1/{$n})",
                        'registrado_por' => $adminUserId,
                    ]
                );
            }

            $totalGrupos++;
            $totalImportadas += $n;

            if ($command) {
                $command->info("    ↳ Grupo '{$grupo->nombre}': lectura {$lecturaAnterior} → {$lecturaActual} ({$consumoTotal} m³ ÷ {$n} = {$consumoPorUnidad} c/u)");
            }
        }

        if (!empty($unidadesNoEncontradas) && $command) {
            $command->warn("  ⚠️ Unidades no encontradas: " . implode(', ', array_unique($unidadesNoEncontradas)));
        }

        if ($command) {
            $command->info("  ✓ Lecturas importadas: {$totalImportadas} (individuales + grupos)");
            $command->info("  ✓ Grupos procesados: {$totalGrupos}");
        }

        return [
            'importadas' => $totalImportadas,
            'grupos' => $totalGrupos,
            'no_encontradas' => array_unique($unidadesNoEncontradas),
        ];
    }
}
