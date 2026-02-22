<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

/*
|--------------------------------------------------------------------------
| RUTAS DE AUTENTICACIÓN (Breeze)
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| DASHBOARD
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'admin'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (SOLO ADMIN)
|--------------------------------------------------------------------------
| Todas las rutas administrativas requieren:
| 1. Usuario autenticado (auth)
| 2. Usuario con rol "admin" (admin)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PERFIL DE USUARIO
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | MÓDULO DE PROPIEDADES (FASE 1.2)
    |--------------------------------------------------------------------------
    | Rutas para gestión de unidades y propietarios
    |--------------------------------------------------------------------------
    */

    // Rutas de Unidades
    Route::prefix('unidades')->name('unidades.')->group(function () {
        Route::get('/', \App\Livewire\Propiedades\UnidadList::class)->name('index');
        Route::get('/crear', \App\Livewire\Propiedades\UnidadForm::class)->name('create');
        Route::get('/{unidad}/editar', \App\Livewire\Propiedades\UnidadForm::class)->name('edit');
    });

    // Rutas de Propietarios
    Route::prefix('propietarios')->name('propietarios.')->group(function () {
        Route::get('/', \App\Livewire\Propietarios\PropietarioList::class)->name('index');
        Route::get('/crear', \App\Livewire\Propietarios\PropietarioForm::class)->name('create');
        Route::get('/{propietario}/editar', \App\Livewire\Propietarios\PropietarioForm::class)->name('edit');
    });

    // Rutas de Asignación
    Route::get('/asignar-propietario', \App\Livewire\Propiedades\AsignarPropietario::class)->name('asignar-propietario');

    /*
    |--------------------------------------------------------------------------
    | MÓDULO DE CONFIGURACIÓN (FASE 1.5)
    |--------------------------------------------------------------------------
    */
    Route::prefix('configuracion')->name('configuracion.')->group(function () {
        Route::get('/factores', function () {
            return view('configuracion.factores.index'); // Placeholder
        })->name('factores.index');

        Route::get('/facturas-agua', function () {
            return view('configuracion.facturas-agua.index'); // Placeholder
        })->name('facturas-agua.index');

        Route::get('/grupos-medidores', function () {
            return view('configuracion.grupos-medidores.index'); // Placeholder
        })->name('grupos-medidores.index');
    });

    /*
    |--------------------------------------------------------------------------
    | MÓDULO DE EXPENSAS (FASE 3)
    |--------------------------------------------------------------------------
    */
    Route::prefix('expensas')->name('expensas.')->group(function () {
        // Placeholder para futuras rutas
    });

    /*
    |--------------------------------------------------------------------------
    | MÓDULO DE MEDIDORES (FASE 2)
    |--------------------------------------------------------------------------
    */
    Route::prefix('medidores')->name('medidores.')->group(function () {
        // Placeholder para futuras rutas
    });

    /*
    |--------------------------------------------------------------------------
    | MÓDULO DE PAGOS (FASE 5)
    |--------------------------------------------------------------------------
    */
    Route::prefix('pagos')->name('pagos.')->group(function () {
        // Placeholder para futuras rutas
    });

    /*
    |--------------------------------------------------------------------------
    | MÓDULO DE REPORTES (FASE 6)
    |--------------------------------------------------------------------------
    */
    Route::prefix('reportes')->name('reportes.')->group(function () {
        // Placeholder para futuras rutas
    });

    /*
    |--------------------------------------------------------------------------
    | MÓDULO DE USUARIOS
    |--------------------------------------------------------------------------
    */
    Route::prefix('usuarios')->name('usuarios.')->group(function () {
        Route::view('/', 'usuarios.index')->name('index');
    });

    /*
    |--------------------------------------------------------------------------
    | ACTIVITY LOGS (AUDITORÍA)
    |--------------------------------------------------------------------------
    */
    Route::prefix('auditoria')->name('auditoria.')->group(function () {
        Route::view('/', 'auditoria.index')->name('index');
    });
});
