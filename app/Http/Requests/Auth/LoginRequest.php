<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * [PORTAL / Fase 1.2] El campo "email" ahora acepta CORREO (admins) o CI
     * (propietarios/inquilinos del portal). El nombre del campo se mantiene
     * para no romper la vista ni los tests existentes.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * [PORTAL / Fase 1.2] Login dual:
     * - Si el identificador es un email → attempt clásico (admins).
     * - Si NO es un email → login por CI vía ci_hash (HMAC, Pii::hash),
     *   sin guardar/exponer el CI en claro.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $identificador = trim($this->string('email')->value() ?? '');
        $password = $this->string('password')->value() ?? '';

        $ok = false;

        // [FIX] Si contiene '@' es un correo (FILTER_VALIDATE_EMAIL rechaza
        // dominios sin TLD como 'admin@local' y los trataría como CI).
        // Los CI nunca contienen '@'.
        if (str_contains($identificador, '@')) {
            // Login clásico por correo (admin)
            $ok = Auth::attempt(
                ['email' => $identificador, 'password' => $password],
                $this->boolean('remember')
            );
        } else {
            // Login por CI (portal): búsqueda por hash + verificación manual
            $user = User::buscarPorCiHash($identificador);

            if ($user && Hash::check($password, $user->password)) {
                Auth::login($user, $this->boolean('remember'));
                $ok = true;
            }
        }

        if (!$ok) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // [PORTAL] Baja lógica de accesos: cuenta desactivada no entra.
        if (!auth()->user()->activo) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'email' => 'Tu acceso al sistema fue desactivado. Contacta al administrador.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
