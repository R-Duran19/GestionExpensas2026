<?php

namespace App\Http\Controllers;

use App\Models\Propietario;
use App\Models\Inquilino;
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
     * Resolver el responsable (Propietario o Inquilino) según el tipo.
     *
     * @param string $tipo  'propietario' | 'inquilino'
     * @param int $id
     * @return Propietario|Inquilino
     */
    protected function resolverResponsable(string $tipo, int $id)
    {
        if ($tipo === 'inquilino') {
            return Inquilino::findOrFail($id);
        }

        return Propietario::findOrFail($id);
    }

    /**
     * Generar y mostrar PDF de boleta de expensa
     *
     * @param PeriodoExpensa $periodo
     * @param string $tipo  'propietario' | 'inquilino'
     * @param int $responsable
     * @return mixed
     */
    public function mostrar(PeriodoExpensa $periodo, string $tipo, int $responsable)
    {
        try {
            $modelo = $this->resolverResponsable($tipo, $responsable);

            return $this->pdfService->generar($modelo, $tipo, $periodo, 'stream');

        } catch (Exception $e) {
            Log::error('Error al mostrar PDF de boleta de expensa', [
                'tipo' => $tipo,
                'responsable_id' => $responsable,
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
     * @param string $tipo  'propietario' | 'inquilino'
     * @param int $responsable
     * @return mixed
     */
    public function descargar(PeriodoExpensa $periodo, string $tipo, int $responsable)
    {
        try {
            $modelo = $this->resolverResponsable($tipo, $responsable);

            return $this->pdfService->generar($modelo, $tipo, $periodo, 'descarga');

        } catch (Exception $e) {
            Log::error('Error al descargar PDF de boleta de expensa', [
                'tipo' => $tipo,
                'responsable_id' => $responsable,
                'periodo_id' => $periodo->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error al descargar el PDF: ' . $e->getMessage());
        }
    }
}
