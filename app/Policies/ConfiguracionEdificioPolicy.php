<?php

namespace App\Policies;

use App\Models\ConfiguracionEdificio;
use App\Models\User;

/**
 * [SEGURIDAD-09 / Fase 2.4] Policy para ConfiguracionEdificio.
 *
 * Hoy el sistema es solo-admin (middleware 'admin' en routes/web.php ya filtra).
 * Esta policy devuelve true solo para admin, manteniendo el comportamiento actual
 * pero dejando la estructura lista para cuando existan roles mas finos
 * (ej: solo alguns roles pueden editar datos del edificio).
 *
 * Nota: ConfiguracionEdificio es un singleton (unica fila), el modelo $config
 * se pasa por consistencia con Laravel policies, pero el metodo no lo usa.
 */
class ConfiguracionEdificioPolicy
{
    public function view(User $user, ?ConfiguracionEdificio $config = null): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, ?ConfiguracionEdificio $config = null): bool
    {
        return $user->hasRole('admin');
    }
}
