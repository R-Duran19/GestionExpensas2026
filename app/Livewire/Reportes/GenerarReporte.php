<?php

namespace App\Livewire\Reportes;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\ReporteService;
use Carbon\Carbon;
use Exception;

class GenerarReporte extends Component
{
    public $mostrarModal = false;
    public $tipoReporte = 'ingresos'; // 'ingresos', 'egresos', 'combinado'
    public $fecha_inicio;
    public $fecha_fin;
    public $formato = 'pdf'; // 'pdf', 'excel'

    protected $rules = [
        'fecha_inicio' => 'required|date|before_or_equal:fecha_fin',
        'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        'formato' => 'required|in:pdf,excel',
    ];

    protected $messages = [
        'fecha_inicio.before_or_equal' => 'La fecha inicio debe ser anterior o igual a la fecha fin',
        'fecha_fin.after_or_equal' => 'La fecha fin debe ser posterior o igual a la fecha inicio',
    ];

    public function mount()
    {
        // Establecer fechas por defecto: mes actual
        $this->fecha_inicio = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->fecha_fin = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    #[On('abrir-modal-reporte')]
    public function abrirModal($tipo = 'ingresos')
    {
        $this->tipoReporte = $tipo;
        $this->mostrarModal = true;
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->reset(['fecha_inicio', 'fecha_fin', 'formato']);

        // Restablecer fechas por defecto
        $this->fecha_inicio = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->fecha_fin = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    /**
     * Limpiar caracteres UTF-8 inválidos
     */
    protected function limpiarUtf8($string)
    {
        if (is_null($string)) {
            return null;
        }

        // Remover caracteres UTF-8 inválidos
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        $string = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]'.
            '|[\x00-\x7F][\x80-\xBF]+'.
            '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*'.
            '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})'.
            '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
            '?', $string);

        return $string;
    }

    public function generarReporte()
    {
        $this->validate();

        try {
            $reporteService = new ReporteService();

            // Generar respuesta sin retornar directamente
            $response = null;

            switch ($this->tipoReporte) {
                case 'ingresos':
                    $response = $reporteService->generarReporteIngresos(
                        $this->fecha_inicio,
                        $this->fecha_fin,
                        $this->formato
                    );
                    break;

                case 'egresos':
                    $response = $reporteService->generarReporteEgresos(
                        $this->fecha_inicio,
                        $this->fecha_fin,
                        $this->formato
                    );
                    break;

                case 'combinado':
                    $response = $reporteService->generarReporteCombinado(
                        $this->fecha_inicio,
                        $this->fecha_fin,
                        $this->formato
                    );
                    break;

                default:
                    throw new Exception('Tipo de reporte no válido');
            }

            // Cerrar modal y retornar la respuesta
            $this->mostrarModal = false;

            return $response;

        } catch (Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => 'Error al generar el reporte: ' . $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.reportes.generar-reporte');
    }
}
