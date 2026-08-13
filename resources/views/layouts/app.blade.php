<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
              integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
              crossorigin="anonymous" referrerpolicy="no-referrer">

        <!-- Alpine cloak (evita parpadeo de elementos x-show/x-collapse) -->
        <style>[x-cloak]{display:none !important;}</style>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            <!-- Sidebar (Mobile & Desktop) -->
            <x-sidebar />

            <!-- Main Content Area -->
            <div class="lg:pl-20 pt-16 lg:pt-0">
                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('scripts')

        {{-- Indicador global de carga Livewire: barra azul arriba cuando hay request en curso --}}
        <div wire:loading.delay
             style="position:fixed;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#4F46E5,#6366F1);z-index:9998;animation:pulse 1.5s ease-in-out infinite;">
        </div>
        <style>
            @keyframes pulse {
                0%, 100% { opacity: 0.7; }
                50% { opacity: 1; }
            }
        </style>

        {{-- Flash messages: se muestran como SweetAlert2 tras una redirección.
            Usar: return redirect()->route('...')->with('swal', ['tipo' => 'success', 'titulo' => '...', 'mensaje' => '...']); --}}
        @if(session()->has('swal'))
            <script>
                window.addEventListener('DOMContentLoaded', () => {
                    if (window.Swal) {
                        const data = @json(session('swal'));
                        const icono = data.tipo || 'info';
                        Swal.fire({
                            icon: ['success', 'error', 'warning', 'info'].includes(icono) ? icono : 'info',
                            title: data.titulo || '',
                            text: data.mensaje || '',
                            timer: icono === 'success' ? 3000 : null,
                            timerProgressBar: icono === 'success',
                            showConfirmButton: icono !== 'success',
                            confirmButtonColor: '#4F46E5',
                        });
                    }
                });
            </script>
        @endif
    </body>
</html>
