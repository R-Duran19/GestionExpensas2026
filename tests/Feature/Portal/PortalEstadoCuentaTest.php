<?php

namespace Tests\Feature\Portal;

use App\Models\Expensa;
use App\Models\Ingreso;
use App\Models\Inquilino;
use App\Models\PeriodoExpensa;
use App\Models\Propietario;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * [PORTAL / Fase 2] Tests del estado de cuenta, historial y PDFs propios.
 *
 * CRÍTICO (anti-IDOR): un usuario del portal NUNCA debe poder ver datos de
 * unidades/pagos ajenos, ni aunque fuerce IDs en la URL o en el componente.
 */
class PortalEstadoCuentaTest extends TestCase
{
    use RefreshDatabase;

    protected User $propietarioUser;
    protected Propietario $propietario;
    protected Unidad $unidadPropia;
    protected Unidad $unidadAjena;
    protected PeriodoExpensa $periodo;
    protected User $otroUser;
    protected User $admin;
    protected int $categoriaIngresoId;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'propietario', 'inquilino'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }

        // Categoría de ingreso requerida por la tabla ingresos (FK a users)
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        \App\Models\CategoriaIngreso::create([
            'nombre' => 'Pago de expensas',
            'activo' => true,
            'creado_por' => $this->admin->id,
        ]);
        $this->categoriaIngresoId = \App\Models\CategoriaIngreso::first()->id;

        // Propietario con unidad propia
        $this->propietario = Propietario::create([
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'ci' => '5551111',
        ]);

        $this->unidadPropia = Unidad::create([
            'codigo' => 'DEP-1-A',
            'tipo_unidad' => 'departamento',
            'piso' => 1,
            'metros_cuadrados' => 80,
            'categoria_unidad' => 'domiciliaria',
            'tiene_medidor' => false,
            'activo' => true,
        ]);

        $this->unidadPropia->propietarios()->attach($this->propietario->id, [
            'fecha_inicio' => now()->subYear(),
            'es_propietario_actual' => true,
        ]);

        // Unidad de OTRO propietario (para probar que NO la vea)
        $otroProp = Propietario::create(['nombre' => 'Ana', 'apellido' => 'Lopez', 'ci' => '9998888']);
        $this->unidadAjena = Unidad::create([
            'codigo' => 'DEP-9-Z',
            'tipo_unidad' => 'departamento',
            'piso' => 9,
            'metros_cuadrados' => 100,
            'categoria_unidad' => 'domiciliaria',
            'tiene_medidor' => false,
            'activo' => true,
        ]);
        $this->unidadAjena->propietarios()->attach($otroProp->id, [
            'fecha_inicio' => now()->subYear(),
            'es_propietario_actual' => true,
        ]);

        // Período + expensas de ambas unidades
        $this->periodo = PeriodoExpensa::create([
            'mes' => 8,
            'año' => 2026,
            'fecha_apertura' => now()->subDays(40),
            'estado' => 'cerrado',
        ]);

        Expensa::create([
            'periodo_id' => $this->periodo->id,
            'unidad_id' => $this->unidadPropia->id,
            'monto_expensa' => 200,
            'monto_agua' => 30,
            'expensa_pendiente' => 200,
            'agua_pendiente' => 30,
            'periodo_cerrado' => true,
            'monto_total' => 230,
        ]);

        Expensa::create([
            'periodo_id' => $this->periodo->id,
            'unidad_id' => $this->unidadAjena->id,
            'monto_expensa' => 500,
            'monto_agua' => 0,
            'expensa_pendiente' => 500,
            'agua_pendiente' => 0,
            'periodo_cerrado' => true,
            'monto_total' => 500,
        ]);

        // Usuario del portal vinculado al propietario
        $this->propietarioUser = User::create([
            'name' => 'Juan Pérez',
            'password' => 'password',
            'tipo' => 'propietario',
            'persona_id' => $this->propietario->id,
            'ci_hash' => \App\Helpers\Pii::hash('5551111'),
            'activo' => true,
        ]);
        $this->propietarioUser->assignRole('propietario');
    }

    public function test_estado_cuenta_muestra_su_unidad(): void
    {
        $response = $this->actingAs($this->propietarioUser)->get('/portal/estado-cuenta');

        $response->assertOk();
        $response->assertSee('DEP-1-A');
        $response->assertSee('230.00');  // total de su expensa
    }

    public function test_estado_cuenta_no_muestra_unidad_ajena(): void
    {
        $response = $this->actingAs($this->propietarioUser)->get('/portal/estado-cuenta');

        $response->assertOk();
        $response->assertDontSee('DEP-9-Z');
        $response->assertDontSee('500.00');
    }

    public function test_admin_no_accede_al_estado_de_cuenta(): void
    {
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $response = $this->actingAs($this->admin)->get('/portal/estado-cuenta');

        $response->assertForbidden();
    }

    public function test_historial_pagos_solo_propios(): void
    {
        // Pago del propietario de prueba
        $propio = Ingreso::create([
            'numero_recibo' => 'REC-26-08-0000001',
            'fecha' => now(),
            'importe' => 230,
            'monto_total' => 230,
            'metodo_pago' => 'efectivo',
            'unidad_id' => $this->unidadPropia->id,
            'pagador_tipo' => 'propietario',
            'pagador_id' => $this->propietario->id,
            'creado_por' => $this->admin->id,
            'categoria_ingreso_id' => $this->categoriaIngresoId,
            'tipo_ingreso' => 'expensa',
        ]);

        // Pago ajeno (otra persona, misma unidad incluso)
        $ajeno = Ingreso::create([
            'numero_recibo' => 'REC-26-08-0000002',
            'fecha' => now(),
            'importe' => 999,
            'monto_total' => 999,
            'metodo_pago' => 'transferencia',
            'unidad_id' => $this->unidadPropia->id,
            'pagador_tipo' => 'propietario',
            'pagador_id' => $this->unidadAjena->propietarioActual->id,
            'creado_por' => $this->admin->id,
            'categoria_ingreso_id' => $this->categoriaIngresoId,
            'tipo_ingreso' => 'expensa',
        ]);

        $response = $this->actingAs($this->propietarioUser)->get('/portal/pagos');

        $response->assertOk();
        $response->assertSee('REC-26-08-0000001');
        $response->assertDontSee('REC-26-08-0000002');
    }

    public function test_recibo_ajeno_da_404(): void
    {
        $otroProp = $this->unidadAjena->propietarioActual;

        $ajeno = Ingreso::create([
            'numero_recibo' => 'REC-26-08-0000003',
            'fecha' => now(),
            'importe' => 500,
            'monto_total' => 500,
            'metodo_pago' => 'efectivo',
            'unidad_id' => $this->unidadAjena->id,
            'pagador_tipo' => 'propietario',
            'pagador_id' => $otroProp->id,
            'creado_por' => $this->admin->id,
            'categoria_ingreso_id' => $this->categoriaIngresoId,
            'tipo_ingreso' => 'expensa',
        ]);

        // El usuario intenta abrir el recibo de OTRO por ID → 404 (firstOrFail)
        $response = $this->actingAs($this->propietarioUser)
            ->get("/portal/recibo/{$ajeno->id}");

        $response->assertNotFound();
    }

    public function test_boleta_propia_genera_pdf(): void
    {
        $response = $this->actingAs($this->propietarioUser)
            ->get("/portal/boleta/{$this->periodo->id}");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
