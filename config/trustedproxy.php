<?php

return [

    /*
    |--------------------------------------------------------------------------
    | [REMEDIACIÓN-1.4] Proxies de confianza (leído del entorno)
    |--------------------------------------------------------------------------
    |
    | Antes: lista hardcodeada en bootstrap/app.php y TRUSTED_PROXIES era
    | configuración muerta. Ahora el entorno manda, con el MISMO default
    | restrictivo (loopback + RFC1918) para no ampliar la confianza.
    |
    | Valores aceptados:
    |   - '*'  : confía en CUALQUIER peer (solo desarrollo en red aislada).
    |   - Lista separada por comas de IPs/CIDRs (recomendado en producción:
    |     la IP específica de nginx-proxy-manager, p. ej. '172.18.0.5').
    |
    | Por qué importa: solo los peers de esta lista pueden definir la IP del
    | cliente vía X-Forwarded-For; el resto de peers tienen su REMOTE_ADDR
    | real. Los throttles por-IP (login, portal) dependen de esto.
    */

    'proxies' => env('TRUSTED_PROXIES', '127.0.0.1,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16'),

];
