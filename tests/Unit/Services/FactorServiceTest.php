<?php

namespace Tests\Unit\Services;

use App\Models\FactorProrrateo;
use App\Models\User;
use App\Services\FactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_actualizar_factor_registra_historial_en_transaccion(): void
    {
        // Arrange: usuario autenticado (cambiado_por) y factor vigente con 30 días de antigüedad
        $user = User::factory()->create();
        $this->actingAs($user);

        FactorProrrateo::create([
            'categoria' => 'domiciliaria',
            'factor' => 3.50,
            'vigencia_desde' => now()->subDays(30)->startOfDay(),
            'activo' => true,
            'creado_por' => $user->id,
        ]);

        $service = new FactorService();

        // Act
        $motivo = 'Ajuste anual aprobado en asamblea';
        $actualizado = $service->actualizarFactor('domiciliaria', 3.80, $motivo);

        // Assert: nuevo valor vigente y anterior desactivado
        $this->assertEquals(3.80, (float) $actualizado->factor);
        $this->assertDatabaseHas('factor_prorrateo', [
            'categoria' => 'domiciliaria',
            'factor' => 3.80,
            'activo' => true,
        ]);
        $this->assertDatabaseHas('factor_historial', [
            'factor_anterior' => 3.50,
            'factor_nuevo' => 3.80,
            'motivo' => $motivo,
        ]);
    }

    public function test_rechaza_factor_menor_o_igual_a_cero(): void
    {
        $this->actingAs(User::factory()->create());
        $service = new FactorService();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('mayor a 0');

        $service->actualizarFactor('comercial', 0, 'Motivo de prueba');
    }

    public function test_rechaza_actualizacion_sin_motivo(): void
    {
        $this->actingAs(User::factory()->create());
        $service = new FactorService();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('motivo');

        $service->actualizarFactor('comercial', 3.80, '   ');
    }

    public function test_rechaza_categoria_invalida(): void
    {
        $this->actingAs(User::factory()->create());
        $service = new FactorService();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Categoría inválida');

        $service->actualizarFactor('industrial', 3.80, 'Motivo de prueba');
    }
}
