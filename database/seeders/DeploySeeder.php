<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * [DEPLOY 2026-10-15] Seeders que corren en CADA arranque del contenedor
 * (docker/entrypoint.sh) sobre bases de datos YA POBLADAS.
 *
 * Propósito: que un deploy aplique automáticamente roles/datos base nuevos
 * (ej. los roles del portal) sin tener que correr seeders a mano por SSH.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * REGLA OBLIGATORIA para agregar un seeder aquí:
 *   1. Debe ser 100% IDEMPOTENTE: re-ejecutarlo N veces no duplica filas ni
 *      sobrescribe datos (usar firstOrCreate/updateOrCreate, JAMÁS create()
 *      directo sobre tablas con unique).
 *   2. NO debe resetear passwords ni pisar datos de producción.
 *   3. Probarlo corriendo `php artisan db:seed --class=DeploySeeder --force`
 *      DOS veces en local antes de pushear.
 * Los seeders NO idempotentes (AdminRoleSeeder usa Role::create) viven solo
 * en DatabaseSeeder para instalaciones frescas (BD vacía).
 */
class DeploySeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PortalRolesSeeder::class,
        ]);
    }
}
