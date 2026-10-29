import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    // [FIX 2026-10-29] Sin esto, el hot file apunta a [::1]:5173 (IPv6) y
    // Vite 7 restringe CORS: el app.js no ejecuta en el navegador y TODOS los
    // botones Livewire mueren (countdown vivo por su Alpine interno).
    server: {
        host: '127.0.0.1',
        cors: {
            origin: ['http://127.0.0.1:8000', 'http://localhost:8000'],
        },
    },
});
