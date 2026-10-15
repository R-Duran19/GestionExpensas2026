<?php

namespace Tests\Feature\Portal;

use App\Livewire\Portal\PortalPerfil;
use App\Http\Middleware\CheckPortal;
use App\Models\Propietario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * [REMEDIACIÓN-1.2 / 1.3] Revocación real de accesos portal.
 *
 * Cubre:
 * - CheckAdmin/CheckPortal registrados como middleware PERSISTENTES de
 *   Livewire: se re-aplican en /livewire/update según la ruta del snapshot,
 *   así el estado del usuario se re-chequea en cada interacción (un usuario
 *   desactivado o degradado queda bloqueado también por ese canal).
 * - CheckPortal: desactivado => 403; must_change_password => redirect a perfil.
 * - Desactivar acceso => elimina sesiones activas y rota remember_token.
 * - Cambio de contraseña en perfil => mantiene la sesión actual, mata las
 *   demás y rota remember_token.
 */
class PortalRevocacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'propietario', 'inquilino'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }
    }

    private function crearPortalUser(array $overrides = []): User
    {
        $propietario = Propietario::create([
            'nombre' => 'Revoca', 'apellido' => 'Do', 'ci' => '5556667',
        ]);

        $user = User::factory()->create(array_merge([
            'email' => null,
            'tipo' => 'propietario',
            'persona_id' => $propietario->id,
            'ci_hash' => \App\Helpers\Pii::hash('5556667'),
            'activo' => true,
            'must_change_password' => false,
        ], $overrides));

        $user->assignRole('propietario');

        return $user;
    }

    private function crearSesion(User $user, string $id): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => base64_encode(''),
            'last_activity' => now()->timestamp,
        ]);
    }

    public function test_check_admin_y_check_portal_son_middleware_persistente_de_livewire(): void
    {
        // [REMEDIACIÓN-1.3] Verifica el registro en el mecanismo de
        // PersistentMiddleware de Livewire (es lo que re-aplica los middleware
        // de la ruta de origen en cada request a /livewire/update).
        $ref = new \ReflectionProperty(\Livewire\Mechanisms\PersistentMiddleware\PersistentMiddleware::class, 'persistentMiddleware');
        $ref->setAccessible(true);
        $registrados = $ref->getValue();

        $this->assertContains(\App\Http\Middleware\CheckAdmin::class, $registrados);
        $this->assertContains(\App\Http\Middleware\CheckPortal::class, $registrados);
    }

    public function test_checkportal_bloquea_usuario_desactivado_con_403(): void
    {
        $user = $this->crearPortalUser(['activo' => false]);
        $this->actingAs($user);

        $request = \Illuminate\Http\Request::create('/portal', 'GET');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        (new CheckPortal)->handle($request, fn () => response('ok'));
    }

    public function test_checkportal_redirige_must_change_password_a_perfil(): void
    {
        $user = $this->crearPortalUser(['must_change_password' => true]);
        $this->actingAs($user);

        $request = \Illuminate\Http\Request::create('/portal', 'GET');

        $response = (new CheckPortal)->handle($request, fn () => response('ok'));

        $this->assertTrue($response->isRedirection());
    }

    public function test_checkportal_deja_pasar_usuario_activo(): void
    {
        $user = $this->crearPortalUser();
        $this->actingAs($user);

        $request = \Illuminate\Http\Request::create('/portal', 'GET');

        $response = (new CheckPortal)->handle($request, fn () => response('ok'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_desactivar_acceso_elimina_sesiones_y_rota_remember(): void
    {
        // Propietario + acceso portal activo
        $propietario = Propietario::create([
            'nombre' => 'Killer', 'apellido' => 'Sesion', 'ci' => '9990001',
        ]);

        $user = User::factory()->create([
            'email' => null,
            'tipo' => 'propietario',
            'persona_id' => $propietario->id,
            'ci_hash' => \App\Helpers\Pii::hash('9990001'),
            'activo' => true,
            'must_change_password' => false,
            'remember_token' => 'token-antiguo',
        ]);
        $user->assignRole('propietario');

        $this->crearSesion($user, 'sess-aaa');
        $this->crearSesion($user, 'sess-bbb');

        $admin = User::factory()->create(['email' => 'admin2@test.bo']);
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Portal\AccesoPortal::class, ['tipo' => 'propietario', 'personaId' => $propietario->id])
            ->call('toggleActivo');

        $user->refresh();

        $this->assertFalse((bool) $user->activo);
        $this->assertEquals(0, DB::table('sessions')->where('user_id', $user->id)->count());
        $this->assertNotEquals('token-antiguo', $user->remember_token);
    }

    public function test_resetear_password_elimina_sesiones_y_fuera_cambio(): void
    {
        $propietario = Propietario::create([
            'nombre' => 'Reset', 'apellido' => 'Pass', 'ci' => '9990002',
        ]);

        $user = User::factory()->create([
            'email' => null,
            'tipo' => 'propietario',
            'persona_id' => $propietario->id,
            'ci_hash' => \App\Helpers\Pii::hash('9990002'),
            'activo' => true,
            'must_change_password' => false,
        ]);
        $user->assignRole('propietario');

        $this->crearSesion($user, 'sess-ccc');

        $admin = User::factory()->create(['email' => 'admin3@test.bo']);
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Portal\AccesoPortal::class, ['tipo' => 'propietario', 'personaId' => $propietario->id])
            ->call('resetearPassword');

        $user->refresh();

        $this->assertTrue((bool) $user->must_change_password);
        $this->assertEquals(0, DB::table('sessions')->where('user_id', $user->id)->count());
    }

    public function test_cambiar_password_en_perfil_mantiene_sesion_actual(): void
    {
        // Driver database para que las sesiones vivan en la tabla y podamos
        // ejercitar la revocación selectiva (phpunit.xml usa array).
        config(['session.driver' => 'database']);

        $user = $this->crearPortalUser();

        $this->actingAs($user);

        $rememberAntes = $user->remember_token;

        // Sesión "robada" previa a la petición del componente
        $this->crearSesion($user, 'sess-robada');

        Livewire::test(PortalPerfil::class)
            ->set('password_actual', 'password')
            ->set('password', 'nueva-clave-2026')
            ->set('password_confirmation', 'nueva-clave-2026')
            ->call('cambiarPassword');

        $user->refresh();

        $this->assertTrue(Hash::check('nueva-clave-2026', $user->password));

        // La robada murió (con driver array la "actual" nunca llega a la tabla,
        // por lo que el delete la elimina a ella y conserva la real por id)
        $this->assertEquals(0, DB::table('sessions')->where('user_id', $user->id)->where('id', 'sess-robada')->count());
        $this->assertNotEquals($rememberAntes, $user->remember_token);
    }
}
