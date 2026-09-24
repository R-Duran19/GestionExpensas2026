<?php

namespace App\Livewire\Periodos;

use App\Models\MedidorGrupo;
use App\Models\PeriodoExpensa;
use App\Services\MedidorService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RegistrarLecturaCompartida extends Component
{

    use AuthorizesRequests;
    public $periodoId;

    public $grupoId;

    public $lecturaAnterior = 0;

    public $lecturaActual;

    public $observaciones;

    /**
     * Distribución manual de consumo por unidad.
     * Array asociativo: [unidad_id => consumo]
     *
     * @var array
     */
    public $distribucion = [];

    protected MedidorService $medidorService;

    public function boot(MedidorService $medidorService)
    {
        $this->medidorService = $medidorService;
    }

    public function mount($periodo)
    {
        $this->periodoId = $periodo;
    }

    public function updatedGrupoId($value)
    {
        if (! $value) {
            $this->lecturaAnterior = 0;
            $this->distribucion = [];

            return;
        }

        // Obtener lectura anterior del grupo
        $lecturaAnterior = $this->medidorService->obtenerLecturaAnteriorCompartida($value, $this->periodoId);
        $this->lecturaAnterior = $lecturaAnterior?->lectura_actual ?? 0;
        $this->lecturaActual = null;
        $this->distribucion = [];
    }

    /**
     * Cuando cambia la lectura actual, recalcular la distribución equitativa
     * automáticamente.
     */
    public function updatedLecturaActual()
    {
        $this->recalcularDistribucionEquitativa();
    }

    /**
     * Recalcula la distribución equitativamente si el grupo está seleccionado
     * y hay una lectura actual válida. Las primeras N-1 unidades reciben el
     * valor base redondeado y la última absorbe el residuo.
     */
    public function recalcularDistribucionEquitativa()
    {
        if (! $this->grupoId || $this->lecturaActual === null || $this->lecturaActual === '' || ! is_numeric($this->lecturaActual)) {
            return;
        }

        if ((float) $this->lecturaActual < (float) $this->lecturaAnterior) {
            return;
        }

        $grupo = $this->grupoSeleccionado;
        if (! $grupo) {
            return;
        }

        $unidades = $grupo['unidades'];
        $cantidad = count($unidades);
        if ($cantidad === 0) {
            return;
        }

        $consumoTotal = (float) $this->lecturaActual - (float) $this->lecturaAnterior;
        $valorBase = round($consumoTotal / $cantidad, 2);
        $sumaBase = $valorBase * ($cantidad - 1);
        $valorUltimo = round($consumoTotal - $sumaBase, 2);

        $nueva = [];
        foreach ($unidades as $i => $unidad) {
            $nueva[$unidad['id']] = ($i === $cantidad - 1) ? $valorUltimo : $valorBase;
        }
        $this->distribucion = $nueva;
    }

    /**
     * Repartir el consumo total equitativamente (botón "Repartir equitativamente").
     */
    public function repartirEquitativo()
    {
        $this->recalcularDistribucionEquitativa();
    }

    public function guardar()
    {
        $this->authorize('create', \App\Models\LecturaMedidor::class);
        $this->validate([
            'grupoId' => 'required|exists:medidor_grupos,id',
            'lecturaActual' => 'required|numeric|min:0',
        ]);

        try {
            // Filtrar distribución: solo enviar valores no vacíos
            $distribucion = array_filter($this->distribucion, function ($v) {
                return $v !== null && $v !== '';
            });

            // Si todos los campos están vacíos, pasar array vacío para distribución equitativa
            $distribucionFinal = ! empty($distribucion) ? $distribucion : [];

            $this->medidorService->registrarLecturaCompartida(
                $this->periodoId,
                $this->grupoId,
                $this->lecturaActual,
                $this->observaciones,
                $distribucionFinal
            );

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Lectura compartida registrada',
                'mensaje' => 'La lectura compartida se registró correctamente para todas las unidades del grupo',
            ]);

            // Limpiar formulario
            $this->reset(['grupoId', 'lecturaActual', 'lecturaAnterior', 'observaciones', 'distribucion']);
        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => $e->getMessage(),
            ]);
        }
    }

    #[Computed]
    public function grupos()
    {
        // Obtener IDs de grupos que ya tienen lectura en este período
        $gruposConLectura = \App\Models\LecturaMedidor::where('periodo_id', $this->periodoId)
            ->whereNotNull('medidor_grupo_id')
            ->pluck('medidor_grupo_id')
            ->unique()
            ->toArray();

        return MedidorGrupo::where('activo', true)
            ->whereNotIn('id', $gruposConLectura) // Excluir grupos con lectura registrada
            ->with(['unidades' => function ($q) {
                $q->where('activo', true);
            }])
            ->get()
            ->filter(fn ($grupo) => $grupo->unidades->count() > 0)
            ->map(function ($grupo) {
                return [
                    'id' => $grupo->id,
                    'nombre' => $grupo->nombre,
                    'unidades_count' => $grupo->unidades->count(),
                    'unidades' => $grupo->unidades->map(fn ($u) => $u->nombre_completo)->implode(', '),
                ];
            });
    }

    #[Computed]
    public function grupoSeleccionado()
    {
        if (! $this->grupoId) {
            return null;
        }

        $grupo = MedidorGrupo::with(['unidades' => function ($q) {
            $q->where('activo', true)->with(['propietarios']);
        }])->find($this->grupoId);

        if (! $grupo) {
            return null;
        }

        return [
            'id' => $grupo->id,
            'nombre' => $grupo->nombre,
            'unidades_count' => $grupo->unidades->count(),
            'unidades' => $grupo->unidades->map(function ($unidad) {
                $propietario = $unidad->propietarioActual;

                return [
                    'id' => $unidad->id,
                    'nombre' => $unidad->nombre_completo,
                    'codigo' => $unidad->codigo,
                    'propietario' => $propietario?->nombre.' '.$propietario?->apellido,
                    'categoria' => $unidad->categoria_unidad->value,
                ];
            }),
        ];
    }

    /**
     * Suma actual de la distribución ingresada manualmente.
     */
    #[Computed]
    public function sumaDistribucion()
    {
        return collect($this->distribucion)
            ->map(fn ($v) => is_numeric($v) ? (float) $v : 0)
            ->sum();
    }

    /**
     * Consumo total calculado a partir de lectura actual - anterior.
     */
    #[Computed]
    public function consumoTotal()
    {
        if ($this->lecturaActual === null || $this->lecturaActual === '' || ! is_numeric($this->lecturaActual)) {
            return 0;
        }

        $actual = (float) $this->lecturaActual;
        $anterior = (float) $this->lecturaAnterior;

        if ($actual < $anterior) {
            return 0;
        }

        return round($actual - $anterior, 2);
    }

    /**
     * Diferencia entre la suma de la distribución y el consumo total.
     */
    #[Computed]
    public function diferenciaDistribucion()
    {
        return round($this->consumoTotal - $this->sumaDistribucion, 2);
    }

    /**
     * Factores de agua del período (Bs/M³). Solo si ya fueron calculados
     * a partir de las 3 facturas registradas (2 domiciliarias + 1 comercial).
     */
    #[Computed]
    public function factoresAgua()
    {
        $periodo = PeriodoExpensa::find($this->periodoId);

        if (! $periodo || ! $periodo->tiene_factores_calculados) {
            return null;
        }

        return [
            'domiciliario' => (float) $periodo->factor_domiciliario,
            'comercial' => (float) $periodo->factor_comercial,
        ];
    }

    /**
     * Factor de agua (Bs/M³) correspondiente a una categoría de unidad.
     */
    public function factorParaCategoria($categoria)
    {
        $factores = $this->factoresAgua;

        if (! $factores) {
            return 0;
        }

        return $categoria === 'comercial' ? $factores['comercial'] : $factores['domiciliario'];
    }

    /**
     * Monto estimado en Bs para un consumo y categoría, usando el factor del
     * período y el redondeo monetario real (igual que al calcular expensas).
     * Devuelve null si no hay factores o el consumo no es válido.
     */
    public function montoEstimadoBs($consumo, $categoria)
    {
        $factores = $this->factoresAgua;

        if (! $factores || $consumo === null || $consumo === '' || ! is_numeric($consumo)) {
            return null;
        }

        $factor = $categoria === 'comercial' ? $factores['comercial'] : $factores['domiciliario'];

        return $this->medidorService->calcularMontoAgua((float) $consumo, $factor);
    }

    /**
     * Suma de los montos estimados de todas las unidades del grupo.
     */
    #[Computed]
    public function montoTotalEstimado()
    {
        if (! $this->factoresAgua) {
            return null;
        }

        $grupo = $this->grupoSeleccionado;
        if (! $grupo) {
            return null;
        }

        $total = 0;
        foreach ($grupo['unidades'] as $unidad) {
            $consumo = $this->distribucion[$unidad['id']] ?? null;
            $monto = $this->montoEstimadoBs($consumo, $unidad['categoria']);
            if ($monto !== null) {
                $total += $monto;
            }
        }

        return $total;
    }

    public function render()
    {
        $periodo = PeriodoExpensa::findOrFail($this->periodoId);

        return view('livewire.periodos.registrar-lectura-compartida', [
            'periodo' => $periodo,
        ])->layout('layouts.app');
    }
}
