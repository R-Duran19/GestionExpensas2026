<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para el portal de propietarios/inquilinos.
 *
 * [PORTAL / Fase 1.3] Verifica que el usuario:
 * 1. Esté autenticado
 * 2. Sea del portal (tipo propietario o inquilino) y esté activo
 * 3. Si tiene must_change_password (credencial temporal del admin),
 *    lo redirige al cambio de contraseña antes de cualquier otra página.
 *
 * El scoping por dueño (qué unidades/expensas ve) NO va aquí: va en cada
 * componente del portal resolviendo desde $user->persona (anti-IDOR).
 *
 * @package App\Http\Middleware
 */
class CheckPortal
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')
                ->with('error', 'Debes iniciar sesión para acceder al portal.');
        }

        $user = auth()->user();

        if (!$user->esPortal() || !$user->activo) {
            abort(403, 'No tienes permiso para acceder al portal.');
        }

        // Cambio de contraseña forzado (credencial temporal del admin).
        // Solo se permite navegar a portal.perfil (donde se cambia).
        if ($user->must_change_password && !$request->routeIs('portal.perfil')) {
            return redirect()->route('portal.perfil')
                ->with('aviso', 'Debes cambiar tu contraseña temporal antes de continuar.');
        }

        return $next($request);
    }
}
