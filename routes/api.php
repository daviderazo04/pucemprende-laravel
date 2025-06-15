<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\AfiliacionController;
use App\Http\Controllers\OrganizacionController;
use App\Http\Controllers\EquipoController;                  
use App\Http\Controllers\DocHabilitanteController;
use App\Http\Controllers\SedeController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\EventoDocHabilitanteController;
use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\MiembrosProyectoController;
use App\Http\Controllers\MiembrosEquipoController;
use App\Http\Controllers\RolesProyectoController;


// Ruta protegida para obtener usuario logueado
Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// Requiere auth y firma para verificar el email
Route::middleware(['auth:sanctum'])->group(function () {
    //DESCOMENTAR AL TENER FRONT SADKLFJSAOÑIFJHWIOFHNSADKNFÑSADILJFLASDM!"#$#%"
    // Ruta a la que el usuario es redirigido al hacer clic en el enlace del email
    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
        

    // Ruta para reenviar el email de verificación
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // Ejemplo de ruta protegida por verificación de email
    Route::get('/dashboard', function () {
        return response()->json(['message' => 'Acceso permitido porque estás verificado']);
    })->middleware('verified');
    
    // Rutas para el CRUD de eventos
    // La lógica de protección por rol para 'store' está en el constructor de EventoController
    Route::apiResource('eventos', EventoController::class);

    // Rutas para cronogramas
    Route::apiResource('cronogramas', CronogramaController::class);

    Route::apiResource('actividades-cronograma', ActividadesCronogramaController::class);

    // Rutas - Documentos habilitates
    Route::apiResource('doc-habilitantes', DocHabilitanteController::class);

    // Rutas - Sede
    Route::apiResource('sede', SedeController::class);

    // Rutas - Categoría de evento
    Route::apiResource('categoria', CategoriaController::class);

    // Rutas - Proyectos
    Route::apiResource('proyecto', ProyectoController::class);

    // Rutas - Miembros de proyecto
    Route::apiResource('miembros-proyecto', MiembrosProyectoController::class);

    // Rutas - Roles Proyecto
    Route::apiResource('roles-proyecto', RolesProyectoController::class);

    // Rutas - Archivos
    Route::apiResource('archivos', ArchivoController::class);

    // Rutas - Archivos de Proyecto
    Route::apiResource('archivos-proyecto', ArchivoProyectoController::class);

    // Rutas - Archivos de Evento
    Route::apiResource('archivos-evento', ArchivoEventoController::class);

    // Rutas - Organizaciones
    Route::apiResource('organizaciones', OrganizacionController::class);

    // Rutas - Afiliaciones
    Route::apiResource('afiliaciones', AfiliacionController::class);

    // Rutas - Equipos
    Route::apiResource('equipos', EquipoController::class);

    //Rutas - Miembros de equipo
    Route::apiResource('miembros-equipo', MiembrosEquipoController::class);

    //Rutas - Equipos Ganadores
    Route::apiResource('equipos-ganadores', EquiposGanadoreController::class);

    //Tabla interseccion de evento-dochabilitante
    Route::get('evento-dochabilitante/{evento_id}/{dochab_id}', [EventoDocHabilitanteController::class, 'show']);
    Route::put('evento-dochabilitante/{evento_id}/{dochab_id}', [EventoDocHabilitanteController::class, 'update']);
    Route::delete('evento-dochabilitante/{evento_id}/{dochab_id}', [EventoDocHabilitanteController::class, 'destroy']);
    Route::get('evento-dochabilitante', [EventoDocHabilitanteController::class, 'index']);
    Route::post('evento-dochabilitante', [EventoDocHabilitanteController::class, 'store']);
    Route::get('cronogramas/{cronograma}/actividades', [ActividadesCronogramaController::class, 'index']);

});



// Incluye las rutas de autenticación como /api/register, /api/login, etc.
require __DIR__.'/auth.php';
