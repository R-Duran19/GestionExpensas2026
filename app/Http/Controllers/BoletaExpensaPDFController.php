<?php

namespace App\Http\Controllers;

use App\Models\Propietario;
use App\Models\PeriodoExpensa;
use App\Services\GenerarBoletaExpensaPDFService;
use Illuminate\Support\Facades\Log;
use Exception;

class BoletaExpensaPDFController extends Controller
{
    protected GenerarBoletaExpensaPDFService $pdfService;

    public function __construct(GenerarBoletaExpensaPDFService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Generar y mostrar PDF de boleta de expensa
     *
     * @param PeriodoExpensa $periodo
     * @param Propietario $propietario
     * @return mixed
     */
    public function mostrar(PeriodoExpensa $periodo, Propietario $propietario)
    {
        try {
            return $this->pdfService->generar($propietario, $periodo, 'stream');

        } catch (Exception $e) {
            Log::error('Error al mostrar PDF de boleta de expensa', [
                'propietario_id' => $propietario->id,
                'periodo_id' => $periodo->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error al generar el PDF: ' . $e->getMessage());
        }
    }

    /**
     * Generar y descargar PDF de boleta de expensa
     *
     * @param PeriodoExpensa $periodo
     * @param Propietario $propietario
     * @return mixed
     */
    public function descargar(PeriodoExpensa $periodo, Propietario $propietario)
    {
        try {
            return $this->pdfService->generar($propietario, $periodo, 'descarga');

        } catch (Exception $e) {
            Log::error('Error al descargar PDF de boleta de expensa', [
                'propietario_id' => $propietario->id,
                'periodo_id' => $periodo->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error al descargar el PDF: ' . $e->getMessage());
        }
    }
}
