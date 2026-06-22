<?php

namespace App\Livewire\Propiedades;

use App\Enums\CategoriaUnidad;
use App\Enums\TipoUnidad;
use App\Models\Unidad;
use Livewire\Component;

class UnidadForm extends Component
{
    // Modo: crear o editar
    public $modo = 'crear';

    // Datos del formulario
    public $unidad_id = null;
    public $codigo = '';
    public $tipo_unidad = '';
    public $piso = '';
    public $metros_cuadrados = '';
    public $categoria_unidad = '';
    public $tiene_medidor = true;
    public $observaciones = '';

    // Para validación en tiempo real
    public $rules = [
        'codigo' => 'required|string|max:20|unique:unidades,codigo',
        'tipo_unidad' => 'required|in:departamento,oficina,local,parqueo,baulera',
        'piso' => 'required|string|max:50',
        'metros_cuadrados' => 'required|numeric|min:0|max:999999.99',
        'categoria_unidad' => 'required|in:domiciliaria,comercial',
        'tiene_medidor' => 'boolean',
        'observaciones' => 'nullable|string|max:500',
    ];

    protected $messages = [
        'codigo.required' => 'El código es obligatorio',
        'codigo.unique' => 'El código ya existe',
        'tipo_unidad.required' => 'El tipo de unidad es obligatorio',
        'piso.required' => 'La ubicación (piso) es obligatoria',
        'metros_cuadrados.required' => 'Los metros cuadrados son obligatorios',
        'metros_cuadrados.numeric' => 'Los metros cuadrados deben ser un número',
        'categoria_unidad.required' => 'La categoría es obligatoria',
    ];

    /**
     * Crear o editar una unidad
     */
    public function mount($unidad = null)
    {
        if ($unidad) {
            $this->modo = 'editar';
            // $unidad puede ser el ID (string/int) o el modelo
            if (is_numeric($unidad) || is_string($unidad)) {
                $unidad = Unidad::findOrFail($unidad);
            }
            $this->cargarDatosUnidad($unidad);
        } else {
            $this->modo = 'crear';
            $this->categoria_unidad = '';
            $this->tipo_unidad = '';
        }
    }

    /**
     * Cargar datos de una unidad existente
     */
    private function cargarDatosUnidad(Unidad $unidad)
    {
        $this->unidad_id = $unidad->id;
        $this->codigo = $unidad->codigo;
        $this->tipo_unidad = $unidad->tipo_unidad->value;
        $this->piso = $unidad->piso;
        $this->metros_cuadrados = $unidad->metros_cuadrados;
        $this->categoria_unidad = $unidad->categoria_unidad->value;
        $this->tiene_medidor = $unidad->tiene_medidor;
        $this->observaciones = $unidad->observaciones ?? '';

        // Actualizar reglas para edición
        $this->rules['codigo'] = 'required|string|max:20|unique:unidades,codigo,' . $this->unidad_id;
    }

    /**
     * Actualizar categoría automáticamente cuando cambia el tipo
     */
    public function updatedTipoUnidad($valor)
    {
        if (!$valor) {
            return;
        }

        $tipo = TipoUnidad::from($valor);
        $categoriaPermitida = $tipo->getCategoriaPermitida();
        $tieneMedidor = $tipo->getTieneMedidor();

        $this->categoria_unidad = $categoriaPermitida->value;
        $this->tiene_medidor = $tieneMedidor;
    }

    /**
     * Guardar unidad (crear o actualizar)
     */
    public function guardar()
    {
        // Validar
        $this->validate();

        try {
            // Verificar compatibilidad tipo-categoría
            $tipo = TipoUnidad::from($this->tipo_unidad);
            $categoria = CategoriaUnidad::from($this->categoria_unidad);
            $categoriaPermitida = $tipo->getCategoriaPermitida();

            if ($categoria !== $categoriaPermitida) {
                $this->dispatch('toast-error', message: sprintf(
                    'El tipo %s no puede ser categoría %s. Debe ser %s.',
                    $tipo->getLabel(),
                    $categoria->getLabel(),
                    $categoriaPermitida->getLabel()
                ));
                return;
            }

            if ($this->modo === 'crear') {
                // Crear nueva unidad
                $unidad = Unidad::create([
                    'codigo' => strtoupper($this->codigo),
                    'tipo_unidad' => $this->tipo_unidad,
                    'piso' => $this->piso,
                    'metros_cuadrados' => $this->metros_cuadrados,
                    'categoria_unidad' => $this->categoria_unidad,
                    'tiene_medidor' => $this->tiene_medidor,
                    'activo' => false, // Inactivo hasta que se asigne propietario
                    'observaciones' => $this->observaciones ?: null,
                ]);

                $this->dispatch('toast-success', message: 'Unidad creada correctamente');
            } else {
                // Actualizar unidad existente
                $unidad = Unidad::find($this->unidad_id);

                if (!$unidad) {
                    $this->dispatch('toast-error', message: 'Unidad no encontrada');
                    return;
                }

                $unidad->update([
                    'codigo' => strtoupper($this->codigo),
                    'tipo_unidad' => $this->tipo_unidad,
                    'piso' => $this->piso,
                    'metros_cuadrados' => $this->metros_cuadrados,
                    'categoria_unidad' => $this->categoria_unidad,
                    'tiene_medidor' => $this->tiene_medidor,
                    'observaciones' => $this->observaciones ?: null,
                ]);

                $this->dispatch('toast-success', message: 'Unidad actualizada correctamente');
            }

            // Notificar al componente lista
            $this->dispatch('unidad-actualizada');

            // Redirigir a la lista
            return redirect()->route('unidades.index');

        } catch (\Exception $e) {
            $this->dispatch('toast-error', message: 'Error al guardar: ' . $e->getMessage());
        }
    }

    /**
     * Cancelar y volver a la lista
     */
    public function cancelar()
    {
        return redirect()->route('unidades.index');
    }

    /**
     * Obtener tipos de unidad
     */
    public function getTiposUnidadProperty(): array
    {
        return TipoUnidad::toSelectArray();
    }

    /**
     * Obtener categorías
     */
    public function getCategoriasProperty(): array
    {
        return CategoriaUnidad::toSelectArray();
    }

    /**
     * Obtener título del formulario
     */
    public function getTituloProperty(): string
    {
        return $this->modo === 'crear' ? 'Nueva Unidad' : 'Editar Unidad';
    }

    /**
     * Verificar si el formulario es de edición
     */
    public function getEsEdicionProperty(): bool
    {
        return $this->modo === 'editar';
    }

    public function render()
    {
        return view('livewire.propiedades.unidad-form')
            ->layout('layouts.app');
    }
}
