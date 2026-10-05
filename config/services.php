<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagos QR bancarios [PORTAL QR / Fase 4.2 — RF-49]
    |--------------------------------------------------------------------------
    | Credenciales del merchant QR (mismas del sistema de tickets 365Soft /
    | Banco MC4). Si cambia el banco, solo se cambian estas variables y el
    | driver — el resto del flujo es idéntico (Plan 12, decisión 6).
    |
    | driver: 'mc4' (producción) | 'fake' (tests / simulador)
    */
    'qr_banco' => [
        'driver' => env('BANCO_QR_DRIVER', 'mc4'),
        'api_url' => env('BANCO_QR_API_URL', ''),
        'api_key' => env('BANCO_QR_API_KEY', ''),
        'service_key' => env('BANCO_QR_SERVICE_KEY', ''),
        'username' => env('BANCO_QR_USERNAME', ''),
        'password' => env('BANCO_QR_PASSWORD', ''),
        'timeout' => (int) env('BANCO_QR_TIMEOUT', 30),
        'callback_url' => env('BANCO_QR_CALLBACK_URL', ''),
        // [REMEDIACIÓN-0.1] Secreto compartido para autenticar las notificaciones
        // entrantes del banco (webhook). Verificación HMAC-SHA256 sobre el body
        // crudo, comparada con el header X-Signature. Sin este secreto el
        // webhook NO procesa nada (fail-closed). Generar con:
        //   php -r "echo bin2hex(random_bytes(32));"
        'webhook_secret' => env('BANCO_QR_WEBHOOK_SECRET', ''),
        // Vencimiento del QR (horas). Validar tope real del banco en pruebas.
        'vencimiento_horas' => (int) env('BANCO_QR_VENCIMIENTO_HORAS', 24),
    ],

];
