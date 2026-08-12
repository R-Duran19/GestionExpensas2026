<?php

namespace App\Helpers;

/**
 * [SEGURIDAD-09 / Fase 3.B] Helper centralizado para PII.
 *
 * Dos operaciones:
 *  - encrypt(): cifra un valor (wrapper sobre encrypt() de Laravel).
 *    Usado por el comando EncryptPii para llenar columnas _enc antes de
 *    que el modelo tenga el cast 'encrypted' aplicado (commit 3.C).
 *
 *  - hash(): HMAC-SHA256 hex del valor, usando PII_HASH_KEY (o fallback APP_KEY).
 *    Usado para unicidad y busqueda exacta de CI sin exponer el valor real.
 *    NO permite LIKE ni rangos.
 *
 * Importante: si se rota PII_HASH_KEY, los ci_hash existentes se invalidan
 * y hay que re-hashear todos los registros (comando EncryptPii --rehash).
 */
class Pii
{
    /**
     * Cifra un valor usando el encrypt() de Laravel (OpenSSL + APP_KEY).
     * Igual al cast 'encrypted' pero explicito, para usar antes del switch 3.C.
     */
    public static function encrypt(string $value): string
    {
        return encrypt($value);
    }

    /**
     * Calcula HMAC-SHA256 hex de un valor.
     * Para unicidad y busqueda exacta de PII sin exponer el valor.
     *
     * @param string|null $value Valor a hashear. Null devuelve null.
     * @return string|null 64 chars hex, o null si el input es null/vacio.
     */
    public static function hash(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return hash_hmac('sha256', $value, self::hashKey());
    }

    /**
     * Key para HMAC. Dedicada (PII_HASH_KEY) o fallback a APP_KEY.
     * APP_KEY ya tiene el prefijo 'base64:' removido por Laravel al cargarla.
     */
    protected static function hashKey(): string
    {
        $key = config('app.pii_hash_key') ?: config('app.key');

        if (empty($key)) {
            throw new \RuntimeException(
                'No se encontro APP_KEY. Configurala en .env antes de usar PII.'
            );
        }

        return (string) $key;
    }
}
