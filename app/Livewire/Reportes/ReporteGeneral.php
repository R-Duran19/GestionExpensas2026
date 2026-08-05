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

    // Filtro de concepto (presentación, no requiere regenerar query).
    // 'ambos' = expensa + agua (total), 'expensa' = solo expensa, 'agua' = solo agua
    public $concepto = 'ambos';

    // Estado de carga
    public $cargando = false;
    public $datosCargados = false; // Control de estado de carga

    // Snapshot de filtros del último reporte generado (liviano).
    // NO almacenamos los datos del reporte en propiedades públicas: Livewire
    // serializa TODAS las propiedades públicas en cada request (wire:snapshot),
    // lo que provoca PayloadTooLargeException con reportes grandes.
    // Los datos pesados se recuperan vía #[Computed] desde el cache del ReporteService.
    public $filtrosReporteActivo = null;

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
            // Generar el reporte (el servicio cachea internamente con Cache::remember).
            // NO se guarda en propiedad pública para evitar PayloadTooLargeException.
            $datos = $this->reporteService->generarReporteConsolidado($filtros, $agruparPorPeriodo);

            // Guardar solo los filtros activos (livianos) para que los
            // computed properties recuperen los datos desde el caché del servicio.
            $this->filtrosReporteActivo = [
                'filtros' => $filtros,
                'agrupar_por_periodo' => $agruparPorPeriodo,
            ];
            $this->datosCargados = true;

            if (empty($datos['resumido']) && empty($datos['detallado'])) {
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
     * Manejar actualizaciones de filtros.
     *
     * IMPORTANTE: NO se regenera el reporte automáticamente.
     * El usuario debe pulsar "Generar Reporte" para evitar múltiples
     * queries en cascada al cambiar varios filtros seguidos.
     */
    public function updated($property)
    {
        // Al cambiar el año, los períodos del año anterior ya no aplican
        if ($property === 'filtroAño') {
            $this->filtroPeriodo = 'todos';
        }

        // Marcar que los datos en pantalla ya no corresponden a los filtros actuales
        if (in_array($property, [
            'filtroTipoUnidad',
            'filtroPeriodo',
            'filtroUnidad',
            'filtroAño',
        ])) {
            $this->datosCargados = false;
        }
    }

    /**
     * Datos crudos del reporte (recuperados desde caché del servicio).
     * #[Computed] NO se serializa en el payload de Livewire.
     */
    #[Computed]
    public function datosReporte(): ?array
    {
        if (!$this->filtrosReporteActivo) {
            return null;
        }

        return $this->reporteService->generarReporteConsolidado(
            $this->filtrosReporteActivo['filtros'],
            $this->filtrosReporteActivo['agrupar_por_periodo']
        );
    }

    /**
     * Datos transformados a formato horizontal (períodos como columnas).
     * Se deriva de datosReporte + vista + concepto + año actuales.
     * Es un #[Computed]: se recalcula automáticamente al cambiar vista/concepto,
     * sin necesidad de llamar manualmente a ningún método de transformación.
     */
    #[Computed]
    public function datosHorizontales(): ?array
    {
        $datosReporte = $this->datosReporte;

        if (!$datosReporte) {
            return null;
        }

        $vista = $this->vista === 'resumida' ? 'resumido' : 'detallado';
        $datos = $this->reporteService->aplicarConcepto(
            $datosReporte[$vista] ?? [],
            $this->concepto
        );

        if ($vista === 'resumido') {
            return $this->reporteService->transformarResumidoAHorizontal(
                $datos,
                $this->filtroAño
            );
        }

        return $this->reporteService->transformarDetalladoAHorizontal(
            $datos,
            $this->filtroAño
        );
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
        $this->concepto = 'ambos';
        $this->filtrosReporteActivo = null; // Invalidar reporte activo
        $this->datosCargados = false; // Reiniciar estado
        // No generar automáticamente - esperar acción del usuario
    }

    /**
     * Cambiar vista (resumida/detallada)
     */
    public function setVista($vista)
    {
        $this->vista = $vista;
        // No se necesita transformar manualmente: el #[Computed] datosHorizontales
        // se recalcula automáticamente con la nueva vista.
    }

    /**
     * Cambiar concepto a mostrar (ambos / expensa / agua).
     * Solo presentación: NO requiere regenerar la query.
     */
    public function setConcepto($concepto)
    {
        if (in_array($concepto, ['ambos', 'expensa', 'agua'])) {
            $this->concepto = $concepto;
            // No se necesita transformar manualmente: el #[Computed] datosHorizontales
            // se recalcula automáticamente con el nuevo concepto.
        }
    }

    /**
     * Helper para la vista: formatear monto o devolver "-" si es null
     * (caso de unidades sin medidor cuando se muestra la columna de agua).
     */
    public function formatMonto($valor): string
    {
        if ($valor === null) {
            return '-';
        }
        return 'Bs. ' . number_format((float) $valor, 2);
    }

    /**
     * Cambiar agrupación (periodo/acumulado)
     * No regenera automáticamente: el cambio de GROUP BY requiere nueva query,
     * el usuario debe pulsar "Generar Reporte".
     */
    public function setAgrupacion($agrupacion)
    {
        if ($this->agrupacion === $agrupacion) {
            return;
        }
        $this->agrupacion = $agrupacion;
        $this->filtrosReporteActivo = null; // Invalidar reporte activo
        $this->datosCargados = false;
    }

    /**
     * Obtener totales generales para las tarjetas.
     * Respeta el filtro de concepto: las tarjetas muestran los totales
     * correspondientes al concepto seleccionado (ambos/expensa/agua).
     */
    #[Computed]
    public function totalesGenerales()
    {
        if (!$this->datosReporte) {
            return null;
        }

        $vista = $this->vista === 'resumida' ? 'resumido' : 'detallado';
        $datos = $this->datosReporte[$vista] ?? [];

        // Aplicar concepto para que las tarjetas coincidan con la tabla
        $datos = $this->reporteService->aplicarConcepto($datos, $this->concepto);

        if (empty($datos)) {
            return null;
        }

        $totalGenerado = collect($datos)->sum('generado');
        $totalCobrado = collect($datos)->sum('cobrado');
        $totalDeuda = collect($datos)->sum('deuda');

        // Contar unidades únicas desde el detallado filtrado por concepto
        // (evita doble conteo cuando hay múltiples períodos en resumido).
        $detalladoFiltrado = $this->reporteService->aplicarConcepto(
            $this->datosReporte['detallado'] ?? [],
            $this->concepto
        );
        $unidades = collect($detalladoFiltrado)->pluck('unidad_id')->unique()->count();

        $porcentaje = $totalGenerado > 0
            ? round(($totalCobrado / $totalGenerado) * 100, 2)
            : 0.0;

        return [
            'unidades' => $unidades,
            'generado' => number_format($totalGenerado, 2),
            'cobrado' => number_format($totalCobrado, 2),
            'deuda' => number_format($totalDeuda, 2),
            'porcentaje_cobranza' => number_format($porcentaje, 1),
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
     * Ordenadas por tipo_unidad y luego por código natural ascendente.
     */
    #[Computed(persist: true)]
    public function unidades()
    {
        return Cache::remember('reportes.unidades', 7200, function () {
            return Unidad::orderByRaw(Unidad::ordenPorTipoUnidadRaw('tipo_unidad'))
                ->orderByRaw(Unidad::ordenPorCodigoRaw('codigo', 'ASC'))
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
     * Obtener períodos disponibles filtrados por año seleccionado.
     * Cacheado por año para no consultar BD en cada render del componente.
     */
    #[Computed]
    public function periodosFiltrados()
    {
        $año = $this->filtroAño;
        $cacheKey = 'reportes.periodos.' . ($año ?? 'all');

        return Cache::remember($cacheKey, 7200, function () use ($año) {
            return $this->reporteService->obtenerPeriodosDisponibles($año);
        });
    }

    public function render()
    {
        return view('livewire.reportes.general')
            ->layout('layouts.app');
    }
}