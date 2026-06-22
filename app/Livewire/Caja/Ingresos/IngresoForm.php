<?php

namespace App\Livewire\Caja\Ingresos;

use App\Models\Ingreso;
use App\Models\CategoriaIngreso;
use App\Models\Unidad;
use App\Models\Propietario;
use App\Models\Expensa;
use App\Models\SaldoAFavor;
use App\Services\IngresoService;
use App\Services\PagoExpensaService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Auth;

class IngresoForm extends Component
{
    use WithFileUploads;

    public $ingreso_id = null;
    public $numero_recibo = '';

    #[Validate('required|date')]
    public $fecha = '';

    #[Validate('required|exists:categorias_ingresos,id')]
    public $categoria_ingreso_id = '';

    #[Validate('required|numeric|min:0')]
    public $importe = 0;

    #[Validate('required|in:efectivo,transferencia,qr,otro')]
    public $metodo_pago = 'efectivo';

    #[Validate('nullable|string|max:255')]
    public $descripcion = '';

    public $comprobante_foto = null;
    public $comprobante_foto_actual = null;

    #[Validate('nullable|string|max:100')]
    public $referencia_bancaria = '';

    public $unidad_id = null;

    #[Validate('nullable|string')]
    public $observaciones = '';

    // Unidad pre-seleccionada (cuando viene del detalle de unidad)
    public $unidad_preseleccionada_id = null;

    // Para crear categoría rápida
    public $mostrarModalCategoria = false;
    public $nombre_categoria_nueva = '';
    public $color_categoria_nueva = '#10B981';

    // Para confirmación de guardado
    public $mostrarModalConfirmacion = false;

    public $categorias = [];
    public $unidades = [];

    // === SISTEMA DE PAGO DE EXPENSAS ===

    // Constante para categoría de expensas
    const CATEGORIA_EXPENSAS_ID = 1;

    // Estado del formulario
    public $esCategoriaExpensas = false;

    // Búsqueda híbrida (propietario/unidad)
    public $busqueda = '';
    public $resultadosBusqueda = [];
    public $propietarioSeleccionado = null;
    public $unidadesPropietario = [];

    // Deudas encontradas
    public $deudasPendientes = [];
    public $totalDeuda = 0;
    public $saldoFavorDisponible = 0;
    public $saldoFavorGenerado = 0;  // Saldo a favor que se generará con este pago
    public $totalAPagar = 0;

    // Campos del formulario para expensas
    #[Validate('nullable|string')]
    public $nombre_propietario = '';

    protected $messages = [
        'categoria_ingreso_id.required' => 'La categoría es obligatoria',
        'categoria_ingreso_id.exists' => 'La categoría seleccionada no es válida',
        'importe.required' => 'El importe es obligatorio',
        'importe.numeric' => 'El importe debe ser un número',
        'importe.min' => 'El importe debe ser mayor a 0',
        'metodo_pago.required' => 'El método de pago es obligatorio',
        'metodo_pago.in' => 'El método de pago seleccionado no es válido',
        'fecha.required' => 'La fecha es obligatoria',
        'fecha.date' => 'La fecha debe ser válida',
    ];

    public function mount($ingreso = null, $unidad = null)
    {
        $this->categorias = CategoriaIngreso::activas()->orderBy('nombre')->get();
        $this->unidades = Unidad::orderBy('codigo')->get();
        $this->fecha = now()->format('Y-m-d');

        // Si viene unidad (desde ruta de expensas), pre-seleccionar
        if ($unidad && !$ingreso) {
            $this->unidad_preseleccionada_id = $unidad;
            $this->preseleccionarUnidad($unidad);
        }

        if ($ingreso) {
            // Si viene como string (ID), buscar el modelo
            if (is_string($ingreso) || is_numeric($ingreso)) {
                $ingreso = Ingreso::with(['detalles.expensa.periodo', 'detalles.expensa.unidad'])
                    ->findOrFail($ingreso);
            }

            $this->ingreso_id = $ingreso->id;
            $this->numero_recibo = $ingreso->numero_recibo;
            $this->fecha = $ingreso->fecha->format('Y-m-d');
            $this->categoria_ingreso_id = $ingreso->categoria_ingreso_id;
            $this->importe = $ingreso->importe;
            $this->metodo_pago = $ingreso->metodo_pago;
            $this->descripcion = $ingreso->descripcion;
            $this->comprobante_foto_actual = $ingreso->comprobante_foto;
            $this->referencia_bancaria = $ingreso->referencia_bancaria;
            $this->unidad_id = $ingreso->unidad_id;
            $this->observaciones = $ingreso->observaciones;

            // Si es categoría expensas, cargar detalles para edición
            if ($this->categoria_ingreso_id == self::CATEGORIA_EXPENSAS_ID) {
                $this->esCategoriaExpensas = true;
                $this->cargarDetallesIngresoExpensas($ingreso);
            }
        }
    }

    /**
     * Cargar los detalles del ingreso de expensas para edición
     */
    protected function cargarDetallesIngresoExpensas($ingreso)
    {
        // Obtener el propietario basándose en las unidades de las expensas
        $primerDetalle = $ingreso->detalles->first();
        if ($primerDetalle && $primerDetalle->expensa) {
            $unidad = $primerDetalle->expensa->unidad;
            if ($unidad && $unidad->propietarioActual) {
                $propietario = $unidad->propietarioActual;

                $this->propietarioSeleccionado = $propietario;
                $this->nombre_propietario = $propietario->nombre_completo;
                $this->unidadesPropietario = $propietario->unidades()->where('activo', true)->get();

                // Cargar deudas del propietario
                $this->cargarDeudasPropietario();

                // Sobrescribir con los detalles del ingreso actual
                $this->restaurarDetallesDesdeIngreso($ingreso);
            }
        }
    }

    /**
     * Restaurar los detalles desde un ingreso existente
     */
    protected function restaurarDetallesDesdeIngreso($ingreso)
    {
        // Primero, desmarcar todas las deudas
        foreach ($this->deudasPendientes as &$periodoData) {
            foreach ($periodoData['expensas'] as &$expensa) {
                $expensa['pagar_expensa'] = false;
                $expensa['pagar_agua'] = false;
                $expensa['monto_expensa'] = 0;
                $expensa['monto_agua'] = 0;
            }
        }

        // Luego, marcar las que están en el ingreso
        foreach ($ingreso->detalles as $detalle) {
            if (!$detalle->expensa) continue;

            $expensaId = $detalle->expensa->id;

            // Buscar la expensa en deudasPendientes
            foreach ($this->deudasPendientes as &$periodoData) {
                foreach ($periodoData['expensas'] as &$expensa) {
                    if ($expensa['expensa_id'] == $expensaId) {
                        // Configurar según el concepto
                        switch ($detalle->concepto) {
                            case 'expensa':
                                $expensa['pagar_expensa'] = true;
                                $expensa['monto_expensa'] = $detalle->monto_expensa_aplicado;
                                break;
                            case 'agua':
                                $expensa['pagar_agua'] = true;
                                $expensa['monto_agua'] = $detalle->monto_agua_aplicado;
                                break;
                            case 'ambos':
                                $expensa['pagar_expensa'] = true;
                                $expensa['pagar_agua'] = true;
                                $expensa['monto_expensa'] = $detalle->monto_expensa_aplicado;
                                $expensa['monto_agua'] = $detalle->monto_agua_aplicado;
                                break;
                        }
                        break 2; // Salir de ambos loops
                    }
                }
            }
        }

        // Recalcular totales
        $this->calcularTotales();
    }

    /**
     * Actualizar estado cuando cambia la categoría
     */
    public function updatedCategoriaIngresoId($value)
    {
        $this->esCategoriaExpensas = ($value == self::CATEGORIA_EXPENSAS_ID);

        if ($this->esCategoriaExpensas) {
            // Limpiar datos cuando se selecciona expensas
            $this->importe = 0;
            $this->unidad_id = null;
            $this->limpiarBusqueda();
        } else {
            // Limpiar datos específicos de expensas
            $this->limpiarBusqueda();
        }
    }

    /**
     * Limpiar búsqueda y selección
     */
    public function limpiarBusqueda()
    {
        $this->busqueda = '';
        $this->resultadosBusqueda = [];
        $this->propietarioSeleccionado = null;
        $this->unidadesPropietario = [];
        $this->deudasPendientes = [];
        $this->totalDeuda = 0;
        $this->saldoFavorDisponible = 0;
        $this->saldoFavorGenerado = 0;
        $this->totalAPagar = 0;
        $this->nombre_propietario = '';
    }

    /**
     * Realizar búsqueda híbrida (propietario o unidad)
     * Usa los scopes optimizados de los modelos para búsquedas precisas
     */
    public function buscarDeudas()
    {
        $this->validate(['busqueda' => 'required|string|min:2']);

        $termino = trim($this->busqueda);
        $this->resultadosBusqueda = [];

        // Usar el scope buscar() optimizado del modelo Propietario
        $propietarios = Propietario::buscar($termino)
            ->with(['unidades' => function($query) {
                $query->where('activo', true);
            }])
            ->limit(10)
            ->get();

        foreach ($propietarios as $propietario) {
            // Obtener unidades activas del propietario
            $unidadesActivas = $propietario->unidades()->where('activo', true)->get();

            if ($unidadesActivas->count() > 0) {
                $this->resultadosBusqueda[] = [
                    'tipo' => 'propietario',
                    'id' => $propietario->id,
                    'nombre' => $propietario->nombre_completo,
                    'ci' => $propietario->ci,
                    'unidades' => $unidadesActivas->map(fn($u) => [
                        'id' => $u->id,
                        'codigo' => $u->codigo,
                        'tipo' => $u->tipo_unidad->getLabel(),
                    ])->toArray(),
                ];
            }
        }

        // Usar el scope buscar() optimizado del modelo Unidad
        $unidades = Unidad::buscar($termino)
            ->where('activo', true)
            ->with(['propietarioActual'])
            ->limit(10)
            ->get();

        foreach ($unidades as $unidad) {
            $propietarioActual = $unidad->propietarioActual;

            if ($propietarioActual && !$this->resultadoYaExiste($propietarioActual->id)) {
                // Obtener todas las unidades del mismo propietario
                $unidadesDelPropietario = $propietarioActual->unidades()
                    ->where('activo', true)
                    ->get();

                $this->resultadosBusqueda[] = [
                    'tipo' => 'unidad',
                    'id' => $propietarioActual->id,
                    'nombre' => $propietarioActual->nombre_completo,
                    'ci' => $propietarioActual->ci,
                    'unidades_buscadas' => [$unidad->codigo], // Unidades que coincidieron con la búsqueda
                    'unidades' => $unidadesDelPropietario->map(fn($u) => [
                        'id' => $u->id,
                        'codigo' => $u->codigo,
                        'tipo' => $u->tipo_unidad->getLabel(),
                    ])->toArray(),
                ];
            }
        }
    }

    /**
     * Verificar si un resultado ya existe en la lista
     */
    private function resultadoYaExiste($propietarioId)
    {
        return collect($this->resultadosBusqueda)->contains('id', $propietarioId);
    }

    /**
     * Seleccionar propietario y cargar sus deudas
     */
    public function seleccionarPropietario($propietarioId)
    {
        $propietario = Propietario::with(['unidades' => function($query) {
            $query->where('activo', true);
        }])->findOrFail($propietarioId);

        $this->propietarioSeleccionado = $propietario;
        $this->nombre_propietario = $propietario->nombre_completo;
        $this->unidadesPropietario = $propietario->unidades->where('activo', true);

        // Cargar deudas de todas las unidades
        $this->cargarDeudasPropietario();

        // Limpiar resultados de búsqueda
        $this->resultadosBusqueda = [];
        $this->busqueda = '';
    }

    /**
     * Cargar deudas del propietario seleccionado
     */
    protected function cargarDeudasPropietario()
    {
        $this->deudasPendientes = [];
        $unidadIds = $this->unidadesPropietario->pluck('id');

        // Obtener expensas con deuda de todas las unidades
        $expensas = Expensa::whereIn('unidad_id', $unidadIds)
            ->where(function($query) {
                $query->where('expensa_pendiente', '>', 0)
                    ->orWhere('agua_pendiente', '>', 0);
            })
            ->with(['periodo', 'unidad'])
            ->orderBy('periodo_id', 'desc')
            ->get();

        // Agrupar por período
        $agrupadas = [];
        foreach ($expensas as $expensa) {
            $periodoNombre = $expensa->periodo->nombre_periodo;

            if (!isset($agrupadas[$periodoNombre])) {
                $agrupadas[$periodoNombre] = [];
            }

            // Determinar si la unidad tiene medidor de agua
            $tieneMedidor = $expensa->unidad->tiene_medidor ?? false;

            $agrupadas[$periodoNombre][] = [
                'expensa_id' => $expensa->id,
                'unidad_id' => $expensa->unidad_id,
                'unidad_codigo' => $expensa->unidad->codigo,
                'periodo' => $periodoNombre,
                'periodo_cerrado' => $expensa->periodo->estado === 'cerrado',
                'tiene_medidor' => $tieneMedidor,
                'deuda_expensa' => $expensa->expensa_pendiente,
                'deuda_agua' => $tieneMedidor ? $expensa->agua_pendiente : 0,
                'deuda_total' => $expensa->expensa_pendiente + ($tieneMedidor ? $expensa->agua_pendiente : 0),
                'pagar_expensa' => $expensa->expensa_pendiente > 0,
                'pagar_agua' => $tieneMedidor && $expensa->agua_pendiente > 0,
                'monto_expensa' => $expensa->expensa_pendiente,
                'monto_agua' => $tieneMedidor ? $expensa->agua_pendiente : 0,
            ];
        }

        // Convertir a array indexado manteniendo nombres de períodos
        $this->deudasPendientes = [];
        foreach ($agrupadas as $periodoNombre => $expensas) {
            $this->deudasPendientes[] = [
                'periodo_nombre' => $periodoNombre,
                'expensas' => $expensas,
            ];
        }

        // Calcular totales
        $this->calcularTotales();
    }

    /**
     * Pre-seleccionar unidad cuando viene del detalle de unidad
     * Carga automáticamente la categoría Expensas, busca el propietario y carga todas sus deudas
     */
    protected function preseleccionarUnidad($unidadId)
    {
        try {
            $unidad = Unidad::with('propietarioActual')->findOrFail($unidadId);

            if (!$unidad->propietarioActual) {
                // Si la unidad no tiene propietario, no se puede pre-seleccionar
                return;
            }

            // Pre-seleccionar categoría Expensas
            $this->categoria_ingreso_id = self::CATEGORIA_EXPENSAS_ID;
            $this->esCategoriaExpensas = true;

            // Guardar la unidad pre-seleccionada para poder resaltarla en la vista
            $this->unidad_preseleccionada_id = $unidadId;

            // Seleccionar el propietario automáticamente
            $this->propietarioSeleccionado = $unidad->propietarioActual;
            $this->nombre_propietario = $unidad->propietarioActual->nombre_completo;
            $this->unidadesPropietario = $unidad->propietarioActual->unidades()->where('activo', true)->get();

            // Cargar deudas de todas las unidades del propietario
            $this->cargarDeudasPropietario();

        } catch (\Exception $e) {
            // Si hay error, loguear pero no interrumpir
            \Log::warning('Error al pre-seleccionar unidad: ' . $e->getMessage());
        }
    }

    /**
     * Calcular totales de deuda y saldos a favor
     */
    protected function calcularTotales()
    {
        // Total deuda de todas las expensas
        $this->totalDeuda = collect($this->deudasPendientes)
            ->pluck('expensas')
            ->flatten(1)
            ->sum('deuda_total');

        // Calcular total que el usuario está pagando (sin importar la deuda)
        $this->totalAPagar = collect($this->deudasPendientes)
            ->pluck('expensas')
            ->flatten(1)
            ->sum(function($expensa) {
                $total = 0;
                if ($expensa['pagar_expensa']) {
                    $total += floatval($expensa['monto_expensa'] ?? 0);
                }
                if ($expensa['pagar_agua']) {
                    $total += floatval($expensa['monto_agua'] ?? 0);
                }
                return $total;
            });

        // Calcular el saldo a favor que se generará con este pago
        // (si el usuario paga más de lo que debe)
        $deudaTotalPagar = collect($this->deudasPendientes)
            ->pluck('expensas')
            ->flatten(1)
            ->sum(function($expensa) {
                $total = 0;
                if ($expensa['pagar_expensa']) {
                    $total += floatval($expensa['deuda_expensa'] ?? 0);
                }
                if ($expensa['pagar_agua']) {
                    $total += floatval($expensa['deuda_agua'] ?? 0);
                }
                return $total;
            });

        // Saldo a favor generado = lo que paga - lo que debe
        $this->saldoFavorGenerado = max(0, $this->totalAPagar - $deudaTotalPagar);

        // Buscar saldos a favor disponibles del propietario (de la base de datos)
        if ($this->propietarioSeleccionado) {
            $this->saldoFavorDisponible = SaldoAFavor::whereHas('ingreso', function($query) {
                $query->where('tipo_ingreso', 'expensa');
            })
            ->where('estado', 'disponible')
            ->whereHas('ingreso', function($query) {
                // Verificar que el ingreso esté asociado a unidades del propietario
                $query->whereHas('detalles', function($q) {
                    $unidadIds = $this->unidadesPropietario->pluck('id');
                    $q->whereIn('expensa_id', function($query) use ($unidadIds) {
                        $query->select('id')
                            ->from('expensas')
                            ->whereIn('unidad_id', $unidadIds);
                    });
                });
            })
            ->sum('monto_saldo');
        } else {
            $this->saldoFavorDisponible = 0;
        }

        // Actualizar importe del formulario
        // El importe es lo que realmente se debe pagar en efectivo/transferencia
        // Total a pagar - saldos disponibles (no incluye el saldo a favor generado)
        $this->importe = max(0, $this->totalAPagar - $this->saldoFavorDisponible);
    }

    /**
     * Seleccionar todas las deudas
     */
    public function seleccionarTodasDeudas()
    {
        foreach ($this->deudasPendientes as &$periodoData) {
            foreach ($periodoData['expensas'] as &$expensa) {
                $expensa['pagar_expensa'] = $expensa['deuda_expensa'] > 0;
                $expensa['pagar_agua'] = $expensa['deuda_agua'] > 0;
                $expensa['monto_expensa'] = $expensa['deuda_expensa'];
                $expensa['monto_agua'] = $expensa['deuda_agua'];
            }
        }
        $this->calcularTotales();
    }

    /**
     * Deseleccionar todas las deudas
     */
    public function deseleccionarTodasDeudas()
    {
        foreach ($this->deudasPendientes as &$periodoData) {
            foreach ($periodoData['expensas'] as &$expensa) {
                $expensa['pagar_expensa'] = false;
                $expensa['pagar_agua'] = false;
                $expensa['monto_expensa'] = 0;
                $expensa['monto_agua'] = 0;
            }
        }
        $this->calcularTotales();
    }

    /**
     * Seleccionar todas las deudas de un período específico
     */
    public function seleccionarPeriodo($periodoIndex)
    {
        if (!isset($this->deudasPendientes[$periodoIndex])) {
            return;
        }

        foreach ($this->deudasPendientes[$periodoIndex]['expensas'] as &$expensa) {
            $expensa['pagar_expensa'] = $expensa['deuda_expensa'] > 0;
            $expensa['pagar_agua'] = $expensa['deuda_agua'] > 0;
            $expensa['monto_expensa'] = $expensa['deuda_expensa'];
            $expensa['monto_agua'] = $expensa['deuda_agua'];
        }
        $this->calcularTotales();
    }

    /**
     * Deseleccionar todas las deudas de un período específico
     */
    public function deseleccionarPeriodo($periodoIndex)
    {
        if (!isset($this->deudasPendientes[$periodoIndex])) {
            return;
        }

        foreach ($this->deudasPendientes[$periodoIndex]['expensas'] as &$expensa) {
            $expensa['pagar_expensa'] = false;
            $expensa['pagar_agua'] = false;
            $expensa['monto_expensa'] = 0;
            $expensa['monto_agua'] = 0;
        }
        $this->calcularTotales();
    }

    /**
     * Actualizar monto de expensa cuando cambia checkbox
     */
    public function actualizarMontoExpensa($periodoIndex, $expensaIndex)
    {
        if (!isset($this->deudasPendientes[$periodoIndex]['expensas'][$expensaIndex])) {
            return;
        }

        $expensa = &$this->deudasPendientes[$periodoIndex]['expensas'][$expensaIndex];

        // Sincronizar: si se marca el checkbox, poner el monto de la deuda
        // Si se desmarca, poner el monto en 0
        if ($expensa['pagar_expensa']) {
            $monto = floatval($expensa['monto_expensa'] ?? 0);
            if ($monto == 0) {
                $expensa['monto_expensa'] = $expensa['deuda_expensa'];
            }
        } else {
            $expensa['monto_expensa'] = 0;
        }

        $this->calcularTotales();
    }

    /**
     * Actualizar monto de agua cuando cambia checkbox
     */
    public function actualizarMontoAgua($periodoIndex, $expensaIndex)
    {
        if (!isset($this->deudasPendientes[$periodoIndex]['expensas'][$expensaIndex])) {
            return;
        }

        $expensa = &$this->deudasPendientes[$periodoIndex]['expensas'][$expensaIndex];

        // Sincronizar: si se marca el checkbox, poner el monto de la deuda
        // Si se desmarca, poner el monto en 0
        if ($expensa['pagar_agua']) {
            $monto = floatval($expensa['monto_agua'] ?? 0);
            if ($monto == 0) {
                $expensa['monto_agua'] = $expensa['deuda_agua'];
            }
        } else {
            $expensa['monto_agua'] = 0;
        }

        $this->calcularTotales();
    }

    /**
     * Cuando el monto de expensa cambia manualmente (desde el input)
     */
    public function actualizarDesdeInputExpensa($periodoIndex, $expensaIndex)
    {
        if (!isset($this->deudasPendientes[$periodoIndex]['expensas'][$expensaIndex])) {
            return;
        }

        $expensa = &$this->deudasPendientes[$periodoIndex]['expensas'][$expensaIndex];
        $monto = floatval($expensa['monto_expensa'] ?? 0);

        // Si el monto es 0, desmarcar el checkbox
        if ($monto == 0) {
            $expensa['pagar_expensa'] = false;
        } else {
            // Si el monto es mayor a 0, marcar el checkbox
            $expensa['pagar_expensa'] = true;
        }

        $this->calcularTotales();
    }

    /**
     * Cuando el monto de agua cambia manualmente (desde el input)
     */
    public function actualizarDesdeInputAgua($periodoIndex, $expensaIndex)
    {
        if (!isset($this->deudasPendientes[$periodoIndex]['expensas'][$expensaIndex])) {
            return;
        }

        $expensa = &$this->deudasPendientes[$periodoIndex]['expensas'][$expensaIndex];
        $monto = floatval($expensa['monto_agua'] ?? 0);

        // Si el monto es 0, desmarcar el checkbox
        if ($monto == 0) {
            $expensa['pagar_agua'] = false;
        } else {
            // Si el monto es mayor a 0, marcar el checkbox
            $expensa['pagar_agua'] = true;
        }

        $this->calcularTotales();
    }

    /**
     * Watcher para recalcular totales cuando cambia deudasPendientes
     */
    public function updatedDeudasPendientes()
    {
        $this->calcularTotales();
    }

    /**
     * Verificar si un período tiene items seleccionados
     */
    public function periodoTieneSeleccionados($periodoIndex)
    {
        if (!isset($this->deudasPendientes[$periodoIndex])) {
            return false;
        }

        foreach ($this->deudasPendientes[$periodoIndex]['expensas'] as $expensa) {
            if (($expensa['pagar_expensa'] && floatval($expensa['monto_expensa'] ?? 0) > 0) ||
                ($expensa['pagar_agua'] && floatval($expensa['monto_agua'] ?? 0) > 0)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtener lista de expensas a pagar para el servicio
     */
    protected function obtenerExpensasAPagar()
    {
        $expensasAProcesar = [];

        foreach ($this->deudasPendientes as $periodoData) {
            foreach ($periodoData['expensas'] as $expensa) {
                $tieneExpensa = $expensa['pagar_expensa'] && floatval($expensa['monto_expensa'] ?? 0) > 0;
                $tieneAgua = $expensa['pagar_agua'] && floatval($expensa['monto_agua'] ?? 0) > 0;

                if ($tieneExpensa || $tieneAgua) {
                    // Determinar el concepto
                    if ($tieneExpensa && $tieneAgua) {
                        $concepto = 'ambos';
                        $montoTotal = floatval($expensa['monto_expensa']) + floatval($expensa['monto_agua']);
                    } elseif ($tieneExpensa) {
                        $concepto = 'expensa';
                        $montoTotal = floatval($expensa['monto_expensa']);
                    } else {
                        $concepto = 'agua';
                        $montoTotal = floatval($expensa['monto_agua']);
                    }

                    $expensasAProcesar[] = [
                        'expensa_id' => $expensa['expensa_id'],
                        'monto_aplicar' => $montoTotal,
                        'concepto' => $concepto,
                        'monto_expensa' => floatval($expensa['monto_expensa'] ?? 0),
                        'monto_agua' => floatval($expensa['monto_agua'] ?? 0),
                    ];
                }
            }
        }

        return $expensasAProcesar;
    }

    public function updatedMetodoPago($value)
    {
        // Si es efectivo, limpiar referencia bancaria
        if ($value === 'efectivo') {
            $this->referencia_bancaria = '';
        }
    }

    public function mostrarConfirmacion()
    {
        // Validaciones básicas primero
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Dejar que Livewire maneje los errores de validación
            throw $e;
        }

        // Si es categoría expensas, validar que haya deudas seleccionadas
        if ($this->esCategoriaExpensas) {
            $expensasAPagar = $this->obtenerExpensasAPagar();

            if (empty($expensasAPagar)) {
                $this->dispatch('alerta', [
                    'tipo' => 'warning',
                    'titulo' => 'Atención',
                    'mensaje' => 'Debe seleccionar al menos una deuda para pagar',
                ]);
                return;
            }
        }

        // Validar que se suba comprobante si NO es efectivo
        if ($this->metodo_pago !== 'efectivo' && !$this->comprobante_foto && !$this->comprobante_foto_actual) {
            $this->dispatch('alerta', [
                'tipo' => 'warning',
                'titulo' => 'Atención',
                'mensaje' => 'Para pagos con transferencia o QR es necesario adjuntar el comprobante',
            ]);
            return;
        }

        // Si todo está validado, mostrar modal de confirmación
        $this->mostrarModalConfirmacion = true;
    }

    public function cancelarConfirmacion()
    {
        $this->mostrarModalConfirmacion = false;
    }

    public function confirmarGuardado()
    {
        // Cerrar modal y proceder con el guardado
        $this->mostrarModalConfirmacion = false;

        try {
            $service = app(IngresoService::class);

            $datos = [
                'fecha' => $this->fecha,
                'categoria_ingreso_id' => $this->categoria_ingreso_id,
                'importe' => $this->importe,
                'metodo_pago' => $this->metodo_pago,
                'descripcion' => $this->descripcion ?: 'Pago de expensas',
                'referencia_bancaria' => $this->referencia_bancaria,
                'unidad_id' => $this->unidad_id,
                'observaciones' => $this->observaciones,
            ];

            if ($this->esCategoriaExpensas) {
                // Preparar datos para el servicio de pago de expensas
                $datosPago = [
                    'expensas' => $this->obtenerExpensasAPagar(),
                    'fecha_pago' => $this->fecha,
                    'metodo_pago' => $this->metodo_pago,
                    'descripcion' => $this->descripcion ?: ('Pago de expensas - ' . $this->propietarioSeleccionado->nombre_completo),
                    'referencia_bancaria' => $this->referencia_bancaria,
                    'observaciones' => $this->observaciones,
                ];

                // Usar el servicio de pago de expensas
                $pagoService = app(PagoExpensaService::class);

                if ($this->ingreso_id) {
                    // Actualizar ingreso existente
                    $ingreso = Ingreso::findOrFail($this->ingreso_id);
                    $resultado = $pagoService->actualizarRecibo($ingreso, $datosPago, $this->comprobante_foto);
                } else {
                    // Crear nuevo ingreso
                    $resultado = $pagoService->procesarRecibo($datosPago, $this->comprobante_foto);
                }

                $ingreso = $resultado['ingreso'];
            } else {
                if ($this->ingreso_id) {
                    // Actualizar
                    $ingreso = Ingreso::findOrFail($this->ingreso_id);
                    $service->actualizar($ingreso, $datos, $this->comprobante_foto);
                } else {
                    // Crear
                    $ingreso = $service->crear($datos, $this->comprobante_foto);
                }
            }

            $mensaje = $this->ingreso_id ? 'Ingreso actualizado correctamente' : 'Ingreso creado correctamente';
            $evento = $this->ingreso_id ? 'ingreso-actualizado' : 'ingreso-creado';

            // Limpiar formulario solo si es creación nueva
            if (!$this->ingreso_id) {
                $this->limpiarFormulario();
            } else {
                // Si es edición, recargar los datos actualizados
                if ($this->esCategoriaExpensas) {
                    $this->cargarDetallesIngresoExpensas($ingreso);
                }
            }

            $this->dispatch($evento);
            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Éxito',
                'mensaje' => $mensaje,
            ]);

            // Redirigir al listado después de la alerta
            $this->dispatch('redirect-to', route('caja.ingresos.index'));

        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => 'Error al guardar el ingreso: ' . $e->getMessage(),
            ]);
        }
    }

    public function limpiarComprobante()
    {
        $this->comprobante_foto = null;
        $this->comprobante_foto_actual = null;
    }

    public function abrirModalCategoria()
    {
        $this->mostrarModalCategoria = true;
    }

    public function cerrarModalCategoria()
    {
        $this->mostrarModalCategoria = false;
        $this->nombre_categoria_nueva = '';
        $this->color_categoria_nueva = '#10B981';
    }

    public function crearCategoriaRapida()
    {
        $this->validate([
            'nombre_categoria_nueva' => 'required|string|max:100',
            'color_categoria_nueva' => 'required|string|max:7',
        ]);

        try {
            $service = app(\App\Services\CategoriaIngresoService::class);
            $categoria = $service->crear([
                'nombre' => $this->nombre_categoria_nueva,
                'color' => $this->color_categoria_nueva,
                'descripcion' => '',
                'icono' => 'dollar-sign',
            ]);

            // Asignar automáticamente al ingreso actual
            $this->categoria_ingreso_id = $categoria->id;

            // Recargar categorías
            $this->categorias = CategoriaIngreso::activas()->orderBy('nombre')->get();

            // Cerrar modal y limpiar
            $this->cerrarModalCategoria();

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Éxito',
                'mensaje' => 'Categoría creada correctamente',
            ]);

        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => 'Error al crear la categoría: ' . $e->getMessage(),
            ]);
        }
    }

    protected function limpiarFormulario()
    {
        $this->ingreso_id = null;
        $this->numero_recibo = '';
        $this->fecha = now()->format('Y-m-d');
        $this->categoria_ingreso_id = '';
        $this->importe = 0;
        $this->metodo_pago = 'efectivo';
        $this->descripcion = '';
        $this->comprobante_foto = null;
        $this->comprobante_foto_actual = null;
        $this->referencia_bancaria = '';
        $this->unidad_id = null;
        $this->observaciones = '';
        $this->limpiarBusqueda();
    }

    public function render()
    {
        return view('livewire.caja.ingresos.ingreso-form')
            ->layout('layouts.app');
    }
}
