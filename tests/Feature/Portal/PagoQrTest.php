<?php

namespace Tests\Feature\Portal;

use App\Models\Expensa;
use App\Models\Ingreso;
use App\Models\PeriodoExpensa;
use App\Models\Propietario;
use App\Models\PagoQr;
use App\Models\Unidad;
use App\Models\User;
use App\Services\QrBanco\PagoQrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * [PORTAL QR / Fase 4] Flujo completo de pago QR con el driver FAKE.
 *
 * Cubre el checklist de edge cases del plan (4.4):
 * - Consistencia: 1 QR activo por usuario (recargar no crea otro).
 * - Webhook duplicado => 1 solo ingreso (idempotencia).
 * - Polling y webhook en carrera => 1 solo ingreso.
 * - Pago tardío tras expiración => se acredita igual.
 * - FIFO: excedente => SaldoAFavor.
 */
class PagoQrTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Unidad $unidad;
    protected PeriodoExpensa $periodo1;
    protected PeriodoExpensa $periodo2;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'propietario', 'inquilino'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }

        $propietario = Propietario::create([
            'nombre' => 'Paga', 'apellido' => 'Qr', 'ci' => '7778889',
        ]);

        $this->unidad = Unidad::create([
            'codigo' => 'DEP-QR-1',
            'tipo_unidad' => 'departamento',
            'piso' => 2,
            'metros_cuadrados' => 70,
            'categoria_unidad' => 'domiciliaria',
            'tiene_medidor' => false,
            'activo' => true,
        ]);

        $this->unidad->propietarios()->attach($propietario->id, [
            'fecha_inicio' => now()->subYear(),
            'es_propietario_actual' => true,
        ]);

        // Dos períodos con deuda (para probar FIFO)
        $this->periodo1 = PeriodoExpensa::create([
            'mes' => 6, 'año' => 2026,
            'fecha_apertura' => now()->subMonths(2),
            'estado' => 'cerrado',
        ]);
        $this->periodo2 = PeriodoExpensa::create([
            'mes' => 7, 'año' => 2026,
            'fecha_apertura' => now()->subMonth(),
            'estado' => 'cerrado',
        ]);

        Expensa::create([
            'periodo_id' => $this->periodo1->id,
            'unidad_id' => $this->unidad->id,
            'monto_expensa' => 200, 'monto_agua' => 50,
            'expensa_pendiente' => 200, 'agua_pendiente' => 50,
            'periodo_cerrado' => true, 'monto_total' => 250,
        ]);
        Expensa::create([
            'periodo_id' => $this->periodo2->id,
            'unidad_id' => $this->unidad->id,
            'monto_expensa' => 200, 'monto_agua' => 50,
            'expensa_pendiente' => 200, 'agua_pendiente' => 50,
            'periodo_cerrado' => true, 'monto_total' => 250,
        ]);

        $this->user = User::create([
            'name' => 'Paga Qr',
            'password' => 'password',
            'tipo' => 'propietario',
            'persona_id' => $propietario->id,
            'ci_hash' => \App\Helpers\Pii::hash('7778889'),
            'activo' => true,
        ]);
        $this->user->assignRole('propietario');
    }

    private function service(): PagoQrService
    {
        return new PagoQrService(
            new \App\Services\QrBanco\FakeQrDriver(),
            app(\App\Services\PagoExpensaService::class)
        );
    }

    private function crearIntencion(float $monto): PagoQr
    {
        return $this->service()->crearIntencion(
            $this->user,
            $this->unidad->id,
            $monto,
            ['glosa' => 'Expensas unidad DEP-QR-1']
        );
    }

    public function test_crear_intencion_genera_qr_pendiente(): void
    {
        $qr = $this->crearIntencion(250);

        $this->assertEquals(PagoQr::ESTADO_PENDIENTE, $qr->estado);
        $this->assertEquals(250, (float) $qr->monto);
        $this->assertNotNull($qr->imagen_qr);
        $this->assertTrue($qr->fecha_vencimiento->isFuture());
    }

    public function test_recargar_no_genera_otro_qr(): void
    {
        $qr1 = $this->service()->obtenerActivo($this->user);
        $this->assertNull($qr1);

        $creado = $this->crearIntencion(250);

        // "Recargar": obtenerActivo devuelve EL MISMO QR
        $activo = $this->service()->obtenerActivo($this->user);
        $this->assertNotNull($activo);
        $this->assertEquals($creado->id, $activo->id);

        // Solo existe 1 QR pendiente para el usuario
        $this->assertEquals(1, PagoQr::where('user_id', $this->user->id)->where('estado', 'pendiente')->count());
    }

    public function test_nueva_intencion_cancela_la_anterior(): void
    {
        $qr1 = $this->crearIntencion(100);
        $qr2 = $this->crearIntencion(500);

        $qr1->refresh();
        $this->assertEquals(PagoQr::ESTADO_CANCELADO, $qr1->estado);
        $this->assertEquals(PagoQr::ESTADO_PENDIENTE, $qr2->estado);
    }

    public function test_pago_se_acredita_fifo_con_webhook(): void
    {
        $qr = $this->crearIntencion(300); // 250 del período 1 + 50 de expensa del 2

        $resultado = $this->service()->acreditar($qr, [
            'alias' => $qr->alias,
            'monto' => 300,
            'numeroOrdenOriginante' => 'ORD-123',
            'nombreCliente' => 'Paga Qr',
            'documentoCliente' => '7778889',
        ]);

        $qr->refresh();

        $this->assertEquals(PagoQr::ESTADO_PAGADO, $qr->estado);
        $this->assertNotNull($qr->ingreso_id);

        // FIFO: período 1 totalmente pagado, período 2 pagó 50 de expensa
        $exp1 = Expensa::find($resultado['pago_qr']->ingreso->detalles->first()->expensa_id);
        $this->assertEquals(0, (float) Expensa::where('periodo_id', $this->periodo1->id)->where('unidad_id', $this->unidad->id)->first()->expensa_pendiente);

        $exp2 = Expensa::where('periodo_id', $this->periodo2->id)->where('unidad_id', $this->unidad->id)->first();
        $this->assertEquals(150, (float) $exp2->expensa_pendiente);

        // El ingreso usa el método qr y registra al pagador
        $ingreso = Ingreso::find($qr->ingreso_id);
        $this->assertEquals('qr', $ingreso->metodo_pago);
        $this->assertEquals('propietario', $ingreso->pagador_tipo);
        $this->assertEquals('ORD-123', $ingreso->referencia_bancaria);
    }

    public function test_excedente_se_convierte_en_saldo_a_favor(): void
    {
        $qr = $this->crearIntencion(600); // deuda total = 500 => 100 de excedente

        $this->service()->acreditar($qr, ['alias' => $qr->alias, 'monto' => 600]);

        $qr->refresh();
        $this->assertEquals(PagoQr::ESTADO_PAGADO, $qr->estado);

        $saldo = \App\Models\SaldoAFavor::where('unidad_id', $this->unidad->id)->sum('monto_saldo');
        $this->assertEquals(100, (float) $saldo);
    }

    public function test_webhook_duplicado_genera_un_solo_ingreso(): void
    {
        $qr = $this->crearIntencion(250);

        $payload = ['alias' => $qr->alias, 'monto' => 250, 'numeroOrdenOriginante' => 'ORD-DUP'];

        // Primera acreditación (webhook)
        $r1 = $this->service()->acreditar($qr, $payload);
        $this->assertTrue($r1['procesado_ahora']);

        // Webhook duplicado
        $r2 = $this->service()->acreditar($qr, $payload);
        $this->assertFalse($r2['procesado_ahora']);

        // Carrera: polling tras el webhook
        $r3 = $this->service()->verificarYAcreditar($qr);
        $this->assertFalse($r3['pago_procesado']);

        $this->assertEquals(1, Ingreso::where('referencia_bancaria', 'ORD-DUP')->count());
    }

    public function test_pago_tardio_tras_expiracion_se_acredita(): void
    {
        $qr = $this->crearIntencion(250);

        // El QR expira localmente...
        $qr->update(['estado' => PagoQr::ESTADO_EXPIRADO]);

        // ...pero el banco notifica que se pagó (pago tardío: el dinero entró)
        $resultado = $this->service()->acreditar($qr, ['alias' => $qr->alias, 'monto' => 250]);

        $this->assertEquals(PagoQr::ESTADO_PAGADO, $resultado['pago_qr']->estado);
        $this->assertNotNull($resultado['pago_qr']->ingreso_id);
    }

    public function test_monto_divergente_va_a_revision_admin(): void
    {
        $qr = $this->crearIntencion(250);

        // Pago externo con monto distinto al QR (QR estático externo)
        $resultado = $this->service()->acreditar($qr, ['alias' => $qr->alias, 'monto' => 999]);

        $this->assertEquals(PagoQr::ESTADO_DIVERGENTE, $resultado['pago_qr']->estado);
        $this->assertNull($resultado['pago_qr']->ingreso_id);
        $this->assertEquals(0, Ingreso::count());
    }

    public function test_webhook_http_responde_formato_banco(): void
    {
        $qr = $this->crearIntencion(250);

        Cache::put("fake_qr_pago_{$qr->alias}", true, 60);

        $response = $this->postJson('/webhook/qr', [
            'alias' => $qr->alias,
            'monto' => 250,
            'numeroOrdenOriginante' => 'ORD-HTTP-1',
        ]);

        $response->assertOk()
            ->assertJson(['codigo' => '0000', 'mensaje' => 'Registro Exitoso']);

        $qr->refresh();
        $this->assertEquals(PagoQr::ESTADO_PAGADO, $qr->estado);
    }

    public function test_webhook_alias_desconocido_responde_exito(): void
    {
        // El banco no debe reintentar infinitamente por alias ajenos
        $response = $this->postJson('/webhook/qr', [
            'alias' => 'ALIAS-INEXISTENTE-123',
            'monto' => 100,
        ]);

        $response->assertOk()
            ->assertJson(['codigo' => '0000']);
    }

    public function test_comando_expira_qrs_vencidos(): void
    {
        $qr = $this->crearIntencion(250);
        $qr->update(['fecha_vencimiento' => now()->subHour()]);

        $this->artisan('qr:expirar')->assertSuccessful();

        $qr->refresh();
        $this->assertEquals(PagoQr::ESTADO_EXPIRADO, $qr->estado);
    }
}
