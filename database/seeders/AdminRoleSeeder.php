<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

/**
 * Seeder para configurar el rol admin y todos los permisos del sistema
 *
 * Crea:
 * - Rol "admin" con todos los permisos asignados
 * - Usuario administrador inicial
 * - Permisos por módulo con wildcards
 *
 * @package Database\Seeders
 */
class AdminRoleSeeder extends Seeder
{
    /**
     * Ejecuta el seeder para crear roles, permisos y usuario admin
     *
     * @return void
     */
    public function run(): void
    {
        // Limpiar cache de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Crear rol admin
        $adminRole = Role::create([
            'name' => 'admin',
            'guard_name' => 'web'
        ]);

        $this->command->info('✅ Rol "admin" creado exitosamente');

        // 2. Crear permisos por módulo (con wildcards)
        $permisos = [
            // Módulo de Expensas
            'expensas.*',
            'expensas.ver',
            'expensas.crear',
            'expensas.editar',
            'expensas.eliminar',
            'expensas.calcular',
            'expensas.recalcular',

            // Módulo de Propiedades
            'propiedades.*',
            'propiedades.ver',
            'propiedades.crear',
            'propiedades.editar',
            'propiedades.eliminar',
            'propiedades.asignar',

            // Módulo de Medidores
            'medidores.*',
            'medidores.ver',
            'medidores.crear',
            'medidores.editar',
            'medidores.eliminar',
            'medidores.registrar_lectura',

            // Módulo de Boletas
            'boletas.*',
            'boletas.ver',
            'boletas.generar',
            'boletas.descargar',
            'boletas.enviar',

            // Módulo de Pagos
            'pagos.*',
            'pagos.ver',
            'pagos.registrar',
            'pagos.verificar',
            'pagos.rechazar',

            // Módulo de Reportes
            'reportes.*',
            'reportes.ver',
            'reportes.exportar',
            'reportes.generar',

            // Módulo de Períodos
            'periodos.*',
            'periodos.ver',
            'periodos.crear',
            'periodos.cerrar',
            'periodos.reabrir',

            // Módulo de Configuración
            'configuracion.*',
            'configuracion.ver',
            'configuracion.editar',
            'configuracion.actualizar',

            // Módulo de Usuarios
            'usuarios.*',
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.eliminar',
        ];

        $permisosCreados = [];
        foreach ($permisos as $permiso) {
            $permisoModel = Permission::firstOrCreate([
                'name' => $permiso,
                'guard_name' => 'web'
            ]);
            $permisosCreados[] = $permisoModel->name;
        }

        $this->command->info('✅ ' . count($permisosCreados) . ' permisos creados');

        // 3. Asignar todos los permisos al rol admin
        $adminRole->givePermissionTo($permisosCreados);

        $this->command->info('✅ Todos los permisos asignados al rol "admin"');

        // 4. Crear usuario administrador inicial
        $admin = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrador',
                'email' => 'admin@admin.com',
                'password' => bcrypt('admin123'), // ⚠️ CAMBIAR EN PRODUCCIÓN
                'email_verified_at' => now(),
            ]
        );

        // Asignar rol admin al usuario
        $admin->assignRole('admin');

        $this->command->newLine(1);
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('🎉 USUARIO ADMINISTRADOR CREADO EXITOSAMENTE');
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->newLine(1);
        $this->command->info('📧 Email:    admin@admin.com');
        $this->command->info('🔑 Password: admin123');
        $this->command->warn('⚠️  IMPORTANTE: Cambiar el password en producción');
        $this->command->newLine(1);
    }
}
