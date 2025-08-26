<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\EventoRolPersona;
use App\Models\Persona;
use App\Models\Evento;
use App\Models\Equipo;
use App\Models\MiembrosProyecto;
use Illuminate\Support\Facades\DB;
use App\Models\Archivo; // Agregar el import del modelo Archivo
use App\Models\ArchivoProyecto;

class ProyectoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $proyecto = Proyecto::all();
        return response()->json($proyecto);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Solo permitir si el usuario está inscrito en un evento o si es dueño del evento o superadministrador
        $persona = Persona::where('users_id', $request->user()->id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        $equipo = Equipo::find($request->equipo_id);
        if (!$equipo) {
            return response()->json(['error' => 'Equipo no encontrado'], 404);
        }

        $evento = Evento::find($equipo->evento_id);
        if (!$evento) {
            return response()->json(['error' => 'Evento no encontrado para este equipo'], 404);
        }

        // Verificar si el usuario tiene rol_id = 1 (administrador del sistema)
        // O si la persona es el autor del evento (rol_id = 1 para este evento en evento_rol_persona)
        $isEventAuthor = EventoRolPersona::where('evento_id', $evento->id)
            ->where('persona_id', $persona->id)
            ->where('rol_id', 1)
            ->where('estado_borrado', false)
            ->exists();

        /*$isRegistered = EventoRolPersona::where('evento_id', $evento->id)
                                        ->where('persona_id', $persona->id)
                                        ->where('estado_borrado', false)
                                        ->exists();*/

        /*if (!$isEventAuthor) {
            return response()->json(['message' => 'No tienes permiso para crear un proyecto ya que no estás inscrito al evento.'], 403);
        }*/

        $validator = Validator::make($request->all(), [
            'equipo_id' => 'nullable|integer|exists:equipos,id',
            'titulo' => 'required|string|max:50',
            'descripcion' => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $proyecto = Proyecto::create([
            'creado_en' => Carbon::now(),
            'actualizado_en' => Carbon::now(),
            'equipo_id' => $request->equipo_id,
            'titulo' => $request->titulo,
            'descripcion' => $request->descripcion,
            'estado' => 'ACTIVO',
            'fecha_inicio' => $evento->fecha_inicio,
            'fecha_fin' => $evento->fecha_fin,
        ]);


        $miembrosProyecto = MiembrosProyecto::create([
            'creado_en' => Carbon::now(),
            'actualizado_en' => Carbon::now(),
            'rol_id' => 1, // Asignar rol de autor al creador del proyecto
            'proyecto_id' => $proyecto->id,
            'persona_id' => $persona->id,
        ]);

        return response()->json($proyecto, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        // Buscar proyecto por id
        $proyecto = Proyecto::find($id);

        if (!$proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado'], 404);
        }

        return response()->json($proyecto);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Proyecto $proyecto)
    {
        // Solo permitir si el usuario está inscrito en un evento o si es dueño del evento o superadministrador
        $persona = Persona::where('users_id', $request->user()->id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        $equipo = Equipo::find($request->equipo_id);
        if (!$equipo) {
            return response()->json(['error' => 'Equipo no encontrado'], 404);
        }

        $evento = Evento::find($equipo->evento_id);
        if (!$evento) {
            return response()->json(['error' => 'Evento no encontrado para este equipo'], 404);
        }

        // Verificar si el usuario tiene rol_id = 1 (administrador del sistema)
        // O si la persona es el autor del evento (rol_id = 1 para este evento en evento_rol_persona)
        $isSystemAdmin = ($request->user()->rol_id == 8);
        $isEventAuthor = EventoRolPersona::where('evento_id', $evento->id)
            ->where('persona_id', $persona->id)
            ->where('rol_id', 1)
            ->where('estado_borrado', false)
            ->exists();

        $isRegistered = EventoRolPersona::where('evento_id', $evento->id)
            ->where('persona_id', $persona->id)
            ->where('estado_borrado', false)
            ->exists();

        if (!$isSystemAdmin && !$isEventAuthor && !$isRegistered) {
            return response()->json(['message' => 'No tienes permiso para actualizar este evento.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'equipo_id' => 'nullable|integer|exists:equipos,id',
            'titulo' => 'required|string|max:50',
            'descripcion' => 'required|string|max:1000',
            'estado' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $proyecto->fill($request->only([
            'equipo_id',
            'titulo',
            'descripcion',
            'estado'
        ]));

        $proyecto->actualizado_en = Carbon::now();
        $proyecto->save();

        return response()->json($proyecto);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Proyecto $proyecto)
    {
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para eliminar proyectos.'], 403);
        }

        $persona = Persona::where('users_id', $request->user()->id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        $equipo = Equipo::find($proyecto->equipo_id);

        if (!$equipo) {
            return response()->json(['error' => 'Equipo no encontrado para este proyecto'], 404);
        }

        $evento = Evento::find($equipo->evento_id);

        if (!$evento) {
            return response()->json(['error' => 'Evento no encontrado para este equipo'], 404);
        }

        // Verificar si el usuario tiene rol_id = 1 (administrador del sistema)
        // O si la persona es el autor del evento (rol_id = 1 para este evento en evento_rol_persona)
        $isSystemAdmin = ($request->user()->rol_id == 8);
        $isEventAuthor = EventoRolPersona::where('evento_id', $evento->id)
            ->where('persona_id', $persona->id)
            ->where('rol_id', 1)
            ->where('estado_borrado', false)
            ->exists();

        if (!$isSystemAdmin && !$isEventAuthor) {
            return response()->json(['message' => 'No tienes permiso para actualizar este evento.'], 403);
        }

        $proyecto->estado = "BORRADO";
        $proyecto->actualizado_en = Carbon::now();
        $proyecto->save();

        return response()->json(['message' => 'Proyecto marcado como borrado lógicamente.'], 200);
    }

    public function ProyectosPorEvento(Request $request, $id)
    {
        // Buscar proyecto por id
        $evento = Evento::find($id);

        if (!$evento) {
            return response()->json(['message' => 'Evento no encontrado'], 404);
        }

        $equipos = Equipo::where('evento_id', $evento->id)->get();

        if (!$equipos) {
            return response()->json(['message' => 'Equipos no encontrados'], 404);
        }
        $proyectos = [];
        foreach ($equipos as $equipo) {
            $proyectos[] = Proyecto::where('equipo_id', $equipo->id)->get();
        }

        return response()->json($proyectos);
    }

    public function ProyectosConEventos(Request $request)
    {
        $proyectos = Proyecto::select(
            'eventos.id as evento_id',
            'proyectos.id as proyecto_id',
            'proyectos.creado_en',
            'proyectos.actualizado_en',
            'proyectos.equipo_id',
            'proyectos.titulo',
            'proyectos.descripcion',
            'proyectos.estado',
            'proyectos.fecha_inicio',
            'proyectos.fecha_fin'
        )
            ->join('equipos', 'proyectos.equipo_id', '=', 'equipos.id')
            ->join('eventos', 'equipos.evento_id', '=', 'eventos.id')
            ->get();

        return response()->json($proyectos);
    }

    /**
     * Obtener proyectos con información completa incluyendo equipo, evento y miembros
     */
    public function getProyectosCompletos(Request $request)
    {
        $proyectos = Proyecto::with([
            'equipo',
            'evento',
            'miembros.persona',
            'logo'
        ])
            ->where('estado', '!=', 'BORRADO')
            ->get()
            ->map(function ($proyecto) {
                $logoUrl = $proyecto->logo->first()?->url ?? null;

                return [
                    'id' => $proyecto->id,
                    'titulo' => $proyecto->titulo,
                    'descripcion' => $proyecto->descripcion,
                    'fecha_inicio' => $proyecto->fecha_inicio,
                    'fecha_fin' => $proyecto->fecha_fin,
                    'estado' => $proyecto->estado,
                    'equipo_id' => $proyecto->equipo_id,
                    'equipo_nombre' => $proyecto->equipo?->nombre,
                    'evento_id' => $proyecto->evento?->id,
                    'evento_nombre' => $proyecto->evento?->nombre,
                    'logoUrl' => $logoUrl,
                    'miembros' => $proyecto->miembros->map(fn($m) => [
                        'id' => $m->id,
                        'persona_id' => $m->persona_id,
                        'rol_id' => $m->rol_id,
                        'nombre' => $m->persona?->nombre,
                        'apellido' => $m->persona?->apellido,
                    ])->toArray(),
                ];
            });

        return response()->json($proyectos);
    }

    /**
     * Obtener un proyecto específico con información completa
     */
    public function getProyectoCompleto(Request $request, $proyectoId)
    {
        $proyecto = Proyecto::with([
            'equipo',
            'evento',
            'miembros.persona',
            'logo'
        ])
            ->where('id', $proyectoId)
            ->where('estado', '!=', 'BORRADO')
            ->first();

        if (!$proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado'], 404);
        }

        $logoUrl = $proyecto->logo->first()?->url ?? null;

        $proyectoCompleto = [
            'id' => $proyecto->id,
            'titulo' => $proyecto->titulo,
            'descripcion' => $proyecto->descripcion,
            'fecha_inicio' => $proyecto->fecha_inicio,
            'fecha_fin' => $proyecto->fecha_fin,
            'estado' => $proyecto->estado,
            'equipo_id' => $proyecto->equipo_id,
            'equipo_nombre' => $proyecto->equipo?->nombre,
            'evento_id' => $proyecto->evento?->id,
            'evento_nombre' => $proyecto->evento?->nombre,
            'logoUrl' => $logoUrl,
            'miembros' => $proyecto->miembros->map(fn($m) => [
                'id' => $m->id,
                'persona_id' => $m->persona_id,
                'rol_id' => $m->rol_id,
                'nombre' => $m->persona?->nombre,
                'apellido' => $m->persona?->apellido,
            ])->toArray(),
        ];

        return response()->json($proyectoCompleto);
    }
    /**
 * Eliminar completamente un proyecto con todas sus relaciones
 */
public function destroyComplete(Request $request, $id)
{
    // Obtener la persona del usuario logueado
    $persona = Persona::where('users_id', $request->user()->id)->first();

    if (!$persona) {
        return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
    }

    // Verificar si es líder del proyecto (ahora $persona ya está definida)
    $isLeaderProject = MiembrosProyecto::where('proyecto_id', $id)
        ->where('persona_id', $persona->id)
        ->where('rol_id', 1)
        ->exists();

    // Verificar permisos - solo superadministrador o líder del proyecto
    if ($request->user()->rol_id !== 8 && !$isLeaderProject) {
        return response()->json(['message' => 'No tienes permiso para eliminar proyectos completamente.'], 403);
    }

    $proyecto = Proyecto::find($id);

    if (!$proyecto) {
        return response()->json(['error' => 'Proyecto no encontrado'], 404);
    }

    try {
        DB::beginTransaction();

        // Guardar datos antes de eliminar para el response
        $titulo = $proyecto->titulo;
        $proyectoId = $proyecto->id;
        $equipoId = $proyecto->equipo_id;

        // 1. Eliminar archivos del proyecto y sus registros
        $archivosProyecto = ArchivoProyecto::where('proyecto_id', $proyecto->id)->get();

        foreach ($archivosProyecto as $archivoProyecto) {
            // Buscar el archivo asociado
            $archivo = Archivo::find($archivoProyecto->archivo_id);

            if ($archivo) {
                // Eliminar archivo físico si existe
                $rutaArchivo = public_path($archivo->url);
                if (file_exists($rutaArchivo)) {
                    unlink($rutaArchivo);
                }

                // Eliminar registro del archivo
                $archivo->delete();
            }

            // Eliminar la relación archivo_proyecto
            $archivoProyecto->delete();
        }

        // 2. Eliminar miembros del proyecto
        MiembrosProyecto::where('proyecto_id', $proyecto->id)->delete();

        // 3. Obtener el equipo asociado al proyecto antes de eliminar el proyecto
        $equipo = Equipo::find($proyecto->equipo_id);

        // 4. PRIMERO eliminar el proyecto (esto libera la restricción de clave foránea)
        $proyecto->delete();

        // 5. Ahora verificar y eliminar el equipo si es necesario
        if ($equipo) {
            // Verificar si hay otros proyectos usando este equipo
            $otrosProyectos = Proyecto::where('equipo_id', $equipo->id)
                                    ->where('estado', '!=', 'BORRADO')
                                    ->count();

            // Si no hay otros proyectos activos usando este equipo, eliminarlo
            if ($otrosProyectos === 0) {
                // Eliminar miembros del equipo antes de eliminar el equipo
                DB::table('miembros_equipo')->where('equipo_id', $equipo->id)->delete();

                // Ahora eliminar el equipo
                $equipo->delete();
                $equipoEliminado = $equipoId;
            } else {
                $equipoEliminado = null; // No se eliminó el equipo porque tiene otros proyectos
            }
        } else {
            $equipoEliminado = null;
        }

        DB::commit();

        return response()->json([
            'message' => 'Proyecto y todas sus relaciones eliminadas completamente.',
            'proyecto_eliminado' => [
                'id' => $proyectoId,
                'titulo' => $titulo,
                'equipo_eliminado' => $equipoEliminado,
                'equipo_conservado' => $equipoEliminado === null && $equipo ? 'El equipo se conservó porque tiene otros proyectos asociados' : null
            ]
        ], 200);

    } catch (\Exception $e) {
        DB::rollback();

        return response()->json([
            'error' => 'Error al eliminar el proyecto',
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
}


    /**
     * Determinar el tipo de permiso que tiene el usuario sobre un proyecto
     */
    private function getTipoPermiso($proyecto, $persona, $user)
    {
        // Superadministrador
        if ($user->rol_id == 8) {
            return 'superadministrador';
        }

        // Verificar si es miembro del proyecto
        $esMiembroProyecto = MiembrosProyecto::where('proyecto_id', $proyecto->id)
            ->where('persona_id', $persona->id)
            ->exists();

        if ($esMiembroProyecto) {
            // Verificar si es líder del proyecto
            $esLiderProyecto = MiembrosProyecto::where('proyecto_id', $proyecto->id)
                ->where('persona_id', $persona->id)
                ->where('rol_id', 1)
                ->exists();

            return $esLiderProyecto ? 'lider_proyecto' : 'miembro_proyecto';
        }

        // Verificar si es autor del evento (si el proyecto tiene evento)
        if ($proyecto->evento) {
            $isEventAuthor = EventoRolPersona::where('evento_id', $proyecto->evento->id)
                ->where('persona_id', $persona->id)
                ->where('rol_id', 1)
                ->where('estado_borrado', false)
                ->exists();

            if ($isEventAuthor) {
                return 'autor_evento';
            }

            // Verificar si está registrado en el evento
            $isRegistered = EventoRolPersona::where('evento_id', $proyecto->evento->id)
                ->where('persona_id', $persona->id)
                ->where('estado_borrado', false)
                ->exists();

            if ($isRegistered) {
                return 'participante_evento';
            }
        }

        return 'sin_permiso';
    }

/**
 * Verificar si el usuario puede editar un proyecto específico
 */
    public function canEditProject(Request $request, $proyectoId)
    {
        $user = $request->user();
        $persona = Persona::where('users_id', $user->id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        $proyecto = Proyecto::with(['equipo', 'evento'])->find($proyectoId);

        if (!$proyecto) {
            return response()->json(['error' => 'Proyecto no encontrado'], 404);
        }

        // Si es superadministrador
        if ($user->rol_id == 8) {
            return response()->json([
                'puede_editar' => true,
                'motivo' => 'superadministrador',
                'proyecto_id' => $proyectoId
            ]);
        }

        // Verificar si es miembro del proyecto
        $esMiembroProyecto = MiembrosProyecto::where('proyecto_id', $proyecto->id)
            ->where('persona_id', $persona->id)
            ->exists();

        if ($esMiembroProyecto) {
            return response()->json([
                'puede_editar' => true,
                'motivo' => 'miembro_proyecto',
                'proyecto_id' => $proyectoId
            ]);
        }

        $equipo = $proyecto->equipo;
        $evento = $proyecto->evento;

        if (!$equipo || !$evento) {
            return response()->json([
                'puede_editar' => false,
                'motivo' => 'datos_incompletos',
                'proyecto_id' => $proyectoId
            ]);
        }

        // Verificar si es autor del evento
        $isEventAuthor = EventoRolPersona::where('evento_id', $evento->id)
            ->where('persona_id', $persona->id)
            ->where('rol_id', 1)
            ->where('estado_borrado', false)
            ->exists();

        if ($isEventAuthor) {
            return response()->json([
                'puede_editar' => true,
                'motivo' => 'autor_evento',
                'proyecto_id' => $proyectoId
            ]);
        }

        // Verificar si está registrado en el evento
        $isRegistered = EventoRolPersona::where('evento_id', $evento->id)
            ->where('persona_id', $persona->id)
            ->where('estado_borrado', false)
            ->exists();

        if ($isRegistered) {
            return response()->json([
                'puede_editar' => true,
                'motivo' => 'participante_evento',
                'proyecto_id' => $proyectoId
            ]);
        }

        return response()->json([
            'puede_editar' => false,
            'motivo' => 'sin_permisos',
            'proyecto_id' => $proyectoId
        ]);
    }

    /**
     * Obtener solo los IDs de proyectos que el usuario puede editar
     */
    public function getProyectosEditablesIds(Request $request)
    {
        $user = $request->user();
        $persona = Persona::where('users_id', $user->id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        // Si es superadministrador, puede editar todos los proyectos
        if ($user->rol_id == 8) {
            $proyectoIds = Proyecto::where('estado', '!=', 'BORRADO')
                ->pluck('id')
                ->toArray();
        } else {
            // Para usuarios normales, obtener proyectos donde el usuario tenga permisos
            $proyectoIds = Proyecto::where('estado', '!=', 'BORRADO')
                ->where(function($query) use ($persona) {
                    // Proyectos donde es miembro
                    $query->whereHas('miembros', function($miembrosQuery) use ($persona) {
                        $miembrosQuery->where('persona_id', $persona->id);
                    })
                    // O proyectos de eventos donde está registrado
                    ->orWhereHas('equipo', function($equipoQuery) use ($persona) {
                        $equipoQuery->whereHas('evento', function($eventoQuery) use ($persona) {
                            $eventoQuery->whereExists(function($subQuery) use ($persona) {
                                $subQuery->select(DB::raw(1))
                                        ->from('evento_rol_persona')
                                        ->whereColumn('evento_rol_persona.evento_id', 'eventos.id')
                                        ->where('evento_rol_persona.persona_id', $persona->id)
                                        ->where('evento_rol_persona.estado_borrado', false);
                            });
                        });
                    });
                })
                ->pluck('id')
                ->toArray();
        }

        return response()->json([
            'proyecto_ids' => $proyectoIds,
        ]);
    }
}
