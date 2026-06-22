<?php

namespace App\Livewire\Reportes;

use App\Services\ReporteService;
use App\Models\PeriodoExpensa;
use App\Models\Unidad;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ReporteGeneral extends Component
{
    // Filtros
    public $filtroTipoUnidad = 'todos'; // todos, departamento, oficina, local, parqueo, baulera
    public $filtroPeriodo = 'todos';    // periodo_id específico o 'todos'
    public $filtroUnidad = 'todas';     // unidad_id específico o 'todas'
    public $filtroAño = null;           // año específico o null (todos los años)
    public $vista = 'resumida';         // 'resumida' o 'detallada'
    public $agrupacion = 'periodo';     // 'periodo' o 'acumulado'

    // Datos cargados
    public $datosReporte = null;
    public $cargando = false;
    public $datosCargados = false; // Control de estado de carga

    // Datos en formato horizontal
    public $datosHorizontales = null; // Datos transformados para tabla horizontal

    protected ReporteService $reporteService;

    public function boot(ReporteService $reporteService)
    {
        $this->reporteService = $reporteService;
    }

    public function mount()
    {
        // No cargar automáticamente - el usuario debe activar manualmente
        // $this->generarReporte(); // Eliminado para optimización
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
            $this->datosCargados = true;

            // Transformar datos a formato horizontal
            $this->transformarDatosAHorizontal();

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
     * Manejar actualizaciones de filtros - solo actualizar si ya hay datos cargados
     */
    public function updated($property)
    {
        // Solo regenerar si ya hay datos cargados y cambió un filtro relevante
        if ($this->datosCargados && in_array($property, [
            'filtroTipoUnidad',
            'filtroPeriodo',
            'filtroUnidad',
            'filtroAño',
            'agrupacion',
        ])) {
            $this->generarReporte();
        }
    }

    /**
     * Transformar datos actuales a formato horizontal
     */
    protected function transformarDatosAHorizontal(): void
    {
        if (!$this->datosReporte) {
            $this->datosHorizontales = null;
            return;
        }

        $vista = $this->vista === 'resumida' ? 'resumido' : 'detallado';
        $datos = $this->datosReporte[$vista] ?? [];

        if ($vista === 'resumido') {
            $this->datosHorizontales = $this->reporteService->transformarResumidoAHorizontal(
                $datos,
                $this->filtroAño
            );
        } else {
            $this->datosHorizontales = $this->reporteService->transformarDetalladoAHorizontal(
                $datos,
                $this->filtroAño
            );
        }
    }

    /**
     * Exportar a Excel
     * Nota: Este método ya no se usa, la exportación se hace directamente desde el formulario HTML
     */
    public function exportarExcel()
    {
        // Método obsoleto - la exportación se hace ahora desde el formulario HTML
        // Se mantiene por compatibilidad si es llamado desde otros lugares
        $this->dispatch('notify', message: 'Use el botón Excel en la interfaz', type: 'info');
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
        $this->filtroAño = null; // Reiniciar filtro de año
        $this->agrupacion = 'periodo';
        $this->datosCargados = false; // Reiniciar estado
        $this->datosReporte = null;
        $this->datosHorizontales = null; // Limpiar datos horizontales
        // No generar automáticamente - esperar acción del usuario
    }

    /**
     * Cambiar vista (resumida/detallada)
     */
    public function setVista($vista)
    {
        $this->vista = $vista;

        // Transformar datos a la nueva vista si hay datos cargados
        if ($this->datosCargados) {
            $this->transformarDatosAHorizontal();
        }
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
     * Obtener períodos disponibles (cacheado)
     */
    #[Computed(persist: true)]
    public function periodos()
    {
        return Cache::remember('reportes.periodos', 7200, function () {
            return PeriodoExpensa::orderByDesc('año')
                ->orderByDesc('mes')
                ->get(['id', 'año', 'mes'])
                ->map(function ($periodo) {
                    return [
                        'id' => $periodo->id,
                        'nombre' => $periodo->nombre_periodo, // Usa el accessor del modelo
                    ];
                })
                ->toArray();
        });
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
     * Obtener unidades disponibles (para filtro, cacheado)
     */
    #[Computed(persist: true)]
    public function unidades()
    {
        return Cache::remember('reportes.unidades', 7200, function () {
            return Unidad::orderByRaw("LENGTH(codigo), codigo")
                ->get(['id', 'codigo', 'tipo_unidad'])
                ->map(function ($unidad) {
                    return [
                        'id' => $unidad->id,
                        'codigo' => $unidad->codigo,
                        'tipo' => ucfirst($unidad->tipo_unidad->value),
                    ];
                })
                ->toArray();
        });
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
        if (!$this->datosHorizontales || empty($this->datosHorizontales['filas'])) {
            return null;
        }

        $filas = $this->datosHorizontales['filas'];
        $periodos = $this->datosHorizontales['periodos'];

        // Inicializar array de totales por período
        $totalesPorPeriodo = [];
        foreach ($periodos as $periodo) {
            $totalesPorPeriodo[$periodo['id']] = [
                'generado' => 0,
                'cobrado' => 0,
                'deuda' => 0,
            ];
        }

        // Sumar totales generales
        $totalesGenerales = [
            'generado' => 0,
            'cobrado' => 0,
            'deuda' => 0,
        ];

        // Sumar datos de cada fila
        foreach ($filas as $fila) {
            // Sumar a los totales generales
            $totalesGenerales['generado'] += $fila['totales']['generado'];
            $totalesGenerales['cobrado'] += $fila['totales']['cobrado'];
            $totalesGenerales['deuda'] += $fila['totales']['deuda'];

            // Sumar a cada período
            foreach ($fila['periodos'] as $periodo) {
                $periodoId = $periodo['periodo_id'];
                if (isset($totalesPorPeriodo[$periodoId])) {
                    $totalesPorPeriodo[$periodoId]['generado'] += $periodo['generado'];
                    $totalesPorPeriodo[$periodoId]['cobrado'] += $periodo['cobrado'];
                    $totalesPorPeriodo[$periodoId]['deuda'] += $periodo['deuda'];
                }
            }
        }

        return [
            'por_periodo' => $totalesPorPeriodo,
            'generales' => $totalesGenerales,
        ];
    }

    /**
     * Obtener años disponibles (cacheado)
     */
    #[Computed(persist: true)]
    public function añosDisponibles()
    {
        return Cache::remember('reportes.años_disponibles', 7200, function () {
            return $this->reporteService->obtenerAñosDisponibles();
        });
    }

    /**
     * Obtener períodos disponibles filtrados por año seleccionado
     */
    #[Computed]
    public function periodosFiltrados()
    {
        $año = $this->filtroAño;
        return $this->reporteService->obtenerPeriodosDisponibles($año);
    }

    public function render()
    {
        return view('livewire.reportes.general')
            ->layout('layouts.app');
    }
}