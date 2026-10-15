<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>

    {{-- [Plan 12 Fase 5.5] Cerrar todas las demás sesiones --}}
    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">
            {{ __('Cerrar otras sesiones') }}
        </h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Cierra todas las sesiones abiertas de esta cuenta en otros dispositivos o navegadores.
        </p>

        <form method="post" action="{{ route('profile.cerrar-sesiones') }}" class="mt-4 flex items-end gap-3">
            @csrf
            <div class="flex-1">
                <x-input-label for="sesiones_password" :value="__('Contraseña actual')" />
                <x-text-input id="sesiones_password" name="password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
                <x-input-error :messages="$errors->sesiones->get('password')" class="mt-2" />
            </div>
            <x-secondary-button>{{ __('Cerrar sesiones') }}</x-secondary-button>
        </form>

        @if (session('status') === 'sesiones-cerradas')
            <p class="mt-2 text-sm text-emerald-600">{{ __('Todas las demás sesiones fueron cerradas.') }}</p>
        @endif
    </div>
</section>
