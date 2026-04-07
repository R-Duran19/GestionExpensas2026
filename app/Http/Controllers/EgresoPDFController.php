<?php

namespace App\Http\Controllers;

use App\Models\Egreso;
use App\Services\GenerarEgresoPDFService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class EgresoPDFController extends Controller
{
    protected GenerarEgresoPDFService $pdfService;

    public function __construct(GenerarEgresoPDFService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Generar y mostrar PDF de un egreso
     *
     * @param Egreso $egreso
     * @return mixed
     */
    public function mostrar(Egreso $egreso)
    {
        try {
            return $this->pdfService->generar($egreso, 'stream');

        } catch (Exception $e) {
            Log::error('Error al mostrar PDF de egreso', [
                'egreso_id' => $egreso->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error al generar el PDF: ' . $e->getMessage());
        }
    }

    /**
     * Generar y descargar PDF de un egreso
     *
     * @param Egreso $egreso
     * @return mixed
     */
    public function descargar(Egreso $egreso)
    {
        try {
            return $this->pdfService->generar($egreso, 'descarga');

        } catch (Exception $e) {
            Log::error('Error al descargar PDF de egreso', [
                'egreso_id' => $egreso->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error al descargar el PDF: ' . $e->getMessage());
        }
    }

    /**
     * Generar y guardar PDF de un egreso
     *
     * @param Egreso $egreso
     * @return \Illuminate\Http\JsonResponse
     */
    public function guardar(Egreso $egreso)
    {
        try {
            $ruta = $this->pdfService->generar($egreso, 'guardar');

            return response()->json([
                'success' => true,
                'message' => 'PDF guardado exitosamente',
                'ruta' => $ruta,
            ]);

        } catch (Exception $e) {
            Log::error('Error al guardar PDF de egreso', [
                'egreso_id' => $egreso->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generar PDF de múltiples egresos
     *
     * @param Request $request
     * @return mixed
     */
    public function multiple(Request $request)
    {
        try {
            $egresosIds = $request->input('egresos_ids', []);

            if (empty($egresosIds)) {
                return back()->with('error', 'No se seleccionaron egresos');
            }

            $egresos = Egreso::with(['categoria', 'creadoPor'])
                ->whereIn('id', $egresosIds)
                ->get();

            if ($egresos->isEmpty()) {
                return back()->with('error', 'No se encontraron egresos válidos');
            }

            return $this->pdfService->generarMultiple($egresos->toArray());

        } catch (Exception $e) {
            Log::error('Error al generar PDF múltiple', [
                'egresos_ids' => $request->input('egresos_ids', []),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error al generar el PDF múltiple: ' . $e->getMessage());
        }
    }
}
