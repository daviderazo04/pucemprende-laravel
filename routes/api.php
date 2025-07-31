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
use App\Http\Controllers\CronogramaController;
use App\Http\Controllers\ActividadesCronogramaController;
use App\Http\Controllers\ArchivoController;
use App\Http\Controllers\ArchivoEventoController;
use App\Http\Controllers\ArchivoProyectoController;
use App\Http\Controllers\EquiposGanadoreController;
use App\Http\Controllers\EquipoProyectoController;
use App\Http\Controllers\EventoRolPersonaController;
use App\Http\Controllers\RolEventoController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\CertificadosController;
use App\Http\Controllers\ResultadoRubricaController;
use App\Http\Controllers\ResultadoEvaluacionController;

//Para mandar correos a organizaciones
use App\Http\Controllers\OrganizacionMailController;

//Para generar QR de proyectos
use App\Http\Controllers\ProyectoQrController;

// Rutas públicas (sin autenticación)
Route::get('/eventos/ultimos', [EventoController::class, 'getUltimosEventos']);
Route::get('/eventos/proximos', [EventoController::class, 'getProximosEventos']);

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
    // Ruta específica para eventos paginados (DEBE IR PRIMERO)
    Route::get('/eventos/limit-offset', [EventoController::class, 'getPaginatedEvents']);
    Route::apiResource('eventos', EventoController::class);

    Route::get('/eventos-cronogramas/{id}', [App\Http\Controllers\EventoController::class, 'obtenerConDetallesCompleto']);

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
    Route::get('proyecto/proyectosConEventos', [ProyectoController::class, 'ProyectosConEventos']);
    Route::get('proyecto/proyectosPorEvento/{evento_id}', [ProyectoController::class, 'ProyectosPorEvento']);
    Route::apiResource('proyecto', ProyectoController::class);

    // Rutas - Miembros de proyecto
    Route::apiResource('miembros-proyecto', MiembrosProyectoController::class);

    // Rutas - Roles Proyecto
    Route::apiResource('roles-proyecto', RolesProyectoController::class);

    // Rutas - Archivos (post para un file especifico, solo acepta files via blob, en este caso imagenes) (multipart/form-data)
    Route::post('/archivos/upload', [ArchivoController::class, 'storeFile']);

    // Rutas - Proyectos (post para un file especifico, solo acepta files via blob, en este caso imagenes) (multipart/form-data)
    Route::post('/archivos/proyecto/upload', [ArchivoController::class, 'storeFileProyecto']);


    // Rutas - Archivos (standard JSON para Archivo (Creando con una URL))
    Route::apiResource('archivos', ArchivoController::class);

    // Rutas - Archivos de Proyecto
    Route::get('archivos-proyecto/proyecto/{proyecto_id}', [ArchivoProyectoController::class, 'getByProyectoURL']);
    Route::apiResource('archivos-proyecto', ArchivoProyectoController::class);


    // Rutas - Roles generales del sistema
    Route::apiResource('rol', RolesController::class);

    // Rutas - Archivos de Evento
    Route::get('archivos-evento/evento/{evento_id}', [ArchivoEventoController::class, 'getByEventoURL']);
    Route::apiResource('archivos-evento', ArchivoEventoController::class);

    // Rutas - Organizaciones
    Route::get('vw-organizaciones', [OrganizacionController::class, 'vwOrganizaciones']); // Vista de organizaciones con su detalle
    Route::apiResource('organizaciones', OrganizacionController::class);

    // Rutas - Afiliaciones
    Route::apiResource('afiliaciones', AfiliacionController::class);

    // Rutas - Equipos
    Route::apiResource('equipos', EquipoController::class);
    Route::get('equipos/evento/{eventoId}', [EquipoController::class, 'getEquiposByEventoId']);

    //Rutas - Miembros de equipo
    Route::apiResource('miembros-equipo', MiembrosEquipoController::class);

    //Rutas - Equipos Ganadores
    Route::apiResource('equipos-ganadores', EquiposGanadoreController::class);

    //Rutas - Evento Rol Persona
    Route::get('/evento-rol-persona/detalles', [EventoRolPersonaController::class, 'indexConDetalles']);
    Route::get('/evento-rol-persona/detalles/{id}', [EventoRolPersonaController::class, 'showConDetalles']);
    Route::delete('/evento-rol-persona/{eventoRolPersona}/borrar', [EventoRolPersonaController::class, 'destroy']);
    Route::patch('/evento-rol-persona/{eventoRolPersona}/activar', [EventoRolPersonaController::class, 'activar']);
    Route::apiResource('evento-rol-persona', EventoRolPersonaController::class);

    //Rutas - Roles de Evento
    Route::apiResource('rolEvento', RolEventoController::class);

    //Tabla interseccion de evento-dochabilitante
    Route::get('evento-dochabilitante/{evento_id}/{dochab_id}', [EventoDocHabilitanteController::class, 'show']);
    Route::put('evento-dochabilitante/{evento_id}/{dochab_id}', [EventoDocHabilitanteController::class, 'update']);
    Route::delete('evento-dochabilitante/{evento_id}/{dochab_id}', [EventoDocHabilitanteController::class, 'destroy']);
    Route::get('evento-dochabilitante', [EventoDocHabilitanteController::class, 'index']);
    Route::post('evento-dochabilitante', [EventoDocHabilitanteController::class, 'store']);
    Route::get('cronogramas/{cronograma}/actividades', [ActividadesCronogramaController::class, 'index']);

    // Rutas (sp) crear equipo y proyecto
    Route::post('equipo-proyecto', [EquipoProyectoController::class, 'store']);

    // Rutas para consumir los Stored Procedures de procesos de evaluación, rubricas, etc.
    Route::get('/procesos-evaluacion-detalle', [App\Http\Controllers\Api\ProcesosEvaluacionController::class, 'getProcesosEvaluacionDetalle']);
    Route::post('/plantillas-criterios', [App\Http\Controllers\Api\ProcesosEvaluacionController::class, 'storePlantillaYCriterios']);
    Route::put('/plantillas-criterios/{plantillaId}', [App\Http\Controllers\Api\ProcesosEvaluacionController::class, 'updatePlantillaYCriterios']);

    Route::apiResource('procesos-evaluacion', App\Http\Controllers\Api\ProcesosEvaluacionController::class);
    Route::apiResource('plantillas-evaluacion', App\Http\Controllers\Api\PlantillasEvaluacionController::class);
    Route::apiResource('criterios', App\Http\Controllers\Api\CriterioController::class);

    // Rutas para Resultados de Rubrica y Evaluación
    Route::apiResource('resultado-rubrica', App\Http\Controllers\ResultadoRubricaController::class);
    Route::get('/resultado-rubrica/equipo/{equipoId}', [App\Http\Controllers\ResultadoRubricaController::class, 'getByEquipo']);
    Route::get('/resultado-rubrica/plantilla/{plantillaId}', [App\Http\Controllers\ResultadoRubricaController::class, 'getByPlantilla']);
    Route::get('/resultado-rubrica/estadisticas/equipo/{equipoId}', [App\Http\Controllers\ResultadoRubricaController::class, 'getEstadisticasByEquipo']);
    Route::get('/resultado-rubrica/estadisticas/plantilla/{plantillaId}', [App\Http\Controllers\ResultadoRubricaController::class, 'getEstadisticasByPlantilla']);

    Route::apiResource('resultado-evaluacion', App\Http\Controllers\ResultadoEvaluacionController::class);
    Route::get('/resultado-evaluacion/equipo/{equipoId}', [App\Http\Controllers\ResultadoEvaluacionController::class, 'getByEquipo']);
    Route::get('/resultado-evaluacion/criterio/{criterioId}', [App\Http\Controllers\ResultadoEvaluacionController::class, 'getByCriterio']);
    Route::get('/resultado-evaluacion/evaluador/{evaluadorId}', [App\Http\Controllers\ResultadoEvaluacionController::class, 'getByEvaluador']);
    Route::get('/resultado-evaluacion/estadisticas/equipo/{equipoId}', [App\Http\Controllers\ResultadoEvaluacionController::class, 'getEstadisticasByEquipo']);
    Route::get('/resultado-evaluacion/vista/consolidada-equipos', [App\Http\Controllers\ResultadoEvaluacionController::class, 'getVistaConsolidadaEquipos']);
    Route::get('/resultado-evaluacion/vista/comparativa-equipos', [App\Http\Controllers\ResultadoEvaluacionController::class, 'getVistaComparativaEquipos']);
    Route::get('/resultado-evaluacion/eventos/con-evaluaciones', [App\Http\Controllers\ResultadoEvaluacionController::class, 'getEventosConEvaluaciones']);

    //Para mandar correos a organizaciones
    Route::post('/organizaciones/enviar-correo', [OrganizacionMailController::class, 'enviarCorreo']);

    //Para generar QR de proyectos
    Route::get('/proyectos/{id}/qr', [ProyectoQrController::class, 'generarQrProyecto']);

    // Rutas - Persona
    Route::get('/persona/cedula/{cedula}', [PersonaController::class, 'getCedula']);
    Route::get('/persona/user/{userId}', [PersonaController::class, 'getByUser']);
    Route::apiResource('persona', PersonaController::class);
    // Rutas - Usuario
    Route::get('/usuario/estadisticas/{id}', [UserController::class, 'getUserEstadisticas']);
    Route::apiResource('usuario', App\Http\Controllers\UserController::class);

    // Ruta certificados
    Route::get('certificados/{id}/generar', [CertificadosController::class, 'generar']);

});



// Incluye las rutas de autenticación como /api/register, /api/login, etc.
require __DIR__ . '/auth.php';
