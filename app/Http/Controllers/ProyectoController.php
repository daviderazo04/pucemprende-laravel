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
            return response()->json(['message' => 'No tienes permiso para crear un proyecto ya que no estás inscrito al evento.'], 403);
        }

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
        $proyectos = Proyecto::select(
                'proyectos.id',
                'proyectos.titulo',
                'proyectos.descripcion',
                'proyectos.fecha_inicio',
                'proyectos.fecha_fin',
                'proyectos.estado',
                'proyectos.equipo_id',
                'equipos.nombre as equipo_nombre',
                'eventos.id as evento_id',
                'eventos.nombre as evento_nombre'
            )
            ->join('equipos', 'proyectos.equipo_id', '=', 'equipos.id')
            ->join('eventos', 'equipos.evento_id', '=', 'eventos.id')
            ->where('proyectos.estado', '!=', 'BORRADO')
            ->get()
            ->map(function ($proyecto) {
                // Obtener los miembros del proyecto con información de la persona
                $miembros = MiembrosProyecto::select(
                        'miembros_proyecto.id',
                        'miembros_proyecto.persona_id',
                        'miembros_proyecto.rol_id',
                        'personas.nombre',
                        'personas.apellido'
                    )
                    ->join('personas', 'miembros_proyecto.persona_id', '=', 'personas.id')
                    ->where('miembros_proyecto.proyecto_id', $proyecto->id)
                    ->get();

                // Buscar logo del proyecto usando la tabla intermedia archivo_proyecto
                // CORRECCIÓN: Usar 'archivo' en lugar de 'archivos'
                $logoUrl = null;
                $archivo = DB::table('archivo')
                    ->join('archivo_proyecto', 'archivo.id', '=', 'archivo_proyecto.archivo_id')
                    ->where('archivo_proyecto.proyecto_id', $proyecto->id)
                    ->where('archivo.estado_borrado', false)
                    ->where('archivo.tipo', 'LIKE', '%logo%') // O el criterio que uses para logos
                    ->select('archivo.url')
                    ->first();

                if ($archivo) {
                    $logoUrl = $archivo->url;
                }

                return [
                    'id' => $proyecto->id,
                    'titulo' => $proyecto->titulo,
                    'descripcion' => $proyecto->descripcion,
                    'fecha_inicio' => $proyecto->fecha_inicio,
                    'fecha_fin' => $proyecto->fecha_fin,
                    'estado' => $proyecto->estado,
                    'equipo_id' => $proyecto->equipo_id,
                    'equipo_nombre' => $proyecto->equipo_nombre,
                    'evento_id' => $proyecto->evento_id,
                    'evento_nombre' => $proyecto->evento_nombre,
                    'logoUrl' => $logoUrl,
                    'miembros' => $miembros->toArray()
                ];
            });

        return response()->json($proyectos);
    }

    /**
     * Obtener un proyecto específico con información completa
     */
     /**
     * Obtener proyecto completo por ID
     */
    public function getProyectoCompleto(Request $request, $id)
    {
        $proyecto = Proyecto::with([
                'equipo.evento',
                'miembros.persona',
                'archivos'
            ])
            ->where('id', $id)
            ->where('estado', '!=', 'BORRADO')
            ->first();

        if (!$proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado'], 404);
        }

        $logo = $proyecto->archivos->firstWhere('tipo', 'like', '%logo%');

        $proyectoCompleto = [
            'id' => $proyecto->id,
            'titulo' => $proyecto->titulo,
            'descripcion' => $proyecto->descripcion,
            'fecha_inicio' => $proyecto->fecha_inicio,
            'fecha_fin' => $proyecto->fecha_fin,
            'estado' => $proyecto->estado,
            'equipo_id' => $proyecto->equipo_id,
            'equipo_nombre' => $proyecto->equipo?->nombre,
            'evento_id' => $proyecto->equipo?->evento?->id,
            'evento_nombre' => $proyecto->equipo?->evento?->nombre,
            'logoUrl' => $logo ? $logo->url : null,
            'miembros' => $proyecto->miembros->map(fn($m) => [
                'id' => $m->id,
                'persona_id' => $m->persona_id,
                'rol_id' => $m->rol_id,
                'nombre' => $m->persona?->nombre,
                'apellido' => $m->persona?->apellido,
            ]),
        ];

        return response()->json($proyectoCompleto);
    }

    /**
     * Obtener proyectos completos por evento
     */
    public function getProyectosPorEventoCompleto(Request $request, $eventoId)
    {
        $evento = Evento::find($eventoId);

        if (!$evento) {
            return response()->json(['message' => 'Evento no encontrado'], 404);
        }

        $proyectos = Proyecto::with([
                'equipo.evento',
                'miembros.persona',
                'archivos'
            ])
            ->whereHas('equipo', fn($q) => $q->where('evento_id', $eventoId))
            ->where('estado', '!=', 'BORRADO')
            ->get()
            ->map(function ($proyecto) {
                $logo = $proyecto->archivos->firstWhere('tipo', 'like', '%logo%');

                return [
                    'id' => $proyecto->id,
                    'titulo' => $proyecto->titulo,
                    'descripcion' => $proyecto->descripcion,
                    'fecha_inicio' => $proyecto->fecha_inicio,
                    'fecha_fin' => $proyecto->fecha_fin,
                    'estado' => $proyecto->estado,
                    'equipo_id' => $proyecto->equipo_id,
                    'equipo_nombre' => $proyecto->equipo?->nombre,
                    'evento_id' => $proyecto->equipo?->evento?->id,
                    'evento_nombre' => $proyecto->equipo?->evento?->nombre,
                    'logoUrl' => $logo ? $logo->url : null,
                    'miembros' => $proyecto->miembros->map(fn($m) => [
                        'id' => $m->id,
                        'persona_id' => $m->persona_id,
                        'rol_id' => $m->rol_id,
                        'nombre' => $m->persona?->nombre,
                        'apellido' => $m->persona?->apellido,
                    ]),
                ];
            });

        return response()->json($proyectos);
    }

    /**
     * Obtener todos los archivos de un proyecto
     */
    public function getArchivosProyecto(Request $request, $proyectoId)
    {
        $proyecto = Proyecto::find($proyectoId);

        if (!$proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado'], 404);
        }

        // CORRECCIÓN: Usar 'archivo' en lugar de 'archivos'
        $archivos = DB::table('archivo')
            ->join('archivo_proyecto', 'archivo.id', '=', 'archivo_proyecto.archivo_id')
            ->where('archivo_proyecto.proyecto_id', $proyectoId)
            ->where('archivo.estado_borrado', false)
            ->select(
                'archivo.id',
                'archivo.url',
                'archivo.tipo',
                'archivo.creado_en'
            )
            ->get();

        return response()->json([
            'proyecto_id' => $proyectoId,
            'archivos' => $archivos
        ]);
    }

}
