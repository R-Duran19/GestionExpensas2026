<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\JsonResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Solo procesar respuestas JSON
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $content = $response->getContent();

            if (!empty($content)) {
                // Limpiar caracteres UTF-8 mal codificados
                $cleanContent = $this->sanitizeUtf8($content);

                // Si el contenido cambió, actualizar la respuesta
                if ($cleanContent !== $content) {
                    $response->setContent($cleanContent);
                }
            }
        }

        return $response;
    }

    /**
     * Limpiar caracteres UTF-8 mal codificados
     */
    protected function sanitizeUtf8($content)
    {
        // Decodificar JSON
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Si hay error de JSON, intentar limpiar el string directamente
            return $this->cleanString($content);
        }

        // Limpiar recursivamente el array/data
        $cleanData = $this->cleanArray($data);

        // Re-codificar a JSON
        return json_encode($cleanData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Limpiar un array recursivamente
     */
    protected function cleanArray($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->cleanArray($value);
            }
        } elseif (is_string($data)) {
            $data = $this->cleanString($data);
        }

        return $data;
    }

    /**
     * Limpiar caracteres UTF-8 mal codificados de un string
     */
    protected function cleanString($string)
    {
        if (empty($string) || !is_string($string)) {
            return $string;
        }

        // Remover caracteres BOM
        $string = preg_replace('/\xEF\xBB\xBF/', '', $string);

        // Convertir a UTF-8 válido
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');

        // Remover caracteres UTF-8 inválidos (rango Unicode válido)
        $string = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]+/u', '', $string);

        // Remover caracteres de control problemáticos (menos tab, newline, return)
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $string);

        // Reemplazar caracteres problemáticos específicos
        $replacements = [
            // Comillas tipográficas
            "\xC2\xAB" => '"', // «
            "\xC2\xBB" => '"', // »
            "\xC2\x84" => '"', // "
            "\xC2\x85" => '"', // "
            "\xC2\x88" => '"', //
            "\xC2\x89" => '"', //
            "\xE2\x80\x98" => "'", // '
            "\xE2\x80\x99" => "'", // '
            "\xE2\x80\x9C" => '"', // "
            "\xE2\x80\x9D" => '"', // "
            "\xE2\x80\x9A" => '-', // –
            "\xE2\x80\x93" => '-', // —
            "\xE2\x80\xA6" => '...', // …

            // Espacios problemáticos
            "\xC2\xA0" => ' ', // NBSP
            "\xE2\x80\xAF" => ' ', // espacio fino

            // Caracteres invisibles
            "\xE2\x80\x8B" => '', // espacio de ancho cero
            "\xE2\x80\x8C" => '', // espacio de ancho cero sin unión
            "\xE2\x80\x8D" => '', // espacio de ancho cero con unión
        ];

        $string = str_replace(array_keys($replacements), array_values($replacements), $string);

        return $string;
    }
}
