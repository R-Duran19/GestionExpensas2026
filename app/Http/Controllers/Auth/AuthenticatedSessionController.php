<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * [PORTAL / Fase 1.2] Los usuarios del portal (propietario/inquilino)
     * aterrizan en su portal; los admin en el dashboard administrativo.
     * Registra last_login_at para la gestión de accesos.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = auth()->user();

        // last_login_at con saveQuietly (sin disparar eventos del modelo).
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return redirect()->intended(
            $user->esPortal()
                ? route('portal.dashboard', absolute: false)
                : route('dashboard', absolute: false)
        );
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
