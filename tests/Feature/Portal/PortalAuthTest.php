<?php

namespace Tests\Feature\Portal;

use App\Helpers\Pii;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * [PORTAL / Fase 1] Tests de autenticación y aislamiento de roles.
 *
 * Cubre:
 * - Login por correo (admin) y por CI (portal) con el MISMO formulario.
 * - Cuenta desactivada → rechazo.
 * - Aislamiento: portal no entra al admin, admin no entra al portal.
 * - must_change_password fuerza la pantalla de perfil.
 */
class PortalAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'propietario', 'inquilino'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }
    }

    private function crearAdmin(): User
    {
        $user = User::factory()->create(['email' => 'admin@test.bo']);
        $user->assignRole('admin');

        return $user;
    }

    private function crearPortalUser(string $tipo = 'propietario', array $overrides = []): User
    {
        $ci = $overrides['ci'] ?? '1234567';
        unset($overrides['ci']);   // 'ci' NO es columna de users: solo define el ci_hash

        $user = User::factory()->create(array_merge([
            'email' => null,
            'tipo' => $tipo,
            'ci_hash' => Pii::hash($ci),
            'activo' => true,
            'must_change_password' => true,
        ], $overrides));

        $user->assignRole($tipo);

        return $user;
    }

    public function test_admin_ingresa_con_correo(): void
    {
        $this->crearAdmin();

        $response = $this->post('/login', [
            'email' => 'admin@test.bo',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();
    }

    public function test_portal_user_ingresa_con_ci(): void
    {
        $user = $this->crearPortalUser('propietario', ['ci' => '7654321']);

        $response = $this->post('/login', [
            'email' => '7654321',          // CI, no email
            'password' => 'password',
        ]);

        $response->assertRedirect(route('portal.dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->refresh()->last_login_at);
    }

    public function test_ci_incorrecto_rechaza(): void
    {
        $this->crearPortalUser('propietario', ['ci' => '7654321']);

        $this->post('/login', [
            'email' => '9999999',
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_cuenta_desactivada_no_ingresa(): void
    {
        $this->crearPortalUser('propietario', ['ci' => '7654321', 'activo' => false]);

        $this->post('/login', [
            'email' => '7654321',
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_portal_user_no_accede_al_admin(): void
    {
        $this->crearPortalUser('propietario');

        $this->actingAs(User::where('tipo', 'propietario')->first())
            ->get('/dashboard');

        $response = $this->get('/dashboard');

        $response->assertForbidden();
    }

    public function test_admin_no_accede_al_portal(): void
    {
        $this->crearAdmin();

        $this->actingAs(User::where('email', 'admin@test.bo')->first())
            ->get('/portal');

        $response = $this->get('/portal');

        $response->assertForbidden();
    }

    public function test_must_change_password_redirige_al_perfil(): void
    {
        $this->crearPortalUser('propietario', ['ci' => '7654321', 'must_change_password' => true]);

        $this->post('/login', [
            'email' => '7654321',
            'password' => 'password',
        ]);

        $response = $this->actingAs(User::where('ci_hash', Pii::hash('7654321'))->first())
            ->get('/portal');

        $response->assertRedirect(route('portal.perfil'));
    }

    public function test_portal_user_ve_su_dashboard(): void
    {
        $user = $this->crearPortalUser('propietario', ['ci' => '7654321', 'must_change_password' => false]);

        $response = $this->actingAs($user)->get('/portal');

        $response->assertOk();
    }
}
