<?php

namespace App\Policies;

use App\Models\Ingreso;
use App\Models\User;

/**
 * [SEGURIDAD-09 / Fase 2.4] Policy para Ingreso.
 *
 * Hoy el sistema es solo-admin (middleware 'admin' en routes/web.php ya filtra).
 * Esta policy devuelve true solo para admin, manteniendo el comportamiento actual
 * pero dejando la estructura lista para cuando existan roles mas finos
 * (ej: propietario que solo ve sus propios ingresos).
 *
 * Cuando eso pase, modificar los metodos para permitir acceso por ownership:
 *   return $user->hasRole('admin') || $user->id === $ingreso->creado_por;
 */
class IngresoPolicy
{
    public function view(User $user, Ingreso $ingreso): bool
    {
        return $user->hasRole('admin');
    }

    public function viewPdf(User $user, Ingreso $ingreso): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Ingreso $ingreso): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Ingreso $ingreso): bool
    {
        return $user->hasRole('admin');
    }
}
