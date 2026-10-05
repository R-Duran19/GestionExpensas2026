<?php

namespace Tests\Feature\Portal;

use App\Livewire\Portal\AccesoPortal;
use App\Models\Inquilino;
use App\Models\Propietario;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * [PORTAL / Fase 3 — RF-48] Autorización del propietario al inquilino.
 */
class PortalAutorizacionTest extends TestCase
{
    use RefreshDatabase;

    protected Propietario $propietario;
    protected Inquilino $inquilino;
    protected Unidad $unidad;
    protected User $propietarioUser;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'propietario', 'inquilino'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }

        $this->propietario = Propietario::create([
            'nombre' => 'Dueño', 'apellido' => 'Casa', 'ci' => '1112223',
        ]);
        $this->inquilino = Inquilino::create([
            'nombre' => 'Renta', 'apellido' => 'Mensual', 'ci' => '4445556',
        ]);
        $this->unidad = Unidad::create([
            'codigo' => 'DEP-5-C',
            'tipo_unidad' => 'departamento',
            'piso' => 5,
            'metros_cuadrados' => 90,
            'categoria_unidad' => 'domiciliaria',
            'tiene_medidor' => false,
            'activo' => true,
        ]);

        $this->unidad->propietarios()->attach($this->propietario->id, [
            'fecha_inicio' => now()->subYears(2),
            'es_propietario_actual' => true,
        ]);

        DB::table('inquilino_unidad')->insert([
            'inquilino_id' => $this->inquilino->id,
            'unidad_id' => $this->unidad->id,
            'fecha_inicio' => now()->subMonths(6),
            'es_inquilino_actual' => true,
            'portal_autorizado' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->propietarioUser = User::create([
            'name' => 'Dueño Casa',
            'password' => 'password',
            'tipo' => 'propietario',
            'persona_id' => $this->propietario->id,
            'ci_hash' => \App\Helpers\Pii::hash('1112223'),
            'activo' => true,
        ]);
        $this->propietarioUser->assignRole('propietario');
    }

    private function autorizar(bool $estado = true): void
    {
        DB::table('inquilino_unidad')
            ->where('inquilino_id', $this->inquilino->id)
            ->where('unidad_id', $this->unidad->id)
            ->update(['portal_autorizado' => $estado]);
    }

    public function test_inquilino_sin_autorizacion_no_ve_unidades(): void
    {
        $this->assertFalse($this->inquilino->tieneAutorizacionPortal());
        $this->assertCount(0, $this->inquilino->unidadesAutorizadasPortal()->get());
    }

    public function test_inquilino_autorizado_ve_su_unidad_en_portal(): void
    {
        $this->autorizar(true);

        $user = User::create([
            'name' => 'Renta Mensual',
            'password' => 'password',
            'tipo' => 'inquilino',
            'persona_id' => $this->inquilino->id,
            'ci_hash' => \App\Helpers\Pii::hash('4445556'),
            'activo' => true,
        ]);
        $user->assignRole('inquilino');

        $response = $this->actingAs($user)->get('/portal');

        $response->assertOk();
        $response->assertSee('DEP-5-C');
    }

    public function test_inquilino_sin_autorizacion_no_ve_la_unidad_en_portal(): void
    {
        $this->autorizar(false);

        $user = User::create([
            'name' => 'Renta Mensual',
            'password' => 'password',
            'tipo' => 'inquilino',
            'persona_id' => $this->inquilino->id,
            'ci_hash' => \App\Helpers\Pii::hash('4445556'),
            'activo' => true,
        ]);
        $user->assignRole('inquilino');

        $response = $this->actingAs($user)->get('/portal');

        $response->assertOk();
        $response->assertDontSee('DEP-5-C');
    }

    public function test_inquilino_no_accede_a_mis_inquilinos(): void
    {
        $this->autorizar(true);

        $user = User::create([
            'name' => 'Renta Mensual',
            'password' => 'password',
            'tipo' => 'inquilino',
            'persona_id' => $this->inquilino->id,
            'ci_hash' => \App\Helpers\Pii::hash('4445556'),
            'activo' => true,
        ]);
        $user->assignRole('inquilino');

        $response = $this->actingAs($user)->get('/portal/inquilinos');

        $response->assertForbidden();
    }

    public function test_propietario_ve_pagina_mis_inquilinos(): void
    {
        $response = $this->actingAs($this->propietarioUser)->get('/portal/inquilinos');

        $response->assertOk();
        $response->assertSee('DEP-5-C');
        $response->assertSee('Renta Mensual');
    }

    public function test_admin_no_puede_crear_cuenta_inquilino_sin_autorizacion(): void
    {
        // Fase 3.4: sin portal_autorizado, crearAcceso debe rechazar
        $this->autorizar(false);

        Livewire::test(AccesoPortal::class, [
            'tipo' => 'inquilino',
            'personaId' => $this->inquilino->id,
        ])->call('crearAcceso');

        $this->assertDatabaseMissing('users', [
            'tipo' => 'inquilino',
            'persona_id' => $this->inquilino->id,
        ]);
    }

    public function test_admin_puede_crear_cuenta_inquilino_con_autorizacion(): void
    {
        $this->autorizar(true);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Livewire::actingAs($admin)->test(AccesoPortal::class, [
            'tipo' => 'inquilino',
            'personaId' => $this->inquilino->id,
        ])->call('crearAcceso');

        $this->assertDatabaseHas('users', [
            'tipo' => 'inquilino',
            'persona_id' => $this->inquilino->id,
            'must_change_password' => true,
        ]);
    }

    /*
    | [REGRESIÓN 2026-09-24] El listado anterior llamaba ->first() sobre el
    | ACCESOR getInquilinoActualAttribute (que devuelve un modelo): ese
    | Model->first() ejecutaba Inquilino::first() y mostraba SIEMPRE el
    | primer inquilino de la tabla, mezclando datos en pantalla.
    */
    public function test_grupo_muestra_el_inquilino_correcto_no_el_primero_de_la_tabla(): void
    {
        // Inquilino #X ya existente con id MENOR que el asignado
        $primerInquilinoDeLaTabla = Inquilino::orderBy('id')->first();

        // ROXANA-like: propietario con 2 unidades alquiladas al MISMO inquilino
        $duena = Propietario::create(['nombre' => 'Dueña', 'apellido' => 'Dos', 'ci' => '2223334']);
        $inq2 = Inquilino::create(['nombre' => 'Inquilino', 'apellido' => 'Correcto', 'ci' => '8889990']);

        $u1 = Unidad::create([
            'codigo' => 'B-98', 'tipo_unidad' => 'baulera', 'piso' => -1,
            'metros_cuadrados' => 6, 'categoria_unidad' => 'domiciliaria',
            'tiene_medidor' => false, 'activo' => true,
        ]);
        $u2 = Unidad::create([
            'codigo' => 'B-99', 'tipo_unidad' => 'baulera', 'piso' => -1,
            'metros_cuadrados' => 6, 'categoria_unidad' => 'domiciliaria',
            'tiene_medidor' => false, 'activo' => true,
        ]);

        foreach ([$u1, $u2] as $u) {
            $u->propietarios()->attach($duena->id, [
                'fecha_inicio' => now()->subYear(),
                'es_propietario_actual' => true,
            ]);
            DB::table('inquilino_unidad')->insert([
                'inquilino_id' => $inq2->id,
                'unidad_id' => $u->id,
                'fecha_inicio' => now()->subMonths(2),
                'es_inquilino_actual' => true,
                'portal_autorizado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $duenaUser = User::create([
            'name' => 'Dueña Dos',
            'password' => 'password',
            'tipo' => 'propietario',
            'persona_id' => $duena->id,
            'ci_hash' => \App\Helpers\Pii::hash('2223334'),
            'activo' => true,
        ]);
        $duenaUser->assignRole('propietario');

        $componente = Livewire::actingAs($duenaUser)
            ->test(\App\Livewire\Portal\PortalMisInquilinos::class);

        $grupos = $componente->get('grupos');

        $this->assertCount(1, $grupos, 'Debe haber UN grupo (un inquilino con 2 unidades)');
        $this->assertEquals(
            $inq2->id,
            $grupos[0]->inquilino->id,
            'El grupo debe ser el inquilino CORRECTO, no el primero de la tabla'
        );
        $this->assertCount(2, $grupos[0]->unidades);
        $this->assertFalse($grupos[0]->autorizado);

        // La respuesta HTTP muestra el inquilino correcto y NO el de la tabla
        $response = $this->actingAs($duenaUser)->get('/portal/inquilinos');
        $response->assertOk();
        $response->assertSee('Inquilino Correcto');
        $response->assertDontSee($primerInquilinoDeLaTabla->nombre_completo);
    }

    public function test_autorizacion_grupo_autoriza_todas_las_unidades_del_inquilino(): void
    {
        $duena = Propietario::create(['nombre' => 'Dueña', 'apellido' => 'Multi', 'ci' => '3334445']);
        $inq = Inquilino::create(['nombre' => 'Multi', 'apellido' => 'Propiedad', 'ci' => '7776665']);

        $unidades = collect();
        foreach (['B-90', 'B-91', 'B-92'] as $cod) {
            $u = Unidad::create([
                'codigo' => $cod, 'tipo_unidad' => 'baulera', 'piso' => -1,
                'metros_cuadrados' => 6, 'categoria_unidad' => 'domiciliaria',
                'tiene_medidor' => false, 'activo' => true,
            ]);
            $u->propietarios()->attach($duena->id, [
                'fecha_inicio' => now()->subYear(),
                'es_propietario_actual' => true,
            ]);
            DB::table('inquilino_unidad')->insert([
                'inquilino_id' => $inq->id,
                'unidad_id' => $u->id,
                'fecha_inicio' => now()->subMonth(),
                'es_inquilino_actual' => true,
                'portal_autorizado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $unidades->push($u->id);
        }

        $duenaUser = User::create([
            'name' => 'Dueña Multi',
            'password' => 'password',
            'tipo' => 'propietario',
            'persona_id' => $duena->id,
            'ci_hash' => \App\Helpers\Pii::hash('3334445'),
            'activo' => true,
        ]);
        $duenaUser->assignRole('propietario');

        Livewire::actingAs($duenaUser)
            ->test(\App\Livewire\Portal\PortalMisInquilinos::class)
            ->call('toggleAutorizacionGrupo', $inq->id);

        // TODAS las unidades del inquilino quedan autorizadas
        $autorizadas = DB::table('inquilino_unidad')
            ->where('inquilino_id', $inq->id)
            ->where('portal_autorizado', true)
            ->count();

        $this->assertEquals(3, $autorizadas, 'Un botón debe autorizar TODAS las propiedades del inquilino');

        // Y revocar las revoca todas
        Livewire::actingAs($duenaUser)
            ->test(\App\Livewire\Portal\PortalMisInquilinos::class)
            ->call('toggleAutorizacionGrupo', $inq->id);

        $revocadas = DB::table('inquilino_unidad')
            ->where('inquilino_id', $inq->id)
            ->where('portal_autorizado', false)
            ->count();

        $this->assertEquals(3, $revocadas);
    }

    public function test_no_puede_autorizar_inquilino_de_otro_propietario(): void
    {
        // ANTI-IDOR: el propietario de prueba intenta autorizar al inquilino
        // de OTRO dueño
        Livewire::actingAs($this->propietarioUser)
            ->test(\App\Livewire\Portal\PortalMisInquilinos::class)
            ->call('toggleAutorizacionGrupo', 999999);

        $this->assertDatabaseHas('inquilino_unidad', [
            'inquilino_id' => $this->inquilino->id,
            'unidad_id' => $this->unidad->id,
            'portal_autorizado' => false,
        ]);
    }
}
