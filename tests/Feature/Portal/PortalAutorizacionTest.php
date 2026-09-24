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
}
