<?php

namespace App\Livewire\Inquilinos;

use App\Models\Inquilino;
use App\Services\InquilinoService;
use Livewire\Component;

class InquilinoForm extends Component
{
    // Modo: crear o editar
    public $modo = 'crear';

    // Datos del formulario
    public $inquilino_id = null;
    public $nombre = '';
    public $apellido = '';
    public $ci = '';
    public $telefono = '';
    public $email = '';
    public $observaciones = '';

    // Para validación de CI único
    public $ci_original = '';

    protected InquilinoService $inquilinoService;

    // Reglas de validación
    public $rules = [
        'nombre' => 'required|string|max:100',
        'apellido' => 'required|string|max:100',
        'ci' => 'required|string|max:20|unique:inquilinos,ci',
        'telefono' => 'required|string|max:20',
        'email' => 'nullable|email|max:100|unique:inquilinos,email',
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

    public function boot(InquilinoService $inquilinoService)
    {
        $this->inquilinoService = $inquilinoService;
    }

    /**
     * Crear o editar un inquilino
     */
    public function mount($inquilino = null)
    {
        if ($inquilino) {
            $this->modo = 'editar';
            // $inquilino puede ser el ID (string/int) o el modelo
            if (is_numeric($inquilino) || is_string($inquilino)) {
                $inquilino = Inquilino::findOrFail($inquilino);
            }
            $this->cargarDatosInquilino($inquilino);
        } else {
            $this->modo = 'crear';
        }
    }

    /**
     * Cargar datos de un inquilino existente
     */
    private function cargarDatosInquilino(Inquilino $inquilino)
    {
        $this->inquilino_id = $inquilino->id;
        $this->nombre = $inquilino->nombre;
        $this->apellido = $inquilino->apellido;
        $this->ci = $inquilino->ci;
        $this->ci_original = $inquilino->ci; // Guardar para validación
        $this->telefono = $inquilino->telefono;
        $this->email = $inquilino->email ?? '';
        $this->observaciones = $inquilino->observaciones ?? '';

        // Actualizar reglas para edición
        $this->rules['ci'] = 'required|string|max:20|unique:inquilinos,ci,' . $this->inquilino_id;
        $this->rules['email'] = 'nullable|email|max:100|unique:inquilinos,email,' . $this->inquilino_id;
    }

    /**
     * Guardar inquilino (crear o actualizar)
     */
    public function guardar()
    {
        // Validar
        $this->validate();

        try {
            if ($this->modo === 'crear') {
                // Crear nuevo inquilino
                $inquilino = Inquilino::create([
                    'nombre' => ucwords(strtolower($this->nombre)),
                    'apellido' => ucwords(strtolower($this->apellido)),
                    'ci' => strtoupper($this->ci),
                    'telefono' => $this->telefono,
                    'email' => $this->email ?: null,
                    'activo' => true,
                    'observaciones' => $this->observaciones ?: null,
                ]);

                $this->dispatch('toast-success', message: 'Inquilino creado correctamente');
            } else {
                // Actualizar inquilino existente
                $inquilino = Inquilino::find($this->inquilino_id);

                if (!$inquilino) {
                    $this->dispatch('toast-error', message: 'Inquilino no encontrado');
                    return;
                }

                $inquilino->update([
                    'nombre' => ucwords(strtolower($this->nombre)),
                    'apellido' => ucwords(strtolower($this->apellido)),
                    'ci' => strtoupper($this->ci),
                    'telefono' => $this->telefono,
                    'email' => $this->email ?: null,
                    'observaciones' => $this->observaciones ?: null,
                ]);

                $this->dispatch('toast-success', message: 'Inquilino actualizado correctamente');
            }

            // Notificar al componente lista
            $this->dispatch('inquilino-actualizado');

            // Redirigir a la lista
            return redirect()->route('inquilinos.index');

        } catch (\Exception $e) {
            $this->dispatch('toast-error', message: 'Error al guardar: ' . $e->getMessage());
        }
    }

    /**
     * Cancelar y volver a la lista
     */
    public function cancelar()
    {
        return redirect()->route('inquilinos.index');
    }

    /**
     * Obtener título del formulario
     */
    public function getTituloProperty(): string
    {
        return $this->modo === 'crear' ? 'Nuevo Inquilino' : 'Editar Inquilino';
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
        return view('livewire.inquilinos.inquilino-form')
            ->layout('layouts.app');
    }
}
