<?php

namespace App\Livewire\Configuracion;

use App\Models\ConfiguracionEdificio;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ConfiguracionEdificioForm extends Component
{

    use AuthorizesRequests;
    public $configuracionId = null;
    public $existe = false;

    public $nombre_edificio = '';
    public $direccion = '';
    public $telefono = '';
    public $email = '';
    public $cuit = '';
    public $ciudad = '';
    public $provincia = '';
    public $codigo_postal = '';
    public $informacion_adicional = '';

    protected function rules()
    {
        return [
            'nombre_edificio' => 'required|string|max:100',
            'direccion' => 'nullable|string|max:200',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'cuit' => 'nullable|string|max:20',
            'ciudad' => 'nullable|string|max:100',
            'provincia' => 'nullable|string|max:100',
            'codigo_postal' => 'nullable|string|max:20',
            'informacion_adicional' => 'nullable|string',
        ];
    }

    protected $messages = [
        'nombre_edificio.required' => 'El nombre del edificio es obligatorio.',
        'email.email' => 'El email debe ser una dirección válida.',
    ];

    public function mount()
    {
        $config = ConfiguracionEdificio::obtenerConfiguracion();

        if ($config) {
            $this->existe = true;
            $this->configuracionId = $config->id;
            $this->nombre_edificio = $config->nombre_edificio ?? '';
            $this->direccion = $config->direccion ?? '';
            $this->telefono = $config->telefono ?? '';
            $this->email = $config->email ?? '';
            $this->cuit = $config->cuit ?? '';
            $this->ciudad = $config->ciudad ?? '';
            $this->provincia = $config->provincia ?? '';
            $this->codigo_postal = $config->codigo_postal ?? '';
            $this->informacion_adicional = $config->informacion_adicional ?? '';
        } else {
            $this->existe = false;
            // Default sensible para que el admin no empiece desde cero
            $this->nombre_edificio = config('app.name', 'Edificio');
        }
    }

    public function guardar()
    {
        $this->authorize('update', \App\Models\ConfiguracionEdificio::class);
        $data = $this->validate();

        try {
            if ($this->existe && $this->configuracionId) {
                $config = ConfiguracionEdificio::findOrFail($this->configuracionId);
                $config->fill($data);
                $config->actualizado_por = auth()->id();
                $config->save();

                $this->dispatch('alerta', [
                    'tipo' => 'success',
                    'titulo' => 'Configuración actualizada',
                    'mensaje' => 'Los datos del edificio se actualizaron correctamente.',
                ]);
            } else {
                $config = new ConfiguracionEdificio($data);
                $config->activo = true;
                $config->creado_por = auth()->id();
                $config->save();

                $this->existe = true;
                $this->configuracionId = $config->id;

                $this->dispatch('alerta', [
                    'tipo' => 'success',
                    'titulo' => 'Configuración creada',
                    'mensaje' => 'Los datos del edificio se crearon correctamente.',
                ]);
            }

            Log::info('Configuración del edificio guardada', [
                'config_id' => $config->id,
                'user_id' => auth()->id(),
                'existe' => $this->existe,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al guardar configuración del edificio', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => 'Error al guardar: ' . $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.configuracion.configuracion-edificio-form')
            ->layout('layouts.app');
    }
}
