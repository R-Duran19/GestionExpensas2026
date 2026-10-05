<?php

namespace App\Livewire\Periodos;

use App\Models\ActivityLog;
use App\Models\Expensa;
use App\Models\PeriodoExpensa;
use App\Models\Unidad;
use App\Support\AjusteTemporalFlag;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * HERRAMIENTA TEMPORAL: sobreescritura manual de montos por unidad
 * para cuadrar periodos contra cálculos externos (Excel).
 *
 * Sobrescribe monto_expensa / monto_agua / monto_total y recalcula
 * los pendientes respetando lo ya pagado. Cada cambio queda en activity_logs.
 *
 * TODO(eliminar): componente + vista + ruta + link en periodo-detail.
 */
class AjusteTemporalMontos extends Component
{

    use AuthorizesRequests;
    use WithPagination;

    #[Locked]
    public $periodoId;

    public $search = '';

    public $filtroTipo = '';

    /** Valores tipeados: [expensa_id => ['monto_expensa' => ?, 'monto_agua' => ?]] */
    public $ajustes = [];

    protected $paginationTheme = 'tailwind';

    public function mount($periodo)
    {
        // Herramienta desactivable sin tocar código: php artisan ajuste-temporal on|off
        if (!AjusteTemporalFlag::enabled()) {
            abort(404);
        }

        $this->periodoId = $periodo;
    }

    public function updatedPeriodoId()
    {
        $this->ajustes = [];
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFiltroTipo()
    {
        $this->resetPage();
    }

    #[Computed]
    public function periodo()
    {
        return PeriodoExpensa::findOrFail($this->periodoId);
    }

    #[Computed]
    public function periodos()
    {
        return PeriodoExpensa::orderBy('año', 'desc')
            ->orderBy('mes', 'desc')
            ->get();
    }

    #[Computed]
    public function expensas()
    {
        $query = Expensa::where('periodo_id', $this->periodoId)
            ->whereNull('expensas.deleted_at')
            ->with(['unidad.propietarios'])
            ->orderByRaw(
                "CASE WHEN unidades.piso LIKE 'Sotano%' OR unidades.piso LIKE 'Sótano%'" .
                " THEN 1000 + CAST(REGEXP_SUBSTR(unidades.piso, '[0-9]+') AS SIGNED)" .
                " ELSE -CAST(REGEXP_SUBSTR(unidades.piso, '[0-9]+') AS SIGNED) END ASC"
            )
            ->orderByRaw(Unidad::ordenPorTipoUnidadRaw('unidades.tipo_unidad'))
            ->orderByRaw(Unidad::ordenPorCodigoRaw('unidades.codigo', 'ASC'))
            ->join('unidades', 'unidades.id', '=', 'expensas.unidad_id')
            ->select('expensas.*');

        $termino = trim($this->search);
        if ($termino !== '') {
            $query->where(function ($q) use ($termino) {
                $q->where('unidades.codigo', 'like', "%{$termino}%")
                    ->orWhereHas('unidad.propietarioActual', function ($propQ) use ($termino) {
                        $propQ->where('nombre', 'like', "%{$termino}%")
                            ->orWhere('apellido', 'like', "%{$termino}%");
                    });
            });
        }

        if ($this->filtroTipo !== '') {
            $query->where('unidades.tipo_unidad', $this->filtroTipo);
        }

        $expensas = $query->paginate(15);

        $expensas->getCollection()->transform(function ($expensa) {
            $unidad = $expensa->unidad;
            $propietario = $unidad?->propietarioActual;

            return [
                'id' => $expensa->id,
                'unidad_id' => $expensa->unidad_id,
                'codigo' => $unidad?->codigo ?? "Unidad {$expensa->unidad_id}",
                'nombre' => $unidad?->nombre_completo,
                'tipo_unidad' => $unidad?->tipo_unidad->value ?? '',
                'piso' => $unidad?->piso ?? '',
                'propietario' => $propietario ? trim($propietario->nombre . ' ' . $propietario->apellido) : null,
                'monto_expensa' => (float) $expensa->monto_expensa,
                'monto_agua' => (float) $expensa->monto_agua,
                'monto_total' => (float) $expensa->monto_total,
                'expensa_pendiente' => (float) $expensa->expensa_pendiente,
                'agua_pendiente' => (float) $expensa->agua_pendiente,
                'pagado_expensa' => round((float) $expensa->monto_expensa - (float) $expensa->expensa_pendiente, 2),
                'pagado_agua' => round((float) $expensa->monto_agua - (float) $expensa->agua_pendiente, 2),
            ];
        });

        return $expensas;
    }

    /** Normaliza coma decimal -> punto y valida numérico. Devuelve null si inválido. */
    private function normalizarMonto($valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        $limpio = str_replace(',', '.', trim($valor));
        if (!is_numeric($limpio) || (float) $limpio < 0) {
            return null;
        }
        return round((float) $limpio, 2);
    }

    /**
     * Aplica el ajuste a una expensa. Campos vacíos = conservar valor actual.
     */
    private function aplicarAjuste(int $expensaId, ?float $nuevaExpensa, ?float $nuevaAgua): array
    {
        $expensa = Expensa::with('periodo')->findOrFail($expensaId);
        $this->authorize('ajustar', $expensa);

        // [REMEDIACIÓN-2.8] Scope real de la herramienta: la expensa debe
        // pertenecer al período montado Y estar en período abierto (la
        // inmutabilidad de períodos cerrados sólo se reversa vía reabrir con
        // motivo, no por esta vía).
        if ((int) $expensa->periodo_id !== (int) $this->periodoId) {
            throw new \Exception('La expensa no pertenece al período de esta herramienta.');
        }

        if ($expensa->periodo && $expensa->periodo->estado !== 'abierto') {
            throw new \Exception('La expensa pertenece a un período cerrado. Reabra el período para ajustarla.');
        }

        $unidad = $expensa->unidad;

        $valoresAntes = [
            'monto_expensa' => (float) $expensa->monto_expensa,
            'monto_agua' => (float) $expensa->monto_agua,
            'monto_total' => (float) $expensa->monto_total,
            'expensa_pendiente' => (float) $expensa->expensa_pendiente,
            'agua_pendiente' => (float) $expensa->agua_pendiente,
        ];

        $finalExpensa = $nuevaExpensa ?? $valoresAntes['monto_expensa'];
        $finalAgua = $nuevaAgua ?? $valoresAntes['monto_agua'];

        // Lo ya pagado se conserva: pendiente = monto nuevo - pagado (mínimo 0)
        $pagadoExpensa = max(0, $valoresAntes['monto_expensa'] - $valoresAntes['expensa_pendiente']);
        $pagadoAgua = max(0, $valoresAntes['monto_agua'] - $valoresAntes['agua_pendiente']);

        $expensa->update([
            'monto_expensa' => $finalExpensa,
            'monto_agua' => $finalAgua,
            'monto_total' => round($finalExpensa + $finalAgua, 2),
            'expensa_pendiente' => max(0, round($finalExpensa - $pagadoExpensa, 2)),
            'agua_pendiente' => max(0, round($finalAgua - $pagadoAgua, 2)),
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'ajuste_temporal_montos',
            'model_type' => Expensa::class,
            'model_id' => $expensa->id,
            'description' => sprintf(
                'AJUSTE TEMPORAL de montos: %s (periodo %s/%s) — expensa %s → %s Bs, agua %s → %s Bs',
                $unidad?->codigo ?? "unidad {$expensa->unidad_id}",
                $expensa->periodo?->mes,
                $expensa->periodo?->año,
                number_format($valoresAntes['monto_expensa'], 2, '.', ''),
                number_format($finalExpensa, 2, '.', ''),
                number_format($valoresAntes['monto_agua'], 2, '.', ''),
                number_format($finalAgua, 2, '.', '')
            ),
            'properties' => [
                'herramienta' => 'ajuste_temporal_montos',
                'antes' => $valoresAntes,
                'despues' => [
                    'monto_expensa' => $finalExpensa,
                    'monto_agua' => $finalAgua,
                    'monto_total' => round($finalExpensa + $finalAgua, 2),
                    'expensa_pendiente' => max(0, round($finalExpensa - $pagadoExpensa, 2)),
                    'agua_pendiente' => max(0, round($finalAgua - $pagadoAgua, 2)),
                ],
            ],
        ]);

        app(\App\Services\ReporteService::class)->invalidarCacheReportes();

        return ['codigo' => $unidad?->codigo ?? "unidad {$expensa->unidad_id}"];
    }

    public function guardarFila($expensaId)
    {
        // [REMEDIACIÓN-2.8] Re-chequear el kill switch en cada acción: el
        // gate sólo en mount() dejaba operar a tabs ya cargados tras
        // "ajuste-temporal off" (los updates hidratan sin re-mount).
        if (!AjusteTemporalFlag::enabled()) {
            abort(404);
        }

        // [REMEDIACIÓN-2.8] El authorize con class-string rompía en runtime
        // (BasePolicy::ajustar exige instancia): se autoriza por instancia
        // dentro de aplicarAjuste().
        $editados = $this->ajustes[$expensaId] ?? [];

        $nuevaExpensa = $this->normalizarMonto($editados['monto_expensa'] ?? null);
        $nuevaAgua = $this->normalizarMonto($editados['monto_agua'] ?? null);

        if (($editados['monto_expensa'] ?? null) !== null && ($editados['monto_expensa'] ?? '') !== '' && $nuevaExpensa === null) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Valor inválido',
                'mensaje' => 'El monto de expensa debe ser un número mayor o igual a 0.',
            ]);
            return;
        }
        if (($editados['monto_agua'] ?? null) !== null && ($editados['monto_agua'] ?? '') !== '' && $nuevaAgua === null) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Valor inválido',
                'mensaje' => 'El monto de agua debe ser un número mayor o igual a 0.',
            ]);
            return;
        }

        if ($nuevaExpensa === null && $nuevaAgua === null) {
            $this->dispatch('alerta', [
                'tipo' => 'warning',
                'titulo' => 'Sin cambios',
                'mensaje' => 'Ingresá al menos un monto nuevo para esta unidad.',
            ]);
            return;
        }

        $info = $this->aplicarAjuste($expensaId, $nuevaExpensa, $nuevaAgua);
        unset($this->ajustes[$expensaId]);

        $this->dispatch('alerta', [
            'tipo' => 'success',
            'titulo' => 'Montos ajustados',
            'mensaje' => "Se sobrescribieron los montos de {$info['codigo']} para este período.",
        ]);
    }

    public function guardarTodo()
    {
        // [REMEDIACIÓN-2.8] Ídem guardarFila
        if (!AjusteTemporalFlag::enabled()) {
            abort(404);
        }

        // [REMEDIACIÓN-2.8] Ídem guardarFila: autorización por instancia en
        // aplicarAjuste().
        $conCambios = array_filter(
            $this->ajustes,
            fn($a) => ($a['monto_expensa'] ?? '') !== '' || ($a['monto_agua'] ?? '') !== ''
        );

        if (empty($conCambios)) {
            $this->dispatch('alerta', [
                'tipo' => 'warning',
                'titulo' => 'Sin cambios',
                'mensaje' => 'No hay montos ingresados para guardar.',
            ]);
            return;
        }

        $aplicados = 0;
        $errores = [];

        foreach ($conCambios as $expensaId => $editados) {
            $nuevaExpensa = $this->normalizarMonto($editados['monto_expensa'] ?? null);
            $nuevaAgua = $this->normalizarMonto($editados['monto_agua'] ?? null);

            if ((($editados['monto_expensa'] ?? '') !== '' && $nuevaExpensa === null)
                || (($editados['monto_agua'] ?? '') !== '' && $nuevaAgua === null)) {
                $errores[] = "Fila con valor inválido (id {$expensaId})";
                continue;
            }

            try {
                $this->aplicarAjuste((int) $expensaId, $nuevaExpensa, $nuevaAgua);
                unset($this->ajustes[$expensaId]);
                $aplicados++;
            } catch (\Exception $e) {
                $errores[] = "id {$expensaId}: " . $e->getMessage();
            }
        }

        $this->dispatch('alerta', [
            'tipo' => $aplicados > 0 ? 'success' : 'error',
            'titulo' => $aplicados > 0 ? "{$aplicados} unidades ajustadas" : 'Error al guardar',
            'mensaje' => count($errores) > 0
                ? 'Algunas filas tuvieron errores: ' . implode('; ', $errores)
                : 'Los montos sobrescritos ya se reflejan en boletas, reportes y deudas.',
        ]);
    }

    public function render()
    {
        return view('livewire.periodos.ajuste-temporal-montos')
            ->layout('layouts.app');
    }
}
