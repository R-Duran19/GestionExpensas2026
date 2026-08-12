import './bootstrap';

import Alpine from 'alpinejs';
import Swal from 'sweetalert2';
import collapse from '@alpinejs/collapse';

window.Alpine = Alpine;
window.Swal = Swal;

Alpine.plugin(collapse);
Alpine.start();

/**
 * Sistema unificado de notificaciones.
 *
 * Escucha todos los formatos históricos del sistema y los normaliza a SweetAlert2:
 *   - alerta           -> { tipo, titulo, mensaje }  (modulo Caja/Configuracion)
 *   - toast-success    -> message: '...'             (modulo Personas/Propiedades)
 *   - toast-error      -> message: '...'             (modulo Personas/Propiedades)
 *   - notify           -> { message, type }          (modulo Reportes)
 *
 * Acepta tanto named args (`message: '...'`) como arrays (`['message' => '...']`)
 * emitidos por Livewire 4.
 */

function normalizarPayload(data) {
    if (Array.isArray(data) && data.length === 1 && typeof data[0] === 'object') {
        return data[0];
    }
    if (Array.isArray(data) && typeof data[0] === 'object') {
        return data[0];
    }
    if (typeof data === 'object' && data !== null) {
        return data;
    }
    return {};
}

function mostrarAlerta({ tipo, type, titulo, title, mensaje, message, text }) {
    const icono = (tipo || type || 'info').toLowerCase();
    const tituloFinal = titulo || title || (
        icono === 'success' ? 'Éxito' :
        icono === 'error' ? 'Error' :
        icono === 'warning' ? 'Atención' : 'Información'
    );
    const texto = mensaje || message || text || '';

    Swal.fire({
        icon: ['success', 'error', 'warning', 'info'].includes(icono) ? icono : 'info',
        title: tituloFinal,
        text: texto,
        timer: icono === 'success' ? 3000 : null,
        timerProgressBar: icono === 'success',
        showConfirmButton: icono !== 'success',
        confirmButtonColor: '#4F46E5',
        background: '#fff',
        color: '#1f2937',
    }).then(() => {
        if (icono === 'success' && window.livewireCurrentUrl) {
            window.location.href = window.livewireCurrentUrl;
            window.livewireCurrentUrl = null;
        }
    });
}

Livewire.on('alerta', (data) => mostrarAlerta(normalizarPayload(data)));

Livewire.on('toast-success', (data) => mostrarAlerta({
    tipo: 'success',
    ...normalizarPayload(data),
}));

Livewire.on('toast-error', (data) => mostrarAlerta({
    tipo: 'error',
    ...normalizarPayload(data),
}));

Livewire.on('notify', (data) => mostrarAlerta(normalizarPayload(data)));

Livewire.on('redirect-to', (url) => {
    window.livewireCurrentUrl = typeof url === 'string' ? url : (url?.url || null);
});
