<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * [PORTAL / Fase 1.2] Roles del portal de propietarios e inquilinos.
 *
 * Convención única de permisos (Plan 09 Fase 4.1):
 * - El rol "admin" conserva el acceso total (verificado por hasRole en
 *   policies/middleware, NO por permisos granulares).
 * - Los roles del portal NO reciben NINGÚN permiso Spatie: su acceso se
 *   restringe por middleware (CheckPortal) y por scoping de dueño en las
 *   policies del portal. Así no hay riesgo de escalada por wildcards.
 */
class PortalRolesSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['propietario', 'inquilino'] as $rol) {
            Role::firstOrCreate(
                ['name' => $rol, 'guard_name' => 'web']
            );
            $this->command->info("✅ Rol \"{$rol}\" asegurado");
        }
    }
}
