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
 *
 * [FIX 2026-09-24] El parámetro $model es OPCIONAL en todas las abilities.
 *
 * Motivo: Laravel DESCARTA el primer argumento cuando es un string
 * (Gate::callPolicyMethod hace array_shift si $arguments[0] es string), por
 * lo que las llamadas del tipo authorize('update', Inquilino::class)
 * invocan la policy con UN solo argumento y provocaban:
 *   ArgumentCountError: Too few arguments to BasePolicy::update()
 * Esto afectaba a TODAS las páginas que autorizan con clase-string
 * (AsignarInquilino, Categorías de Caja, EgresoList, etc.).
 */
abstract class BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, $model = null): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, $model = null): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, $model = null): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, $model = null): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, $model = null): bool
    {
        return $user->hasRole('admin');
    }

    public function recalcular(User $user, $model = null): bool
    {
        return $user->hasRole('admin');
    }

    public function cerrar(User $user, $model = null): bool
    {
        return $user->hasRole('admin');
    }

    public function reabrir(User $user, $model = null): bool
    {
        return $user->hasRole('admin');
    }

    public function ajustar(User $user, $model = null): bool
    {
        return $user->hasRole('admin');
    }
}
