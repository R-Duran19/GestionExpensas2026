<?php

namespace App\Livewire\Reportes;

use App\Services\ReporteService;
use App\Models\PeriodoExpensa;
use App\Models\Unidad;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ReporteGeneral extends Component
{
    // Filtros
    public $filtroTipoUnidad = 'todos'; // todos, departamento, oficina, local, parqueo, baulera
    public $filtroPeriodo = 'todos';    // periodo_id específico o 'todos'
    public $filtroUnidad = 'todas';     // unidad_id específico o 'todas'
    public $vista = 'resumida';         // 'resumida' o 'detallada'
    public $agrupacion = 'periodo';     // 'periodo' o 'acumulado'

    // Datos cargados
    public $datosReporte = null;
    public $cargando = false;

    protected ReporteService $reporteService;

    public function boot(ReporteService $reporteService)
    {
        $this->reporteService = $reporteService;
    }

    public function mount()
    {
        $this->generarReporte();
    }

    /**
     * Generar reporte con los filtros actuales
     */
    public function generarReporte()
    {
        $this->cargando = true;

        $filtros = [
            'tipo_unidad' => $this->filtroTipoUnidad,
            'periodo_id' => $this->filtroPeriodo,
            'unidad_id' => $this->filtroUnidad,
        ];

        $agruparPorPeriodo = $this->agrupacion === 'periodo';

        try {
            $this->datosReporte = $this->reporteService->generarReporteConsolidado($filtros, $agruparPorPeriodo);

            if (empty($this->datosReporte['resumido']) && empty($this->datosReporte['detallado'])) {
                $this->dispatch('notify',
                    message: 'No se encontraron datos con los filtros seleccionados',
                    type: 'warning'
                );
            }
        } catch (\Exception $e) {
            $this->dispatch('notify',
                message: 'Error al generar el reporte: ' . $e->getMessage(),
                type: 'error'
            );
        } finally {
            $this->cargando = false;
        }
    }

    /**
     * Exportar a Excel
     */
    public function exportarExcel()
    {
        $vista = $this->vista === 'resumida' ? 'resumido' : 'detallado';

        if (!$this->datosReporte || empty($this->datosReporte[$vista])) {
            $this->dispatch('notify', message: 'No hay datos para exportar', type: 'warning');
            return;
        }

        try {
            $response = $this->reporteService->exportarExcel($this->datosReporte[$vista], $vista);

            $this->dispatch('notify',
                message: 'Exportación a Excel implementada (ver consola)',
                type: 'success'
            );
        } catch (\Exception $e) {
            $this->dispatch('notify',
                message: 'Error al exportar: ' . $e->getMessage(),
                type: 'error'
            );
        }
    }

    /**
     * Exportar a PDF
     */
    public function exportarPDF()
    {
        $vista = $this->vista === 'resumida' ? 'resumido' : 'detallado';

        if (!$this->datosReporte || empty($this->datosReporte[$vista])) {
            $this->dispatch('notify', message: 'No hay datos para exportar', type: 'warning');
            return;
        }

        try {
            $response = $this->reporteService->exportarPDF($this->datosReporte[$vista], $vista);

            $this->dispatch('notify',
                message: 'Exportación a PDF implementada (ver consola)',
                type: 'success'
            );
        } catch (\Exception $e) {
            $this->dispatch('notify',
                message: 'Error al exportar: ' . $e->getMessage(),
                type: 'error'
            );
        }
    }

    /**
     * Limpiar todos los filtros
     */
    public function limpiarFiltros()
    {
        $this->filtroTipoUnidad = 'todos';
        $this->filtroPeriodo = 'todos';
        $this->filtroUnidad = 'todas';
        $this->agrupacion = 'periodo';
        $this->generarReporte();
    }

    /**
     * Cambiar vista (resumida/detallada)
     */
    public function setVista($vista)
    {
        $this->vista = $vista;
    }

    /**
     * Cambiar agrupación (periodo/acumulado)
     */
    public function setAgrupacion($agrupacion)
    {
        $this->agrupacion = $agrupacion;
        $this->generarReporte(); // Regenerar con nueva agrupación
    }

    /**
     * Obtener totales generales para las tarjetas
     */
    #[Computed]
    public function totalesGenerales()
    {
        if (!$this->datosReporte || empty($this->datosReporte['totales'])) {
            return null;
        }

        $totales = $this->datosReporte['totales'];

        return [
            'unidades' => $totales['unidades'],
            'generado' => number_format($totales['total_generado'], 2),
            'cobrado' => number_format($totales['total_cobrado'], 2),
            'deuda' => number_format($totales['total_deuda'], 2),
            'porcentaje_cobranza' => number_format($totales['porcentaje_cobranza'], 1),
        ];
    }

    /**
     * Obtener períodos disponibles
     */
    #[Computed]
    public function periodos()
    {
        return PeriodoExpensa::orderByDesc('año')
            ->orderByDesc('mes')
            ->get()
            ->map(function ($periodo) {
                return [
                    'id' => $periodo->id,
                    'nombre' => $periodo->nombre_periodo,
                ];
            })
            ->toArray();
    }

    /**
     * Obtener tipos de unidad disponibles
     */
    #[Computed]
    public function tiposUnidad()
    {
        return [
            'todos' => 'Todos',
            'departamento' => 'Departamentos',
            'oficina' => 'Oficinas',
            'local' => 'Locales',
            'parqueo' => 'Parqueos',
            'baulera' => 'Bauleras',
        ];
    }

    /**
     * Obtener unidades disponibles (para filtro)
     */
    #[Computed]
    public function unidades()
    {
        return Unidad::orderByRaw("LENGTH(codigo), codigo")
            ->get()
            ->map(function ($unidad) {
                return [
                    'id' => $unidad->id,
                    'codigo' => $unidad->codigo,
                    'tipo' => ucfirst($unidad->tipo_unidad->value),
                ];
            })
            ->toArray();
    }

    /**
     * Obtener datos de la vista actual
     */
    #[Computed]
    public function vistaActual()
    {
        if (!$this->datosReporte) {
            return [];
        }

        return $this->vista === 'resumida'
            ? $this->datosReporte['resumido']
            : $this->datosReporte['detallado'];
    }

    /**
     * Calcular totales por columna (estilo Excel)
     */
    #[Computed]
    public function totalesColumnas()
    {
        if (!$this->datosReporte || empty($this->vistaActual)) {
            return null;
        }

        $datos = $this->vistaActual;

        if ($this->vista === 'resumida') {
            // Vista resumida: totales de unidades, generado, cobrado, deuda
            return [
                'unidades' => collect($datos)->sum('unidades'),
                'generado' => collect($datos)->sum('generado'),
                'cobrado' => collect($datos)->sum('cobrado'),
                'deuda' => collect($datos)->sum('deuda'),
            ];
        } else {
            // Vista detallada: totales de generado, cobrado, deuda
            return [
                'generado' => collect($datos)->sum('generado'),
                'cobrado' => collect($datos)->sum('cobrado'),
                'deuda' => collect($datos)->sum('deuda'),
            ];
        }
    }

    public function render()
    {
        return view('livewire.reportes.general');
    }
}