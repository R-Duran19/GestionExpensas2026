<?php

namespace Tests\Feature;

use App\Models\CategoriaIngreso;
use App\Models\Expensa;
use App\Models\Ingreso;
use App\Models\IngresoDetalle;
use App\Models\PeriodoExpensa;
use App\Models\Propietario;
use App\Models\SaldoAFavor;
use App\Models\Unidad;
use App\Models\User;
use App\Services\ExpensaService;
use App\Services\IngresoService;
use App\Services\PagoExpensaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * [REMEDIACIÓN-2.1 / 2.2] Integridad financiera básica.
 *
 * Cubre:
 * - generarExpensaUnidad: período cerrado => rechazo; regenerar con pago
 *   parcial conserva el pendiente (no re-cobra).
 * - recalcularExpensas: bloqueado cuando el período tiene pagos registrados.
 * - SaldoAFavor consumido: impide eliminar/editar el recibo que lo respalda.
 */
class IntegridadFinancieraTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Propietario $propietario;
    protected Unidad $unidad;
    protected PeriodoExpensa $periodo;
    protected CategoriaIngreso $categoria;
    protected ExpensaService $expensaService;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'propietario'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }

        $this->admin = User::factory()->create(['email' => 'admin-if@test.bo']);
        $this->admin->assignRole('admin');
        $this->actingAs($this->admin);

        $this->propietario = Propietario::create([
            'nombre' => 'Inte', 'apellido' => 'Gridad', 'ci' => '4445556',
        ]);

        $this->unidad = Unidad::create([
            'codigo' => 'DEP-IF-1',
            'tipo_unidad' => 'departamento',
            'piso' => 1,
            'metros_cuadrados' => 60,
            'categoria_unidad' => 'domiciliaria',
            'tiene_medidor' => false,
            'activo' => true,
        ]);

        $this->unidad->propietarios()->attach($this->propietario->id, [
            'fecha_inicio' => now()->subYear(),
            'es_propietario_actual' => true,
        ]);

        $this->periodo = PeriodoExpensa::create([
            'mes' => 8, 'año' => 2026,
            'fecha_apertura' => now()->subDays(10),
            'estado' => 'abierto',
        ]);

        $this->categoria = CategoriaIngreso::create([
            'nombre' => 'Expensas IF',
            'tipo' => 'ingreso',
            'creado_por' => $this->admin->id,
        ]);

        $this->expensaService = app(ExpensaService::class);
    }

    private function crearExpensa(float $monto = 250): Expensa
    {
        return Expensa::create([
            'periodo_id' => $this->periodo->id,
            'unidad_id' => $this->unidad->id,
            'monto_expensa' => $monto, 'monto_agua' => 0,
            'expensa_pendiente' => $monto, 'agua_pendiente' => 0,
            'periodo_cerrado' => false, 'monto_total' => $monto,
        ]);
    }

    private function pagarParcial(Expensa $expensa, float $monto): void
    {
        $ingreso = Ingreso::create([
            'numero_recibo' => 'IF-' . uniqid(),
            'fecha' => now()->toDateString(),
            'categoria_ingreso_id' => $this->categoria->id,
            'tipo_ingreso' => 'expensa',
            'importe' => $monto,
            'monto_total' => $monto,
            'metodo_pago' => 'efectivo',
            'unidad_id' => $this->unidad->id,
            'pagador_tipo' => 'propietario',
            'pagador_id' => $this->propietario->id,
            'creado_por' => $this->admin->id,
        ]);

        IngresoDetalle::create([
            'ingreso_id' => $ingreso->id,
            'expensa_id' => $expensa->id,
            'concepto' => 'ambos',
            'monto_aplicado' => $monto,
            'monto_expensa_aplicado' => $monto,
            'monto_agua_aplicado' => 0,
        ]);

        $expensa->decrement('expensa_pendiente', $monto);
    }

    public function test_no_se_puede_generar_expensa_en_periodo_cerrado(): void
    {
        $this->periodo->update(['estado' => 'cerrado']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('está cerrado');

        $this->expensaService->generarExpensaUnidad($this->periodo->id, $this->unidad);
    }

    public function test_regenerar_expensa_con_pago_parcial_conserva_pendiente(): void
    {
        $expensa = $this->crearExpensa(250);
        $this->pagarParcial($expensa, 100);

        $this->assertEquals(150, (float) $expensa->fresh()->expensa_pendiente);

        // Regenerar (mismo flujo que antes borraba el pago): la emisión se
        // recalcula desde factores pero el pendiente DEBE conservar lo pagado.
        $resultado = $this->expensaService->generarExpensaUnidad($this->periodo->id, $this->unidad);

        $this->assertLessThan(
            (float) 250,
            (float) $resultado->expensa_pendiente,
            'El pendiente no debe resetearse a la emisión completa'
        );

        // El detalle del pago sigue existiendo (no se borró nada)
        $this->assertEquals(100, (float) IngresoDetalle::where('expensa_id', $expensa->id)->sum('monto_expensa_aplicado'));
    }

    public function test_recalcular_bloqueado_cuando_hay_pagos(): void
    {
        $expensa = $this->crearExpensa(250);
        $this->pagarParcial($expensa, 100);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('pagos registrados');

        $this->expensaService->recalcularExpensas($this->periodo->id);
    }

    public function test_recalcular_es_atomico(): void
    {
        $expensa = $this->crearExpensa(250);

        // Fixture sin factores de agua: generarExpensas termina en excepción.
        // El punto de este test: la ELIMINACIÓN no puede quedar a mitad de
        // camino (antes: delete suelto + excepción => período sin expensas).
        try {
            $this->expensaService->recalcularExpensas($this->periodo->id);
            $this->fail('Se esperaba excepción (fixture sin factores de agua)');
        } catch (\Exception $e) {
            $this->assertStringContainsStringIgnoringCase('factores', $e->getMessage());
        }

        $this->assertEquals(
            1,
            Expensa::where('periodo_id', $this->periodo->id)->count(),
            'La eliminación debe ser atómica con la regeneración'
        );
    }

    public function test_no_se_puede_eliminar_recibo_con_saldo_consumido(): void
    {
        $expensa = $this->crearExpensa(250);

        $ingreso = Ingreso::create([
            'numero_recibo' => 'IF-SALDO-1',
            'fecha' => now()->toDateString(),
            'categoria_ingreso_id' => $this->categoria->id,
            'tipo_ingreso' => 'expensa',
            'importe' => 500,
            'monto_total' => 500,
            'metodo_pago' => 'efectivo',
            'unidad_id' => $this->unidad->id,
            'pagador_tipo' => 'propietario',
            'pagador_id' => $this->propietario->id,
            'creado_por' => $this->admin->id,
        ]);

        // Saldo ya CONSUMIDO (aplicado por generación de períodos)
        SaldoAFavor::create([
            'unidad_id' => $this->unidad->id,
            'ingreso_id' => $ingreso->id,
            'monto_original' => 500,
            'monto_aplicado' => 500,
            'monto_saldo' => 0,
            'estado' => 'consumido',
            'creado_por' => $this->admin->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('saldo a favor que ya fue aplicado');

        app(IngresoService::class)->eliminar($ingreso);
    }

    public function test_eliminar_recibo_con_saldo_disponible_sigue_funcionando(): void
    {
        $expensa = $this->crearExpensa(250);

        $ingreso = Ingreso::create([
            'numero_recibo' => 'IF-SALDO-2',
            'fecha' => now()->toDateString(),
            'categoria_ingreso_id' => $this->categoria->id,
            'tipo_ingreso' => 'expensa',
            'importe' => 300,
            'monto_total' => 300,
            'metodo_pago' => 'efectivo',
            'unidad_id' => $this->unidad->id,
            'pagador_tipo' => 'propietario',
            'pagador_id' => $this->propietario->id,
            'creado_por' => $this->admin->id,
        ]);

        SaldoAFavor::create([
            'unidad_id' => $this->unidad->id,
            'ingreso_id' => $ingreso->id,
            'monto_original' => 300,
            'monto_aplicado' => 0,
            'monto_saldo' => 300,
            'estado' => 'disponible',
            'creado_por' => $this->admin->id,
        ]);

        app(IngresoService::class)->eliminar($ingreso);

        $this->assertTrue($ingreso->fresh()->trashed());
        $this->assertEquals(0, SaldoAFavor::where('ingreso_id', $ingreso->id)->count());
    }
}
