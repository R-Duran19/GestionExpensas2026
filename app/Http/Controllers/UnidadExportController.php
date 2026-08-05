<?php

namespace App\Http\Controllers;

use App\Enums\CategoriaUnidad;
use App\Exports\UnidadesProrrateoExport;
use App\Models\Unidad;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class UnidadExportController extends Controller
{
    /**
     * Exportar listado de unidades con su cálculo de prorrateo a Excel.
     */
    public function prorrateo(Request $request)
    {
        $ordenTipos = [
            'parqueo' => 1,
            'baulera' => 2,
            'local' => 3,
            'oficina' => 4,
            'departamento' => 5,
        ];

        $ordenCases = implode(' ', array_map(
            fn ($tipo, $i) => "WHEN '{$tipo}' THEN {$i}",
            array_keys($ordenTipos),
            range(1, count($ordenTipos))
        ));

        $unidades = Unidad::with('propietarioActual')
            ->selectRaw("*, CASE tipo_unidad {$ordenCases} END AS orden_tipo")
            ->orderBy('orden_tipo')
            ->orderByRaw(Unidad::ordenPorCodigoRaw('codigo', 'ASC'))
            ->get();

        $factorDomiciliario = CategoriaUnidad::DOMICILIARIA->getFactorProrrateo();
        $factorComercial = CategoriaUnidad::COMERCIAL->getFactorProrrateo();

        $export = new UnidadesProrrateoExport(
            $unidades,
            $factorDomiciliario,
            $factorComercial,
        );

        $fecha = now()->format('Y-m-d_H-i');
        $nombreArchivo = "PRORRATEO_UNIDADES_{$fecha}.xlsx";

        return Excel::download($export, $nombreArchivo);
    }
}
