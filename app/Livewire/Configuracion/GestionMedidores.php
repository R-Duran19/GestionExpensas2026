<?php

namespace App\Livewire\Configuracion;

use App\Models\MedidorGrupo;
use App\Models\Unidad;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class GestionMedidores extends Component
{

    use AuthorizesRequests;
    use WithPagination;

    public $search = '';
    public $filtroTipo = '';
    public $filtroMedidor = '';

    public function render()
    {
        return view('livewire.configuracion.gestion-medidores')
            ->layout('layouts.app');
    }

    #[Computed]
    public function unidades()
    {
        $query = Unidad::with('medidorGrupo', 'propietarios')
            ->whereIn('tipo_unidad', ['departamento', 'oficina', 'local']) // Solo los que pueden tener medidor
            ->orderByRaw(Unidad::ordenPorTipoUnidadRaw('tipo_unidad'))
            ->orderByRaw(Unidad::ordenPorCodigoRaw('codigo', 'ASC'));

        if ($this->search) {
            $termino = trim($this->search);
            if (!empty($termino)) {
                $query->where(function ($q) use ($termino) {
                    $q->where('codigo', 'like', "%{$termino}%")
                      ->orWhere('numero_medidor', 'like', "%{$termino}%");
                });
            }
        }

        if ($this->filtroTipo) {
            $query->where('tipo_unidad', $this->filtroTipo);
        }

        if ($this->filtroMedidor === 'con_medidor') {
            $query->where('tiene_medidor', true);
        } elseif ($this->filtroMedidor === 'sin_medidor') {
            $query->where('tiene_medidor', false);
        }

        return $query->paginate(20);
    }

    #[Computed]
    public function grupos()
    {
        return MedidorGrupo::orderBy('nombre')->get();
    }

    public function toggleMedidor($unidadId)
    {
        $unidad = Unidad::findOrFail($unidadId);

        // Si tiene medidor, se lo quitamos
        if ($unidad->tiene_medidor) {
            $unidad->update([
                'tiene_medidor' => false,
                'tipo_medidor' => null,
                'numero_medidor' => null,
                'medidor_grupo_id' => null,
            ]);

            $this->dispatch('alerta',
                tipo: 'success',
                titulo: 'Medidor removido',
                mensaje: "Se quitó el medidor de {$unidad->nombre_completo}",
            );
        } else {
            // Si no tiene, le asignamos uno individual por defecto
            $unidad->update([
                'tiene_medidor' => true,
                'tipo_medidor' => 'individual',
                'numero_medidor' => null,
                'medidor_grupo_id' => null,
            ]);

            $this->dispatch('alerta',
                tipo: 'success',
                titulo: 'Medidor asignado',
                mensaje: "Se asignó medidor individual a {$unidad->nombre_completo}",
            );
        }
    }

    public function asignarIndividual($unidadId)
    {
        $this->authorize('update', \App\Models\Medidor::class);
        $unidad = Unidad::findOrFail($unidadId);
        $unidad->update([
            'tiene_medidor' => true,
            'tipo_medidor' => 'individual',
            'medidor_grupo_id' => null,
        ]);

        $this->dispatch('alerta',
            tipo: 'success',
            titulo: 'Medidor individual asignado',
            mensaje: "Se asignó medidor individual a {$unidad->nombre_completo}",
        );
    }

    public function asignarCompartido($unidadId, $grupoId)
    {
        $this->authorize('update', \App\Models\Medidor::class);
        $unidad = Unidad::findOrFail($unidadId);
        $grupo = MedidorGrupo::findOrFail($grupoId);

        $unidad->update([
            'tiene_medidor' => true,
            'tipo_medidor' => 'compartido',
            'medidor_grupo_id' => $grupoId,
        ]);

        $this->dispatch('alerta',
            tipo: 'success',
            titulo: 'Medidor compartido asignado',
            mensaje: "Se asignó medidor compartido ({$grupo->nombre}) a {$unidad->nombre_completo}",
        );
    }

    public function actualizarNumeroMedidor($unidadId, $numero)
    {
        $this->authorize('update', \App\Models\Medidor::class);
        $unidad = Unidad::findOrFail($unidadId);
        $unidad->update(['numero_medidor' => $numero ?: null]);

        $this->dispatch('alerta',
            tipo: 'success',
            titulo: 'Número actualizado',
            mensaje: 'Número de medidor actualizado correctamente',
        );
    }

    public function seleccionarGrupo($unidadId, $grupoId)
    {
        if (!$grupoId) {
            return;
        }

        $this->asignarCompartido($unidadId, $grupoId);
    }
}
