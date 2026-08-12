<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropietarioExportController;
use App\Http\Controllers\ReciboPDFController;
use App\Http\Controllers\EgresoPDFController;
use App\Http\Controllers\BoletaExpensaPDFController;
use App\Http\Controllers\ExpensaUnidadPDFController;
use App\Http\Controllers\ReporteExcelController;
use App\Http\Controllers\UnidadExportController;
use App\Http\Controllers\ComprobanteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
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
    | COMPROBANTES (FASE 1.2 - SEGURIDAD)
    |--------------------------------------------------------------------------
    | Rutas para servir comprobantes desde disco privado (storage/app/private).
    | Antes estaban en storage/app/public accesibles sin auth via /storage/*.
    | Throttle 60/min para evitar abuso. Auth+admin requerido.
    */
    Route::prefix('comprobante')->name('comprobante.')->middleware('throttle:60,1')->group(function () {
        Route::get('/ingreso/{ingreso}', [ComprobanteController::class, 'ingreso'])->name('ingreso');
        Route::get('/egreso/{egreso}', [ComprobanteController::class, 'egreso'])->name('egreso');
        Route::get('/pago/{ingreso}', [ComprobanteController::class, 'pago'])->name('pago');
    });

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
        Route::get('/exportar/prorrateo-excel', [UnidadExportController::class, 'prorrateo'])
            ->name('exportar.prorrateo');
    });

    // Rutas de Propietarios
    Route::prefix('propietarios')->name('propietarios.')->group(function () {
        Route::get('/', \App\Livewire\Propietarios\PropietarioList::class)->name('index');
        Route::get('/crear', \App\Livewire\Propietarios\PropietarioForm::class)->name('create');

        // Exportación a Excel - DEBE ir antes de /{propietario} para evitar conflicto de rutas
        Route::get('/exportar/unidades-excel', [PropietarioExportController::class, 'unidadesConInquilinos'])
            ->name('exportar.unidades');

        Route::get('/{propietario}', \App\Livewire\Propietarios\PropietarioShow::class)->name('show');
        Route::get('/{propietario}/editar', \App\Livewire\Propietarios\PropietarioEditConUnidades::class)->name('edit');
    });

    // Rutas de Inquilinos
    Route::prefix('inquilinos')->name('inquilinos.')->group(function () {
        Route::get('/', \App\Livewire\Inquilinos\InquilinoList::class)->name('index');
        Route::get('/crear', \App\Livewire\Inquilinos\InquilinoForm::class)->name('create');
        Route::get('/{inquilino}', \App\Livewire\Inquilinos\InquilinoShow::class)->name('show');
        Route::get('/{inquilino}/editar', \App\Livewire\Inquilinos\InquilinoForm::class)->name('edit');
        Route::get('/{inquilino}/asignar-unidades', \App\Livewire\Inquilinos\AsignarInquilino::class)->name('asignar-unidades');
    });

    // Rutas de Asignación
    Route::get('/asignar-propietario', \App\Livewire\Propiedades\AsignarPropietario::class)->name('asignar-propietario');
    Route::get('/asignar-inquilino', \App\Livewire\Inquilinos\AsignarInquilino::class)->name('asignar-inquilino');

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

        // Datos del Edificio (singleton: nombre, dirección, mensaje de PDFs, etc.)
        Route::get('/edificio', \App\Livewire\Configuracion\ConfiguracionEdificioForm::class)->name('edificio');

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

        // Boleta Manual - Generación manual de PDFs
        Route::get('/boleta-manual', \App\Livewire\Expensas\BoletaManual::class)->name('boleta.manual');

        // Detalle de expensa por unidad (período vía query string ?periodoId=)
        Route::get('/unidad/{unidad}', \App\Livewire\Expensas\ExpensaUnidadDetalle::class)->name('unidad.detalle');

        // Detalle de expensas por propietario (todas las unidades del propietario)
        Route::get('/propietario/{periodo}/{propietario}', \App\Livewire\Expensas\ExpensaPropietarioDetalle::class)->name('propietario.detalle');

        // Sistema de pagos de expensas (por unidad) - redirige a IngresoForm con categoría Expensas
        Route::get('/pagar/{unidad}', \App\Livewire\Caja\Ingresos\IngresoForm::class)->name('pagar.formulario');

        // Formulario de pago (ruta alternativa para compatibilidad) - redirige a IngresoForm con categoría Expensas
        Route::get('/pago/{unidad}', \App\Livewire\Caja\Ingresos\IngresoForm::class)->name('pago');

        // Detalle de expensa individual (redirige a la vista por unidad)
        Route::get('/{expensa}', function($expensa) {
            $expensa = \App\Models\Expensa::findOrFail($expensa);
            return redirect()->route('expensas.unidad.detalle', [
                'unidad' => $expensa->unidad_id,
                'periodoId' => $expensa->periodo_id,
            ]);
        })->name('detalle');

        // PDF de boleta de expensa (por período, tipo y responsable)
        // {tipo} = 'propietario' | 'inquilino'
        Route::get('/pdf/{periodo}/{tipo}/{responsable}', [BoletaExpensaPDFController::class, 'mostrar'])
            ->where('tipo', 'propietario|inquilino')
            ->name('pdf.mostrar');
        Route::get('/pdf/{periodo}/{tipo}/{responsable}/descargar', [BoletaExpensaPDFController::class, 'descargar'])
            ->where('tipo', 'propietario|inquilino')
            ->name('pdf.descargar');

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

        // Editar lectura individual
        Route::get('/individual/{lectura}/editar', \App\Livewire\Periodos\EditarLecturaIndividual::class)->name('individual.edit');

        // Editar lectura compartida (grupo)
        Route::get('/compartida/{lecturaGrupo}/editar', \App\Livewire\Periodos\EditarLecturaCompartida::class)->name('compartida.edit');
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
            Route::get('/{ingreso}', \App\Livewire\Caja\Ingresos\IngresoShow::class)->name('show');
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
    | [SEGURIDAD-09 / Fase 1.4] Deshabilitado: Route::view tiraba 500 porque
    | no existe la vista 'usuarios.index'. Pendiente modulo real (Fase 4.2).
    */
    // Route::prefix('usuarios')->name('usuarios.')->group(function () {
    //     Route::view('/', 'usuarios.index')->name('index');
    // });

    /*
    |--------------------------------------------------------------------------
    | ACTIVITY LOGS (AUDITORÍA)
    |--------------------------------------------------------------------------
    | [SEGURIDAD-09 / Fase 1.4] Deshabilitado: Route::view tiraba 500 porque
    | no existe la vista 'auditoria.index'. Pendiente modulo real (Fase 4.5).
    */
    // Route::prefix('auditoria')->name('auditoria.')->group(function () {
    //     Route::view('/', 'auditoria.index')->name('index');
    // });
});
