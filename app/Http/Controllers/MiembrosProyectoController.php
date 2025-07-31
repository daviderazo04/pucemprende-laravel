<?php

namespace App\Http\Controllers;

use App\Models\MiembrosProyecto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\EventoRolPersona;
use App\Models\Persona;
use App\Models\Evento;
use App\Models\Equipo;
use App\Models\Proyecto;
use App\Models\RolesProyecto;

class MiembrosProyectoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $miembrosProyecto = MiembrosProyecto::all();
        return response()->json($miembrosProyecto);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $persona = Persona::where('users_id', $request->user()->id)->first();
        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        $proyecto = Proyecto::find($request->proyecto_id);
        if (!$proyecto) {
            return response()->json(['error' => 'Proyecto no encontrado'], 404);
        }

        $equipo = Equipo::find($proyecto->equipo_id);
        if (!$equipo) {
            return response()->json(['error' => 'Equipo no encontrado'], 404);
        }

        $evento = Evento::find($equipo->evento_id);
        if (!$evento) {
            return response()->json(['error' => 'Evento no encontrado para este equipo'], 404);
        }

        // Permisos
        $isSystemAdmin = ($request->user()->rol_id == 8);

        $isEventAuthor = EventoRolPersona::where('evento_id', $evento->id)
            ->where('persona_id', $persona->id)
            ->where('rol_id', 1)
            ->where('estado_borrado', false)
            ->exists();

        $isProjectLeader = MiembrosProyecto::where('proyecto_id', $proyecto->id)
            ->where('persona_id', $persona->id)
            ->where('rol_id', 1)
            ->exists();

        $hasPermission = $isSystemAdmin || $isEventAuthor || $isProjectLeader;

        if (!$hasPermission) {
            return response()->json([
                'message' => 'No tienes permiso para añadir miembros al proyecto.',
                'isSystemAdmin' => $isSystemAdmin,
                'isEventAuthor' => $isEventAuthor,
                'isProjectLeader' => $isProjectLeader
            ], 403);
        }

        // Verificar si ya está inscrito en el evento
        $inscrito = EventoRolPersona::where('evento_id', $evento->id)
            ->where('persona_id', $persona->id)
            ->exists();

        if (!$inscrito) {
            $eventoRolPersona = EventoRolPersona::create([
                'evento_id' => $evento->id,
                'rol_id' => 4,                      // Asignar rol_id 4 para inscripción (miembro)
                'persona_id' => $persona->id,
                'estado_borrado' => false, // No marcado como borrado
            ]);
        }

        // Validación de datos
        $validator = Validator::make($request->all(), [
            'rol_id' => 'required|integer|exists:roles_proyectos,id',
            'proyecto_id' => 'required|integer|exists:proyectos,id',
            'persona_id' => 'required|integer|exists:personas,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $miembrosProyecto = MiembrosProyecto::create([
            'creado_en' => Carbon::now(),
            'actualizado_en' => Carbon::now(),
            'rol_id' => $request->rol_id,
            'persona_id' => $request->persona_id,
            'proyecto_id' => $request->proyecto_id,
        ]);

        return response()->json($miembrosProyecto, 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(MiembrosProyecto $miembrosProyecto)
    {
        // Todos pueden ver los miembros del proyecto
        if (!$miembrosProyecto) {
            return response()->json(['message' => 'Miembro del proyecto no encontrado'], 404);
        }

        return response()->json($miembrosProyecto);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MiembrosProyecto $miembrosProyecto)
    {
        $persona = Persona::where('users_id', $request->user()->id)->first();
        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        $proyecto = Proyecto::find($request->proyecto_id);
        if (!$proyecto) {
            return response()->json(['error' => 'Proyecto no encontrado'], 404);
        }

        $equipo = Equipo::find($proyecto->equipo_id);
        if (!$equipo) {
            return response()->json(['error' => 'Equipo no encontrado'], 404);
        }

        $evento = Evento::find($equipo->evento_id);
        if (!$evento) {
            return response()->json(['error' => 'Evento no encontrado para este equipo'], 404);
        }

        // Permisos
        $isSystemAdmin = ($request->user()->rol_id == 8);

        $isEventAuthor = EventoRolPersona::where('evento_id', $evento->id)
            ->where('persona_id', $persona->id)
            ->where('rol_id', 1)
            ->where('estado_borrado', false)
            ->exists();

        $isProjectLeader = MiembrosProyecto::where('proyecto_id', $proyecto->id)
            ->where('persona_id', $persona->id)
            ->where('rol_id', 1)
            ->exists();

        $hasPermission = $isSystemAdmin || $isEventAuthor || $isProjectLeader;

        if (!$hasPermission) {
            return response()->json([
                'message' => 'No tienes permiso para editar miembros del proyecto.',
                'isSystemAdmin' => $isSystemAdmin,
                'isEventAuthor' => $isEventAuthor,
                'isProjectLeader' => $isProjectLeader
            ], 403);
        }

        // Validación de datos
        $validator = Validator::make($request->all(), [
            'rol_id' => 'required|integer|exists:roles_proyectos,id',
            'proyecto_id' => 'required|integer|exists:proyectos,id',
            'persona_id' => 'required|integer|exists:personas,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $miembrosProyecto->fill($request->only([
            'rol_id',
            'proyecto_id',
            'persona_id'
        ]));

        $miembrosProyecto->actualizado_en = Carbon::now();
        $miembrosProyecto->save();

        return response()->json($miembrosProyecto);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, MiembrosProyecto $miembrosProyecto)
    {
         // Admin y usuarios pueden borrar
        if (request()->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para eliminar una miembro de proyecto.'], 403);
        }
        $persona = Persona::where('users_id', $request->user()->id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        $proyecto = Proyecto::find($request->proyecto_id);
        if (!$proyecto) {
            return response()->json(['error' => 'Proyecto no encontrado'], 404);
        }

        $equipo = Equipo::find($proyecto->equipo_id);
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

        $hasPermission = $isSystemAdmin || $isEventAuthor;

        if (!$hasPermission) {
            return response()->json(['message' => 'No tienes permiso para eliminar miembros del proyecto.'], 403);
        }
        // Intenta eliminar el documento
        try {
            $miembrosProyecto->delete();
            return response()->json(['message' => 'Miembro eliminado correctamente del proyecto.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar el miembro del proyecto.', 'error' => $e->getMessage()], 500);
        }
    }
}
