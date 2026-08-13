import './bootstrap';

import Alpine from 'alpinejs';
import Swal from 'sweetalert2';
import collapse from '@alpinejs/collapse';

window.Alpine = Alpine;
window.Swal = Swal;

window.livewireCurrentUrl = null;

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
    if (Array.isArray(data) && data.length > 0 && typeof data[0] === 'object') {
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
        // Redirigir si hay URL pendiente (seteada por el evento redirect-to).
        // Se hace para cualquier tipo de alerta (no solo success) porque
        // el redirect-to se dispatcha explícitamente cuando se quiere navegar.
        if (window.livewireCurrentUrl) {
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

/**
 * Listener de redirect-to: acepta cualquier formato que Livewire 4 pueda enviar.
 *   - dispatch('redirect-to', $url)            -> string
 *   - dispatch('redirect-to', url: $url)       -> { url: '...' }
 *   - dispatch('redirect-to', [$url])          -> ['...']
 *   - dispatch('redirect-to', [$obj])          -> [{ url: '...' }]
 */
function extraerUrl(payload) {
    if (!payload) return null;
    if (typeof payload === 'string') return payload;
    if (Array.isArray(payload)) {
        if (payload.length === 0) return null;
        if (typeof payload[0] === 'string') return payload[0];
        if (payload[0] && typeof payload[0] === 'object') return payload[0].url || null;
        return null;
    }
    if (typeof payload === 'object') return payload.url || null;
    return null;
}

Livewire.on('redirect-to', (payload) => {
    window.livewireCurrentUrl = extraerUrl(payload);
});

/**
 * Barra de progreso para upload de archivos Livewire.
 * Escucha eventos globales de Livewire (livewire-upload-start/progress/finish/error)
 * y actualiza un div con id="upload-progress" si existe en el DOM.
 */
function getOrCreateProgressBar() {
    let bar = document.getElementById('upload-progress-container');
    if (bar) return bar;

    bar = document.createElement('div');
    bar.id = 'upload-progress-container';
    bar.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:9999;background:#fff;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);padding:12px 16px;min-width:280px;display:none;font-family:Figtree,sans-serif;';
    bar.innerHTML = `
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
            <svg style="width:16px;height:16px;animate:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="#4F46E5" stroke-width="2">
                <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/>
            </svg>
            <span style="font-size:14px;font-weight:500;color:#1f2937;">Subiendo imagen...</span>
            <span id="upload-progress-pct" style="margin-left:auto;font-size:13px;font-weight:600;color:#4F46E5;">0%</span>
        </div>
        <div style="width:100%;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden;">
            <div id="upload-progress-bar" style="width:0%;height:100%;background:linear-gradient(90deg,#4F46E5,#6366F1);border-radius:3px;transition:width 0.2s ease;"></div>
        </div>
    `;
    document.body.appendChild(bar);
    return bar;
}

window.addEventListener('livewire-upload-start', (e) => {
    const bar = getOrCreateProgressBar();
    bar.style.display = 'block';
    const inner = document.getElementById('upload-progress-bar');
    const pct = document.getElementById('upload-progress-pct');
    if (inner) inner.style.width = '0%';
    if (pct) pct.textContent = '0%';
});

window.addEventListener('livewire-upload-progress', (e) => {
    const progress = e.detail?.progress ?? 0;
    const inner = document.getElementById('upload-progress-bar');
    const pct = document.getElementById('upload-progress-pct');
    if (inner) inner.style.width = progress + '%';
    if (pct) pct.textContent = Math.round(progress) + '%';
});

window.addEventListener('livewire-upload-finish', (e) => {
    const inner = document.getElementById('upload-progress-bar');
    const pct = document.getElementById('upload-progress-pct');
    const label = document.querySelector('#upload-progress-container span');
    if (inner) inner.style.width = '100%';
    if (pct) pct.textContent = '100%';
    if (label) label.textContent = 'Imagen procesada ✓';

    setTimeout(() => {
        const bar = document.getElementById('upload-progress-container');
        if (bar) bar.style.display = 'none';
    }, 800);
});

window.addEventListener('livewire-upload-error', (e) => {
    const bar = document.getElementById('upload-progress-container');
    if (bar) bar.style.display = 'none';
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'No se pudo subir la imagen. Intente nuevamente.',
        confirmButtonColor: '#4F46E5',
    });
});

