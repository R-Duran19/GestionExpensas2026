<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificar que el usuario tiene rol admin
 *
 * Este middleware protege las rutas administrativas del sistema.
 * Solo permite acceso a usuarios autenticados con el rol "admin".
 *
 * @package App\Http\Middleware
 */
class CheckAdmin
{
    /**
     * Handle an incoming request.
     *
     * Verifica que:
     * 1. El usuario esté autenticado
     * 2. El usuario tenga el rol "admin"
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar que el usuario esté autenticado
        if (!auth()->check()) {
            return redirect()->route('login')
                ->with('error', 'Debes iniciar sesión para acceder a esta página.');
        }

        // Verificar que el usuario tenga el rol admin
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'No tienes permiso para acceder a esta página.');
        }

        return $next($request);
    }
}
