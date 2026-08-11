<?php

namespace App\Services;

use App\Models\ConfiguracionEdificio;
use App\Models\PeriodoExpensa;
use App\Models\Unidad;
use App\Models\Expensa;
use App\Models\LecturaMedidor;
use App\Enums\CategoriaUnidad;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Exception;

class GenerarBoletasImprimirPDFService
{
    protected object $configuracion;

    public function __construct()
    {
        $this->configuracion = ConfiguracionEdificio::obtenerConfiguracion() ?? $this->getConfiguracionDefault();
    }

    /**
     * Generar PDF consolidado con una boleta por unidad seleccionada.
     *
     * @param array $unidadIds  IDs de unidades seleccionadas
     * @param PeriodoExpensa $periodo
     * @param string $tipo  'stream' | 'descarga'
     * @return mixed
     */
    public function generar(array $unidadIds, PeriodoExpensa $periodo, string $tipo = 'stream')
    {
        try {
            $unidadIds = array_values(array_unique(array_filter($unidadIds, fn($id) => !is_null($id))));

            if (empty($unidadIds)) {
                throw new Exception('No se seleccionaron unidades.');
            }

            // Cargar unidades con relaciones (1 query)
            $unidades = Unidad::with(['propietarioActual', 'inquilinoActual'])
                ->whereIn('id', $unidadIds)
                ->orderByRaw(Unidad::ordenPorTipoUnidadRaw('tipo_unidad'))
                ->orderByRaw(Unidad::ordenPorCodigoRaw('codigo', 'ASC'))
                ->get();

            if ($unidades->isEmpty()) {
                throw new Exception('No se encontraron las unidades seleccionadas.');
            }

            // Expensas del período para estas unidades (1 query)
            $expensas = Expensa::where('periodo_id', $periodo->id)
                ->whereIn('unidad_id', $unidadIds)
                ->get()
                ->keyBy('unidad_id');

            // Lecturas de medidor del período (1 query, solo si hay unidades con medidor)
            $lecturas = collect();
            if ($unidades->where('tiene_medidor', true)->isNotEmpty()) {
                $lecturas = LecturaMedidor::where('periodo_id', $periodo->id)
                    ->whereIn('unidad_id', $unidadIds)
                    ->get()
                    ->keyBy('unidad_id');
            }

            // Armar items para la vista
            $items = [];
            foreach ($unidades as $unidad) {
                $expensa = $expensas->get($unidad->id);
                $lectura = $lecturas->get($unidad->id);

                $factorAgua = 0;
                if ($expensa && $expensa->monto_agua > 0 && $unidad->tiene_medidor && $lectura) {
                    $factorAgua = $unidad->categoria_unidad === CategoriaUnidad::DOMICILIARIA
                        ? $periodo->factor_domiciliario
                        : $periodo->factor_comercial;
                }

                // Responsable de pago: inquilino si tiene, si no propietario
                $responsable = $unidad->inquilinoActual ?: $unidad->propietarioActual;
                $etiqueta = $unidad->inquilinoActual ? 'INQUILINO' : 'COPROPIETARIO';

                $items[] = [
                    'unidad' => $unidad,
                    'expensa' => $expensa,
                    'lectura' => $lectura,
                    'factor_agua' => $factorAgua,
                    'responsable' => $responsable,
                    'etiqueta_responsable' => $etiqueta,
                ];
            }

            $datos = [
                'configuracion' => $this->configuracion,
                'periodo' => $periodo,
                'items' => $items,
                'fecha_generacion' => now()->format('d/m/Y H:i'),
            ];

            $pdf = PDF::loadView('expensas.pdf.boletas-para-imprimir', $datos);
            $pdf->setPaper('letter', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'DejaVu Sans',
                'enable_font_subsetting' => true,
            ]);

            $nombreArchivo = $this->generarNombreArchivo($periodo, count($items));

            return match ($tipo) {
                'descarga' => $pdf->download($nombreArchivo),
                default => $pdf->stream($nombreArchivo),
            };

        } catch (Exception $e) {
            Log::error('Error al generar PDF de boletas para imprimir', [
                'unidad_ids' => $unidadIds,
                'periodo_id' => $periodo->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function generarNombreArchivo(PeriodoExpensa $periodo, int $cantidad): string
    {
        $periodoNombre = str_replace([' ', '/'], '_', $periodo->nombre_periodo);
        return "BOLETAS_IMPRIMIR_{$cantidad}_unidades_{$periodoNombre}.pdf";
    }

    protected function getConfiguracionDefault(): object
    {
        return (object)[
            'nombre_edificio' => config('app.name', 'Edificio'),
            'direccion' => 'Dirección no configurada',
            'telefono' => '',
            'email' => '',
            'cuit' => '',
            'ciudad' => '',
            'provincia' => '',
            'codigo_postal' => '',
            'logo_url' => null,
            'informacion_adicional' => '',
        ];
    }
}
