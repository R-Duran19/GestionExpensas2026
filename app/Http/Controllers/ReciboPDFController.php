<?php

namespace App\Http\Controllers;

use App\Models\Ingreso;
use App\Services\GenerarReciboPDFService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class ReciboPDFController extends Controller
{
    protected GenerarReciboPDFService $pdfService;

    public function __construct(GenerarReciboPDFService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Generar y mostrar PDF de un recibo
     *
     * @param Ingreso $ingreso
     * @return mixed
     */
    public function mostrar(Ingreso $ingreso)
    {
        try {
            return $this->pdfService->generar($ingreso, 'stream');

        } catch (Exception $e) {
            Log::error('Error al mostrar PDF de recibo', [
                'ingreso_id' => $ingreso->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error al generar el PDF: ' . $e->getMessage());
        }
    }

    /**
     * Generar y descargar PDF de un recibo
     *
     * @param Ingreso $ingreso
     * @return mixed
     */
    public function descargar(Ingreso $ingreso)
    {
        try {
            return $this->pdfService->generar($ingreso, 'descarga');

        } catch (Exception $e) {
            Log::error('Error al descargar PDF de recibo', [
                'ingreso_id' => $ingreso->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error al descargar el PDF: ' . $e->getMessage());
        }
    }

    /**
     * Generar y guardar PDF de un recibo
     *
     * @param Ingreso $ingreso
     * @return \Illuminate\Http\JsonResponse
     */
    public function guardar(Ingreso $ingreso)
    {
        try {
            $ruta = $this->pdfService->generar($ingreso, 'guardar');

            return response()->json([
                'success' => true,
                'message' => 'PDF guardado exitosamente',
                'ruta' => $ruta,
            ]);

        } catch (Exception $e) {
            Log::error('Error al guardar PDF de recibo', [
                'ingreso_id' => $ingreso->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generar PDF de múltiples recibos
     *
     * @param Request $request
     * @return mixed
     */
    public function multiple(Request $request)
    {
        try {
            $ingresosIds = $request->input('ingresos_ids', []);

            if (empty($ingresosIds)) {
                return back()->with('error', 'No se seleccionaron recibos');
            }

            $ingresos = Ingreso::with(['expensaTotal.periodo', 'propietario', 'categoria'])
                ->whereIn('id', $ingresosIds)
                ->whereNotNull('expensa_total_id')
                ->get();

            if ($ingresos->isEmpty()) {
                return back()->with('error', 'No se encontraron recibos válidos');
            }

            return $this->pdfService->generarMultiple($ingresos->toArray());

        } catch (Exception $e) {
            Log::error('Error al generar PDF múltiple', [
                'ingresos_ids' => $request->input('ingresos_ids', []),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error al generar el PDF múltiple: ' . $e->getMessage());
        }
    }
}
