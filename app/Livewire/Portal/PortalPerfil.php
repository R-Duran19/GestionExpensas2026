<?php

namespace App\Livewire\Portal;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * [PORTAL / Fase 1.5] Perfil del usuario del portal.
 *
 * - Cambio de contraseña propio (requiere contraseña actual).
 * - Datos de contacto en SOLO LECTURA (sin email verificable, la edición la
 *   hace el admin — decisión de diseño del plan 12).
 * - Si el usuario tiene must_change_password (credencial temporal entregada
 *   por el admin), al cambiarla se limpia el flag.
 */
class PortalPerfil extends Component
{
    public string $password_actual = '';
    public string $password = '';
    public string $password_confirmation = '';

    #[Computed]
    public function user(): \App\Models\User
    {
        return auth()->user();
    }

    #[Computed]
    public function persona()
    {
        return $this->user->persona;
    }

    protected function rules(): array
    {
        return [
            'password_actual' => ['required', 'string'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ];
    }

    protected $validationAttributes = [
        'password_actual' => 'contraseña actual',
        'password' => 'contraseña nueva',
        'password_confirmation' => 'confirmación de contraseña',
    ];

    public function cambiarPassword(): void
    {
        $this->validate();

        if (!Hash::check($this->password_actual, $this->user->password)) {
            $this->addError('password_actual', 'La contraseña actual no es correcta.');
            return;
        }

        // Evitar reutilizar la misma contraseña
        if (Hash::check($this->password, $this->user->password)) {
            $this->addError('password', 'La contraseña nueva debe ser distinta a la actual.');
            return;
        }

        $this->user->forceFill([
            'password' => $this->password,
            'must_change_password' => false,
            // [REMEDIACIÓN-1.2] Rotar remember: cookies "recordarme" robadas
            // dejan de funcionar tras un cambio de contraseña.
            'remember_token' => \Illuminate\Support\Str::random(60),
        ])->saveQuietly();

        // [REMEDIACIÓN-1.2] Revocar las OTRAS sesiones del usuario (la actual
        // queda viva para no expulsar a quien está cambiando la contraseña).
        \Illuminate\Support\Facades\DB::table('sessions')
            ->where('user_id', $this->user->id)
            ->where('id', '!=', session()->getId())
            ->delete();

        $this->reset(['password_actual', 'password', 'password_confirmation']);

        // Patrón del sistema: dispatch a listener global en app.js (SweetAlert2).
        // Nos quedamos en la página (sin redirect) para evitar perder el evento.
        $this->dispatch('toast-success', message: 'Contraseña actualizada correctamente');
    }

    public function render()
    {
        return view('livewire.portal.portal-perfil')
            ->layout('layouts.portal');
    }
}
