import './bootstrap';

import Swal from 'sweetalert2';
import collapse from '@alpinejs/collapse';

// [FIX DOBLE ALPINE 2026-10-29] Alpine DEBE ser la MISMA instancia que
// Livewire trae por dentro. Importar 'alpinejs' + Alpine.start() creaba una
// SEGUNDA instancia de Alpine: el magic "$wire" no existía en esa instancia
// (error "$wire is not defined") y TODOS los wire:click del sistema morían
// (cancelar/verificar del QR) mientras el countdown puro-Alpine seguía vivo.
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

window.Alpine = Alpine;
window.Swal = Swal;

Alpine.plugin(collapse);

Livewire.start();

// Escuchar alertas de Livewire
Livewire.on('alerta', (data) => {
    let tipo, titulo, mensaje;

    // Manejar diferentes formatos de datos
    if (Array.isArray(data)) {
        if (data.length === 1 && typeof data[0] === 'object') {
            // Formato: [{tipo, titulo, mensaje}]
            tipo = data[0].tipo;
            titulo = data[0].titulo;
            mensaje = data[0].mensaje;
        } else if (data.length === 3) {
            // Formato: [tipo, titulo, mensaje]
            tipo = data[0];
            titulo = data[1];
            mensaje = data[2];
        }
    } else if (typeof data === 'object') {
        // Formato: {tipo, titulo, mensaje}
        tipo = data.tipo;
        titulo = data.titulo;
        mensaje = data.mensaje;
    }

    const tipos = {
        success: 'success',
        error: 'error',
        warning: 'warning',
        info: 'info'
    };

    Swal.fire({
        icon: tipos[tipo] || 'info',
        title: titulo,
        text: mensaje,
        timer: 3000,
        timerProgressBar: true,
        showConfirmButton: true,
        confirmButtonColor: '#4F46E5',
        background: '#fff',
        color: '#1f2937'
    }).then(() => {
        // Después de cerrar la alerta, redirigir si es una alerta de éxito
        if (tipo === 'success' && window.livewireCurrentUrl) {
            window.location.href = window.livewireCurrentUrl;
            window.livewireCurrentUrl = null;
        }
    });
});

// Escuchar redirecciones de Livewire
Livewire.on('redirect-to', (url) => {
    console.log('Redirect-to:', url);
    window.livewireCurrentUrl = url;
});
