<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Ingreso;
use App\Models\PeriodoExpensa;
use App\Services\GenerarBoletaExpensaPDFService;
use App\Services\GenerarReciboPDFService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * [PORTAL / Fase 2.5] PDFs del portal con verificación de PROPIEDAD.
 *
 * A diferencia de los controllers admin (que solo exigen rol admin), aquí el
 * usuario del portal SOLO puede descargar sus propios documentos:
 * - Boleta: GenerarBoletaExpensaPDFService ya aplica la regla maestra sobre
 *   las unidades del responsable; se verifica que el user sea ese responsable.
 * - Recibo: se verifica que el ingreso tenga pagador_tipo/pagador_id = user.
 */
class PortalPdfController extends Controller
{
    public function __construct(
        protected GenerarBoletaExpensaPDFService $boletaService,
        protected GenerarReciboPDFService $reciboService,
    ) {}

    /**
     * Boleta de expensas del propio responsable para un período.
     */
    public function boleta(Request $request, int $periodoId)
    {
        $user = $request->user();
        $persona = $user->persona;

        if (!$persona) {
            abort(403);
        }

        $periodo = PeriodoExpensa::findOrFail($periodoId);

        try {
            // El service aplica la regla maestra sobre las unidades de $persona
            return $this->boletaService->generar(
                $persona,
                $user->tipo,
                $periodo,
                'stream'
            );
        } catch (Exception $e) {
            Log::error('Error generando boleta del portal', [
                'user_id' => $user->id,
                'periodo_id' => $periodoId,
                'error' => $e->getMessage(),
            ]);

            abort(500, 'No se pudo generar la boleta. Intenta nuevamente.');
        }
    }

    /**
     * Recibo de un pago PROPIO.
     */
    public function recibo(Request $request, int $ingresoId)
    {
        $user = $request->user();

        $ingreso = Ingreso::where('id', $ingresoId)
            ->where('pagador_tipo', $user->tipo)
            ->where('pagador_id', $user->persona_id)
            ->firstOrFail();

        try {
            return $this->reciboService->generar($ingreso, 'stream');
        } catch (Exception $e) {
            Log::error('Error generando recibo del portal', [
                'user_id' => $user->id,
                'ingreso_id' => $ingresoId,
                'error' => $e->getMessage(),
            ]);

            abort(500, 'No se pudo generar el recibo. Intenta nuevamente.');
        }
    }
}
