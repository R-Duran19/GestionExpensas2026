<?php

namespace App\Http\Controllers;

use App\Models\PeriodoExpensa;
use App\Services\GenerarBoletasImprimirPDFService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class BoletasImprimirController extends Controller
{
    protected GenerarBoletasImprimirPDFService $pdfService;

    public function __construct(GenerarBoletasImprimirPDFService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Mostrar PDF consolidado con las boletas de las unidades seleccionadas.
     *
     * GET /expensas/boletas-imprimir/{periodo}?unidades=1,2,3
     *
     * @param Request $request
     * @param PeriodoExpensa $periodo
     * @return mixed
     */
    public function mostrar(Request $request, PeriodoExpensa $periodo)
    {
        return $this->generar($request, $periodo, 'stream');
    }

    /**
     * Descargar PDF consolidado con las boletas de las unidades seleccionadas.
     *
     * GET /expensas/boletas-imprimir/{periodo}/descargar?unidades=1,2,3
     */
    public function descargar(Request $request, PeriodoExpensa $periodo)
    {
        return $this->generar($request, $periodo, 'descarga');
    }

    protected function generar(Request $request, PeriodoExpensa $periodo, string $tipo)
    {
        try {
            $unidadesParam = $request->input('unidades', '');

            // Acepta "1,2,3" o "1|2|3" o array
            if (is_array($unidadesParam)) {
                $unidadIds = $unidadesParam;
            } else {
                $unidadIds = array_filter(explode(',', (string) $unidadesParam), fn($v) => trim($v) !== '');
            }

            $unidadIds = array_map(fn($id) => (int) trim($id), $unidadIds);
            $unidadIds = array_values(array_unique($unidadIds));

            if (empty($unidadIds)) {
                return back()->with('error', 'No se seleccionaron unidades.');
            }

            if (count($unidadIds) > 200) {
                return back()->with('error', 'Demasiadas unidades seleccionadas (máximo 200). Seleccioná menos unidades.');
            }

            return $this->pdfService->generar($unidadIds, $periodo, $tipo);

        } catch (Exception $e) {
            Log::error('Error al generar PDF de boletas para imprimir', [
                'periodo_id' => $periodo->id,
                'unidades' => $request->input('unidades'),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error al generar el PDF: ' . $e->getMessage());
        }
    }
}
