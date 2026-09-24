<?php

namespace App\Policies;

use App\Models\User;

/**
 * [MANTENIMIENTO-2026-09-23 / B4] Policy base.
 *
 * Todas las policies del sistema extienden esta clase: mientras el sistema
 * sea solo-admin, toda ability responde a hasRole('admin') — mismo criterio
 * que CheckAdmin middleware, así ningún usuario legítimo queda bloqueado.
 *
 * Cuando existan roles más finos, basta con sobreescribir la ability
 * específica en la policy hija.
 */
abstract class BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, $model): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, $model): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, $model): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, $model): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, $model): bool
    {
        return $user->hasRole('admin');
    }

    public function recalcular(User $user, $model): bool
    {
        return $user->hasRole('admin');
    }

    public function cerrar(User $user, $model): bool
    {
        return $user->hasRole('admin');
    }

    public function reabrir(User $user, $model): bool
    {
        return $user->hasRole('admin');
    }

    public function ajustar(User $user, $model): bool
    {
        return $user->hasRole('admin');
    }
}
