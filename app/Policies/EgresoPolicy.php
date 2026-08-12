<?php

namespace App\Policies;

use App\Models\Egreso;
use App\Models\User;

/**
 * [SEGURIDAD-09 / Fase 2.4] Policy para Egreso.
 *
 * Hoy el sistema es solo-admin (middleware 'admin' en routes/web.php ya filtra).
 * Esta policy devuelve true solo para admin, manteniendo el comportamiento actual
 * pero dejando la estructura lista para cuando existan roles mas finos.
 */
class EgresoPolicy
{
    public function view(User $user, Egreso $egreso): bool
    {
        return $user->hasRole('admin');
    }

    public function viewPdf(User $user, Egreso $egreso): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Egreso $egreso): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Egreso $egreso): bool
    {
        return $user->hasRole('admin');
    }
}
