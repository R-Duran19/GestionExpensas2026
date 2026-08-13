<?php

namespace App\Http\Controllers;

use App\Exports\LecturasPeriodoExport;
use App\Models\LecturaGrupoPeriodo;
use App\Models\LecturaMedidor;
use App\Models\PeriodoExpensa;
use Maatwebsite\Excel\Facades\Excel;

class LecturaPeriodoExportController extends Controller
{
    /**
     * Exportar las lecturas de un período a Excel.
     *
     * Orden: locales -> oficinas -> departamentos, y dentro de cada tipo,
     * orden natural por código (LOC-1, LOC-2, ..., LOC-10; OF-3-A, OF-3-B, OF-4-A ...).
     * Los medidores compartidos (grupos) se consolidan en una sola fila, mostrando
     * la lectura anterior/actual/consumo del grupo y los códigos de las unidades que lo integran.
     */
    public function lecturas($periodo)
    {
        $periodoModel = PeriodoExpensa::findOrFail($periodo);

        $ordenTipos = [
            'local' => 1,
            'oficina' => 2,
            'departamento' => 3,
            'parqueo' => 4,
            'baulera' => 5,
        ];

        $items = collect();

        // 1) Lecturas individuales (una fila por unidad).
        $individuales = LecturaMedidor::where('periodo_id', $periodoModel->id)
            ->whereNull('medidor_grupo_id')
            ->with(['unidad.propietarioActual'])
            ->get();

        foreach ($individuales as $lectura) {
            $unidad = $lectura->unidad;
            if (! $unidad) {
                continue;
            }

            $propietario = $unidad->propietarioActual;

            $items->push([
                'tipo_orden' => $ordenTipos[$unidad->tipo_unidad->value] ?? 99,
                'sort_codigo' => $unidad->codigo,
                'row' => [
                    'codigo' => $unidad->codigo,
                    'propietario' => trim(($propietario?->nombre ?? '').' '.($propietario?->apellido ?? '')),
                    'medidor' => 'Individual',
                    'lectura_anterior' => $lectura->lectura_anterior !== null ? (float) $lectura->lectura_anterior : null,
                    'lectura_actual' => $lectura->lectura_actual !== null ? (float) $lectura->lectura_actual : null,
                    'consumo_m3' => (float) $lectura->consumo_m3,
                ],
            ]);
        }

        // 2) Lecturas de grupos compartidos (una fila consolidada por grupo).
        $grupos = LecturaGrupoPeriodo::where('periodo_id', $periodoModel->id)
            ->with('medidorGrupo')
            ->get();

        // Miembros distribuidos en una sola consulta (evita N+1 y el bug de eager
        // loading de la relación lecturasDistribuidas, que usa $this->periodo_id).
        $miembrosPorGrupo = LecturaMedidor::where('periodo_id', $periodoModel->id)
            ->whereNotNull('medidor_grupo_id')
            ->with('unidad')
            ->get()
            ->groupBy('medidor_grupo_id');

        foreach ($grupos as $grupo) {
            $miembros = ($miembrosPorGrupo->get($grupo->medidor_grupo_id) ?? collect())
                ->filter(fn ($d) => $d->unidad)
                ->sortBy(fn ($d) => $d->unidad->codigo);

            if ($miembros->isEmpty()) {
                continue;
            }

            // Tipo de orden = el tipo "menor" entre los miembros.
            $tipoOrden = $miembros
                ->map(fn ($d) => $ordenTipos[$d->unidad->tipo_unidad->value] ?? 99)
                ->min();

            // Códigos de miembros en orden natural, para mostrar y para ordenar.
            $codigos = $miembros->map(fn ($d) => $d->unidad->codigo)->all();
            usort($codigos, 'strnatcasecmp');

            $items->push([
                'tipo_orden' => $tipoOrden,
                'sort_codigo' => $codigos[0] ?? $grupo->medidorGrupo?->nombre,
                'row' => [
                    'codigo' => implode(', ', $codigos),
                    'propietario' => 'Medidor compartido ('.count($codigos).' unidades)',
                    'medidor' => 'Grupo: '.($grupo->medidorGrupo?->nombre ?? ('#'.$grupo->medidor_grupo_id)),
                    'lectura_anterior' => $grupo->lectura_anterior !== null ? (float) $grupo->lectura_anterior : null,
                    'lectura_actual' => $grupo->lectura_actual !== null ? (float) $grupo->lectura_actual : null,
                    'consumo_m3' => (float) $grupo->consumo_total,
                ],
            ]);
        }

        // 3) Orden final: tipo (locales->oficinas->depas) y luego código natural.
        $ordenado = $items->values()->all();
        usort($ordenado, function ($a, $b) {
            if ($a['tipo_orden'] !== $b['tipo_orden']) {
                return $a['tipo_orden'] <=> $b['tipo_orden'];
            }

            return strnatcasecmp($a['sort_codigo'], $b['sort_codigo']);
        });

        $rows = collect($ordenado)->map(fn ($item) => $item['row'])->values();

        $export = new LecturasPeriodoExport($rows, $periodoModel);

        $slug = str($periodoModel->nombre_periodo)->slug('_');
        $fecha = now()->format('Y-m-d');
        $nombreArchivo = "LECTURAS_{$slug}_{$fecha}.xlsx";

        return Excel::download($export, $nombreArchivo);
    }
}
