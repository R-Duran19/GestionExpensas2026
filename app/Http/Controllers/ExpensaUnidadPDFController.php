<?php

namespace App\Http\Controllers;

use App\Models\Unidad;
use App\Models\PeriodoExpensa;
use App\Services\GenerarExpensaUnidadPDFService;
use Illuminate\Support\Facades\Log;
use Exception;

class ExpensaUnidadPDFController extends Controller
{
    protected GenerarExpensaUnidadPDFService $pdfService;

    public function __construct(GenerarExpensaUnidadPDFService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Generar y mostrar PDF de expensa de unidad
     *
     * @param PeriodoExpensa $periodo
     * @param Unidad $unidad
     * @return mixed
     */
    public function mostrar(PeriodoExpensa $periodo, Unidad $unidad)
    {
        try {
            return $this->pdfService->generar($unidad, $periodo, 'stream');

        } catch (\Exception $e) {
            Log::error('Error al mostrar PDF de expensa de unidad', [
                'unidad_id' => $unidad->id,
                'periodo_id' => $periodo->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // En lugar de back(), mostrar un mensaje más claro
            return response()->json([
                'error' => 'Error al generar el PDF',
                'message' => $e->getMessage(),
                'unidad_id' => $unidad->id,
                'periodo_id' => $periodo->id,
            ], 500);
        }
    }

    /**
     * Generar y descargar PDF de expensa de unidad
     *
     * @param PeriodoExpensa $periodo
     * @param Unidad $unidad
     * @return mixed
     */
    public function descargar(PeriodoExpensa $periodo, Unidad $unidad)
    {
        try {
            return $this->pdfService->generar($unidad, $periodo, 'descarga');

        } catch (Exception $e) {
            Log::error('Error al descargar PDF de expensa de unidad', [
                'unidad_id' => $unidad->id,
                'periodo_id' => $periodo->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error al descargar el PDF: ' . $e->getMessage());
        }
    }
}
