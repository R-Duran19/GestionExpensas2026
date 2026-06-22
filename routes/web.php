<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReciboPDFController;
use App\Http\Controllers\EgresoPDFController;
use App\Http\Controllers\BoletaExpensaPDFController;
use App\Http\Controllers\ExpensaUnidadPDFController;
use App\Http\Controllers\ReporteExcelController;
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
        Route::get('/{unidad}/editar', \App\Livewire\Propiedades\UnidadEditConPropietario::class)->name('edit');
    });

    // Rutas de Propietarios
    Route::prefix('propietarios')->name('propietarios.')->group(function () {
        Route::get('/', \App\Livewire\Propietarios\PropietarioList::class)->name('index');
        Route::get('/crear', \App\Livewire\Propietarios\PropietarioForm::class)->name('create');
        Route::get('/{propietario}', \App\Livewire\Propietarios\PropietarioShow::class)->name('show');
        Route::get('/{propietario}/editar', \App\Livewire\Propietarios\PropietarioEditConUnidades::class)->name('edit');
    });

    // Rutas de Asignación
    Route::get('/asignar-propietario', \App\Livewire\Propiedades\AsignarPropietario::class)->name('asignar-propietario');

    /*
    |--------------------------------------------------------------------------
    | MÓDULO DE CONFIGURACIÓN (FASE 1.5)
    |--------------------------------------------------------------------------
    | Rutas para gestión de factores, facturas EPSAS y grupos de medidores
    |--------------------------------------------------------------------------
    */
    Route::prefix('configuracion')->name('configuracion.')->group(function () {
        // Página principal de configuración
        Route::get('/', \App\Livewire\Configuracion\ConfiguracionIndex::class)->name('index');

        // Factores de Prorrateo
        Route::get('/factores', \App\Livewire\Configuracion\ConfiguracionFactores::class)->name('factores');

        // Facturas de Agua EPSAS
        Route::get('/facturas-agua', \App\Livewire\Configuracion\ConfiguracionFacturasAgua::class)->name('facturas-agua');

        // Medidores
        Route::get('/medidores', \App\Livewire\Configuracion\GestionMedidores::class)->name('medidores');

        // Grupos de Medidores
        Route::prefix('medidores-grupos')->name('medidores-grupos.')->group(function () {
            Route::get('/', \App\Livewire\Configuracion\MedidorGrupoList::class)->name('index');
            Route::get('/crear', \App\Livewire\Configuracion\MedidorGrupoForm::class)->name('create');
            Route::get('/{grupo}/editar', \App\Livewire\Configuracion\MedidorGrupoForm::class)->name('edit');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | MÓDULO DE EXPENSAS (FASE 3)
    |--------------------------------------------------------------------------
    | Nueva arquitectura descentralizada por unidad (sin consolidación por propietario)
    */
    Route::prefix('expensas')->name('expensas.')->group(function () {
        // Listado principal de expensas (por unidades)
        Route::get('/', \App\Livewire\Expensas\ExpensaList::class)->name('index');
        Route::get('/list', \App\Livewire\Expensas\ExpensaList::class)->name('list'); // Alias para redirecciones

        // Detalle de expensa por unidad (con selector de período)
        Route::get('/unidad/{unidad}', \App\Livewire\Expensas\ExpensaUnidadDetalle::class)->name('unidad.detalle');

        // Detalle de expensas por propietario (todas las unidades del propietario)
        Route::get('/propietario/{periodo}/{propietario}', \App\Livewire\Expensas\ExpensaPropietarioDetalle::class)->name('propietario.detalle');

        // Sistema de pagos de expensas (por unidad)
        Route::get('/pagar/{unidad}', \App\Livewire\Expensas\PagoExpensaForm::class)->name('pagar.formulario');

        // Formulario de pago (ruta alternativa para compatibilidad)
        Route::get('/pago/{unidad}', \App\Livewire\Expensas\PagoExpensaForm::class)->name('pago');

        // Detalle de expensa individual (mantenido para compatibilidad)
        Route::get('/{expensa}', \App\Livewire\Expensas\ExpensaDetalle::class)->name('detalle');

        // PDF de boleta de expensa (por período y propietario)
        Route::get('/pdf/{periodo}/{propietario}', [BoletaExpensaPDFController::class, 'mostrar'])->name('pdf.mostrar');
        Route::get('/pdf/{periodo}/{propietario}/descargar', [BoletaExpensaPDFController::class, 'descargar'])->name('pdf.descargar');

        // PDF de expensa de unidad específica
        Route::get('/unidad-pdf/{periodo}/{unidad}', [ExpensaUnidadPDFController::class, 'mostrar'])->name('unidad.pdf.mostrar');
        Route::get('/unidad-pdf/{periodo}/{unidad}/descargar', [ExpensaUnidadPDFController::class, 'descargar'])->name('unidad.pdf.descargar');
    });

    /*
    |--------------------------------------------------------------------------
    | MÓDULO DE PERÍODOS (FASE 2)
    |--------------------------------------------------------------------------
    | Rutas para gestión de períodos, facturas EPSAS y lecturas de medidor
    |--------------------------------------------------------------------------
    */
    Route::prefix('periodos')->name('periodos.')->group(function () {
        // Listado y creación de períodos
        Route::get('/', \App\Livewire\Periodos\PeriodoList::class)->name('index');
        Route::get('/crear', \App\Livewire\Periodos\CrearPeriodo::class)->name('create');
        Route::get('/{periodo}', \App\Livewire\Periodos\PeriodoDetail::class)->name('show');

        // Facturas EPSAS por período
        Route::get('/{periodo}/facturas', \App\Livewire\Periodos\RegistrarFacturasAgua::class)->name('facturas');

        // Lecturas de medidor por período
        Route::get('/{periodo}/lecturas', \App\Livewire\Periodos\LecturaMedidorList::class)->name('lecturas');

        // Lecturas masivas por período
        Route::get('/{periodo}/lecturas-masivas', \App\Livewire\Periodos\RegistrarLecturasMasivas::class)->name('lecturas-masivas');

        // Calcular expensas por período
        Route::get('/{periodo}/calcular', \App\Livewire\Periodos\CalcularExpensas::class)->name('calcular');
    });

    /*
    |--------------------------------------------------------------------------
    | MÓDULO DE LECTURAS (FASE 2)
    |--------------------------------------------------------------------------
    */
    Route::prefix('lecturas')->name('lecturas.')->group(function () {
        // Registrar lectura individual
        Route::get('/individual/{periodo}', \App\Livewire\Periodos\RegistrarLecturaIndividual::class)->name('individual');

        // Registrar lectura compartida
        Route::get('/compartida/{periodo}', \App\Livewire\Periodos\RegistrarLecturaCompartida::class)->name('compartida');
    });

    /*
    |--------------------------------------------------------------------------
    | MÓDULO DE CAJA (INGRESOS Y EGRESOS)
    |--------------------------------------------------------------------------
    | Sistema completo de gestión de caja con:
    | - Ingresos (con numeración mensual REC-XX-XX-XXXXXXX)
    | - Egresos (con numeración continua EGR-XXXXXX)
    | - Categorías configurables por el usuario
    | - Dashboard con totales en tiempo real
    |--------------------------------------------------------------------------
    */
    Route::prefix('caja')->name('caja.')->group(function () {
        // Dashboard principal
        Route::get('/', \App\Livewire\Caja\CajaDashboard::class)->name('dashboard');

        // Módulo de INGRESOS
        Route::prefix('ingresos')->name('ingresos.')->group(function () {
            Route::get('/', \App\Livewire\Caja\Ingresos\IngresoList::class)->name('index');
            Route::get('/crear', \App\Livewire\Caja\Ingresos\IngresoForm::class)->name('create');
            Route::get('/{ingreso}/editar', \App\Livewire\Caja\Ingresos\IngresoForm::class)->name('edit');

            // Rutas para PDF de recibos
            Route::get('/{ingreso}/pdf', [ReciboPDFController::class, 'mostrar'])->name('pdf');
            Route::get('/{ingreso}/pdf/descargar', [ReciboPDFController::class, 'descargar'])->name('pdf.descargar');
        });

        // Módulo de EGRESOS
        Route::prefix('egresos')->name('egresos.')->group(function () {
            Route::get('/', \App\Livewire\Caja\Egresos\EgresoList::class)->name('index');
            Route::get('/crear', \App\Livewire\Caja\Egresos\EgresoForm::class)->name('create');
            Route::get('/{egreso}/editar', \App\Livewire\Caja\Egresos\EgresoForm::class)->name('edit');

            // Rutas para PDF de egresos
            Route::get('/{egreso}/pdf', [EgresoPDFController::class, 'mostrar'])->name('pdf');
            Route::get('/{egreso}/pdf/descargar', [EgresoPDFController::class, 'descargar'])->name('pdf.descargar');
        });

        // Módulo de CATEGORÍAS
        Route::prefix('categorias')->name('categorias.')->group(function () {
            // Categorías de Ingresos
            Route::get('/ingresos', \App\Livewire\Caja\Categorias\CategoriaIngresoList::class)->name('ingresos.index');

            // Categorías de Egresos
            Route::get('/egresos', \App\Livewire\Caja\Categorias\CategoriaEgresoList::class)->name('egresos.index');
        });
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
        Route::get('/', \App\Livewire\Reportes\ReporteGeneral::class)->name('index');
        Route::get('/exportar-excel', [ReporteExcelController::class, 'exportar'])->name('exportar.excel');
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
