<?php

namespace App\Http\Controllers;

use App\Exports\PropietariosUnidadesExport;
use App\Models\Propietario;
use App\Models\Unidad;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PropietarioExportController extends Controller
{
    /**
     * Exportar listado de propietarios con sus unidades e inquilinos actuales a Excel.
     *
     * Estructura:
     *   - Una fila por cada unidad actual del propietario.
     *   - El nombre del propietario se muestra solo en la primera fila de su grupo.
     *   - La columna INQUILINO muestra "-" si la unidad no tiene inquilino actual.
     */
    public function unidadesConInquilinos(Request $request)
    {
        $propietarios = Propietario::with(['unidadesActuales' => function ($q) {
            $q->orderByRaw(Unidad::ordenPorCodigoRaw('unidades.codigo', 'ASC'))
              ->with('inquilinoActual');
        }])
            ->whereHas('unidadesActuales')
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->get();

        $export = new PropietariosUnidadesExport($propietarios);

        $fecha = now()->format('Y-m-d_H-i');
        $nombreArchivo = "PROPIETARIOS_UNIDADES_{$fecha}.xlsx";

        return Excel::download($export, $nombreArchivo);
    }
}
