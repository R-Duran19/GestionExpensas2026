<?php

namespace Tests\Unit\Services;

use App\Models\FactorProrrateo;
use App\Models\PeriodoExpensa;
use App\Models\Unidad;
use App\Models\User;
use App\Services\ExpensaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpensaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_redondeo_monetario_al_entero(): void
    {
        $service = new ExpensaService();

        // Decimal >= 0.50 sube al entero siguiente
        $this->assertEquals(242.0, $service->redondear(241.536));
        // Decimal < 0.50 baja al entero
        $this->assertEquals(241.0, $service->redondear(241.49));
        // Montos enteros se mantienen
        $this->assertEquals(100.0, $service->redondear(100.0));
    }

    public function test_no_genera_expensa_en_periodo_cerrado(): void
    {
        // Arrange: período con estado distinto de 'abierto'
        $periodo = PeriodoExpensa::factory()->create(['estado' => 'cerrado']);
        $unidad = Unidad::factory()->create();

        $service = new ExpensaService();

        // Act + Assert: guard de inmutabilidad del período
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('cerrado');

        $service->generarExpensaUnidad($periodo->id, $unidad);
    }

    public function test_genera_expensa_por_superficie_con_factor_vigente(): void
    {
        // Arrange: usuario autenticado, factor domiciliario vigente de 2.00 y unidad sin medidor
        $user = User::factory()->create();
        $this->actingAs($user);

        FactorProrrateo::create([
            'categoria' => 'domiciliaria',
            'factor' => 2.00,
            'vigencia_desde' => now()->subDays(10)->startOfDay(),
            'activo' => true,
            'creado_por' => $user->id,
        ]);

        $periodo = PeriodoExpensa::factory()->create(['estado' => 'abierto']);
        $unidad = Unidad::factory()->create([
            'metros_cuadrados' => 100.00,
            'categoria_unidad' => 'domiciliaria',
            'tiene_medidor' => false,
        ]);

        $service = new ExpensaService();

        // Act: 100 m2 x 2.00 = 200.00; sin medidor el agua es 0
        $expensa = $service->generarExpensaUnidad($periodo->id, $unidad);

        // Assert
        $this->assertEquals(200.00, (float) $expensa->monto_expensa);
        $this->assertEquals(0.00, (float) $expensa->monto_agua);
        $this->assertEquals(200.00, (float) $expensa->monto_total);
        $this->assertDatabaseHas('expensas', [
            'periodo_id' => $periodo->id,
            'unidad_id' => $unidad->id,
            'monto_total' => 200.00,
        ]);
    }
}
