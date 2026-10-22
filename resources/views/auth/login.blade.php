<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email o CI [PORTAL F1.2]: admins entran con correo; propietarios/inquilinos con su CI -->
        <div>
            <x-input-label for="email" :value="__('Correo electrónico o CI')" />
            <x-text-input id="email" class="block mt-1 w-full" type="text" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password (con ojito para mostrar/ocultar) -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <div class="relative mt-1" x-data="{ mostrar: false }">
                {{-- Input nativo (no x-text-input): los :bind son de Alpine, no
                     de Blade (un :attr sobre componente Blade se evalúa en PHP) --}}
                <input id="password"
                       type="password"
                       name="password"
                       required
                       autocomplete="current-password"
                       :type="mostrar ? 'text' : 'password'"
                       class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full pr-10" />
                <button type="button"
                        @click="mostrar = !mostrar"
                        tabindex="-1"
                        :aria-label="mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                        class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600 focus:outline-none">
                    <i class="fa-solid" :class="mostrar ? 'fa-eye-slash' : 'fa-eye'"></i>
                </button>
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" name="remember">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
            </label>
        </div>

        {{-- [PORTAL 2026-10-15] Sin "olvidé mi contraseña": por ahora el único
             canal de reset es solicitarlo al admin (pendiente: notificación al
             admin desde el login). --}}
        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
