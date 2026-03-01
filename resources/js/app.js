import './bootstrap';

import Alpine from 'alpinejs';
import Swal from 'sweetalert2';

window.Alpine = Alpine;
window.Swal = Swal;

Alpine.start();

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
        timer: tipo === 'success' ? 3000 : null,
        timerProgressBar: tipo === 'success',
        showConfirmButton: tipo !== 'success',
        confirmButtonColor: '#4F46E5',
        background: '#fff',
        color: '#1f2937'
    });
});
