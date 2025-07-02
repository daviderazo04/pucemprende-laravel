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
                                        ->exists();

        $isRegistered = EventoRolPersona::where('evento_id', $evento->id)
                                        ->where('persona_id', $persona->id)
                                        ->exists();

        if (!$isSystemAdmin && !$isEventAuthor && !$isRegistered) {
            return response()->json(['message' => 'No tienes permiso para crear un proyecto ya que no estás inscrito al evento.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'equipo_id' => 'nullable|integer|exists:equipos,id',
            'titulo' => 'required|string|max:50',
            'descripcion' => 'required|string|max:1000',
            'estado' => 'required|string|max:20',
            'fecha_inicio' => 'required|date_format:Y-m-d',
            'fecha_fin' => 'required|date_format:Y-m-d|after_or_equal:fecha_inicio',
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
            'estado' => $request->estado,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
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
                                        ->exists();
        $isRegistered = EventoRolPersona::where('evento_id', $evento->id)
                                        ->where('persona_id', $persona->id)
                                        ->exists();

        if (!$isSystemAdmin && !$isEventAuthor && !$isRegistered) {
            return response()->json(['message' => 'No tienes permiso para actualizar este evento.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'equipo_id' => 'nullable|integer|exists:equipos,id',
            'titulo' => 'required|string|max:50',
            'descripcion' => 'required|string|max:1000',
            'estado' => 'required|string|max:20',
            'fecha_inicio' => 'required|date_format:Y-m-d',
            'fecha_fin' => 'required|date_format:Y-m-d|after_or_equal:fecha_inicio',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $proyecto->fill($request->only([
            'equipo_id',
            'titulo',
            'descripcion',
            'estado',
            'fecha_inicio',
            'fecha_fin'
        ]));

        $proyecto->actualizado_en = Carbon::now();
        $proyecto->save();

        return response()->json($proyecto);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Proyecto $proyecto)
    {
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para eliminar proyectos.'], 403);
        }

        $persona = Persona::where('users_id', $request->user()->id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        // Verificar si el usuario tiene rol_id = 1 (administrador del sistema)
        // O si la persona es el autor del evento (rol_id = 1 para este evento en evento_rol_persona)
        $isSystemAdmin = ($request->user()->rol_id == 8);
        $isEventAuthor = EventoRolPersona::where('evento_id', $evento->id)
                                        ->where('persona_id', $persona->id)
                                        ->where('rol_id', 1) 
                                        ->exists();

        if (!$isSystemAdmin && !$isEventAuthor) {
            return response()->json(['message' => 'No tienes permiso para actualizar este evento.'], 403);
        }

        $proyecto->estado = "BORRADO";
        $proyecto->actualizado_en = Carbon::now();
        $proyecto->save();

        return response()->json(['message' => 'Proyecto marcado como borrado lógicamente.'], 200);
    }
}
