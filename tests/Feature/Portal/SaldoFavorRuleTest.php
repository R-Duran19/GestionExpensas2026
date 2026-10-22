<?php

namespace Tests\Feature\Portal;

use App\Livewire\Portal\PagarExpensas;
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
use App\Services\QrBanco\FakeQrDriver;
use App\Services\QrBanco\PagoQrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * [REGLA SALDO A FAVOR — OPCIÓN 3 + FIX B, 2026-10-15]
 *
 * Opción 3 (por unidad): solo la fila cuya unidad NO tiene OTRAS deudas
 * pendientes permite sobrepagar (excedente → SaldoAFavor). El clamp vive en
 * el componente (UI + servidor) para que una petición manipulada no pase.
 *
 * Fix B: al generar el expensado de un período nuevo, el saldo a favor
 * disponible de la unidad se aplica a la expensa NUEVA (ingreso_detalle del
 * ingreso que generó el saldo) — el mes siguiente "nace pagado" o pagado
 * parcial según cubra.
 */
class SaldoFavorRuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Unidad $unidad;
    protected PeriodoExpensa $periodo1;
    protected PeriodoExpensa $periodo2;
    protected ExpensaService $expensaService;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'propietario', 'inquilino'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }

        $this->expensaService = app(ExpensaService::class);

        $propietario = Propietario::create([
            'nombre' => 'Regla', 'apellido' => 'Saldo', 'ci' => '5556667',
        ]);

        $this->unidad = Unidad::create([
            'codigo' => 'DEP-SF-1',
            'tipo_unidad' => 'departamento',
            'piso' => 3,
            'metros_cuadrados' => 70,
            'categoria_unidad' => 'domiciliaria',
            'tiene_medidor' => false,
            'activo' => true,
        ]);

        $this->unidad->propietarios()->attach($propietario->id, [
            'fecha_inicio' => now()->subYear(),
            'es_propietario_actual' => true,
        ]);

        $this->periodo1 = PeriodoExpensa::create([
            'mes' => 6, 'año' => 2026,
            'fecha_apertura' => now()->subMonths(2),
            'estado' => 'abierto',
        ]);
        $this->periodo2 = PeriodoExpensa::create([
            'mes' => 7, 'año' => 2026,
            'fecha_apertura' => now()->subMonth(),
            'estado' => 'abierto',
        ]);

        Expensa::create([
            'periodo_id' => $this->periodo1->id,
            'unidad_id' => $this->unidad->id,
            'monto_expensa' => 200, 'monto_agua' => 50,
            'expensa_pendiente' => 200, 'agua_pendiente' => 50,
            'periodo_cerrado' => false, 'monto_total' => 250,
        ]);
        Expensa::create([
            'periodo_id' => $this->periodo2->id,
            'unidad_id' => $this->unidad->id,
            'monto_expensa' => 200, 'monto_agua' => 50,
            'expensa_pendiente' => 200, 'agua_pendiente' => 50,
            'periodo_cerrado' => false, 'monto_total' => 250,
        ]);

        $this->user = User::create([
            'name' => 'Regla Saldo',
            'password' => 'password',
            'tipo' => 'propietario',
            'persona_id' => $propietario->id,
            'ci_hash' => \App\Helpers\Pii::hash('5556667'),
            'activo' => true,
        ]);
        $this->user->assignRole('propietario');
    }

    private function componentePortal(): \Livewire\Features\SupportTesting\Testable
    {
        $this->actingAs($this->user);

        return \Livewire\Livewire::test(PagarExpensas::class);
    }

    private function distribucion(PagarExpensas $componente): array
    {
        $ref = new \ReflectionMethod($componente, 'obtenerExpensasAPagar');
        $ref->setAccessible(true);

        return $ref->invoke($componente);
    }

    public function test_unidad_con_deudas_en_varios_periodos_no_permite_sobrepago(): void
    {
        $t = $this->componentePortal();

        // La unidad tiene 2 filas pendientes → NINGUNA permite sobrepago
        foreach ($t->instance()->deudasPendientes as $periodo) {
            foreach ($periodo['expensas'] as $fila) {
                $this->assertFalse($fila['permite_sobrepago']);
            }
        }

        // Intento de sobrepago en la última fila: se clampea a la deuda (200)
        $t->set('deudasPendientes.1.expensas.0.pagar_expensa', true)
            ->call('actualizarMontoExpensa', 1, 0)
            ->set('deudasPendientes.1.expensas.0.monto_expensa', 1000)
            ->call('actualizarDesdeInputExpensa', 1, 0);

        $fila = $t->instance()->deudasPendientes[1]['expensas'][0];
        $this->assertEquals(200, $fila['monto_expensa']);
        $this->assertTrue($fila['aviso_expensa']);

        // Clamp de servidor: la distribución tampoco lleva excedente
        $dist = $this->distribucion($t->instance());
        $this->assertEquals(200, $dist[0]['monto_aplicar']);
    }

    public function test_unidad_con_una_sola_deuda_permite_sobrepago_y_genera_saldo(): void
    {
        // Pagar por completo el período 1 → queda UNA sola deuda (período 2)
        Expensa::where('periodo_id', $this->periodo1->id)->update([
            'expensa_pendiente' => 0, 'agua_pendiente' => 0,
        ]);

        $t = $this->componentePortal();

        $fila = $t->instance()->deudasPendientes[0]['expensas'][0];
        $this->assertTrue($fila['permite_sobrepago']);

        // Sobrepago: deuda 250 → paga 300 (50 de excedente)
        $t->set('deudasPendientes.0.expensas.0.pagar_expensa', true)
            ->call('actualizarMontoExpensa', 0, 0)
            ->set('deudasPendientes.0.expensas.0.monto_expensa', 250)
            ->set('deudasPendientes.0.expensas.0.pagar_agua', true)
            ->call('actualizarMontoAgua', 0, 0)
            ->set('deudasPendientes.0.expensas.0.monto_agua', 50)
            ->call('actualizarDesdeInputAgua', 0, 0);

        // El clamp NO toca la fila sobrepagable
        $dist = $this->distribucion($t->instance());
        $this->assertEquals(300, collect($dist)->sum('monto_aplicar'));

        // Acreditar el QR → el excedente se convierte en SaldoAFavor
        $service = new PagoQrService(new FakeQrDriver(), app(\App\Services\PagoExpensaService::class));
        $qr = $service->crearIntencion($this->user, $this->unidad->id, 300, ['glosa' => 'Expensas DEP-SF-1']);
        $service->acreditar($qr, ['alias' => $qr->alias, 'monto' => 300]);

        $saldo = SaldoAFavor::where('unidad_id', $this->unidad->id)->first();
        $this->assertNotNull($saldo);
        $this->assertEquals(50, (float) $saldo->monto_saldo);
        $this->assertEquals('disponible', $saldo->estado);
    }

    public function test_fix_b_saldo_favor_se_aplica_al_nuevo_periodo(): void
    {
        // Unidad AL DÍA (sin pendientes) + saldo a favor de 50 disponible
        Expensa::query()->update(['expensa_pendiente' => 0, 'agua_pendiente' => 0]);

        $categoria = CategoriaIngreso::create([
            'nombre' => 'Expensas', 'descripcion' => 'Test', 'activo' => true,
            'creado_por' => $this->user->id,
            'actualizado_por' => $this->user->id,
        ]);
        $ingreso = Ingreso::create([
            'numero_recibo' => 'REC-TEST-SF-1',
            'fecha' => now(),
            'categoria_ingreso_id' => $categoria->id,
            'tipo_ingreso' => 'expensa',
            'importe' => 300,
            'monto_total' => 300,
            'metodo_pago' => 'qr',
            'unidad_id' => $this->unidad->id,
            'pagador_tipo' => 'propietario',
            'pagador_id' => $this->user->persona_id,
            'activo' => true,
            'creado_por' => $this->user->id,
            'actualizado_por' => $this->user->id,
        ]);
        SaldoAFavor::create([
            'unidad_id' => $this->unidad->id,
            'ingreso_id' => $ingreso->id,
            'monto_original' => 300,
            'monto_aplicado' => 250,
            'monto_saldo' => 50,
            'estado' => 'disponible',
            'creado_por' => $this->user->id,
        ]);

        // Período NUEVO (agosto) con factores calculados
        $nuevo = PeriodoExpensa::create([
            'mes' => 8, 'año' => 2026,
            'fecha_apertura' => now(),
            'estado' => 'abierto',
            'factor_domiciliario' => 0.1,
            'factor_comercial' => 0.2,
        ]);

        $this->expensaService->generarExpensas($nuevo->id);

        $expensa = Expensa::where('periodo_id', $nuevo->id)
            ->where('unidad_id', $this->unidad->id)
            ->first();

        $this->assertNotNull($expensa);

        // El saldo de 50 se aplicó como pago automático (trazable)
        $detalle = IngresoDetalle::where('expensa_id', $expensa->id)
            ->where('ingreso_id', $ingreso->id)
            ->first();
        $this->assertNotNull($detalle);
        $this->assertEquals(50, (float) $detalle->monto_expensa_aplicado);

        // Pendiente = monto emitido - lo aplicado por el saldo
        $this->assertEquals(
            max(0, (float) $expensa->monto_expensa - 50),
            (float) $expensa->expensa_pendiente
        );

        // Si el saldo cubría TODO, queda consumido; si no, parcialmente
        $saldo = SaldoAFavor::where('unidad_id', $this->unidad->id)->first();
        $this->assertEquals(
            (float) $expensa->monto_expensa >= 50 ? 'consumido' : 'parcialmente_consumido',
            $saldo->estado
        );
        $this->assertEquals(
            max(0, 50 - (float) $expensa->monto_expensa),
            (float) $saldo->monto_saldo
        );
    }
}
