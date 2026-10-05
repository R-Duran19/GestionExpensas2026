<?php

namespace App\Services\QrBanco;

/**
 * [PORTAL QR] Driver simulado para tests y modo sandbox (BANCO_QR_DRIVER=fake).
 *
 * - generaQr: devuelve un PNG REAL dibujado con GD (patrón tipo QR con los
 *   cuadrados de posición) para que la vista muestre una imagen visible.
 *   NO es escaneable — es un simulador visual.
 * - verificarEstado: controlado por Cache:
 *     Cache::put("fake_qr_pago_{alias}", true)  =>  PAGADO
 *     Cache::put("fake_qr_error", true)         =>  generarQr lanza excepción
 *   Default => PENDIENTE
 */
class FakeQrDriver implements QrBancoDriver
{
    public function generarQr(string $alias, float $monto, string $glosa, \DateTimeInterface $vencimiento): array
    {
        if (\Illuminate\Support\Facades\Cache::pull('fake_qr_error')) {
            throw new \Exception('Simulador: banco no disponible');
        }

        return [
            'imagen_qr' => $this->dibujarQrSimulado($alias, $monto),
            'respuesta' => ['codigo' => '0000', 'mensaje' => 'OK (simulador)'],
        ];
    }

    public function verificarEstado(string $alias): string
    {
        $pagado = \Illuminate\Support\Facades\Cache::get("fake_qr_pago_{$alias}", false);

        return $pagado ? 'PAGADO' : 'PENDIENTE';
    }

    public function inhabilitarQr(string $alias): bool
    {
        return true;
    }

    /**
     * Dibuja un PNG con aspecto de QR (determinístico por alias).
     */
    protected function dibujarQrSimulado(string $alias, float $monto): string
    {
        $tamaño = 29;                 // celdas (como un QR versión 3)
        $celda = 10;                  // px por celda
        $margen = 2;                  // celdas de margen blanco
        $px = ($tamaño + $margen * 2) * $celda;

        $img = imagecreatetruecolor($px, $px);
        $blanco = imagecolorallocate($img, 255, 255, 255);
        $negro = imagecolorallocate($img, 20, 20, 20);
        imagefilledrectangle($img, 0, 0, $px, $px, $blanco);

        // Semilla determinística por alias (mismo alias = mismo patrón)
        $semilla = crc32($alias);
        mt_srand($semilla);

        $esModulo = function ($x, $y) use ($tamaño, $semilla): bool {
            // Cuadrados de posición (3 esquinas) como un QR real
            $enFinder = ($x < 7 && $y < 7) || ($x >= $tamaño - 7 && $y < 7) || ($x < 7 && $y >= $tamaño - 7);
            if ($enFinder) {
                $lx = $x < 7 ? $x : $x - ($tamaño - 7);
                $ly = $y < 7 ? $y : $y - ($tamaño - 7);
                $borde = $lx === 0 || $lx === 6 || $ly === 0 || $ly === 6;
                $centro = $lx >= 2 && $lx <= 4 && $ly >= 2 && $ly <= 4;
                return $borde || $centro;
            }
            // Resto: pseudo-aleatorio
            return mt_rand(0, 1) === 1;
        };

        for ($y = 0; $y < $tamaño; $y++) {
            for ($x = 0; $x < $tamaño; $x++) {
                if ($esModulo($x, $y)) {
                    imagefilledrectangle(
                        $img,
                        ($x + $margen) * $celda,
                        ($y + $margen) * $celda,
                        ($x + $margen + 1) * $celda - 1,
                        ($y + $margen + 1) * $celda - 1,
                        $negro
                    );
                }
            }
        }

        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        return 'data:image/png;base64,' . base64_encode($png);
    }
}
