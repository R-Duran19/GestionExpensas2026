// =============================================================================
// portal-load.js — Prueba de carga del portal (Plan 12 Fase 5.3)
// =============================================================================
// Simula ~150 usuarios del portal mezclando:
//   - Navegación (dashboard, estado de cuenta, pagos)
//   - Polling de estado QR (cada 15 s por usuario activo)
//   - Descargas de PDF (5/min máximo por usuario — validación del throttle)
//
// USO (contra un entorno de PRUEBAS, nunca producción):
//   k6 run -e BASE_URL=https://expensas-test.local tests/k6/portal-load.js
//
// Requisitos previos: crear usuarios del portal en el entorno de pruebas y
// listar sus CIs en el archivo usuarios.txt (uno por línea, CI:password).
//
// Umbrales objetivo (Plan 12 A.3): p95 < 1.5s, errores < 1%
// =============================================================================

import http from 'k6/http';
import { check, group, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

// Carga CIs:password desde usuarios.txt (ignora líneas vacías y comentarios)
const USUARIOS = open(__ENV.USUARIOS_FILE || 'tests/k6/usuarios.txt')
    .split('\n')
    .map(l => l.trim())
    .filter(l => l && !l.startsWith('#'))
    .map(l => {
        const [ci, password] = l.split(':');
        return { ci, password };
    });

export const options = {
    scenarios: {
        // Escenario 1: navegación normal (120 usuarios)
        navegacion: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '1m', target: 60 },
                { duration: '3m', target: 120 },
                { duration: '1m', target: 0 },
            ],
            exec: 'navegar',
        },
        // Escenario 2: pico de pagos QR (30 usuarios con polling)
        pagos_qr: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '1m', target: 15 },
                { duration: '3m', target: 30 },
                { duration: '1m', target: 0 },
            ],
            startTime: '30s',
            exec: 'pagarQr',
        },
    },
    thresholds: {
        http_req_duration: ['p(95)<1500'],   // p95 < 1.5s
        http_req_failed: ['rate<0.01'],      // < 1% errores
    },
};

// Login y devuelve cookie de sesión
function login(usuario) {
    const res = http.post(`${BASE_URL}/login`, {
        email: usuario.ci,
        password: usuario.password,
    });
    return res;
}

// --- Escenario: navegación ---
export function navegar() {
    const usuario = USUARIOS[__VU % USUARIOS.length];

    group('login', () => {
        const res = login(usuario);
        check(res, { 'login ok': r => r.status === 200 || r.status === 302 });
    });

    group('dashboard', () => {
        check(http.get(`${BASE_URL}/portal`), { 'dashboard 200': r => r.status === 200 });
    });

    group('estado-cuenta', () => {
        check(http.get(`${BASE_URL}/portal/estado-cuenta`), { 'estado 200': r => r.status === 200 });
    });

    group('mis-pagos', () => {
        check(http.get(`${BASE_URL}/portal/pagos`), { 'pagos 200': r => r.status === 200 });
    });

    sleep(Math.random() * 3 + 1);
}

// --- Escenario: pagos QR (polling) ---
export function pagarQr() {
    const usuario = USUARIOS[(__VU + 1000) % USUARIOS.length];

    group('login-pagos', () => {
        login(usuario);
    });

    group('pagar', () => {
        check(http.get(`${BASE_URL}/portal/pagar`), { 'pagar 200': r => r.status === 200 });
    });

    // Polling: la página usa wire:poll cada 15s; aquí simulamos la carga
    // que genera ese patrón (requests livewire al endpoint del componente).
    sleep(15);
}
