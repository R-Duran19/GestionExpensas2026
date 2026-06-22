<?php

namespace App\Livewire\Propietarios;

use App\Models\Propietario;
use App\Services\PropietarioService;
use Livewire\Component;

class PropietarioForm extends Component
{
    // Modo: crear o editar
    public $modo = 'crear';

    // Datos del formulario
    public $propietario_id = null;
    public $nombre = '';
    public $apellido = '';
    public $ci = '';
    public $telefono = '';
    public $email = '';
    public $observaciones = '';

    // Para validación de CI único
    public $ci_original = '';

    protected PropietarioService $propietarioService;

    // Reglas de validación
    public $rules = [
        'nombre' => 'required|string|max:100',
        'apellido' => 'required|string|max:100',
        'ci' => 'required|string|max:20|unique:propietarios,ci',
        'telefono' => 'required|string|max:20',
        'email' => 'nullable|email|max:100|unique:propietarios,email',
        'observaciones' => 'nullable|string|max:500',
    ];

    protected $messages = [
        'nombre.required' => 'El nombre es obligatorio',
        'apellido.required' => 'El apellido es obligatorio',
        'ci.required' => 'El CI es obligatorio',
        'ci.unique' => 'El CI ya está registrado',
        'telefono.required' => 'El teléfono es obligatorio',
        'email.email' => 'El email no es válido',
        'email.unique' => 'El email ya está registrado',
    ];

    public function boot(PropietarioService $propietarioService)
    {
        $this->propietarioService = $propietarioService;
    }

    /**
     * Crear o editar un propietario
     */
    public function mount($propietario = null)
    {
        if ($propietario) {
            $this->modo = 'editar';
            // $propietario puede ser el ID (string/int) o el modelo
            if (is_numeric($propietario) || is_string($propietario)) {
                $propietario = Propietario::findOrFail($propietario);
            }
            $this->cargarDatosPropietario($propietario);
        } else {
            $this->modo = 'crear';
        }
    }

    /**
     * Cargar datos de un propietario existente
     */
    private function cargarDatosPropietario(Propietario $propietario)
    {
        $this->propietario_id = $propietario->id;
        $this->nombre = $propietario->nombre;
        $this->apellido = $propietario->apellido;
        $this->ci = $propietario->ci;
        $this->ci_original = $propietario->ci; // Guardar para validación
        $this->telefono = $propietario->telefono;
        $this->email = $propietario->email ?? '';
        $this->observaciones = $propietario->observaciones ?? '';

        // Actualizar reglas para edición
        $this->rules['ci'] = 'required|string|max:20|unique:propietarios,ci,' . $this->propietario_id;
        $this->rules['email'] = 'nullable|email|max:100|unique:propietarios,email,' . $this->propietario_id;
    }

    /**
     * Guardar propietario (crear o actualizar)
     */
    public function guardar()
    {
        // Validar
        $this->validate();

        try {
            if ($this->modo === 'crear') {
                // Crear nuevo propietario
                $propietario = Propietario::create([
                    'nombre' => ucwords(strtolower($this->nombre)),
                    'apellido' => ucwords(strtolower($this->apellido)),
                    'ci' => strtoupper($this->ci),
                    'telefono' => $this->telefono,
                    'email' => $this->email ?: null,
                    'activo' => true,
                    'observaciones' => $this->observaciones ?: null,
                ]);

                $this->dispatch('toast-success', message: 'Propietario creado correctamente');
            } else {
                // Actualizar propietario existente
                $propietario = Propietario::find($this->propietario_id);

                if (!$propietario) {
                    $this->dispatch('toast-error', message: 'Propietario no encontrado');
                    return;
                }

                $propietario->update([
                    'nombre' => ucwords(strtolower($this->nombre)),
                    'apellido' => ucwords(strtolower($this->apellido)),
                    'ci' => strtoupper($this->ci),
                    'telefono' => $this->telefono,
                    'email' => $this->email ?: null,
                    'observaciones' => $this->observaciones ?: null,
                ]);

                $this->dispatch('toast-success', message: 'Propietario actualizado correctamente');
            }

            // Notificar al componente lista
            $this->dispatch('propietario-actualizado');

            // Redirigir a la lista
            return redirect()->route('propietarios.index');

        } catch (\Exception $e) {
            $this->dispatch('toast-error', message: 'Error al guardar: ' . $e->getMessage());
        }
    }

    /**
     * Cancelar y volver a la lista
     */
    public function cancelar()
    {
        return redirect()->route('propietarios.index');
    }

    /**
     * Obtener título del formulario
     */
    public function getTituloProperty(): string
    {
        return $this->modo === 'crear' ? 'Nuevo Propietario' : 'Editar Propietario';
    }

    /**
     * Verificar si el formulario es de edición
     */
    public function getEsEdicionProperty(): bool
    {
        return $this->modo === 'editar';
    }

    /**
     * Obtener nombre completo formateado
     */
    public function getNombreCompletoProperty(): string
    {
        return trim($this->nombre . ' ' . $this->apellido);
    }

    public function render()
    {
        return view('livewire.propietarios.propietario-form')
            ->layout('layouts.app');
    }
}
