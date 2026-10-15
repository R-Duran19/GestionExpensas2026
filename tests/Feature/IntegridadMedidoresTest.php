<?php

namespace Tests\Feature;

use App\Models\LecturaMedidor;
use App\Models\MedidorGrupo;
use App\Models\PeriodoExpensa;
use App\Models\Propietario;
use App\Models\SecuenciaCaja;
use App\Models\Unidad;
use App\Models\User;
use App\Services\MedidorGrupoService;
use App\Services\MedidorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * [REMEDIACIÓN-2.3 / 2.7] Integridad de medidores y numeración.
 *
 * Cubre:
 * - No editar una lectura individual si existe lectura posterior (cadena).
 * - obtenerLecturaAnterior ignora filas DISTRIBUIDAS de grupo (base 0).
 * - No desasignar unidad con lecturas compartidas en período abierto.
 * - SecuenciaCaja: cada (tipo,año,mes) incrementa SU fila (no contador global).
 */
class IntegridadMedidoresTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Unidad $unidad;
    protected PeriodoExpensa $periodo1;
    protected PeriodoExpensa $periodo2;
    protected MedidorService $medidorService;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'propietario'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }

        $this->admin = User::factory()->create(['email' => 'med@test.bo']);
        $this->admin->assignRole('admin');
        $this->actingAs($this->admin);

        $propietario = Propietario::create([
            'nombre' => 'Medi', 'apellido' => 'Dor', 'ci' => '3334445',
        ]);

        $this->unidad = Unidad::create([
            'codigo' => 'DEP-MED-1',
            'tipo_unidad' => 'departamento',
            'piso' => 3,
            'metros_cuadrados' => 80,
            'categoria_unidad' => 'domiciliaria',
            'tiene_medidor' => true,
            'tipo_medidor' => 'individual',
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

        $this->medidorService = app(MedidorService::class);
    }

    private function crearLectura(PeriodoExpensa $periodo, ?float $anterior, ?float $actual, ?int $grupoId = null): LecturaMedidor
    {
        return LecturaMedidor::create([
            'periodo_id' => $periodo->id,
            'unidad_id' => $this->unidad->id,
            'lectura_anterior' => $anterior,
            'lectura_actual' => $actual,
            'consumo_m3' => ($actual ?? 0) - ($anterior ?? 0),
            'medidor_grupo_id' => $grupoId,
            'registrado_por' => $this->admin->id,
        ]);
    }

    public function test_no_editar_lectura_individual_con_lectura_posterior(): void
    {
        $l1 = $this->crearLectura($this->periodo1, 100, 110);
        $this->crearLectura($this->periodo2, 110, 120);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('período posterior');

        $this->medidorService->actualizarLecturaIndividual($l1->id, 150);
    }

    public function test_editar_lectura_ultima_funciona(): void
    {
        $this->crearLectura($this->periodo1, 100, 110);
        $l2 = $this->crearLectura($this->periodo2, 110, 120);

        $editada = $this->medidorService->actualizarLecturaIndividual($l2->id, 130);

        $this->assertEquals(20, (float) $editada->consumo_m3);
    }

    public function test_obtener_lectura_anterior_ignora_fila_huerfana_de_grupo(): void
    {
        $grupoOrfano = MedidorGrupo::create(['nombre' => 'GRUPO-ORF', 'activo' => false, 'creado_por' => $this->admin->id]);

        // Historial: lectura INDIVIDUAL real (P1: terminó en 100)...
        $this->crearLectura($this->periodo1, 50, 100);

        // ...y una fila DISTRIBUIDA de grupo en el período siguiente (la
        // huérfana que dejaba base 0.0 tras una desasignación).
        $this->crearLectura($this->periodo2, null, null, grupoId: $grupoOrfano->id);

        // Unidad ya es individual (fue "desasignada")
        $base = $this->medidorService->obtenerLecturaAnterior($this->unidad->id, $this->periodo2->id);

        // Debe resolver la P1 individual (base 100), NO la huérfana (base 0)
        $this->assertNotNull($base);
        $this->assertEquals($this->periodo1->id, $base->periodo_id);
        $this->assertEquals(100, (float) $base->lectura_actual);
    }

    public function test_no_desasignar_unidad_con_lecturas_compartidas_abiertas(): void
    {
        $grupo = MedidorGrupo::create(['nombre' => 'GRUPO-MED-1', 'activo' => true, 'creado_por' => $this->admin->id]);
        $grupo->unidades()->attach($this->unidad->id, ['fecha_asignacion' => now()->subMonths(3)]);

        // Lectura distribuida (lectura_actual NULL) en período ABIERTO
        $this->crearLectura($this->periodo2, null, null, grupoId: $grupo->id);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('período abierto');

        app(MedidorGrupoService::class)->desasignarUnidad($grupo->id, $this->unidad->id);
    }

    public function test_secuencias_caja_independientes_por_mes(): void
    {
        $sAgosto = SecuenciaCaja::obtenerIngreso(2026, 8);
        $n1 = $sAgosto->incrementar($this->admin->id);

        $sSeptiembre = SecuenciaCaja::obtenerIngreso(2026, 9);
        $n2 = $sSeptiembre->incrementar($this->admin->id);

        // [REMEDIACIÓN-2.7] Cada mes lleva SU contador (antes compartían el
        // de la primera fila de la tabla y los meses quedaban en 0).
        $this->assertEquals(1, $n1);
        $this->assertEquals(1, $n2);

        $n3 = SecuenciaCaja::obtenerIngreso(2026, 8)->incrementar($this->admin->id);
        $this->assertEquals(2, $n3);

        // La fila de septiembre NO avanzó
        $this->assertEquals(1, $sSeptiembre->fresh()->ultimo_numero);
    }
}
