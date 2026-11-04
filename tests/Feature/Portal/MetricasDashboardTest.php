<?php

namespace Tests\Feature\Portal;

use App\Livewire\Portal\PortalDashboard;
use App\Models\LecturaMedidor;
use App\Models\PeriodoExpensa;
use App\Models\Propietario;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * [METRICAS 2026-10-30] Dashboard admin con panel de métricas + páginas
 * "Ver más" (morosidad, consumo, drill-down por unidad) + sección de consumo
 * del portal.
 *
 * - /dashboard, /metricas/* SOLO admin (middleware 'admin').
 * - PortalDashboard muestra el consumo histórico de las unidades del usuario
 *   (anti-IDOR: unidades resueltas desde auth(), nunca por parámetro).
 */
class MetricasDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $portalUser;
    protected Unidad $unidad;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'propietario'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }

        $propietario = Propietario::create([
            'nombre' => 'Metrica', 'apellido' => 'Test', 'ci' => '9991112',
        ]);

        $this->unidad = Unidad::create([
            'codigo' => 'DEP-MET-1',
            'tipo_unidad' => 'departamento',
            'piso' => 4,
            'metros_cuadrados' => 80,
            'categoria_unidad' => 'domiciliaria',
            'tiene_medidor' => true,
            'activo' => true,
        ]);

        $this->unidad->propietarios()->attach($propietario->id, [
            'fecha_inicio' => now()->subYear(),
            'es_propietario_actual' => true,
        ]);

        // Período con lectura (consumo variado para el histórico)
        $periodo = PeriodoExpensa::create([
            'mes' => 6, 'año' => 2026,
            'fecha_apertura' => now()->subMonths(2),
            'estado' => 'cerrado',
        ]);

        $this->admin = User::create([
            'name' => 'Admin Metricas',
            'email' => 'admin-metricas@test.local',
            'password' => bcrypt('password'),
            'tipo' => 'admin',
            'activo' => true,
        ]);
        $this->admin->assignRole('admin');

        LecturaMedidor::create([
            'periodo_id' => $periodo->id,
            'unidad_id' => $this->unidad->id,
            'lectura_anterior' => 0,
            'lectura_actual' => 12,
            'consumo_m3' => 12,
            'registrado_por' => $this->admin->id,
        ]);

        // Expensa pendiente del período (para el timeline Pagado/Parcial/Pendiente)
        \App\Models\Expensa::create([
            'periodo_id' => $periodo->id,
            'unidad_id' => $this->unidad->id,
            'monto_expensa' => 150, 'monto_agua' => 20,
            'expensa_pendiente' => 150, 'agua_pendiente' => 20,
            'periodo_cerrado' => false, 'monto_total' => 170,
        ]);

        $this->portalUser = User::create([
            'name' => 'Metrica Test',
            'password' => bcrypt('password'),
            'tipo' => 'propietario',
            'persona_id' => $propietario->id,
            'ci_hash' => \App\Helpers\Pii::hash('9991112'),
            'activo' => true,
        ]);
        $this->portalUser->assignRole('propietario');
    }

    public function test_dashboard_admin_muestra_metricas(): void
    {
        $resp = $this->actingAs($this->admin)->get('/dashboard');

        $resp->assertStatus(200);
        $resp->assertSee('Morosidad global');
        $resp->assertSee('Top 5 morosos');
        $resp->assertSee('Deuda por antigüedad');
        $resp->assertSee('chartConsumo');
    }

    public function test_paginas_metricas_solo_para_admin(): void
    {
        // Portal user NO puede ver métricas de admin
        $resp = $this->actingAs($this->portalUser)->get('/metricas/morosidad');
        $this->assertNotEquals(200, $resp->status());

        // Admin sí
        $this->actingAs($this->admin)->get('/metricas/morosidad')
            ->assertStatus(200)
            ->assertSee('Todas las unidades con deuda');

        $this->actingAs($this->admin)->get('/metricas/consumo')
            ->assertStatus(200)
            ->assertSee('Anomalías de consumo');

        $this->withoutExceptionHandling();
        $this->actingAs($this->admin)->get("/metricas/consumo/{$this->unidad->id}")
            ->assertStatus(200)
            ->assertSee('Histórico de consumo por medidor');
    }

    public function test_portal_dashboard_muestra_consumo_historico(): void
    {
        $this->actingAs($this->portalUser);
        $t2 = \Livewire\Livewire::test(PortalDashboard::class);

        $historico = $t2->instance()->consumoHistorico;
        $this->assertNotEmpty($historico['serie']);
        $this->assertEquals(12, $historico['serie'][0]['m3']);

        // assertSee sin escape + texto ASCII (los acentos se transforman en entidades)
        $t2->assertSee('chartConsumoPortal', false);
        $t2->assertSee('Historial por', false);
        $t2->assertSee('Pendiente', false);

        // assertSee sin escape + texto ASCII: 'período' lleva acentos que el
        // HTML transforma en entidades y romperían la comparación.
        $t2->assertSee('chartConsumoPortal', false);
        $t2->assertSee('Historial por', false);
    }
}
