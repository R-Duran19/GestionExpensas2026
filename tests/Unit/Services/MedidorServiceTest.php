<?php

namespace Tests\Unit\Services;

use App\Services\MedidorService;
use Tests\TestCase;

class MedidorServiceTest extends TestCase
{
    public function test_valida_lectura_actual_mayor_a_anterior(): void
    {
        $service = new MedidorService();

        $this->assertTrue(
            $service->validarLecturaActualMayorAnterior(1250.5, 1240.2)
        );
    }

    public function test_rechaza_lectura_actual_menor_a_anterior(): void
    {
        $service = new MedidorService();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('no puede ser menor');

        $service->validarLecturaActualMayorAnterior(1240.0, 1250.5);
    }

    public function test_acepta_lecturas_iguales(): void
    {
        $service = new MedidorService();

        // Consumo cero es válido (unidad sin uso en el período)
        $this->assertTrue(
            $service->validarLecturaActualMayorAnterior(1000.0, 1000.0)
        );
    }

    public function test_calculo_monto_agua_multiplica_consumo_por_factor(): void
    {
        $service = new MedidorService();

        // 10 m3 x 2.40 = 24.00 exacto
        $this->assertEquals(24.0, $service->calcularMontoAgua(10.0, 2.40));
    }

    public function test_calculo_monto_agua_redondea_moneda_al_entero(): void
    {
        $service = new MedidorService();

        // Redondeo monetario del sistema: decimal >= 0.50 sube, < 0.50 baja
        $this->assertEquals(17.0, $service->calcularMontoAgua(3.5, 4.83));   // 16.905 -> 17
        $this->assertEquals(16.0, $service->calcularMontoAgua(3.3, 4.83));   // 15.939 -> 16
    }
}
