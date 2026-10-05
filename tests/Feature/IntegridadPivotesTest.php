<?php

namespace Tests\Feature;

use App\Livewire\Periodos\AjusteTemporalMontos;
use App\Models\Expensa;
use App\Models\PeriodoExpensa;
use App\Models\Propietario;
use App\Models\Unidad;
use App\Models\User;
use App\Services\UnidadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * [REMEDIACIÓN-2.4 / 2.8] Pivote de propiedad + herramienta ajuste temporal.
 *
 * Cubre:
 * - Reactivar a un propietario anterior CIERRA al dueño vigente (un solo
 *   es_propietario_actual=true por unidad).
 * - El índice único generado rechaza a nivel DB un doble dueño actual.
 * - ajuste-temporal OFF => guardarFila/guardarTodo abortan (404).
 * - aplicarAjuste rechaza expensas de otro período o de período cerrado.
 */
class IntegridadPivotesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'propietario'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }

        $this->admin = User::factory()->create(['email' => 'pivot@test.bo']);
        $this->admin->assignRole('admin');
        $this->actingAs($this->admin);
    }

    private function crearUnidad(): Unidad
    {
        $propietario = Propietario::create([
            'nombre' => 'Pivot', 'apellido' => 'Test ' . uniqid(), 'ci' => (string) random_int(1000000, 9999999),
        ]);

        $unidad = Unidad::create([
            'codigo' => 'PIV-' . substr(uniqid(), -4),
            'tipo_unidad' => 'departamento',
            'piso' => 1,
            'metros_cuadrados' => 50,
            'categoria_unidad' => 'domiciliaria',
            'tiene_medidor' => false,
            'activo' => true,
        ]);

        $unidad->propietarios()->attach($propietario->id, [
            'fecha_inicio' => now()->subYear(),
            'es_propietario_actual' => true,
        ]);

        return $unidad;
    }

    private function crearPropietario(): Propietario
    {
        return Propietario::create([
            'nombre' => 'Otro', 'apellido' => 'Dueno ' . uniqid(), 'ci' => (string) random_int(1000000, 9999999),
        ]);
    }

    public function test_reactivar_propietario_anterior_cierra_al_vigente(): void
    {
        $unidad = $this->crearUnidad();
        $servicio = app(UnidadService::class);

        $a = $unidad->propietarioActual->first();
        $b = $this->crearPropietario();

        // "Desvincular" a A: el componente cierra la fila conservando fecha_inicio
        DB::table('propietario_unidad')
            ->where('unidad_id', $unidad->id)
            ->update(['es_propietario_actual' => false, 'fecha_fin' => now()]);

        // B pasa a ser el actual
        $servicio->asignarPropietario($unidad, $b, '2026-02-01');

        // Reasignar A con su FECHA ORIGINAL: era el caso que dejaba dos actuales
        $servicio->asignarPropietario($unidad, $a, '2026-01-01');

        $actuales = DB::table('propietario_unidad')
            ->where('unidad_id', $unidad->id)
            ->where('es_propietario_actual', true)
            ->get();

        $this->assertCount(1, $actuales, 'Debe haber exactamente UN dueño actual');
        $this->assertEquals($a->id, $actuales[0]->propietario_id);
        $this->assertNull($actuales[0]->fecha_fin);
    }

    public function test_indice_unico_rechaza_doble_actual_a_nivel_db(): void
    {
        $unidad = $this->crearUnidad();
        $b = $this->crearPropietario();

        $this->expectException(\Illuminate\Database\QueryException::class);

        // Insert crudo saltándose los guards del servicio
        DB::table('propietario_unidad')->insert([
            'propietario_id' => $b->id,
            'unidad_id' => $unidad->id,
            'fecha_inicio' => '2026-05-01',
            'fecha_fin' => null,
            'es_propietario_actual' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_ajuste_temporal_off_bloquea_guardar_fila(): void
    {
        // Flag OFF (por defecto en tests: el archivo no existe)
        Storage::disk('local')->delete(\App\Support\AjusteTemporalFlag::ARCHIVO);

        $unidad = $this->crearUnidad();
        $periodo = PeriodoExpensa::create([
            'mes' => 9, 'año' => 2026,
            'fecha_apertura' => now(),
            'estado' => 'abierto',
        ]);

        $expensa = Expensa::create([
            'periodo_id' => $periodo->id,
            'unidad_id' => $unidad->id,
            'monto_expensa' => 100, 'monto_agua' => 0,
            'expensa_pendiente' => 100, 'agua_pendiente' => 0,
            'periodo_cerrado' => false, 'monto_total' => 100,
        ]);

        $this->withoutExceptionHandling();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        Livewire::test(AjusteTemporalMontos::class, ['periodo' => $periodo->id])
            ->call('guardarFila', $expensa->id);
    }

    public function test_ajuste_temporal_rechaza_expensa_de_otro_periodo(): void
    {
        // Flag ON
        \App\Support\AjusteTemporalFlag::activar();

        $unidad = $this->crearUnidad();

        $periodoA = PeriodoExpensa::create([
            'mes' => 9, 'año' => 2026, 'fecha_apertura' => now(), 'estado' => 'abierto',
        ]);
        $periodoCerrado = PeriodoExpensa::create([
            'mes' => 8, 'año' => 2026, 'fecha_apertura' => now()->subMonth(), 'estado' => 'cerrado',
        ]);

        $expensaCerrada = Expensa::create([
            'periodo_id' => $periodoCerrado->id,
            'unidad_id' => $unidad->id,
            'monto_expensa' => 200, 'monto_agua' => 0,
            'expensa_pendiente' => 200, 'agua_pendiente' => 0,
            'periodo_cerrado' => true, 'monto_total' => 200,
        ]);

        try {
            $this->withoutExceptionHandling();

            Livewire::test(AjusteTemporalMontos::class, ['periodo' => $periodoA->id])
                ->call('guardarFila', $expensaCerrada->id);
            $this->fail('Se esperaba rechazo por período cerrado');
        } catch (\Exception $e) {
            $this->assertStringContainsStringIgnoringCase('cerrado', $e->getMessage());
        }

        // La expensa del período cerrado NO fue tocada
        $this->assertEquals(200, (float) $expensaCerrada->fresh()->expensa_pendiente);
        $this->assertTrue((bool) $expensaCerrada->fresh()->periodo_cerrado);

        \App\Support\AjusteTemporalFlag::desactivar();
    }
}
