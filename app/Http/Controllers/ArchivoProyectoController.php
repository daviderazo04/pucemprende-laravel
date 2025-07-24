<?php

namespace App\Http\Controllers;

use App\Models\ArchivoProyecto;
use Illuminate\Http\Request;
use App\Models\Proyecto;
use App\Models\Equipo;
use App\Models\Persona;
use App\Models\EventoRolPersona;

class ArchivoProyectoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $archivosProyectos = ArchivoProyecto::all();
        return response()->json($archivosProyectos);
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
            ->exists();

        $isProjectLeader = MiembrosProyecto::where('proyecto_id', $proyecto->id)
            ->where('persona_id', $persona->id)
            ->where('rol_id', 1)
            ->exists();

        $hasPermission = $isSystemAdmin || $isEventAuthor || $isProjectLeader;

        if (!$hasPermission) {
            return response()->json([
                'message' => 'No tienes permiso para añadir archivos al proyecto.',
                'isSystemAdmin' => $isSystemAdmin,
                'isEventAuthor' => $isEventAuthor,
                'isProjectLeader' => $isProjectLeader
            ], 403);
        }

        $validator = \Validator::make($request->all(), [
            'archivo_id' => 'required|integer|exists:archivo,id',
            'proyecto_id' => 'required|integer|exists:proyectos,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $archivoProyecto = ArchivoProyecto::create([
            'archivo_id' => $request->archivo_id,
            'proyecto_id' => $request->proyecto_id
        ]);

        return response()->json($archivoProyecto, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ArchivoProyecto $archivoProyecto)
    {
        return response()->json($archivoProyecto);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ArchivoProyecto $archivoProyecto)
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
            ->exists();

        $isProjectLeader = MiembrosProyecto::where('proyecto_id', $proyecto->id)
            ->where('persona_id', $persona->id)
            ->where('rol_id', 1)
            ->exists();

        $hasPermission = $isSystemAdmin || $isEventAuthor || $isProjectLeader;

        if (!$hasPermission) {
            return response()->json([
                'message' => 'No tienes permiso para añadir archivos al proyecto.',
                'isSystemAdmin' => $isSystemAdmin,
                'isEventAuthor' => $isEventAuthor,
                'isProjectLeader' => $isProjectLeader
            ], 403);
        }

        $validator = \Validator::make($request->all(), [
            'archivo_id' => 'sometimes|integer|exists:archivo,id',
            'proyecto_id' => 'sometimes|integer|exists:proyectos,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
         $archivoProyecto->archivo_id = $request->archivo_id;
        $archivoProyecto->proyecto_id = $request->proyecto_id;
        $archivoProyecto->save();

        return response()->json($archivoProyecto, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ArchivoProyecto $archivoProyecto)
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
            ->exists();

        $isProjectLeader = MiembrosProyecto::where('proyecto_id', $proyecto->id)
            ->where('persona_id', $persona->id)
            ->where('rol_id', 1)
            ->exists();

        $hasPermission = $isSystemAdmin || $isEventAuthor || $isProjectLeader;

        if (!$hasPermission) {
            return response()->json([
                'message' => 'No tienes permiso para añadir archivos al proyecto.',
                'isSystemAdmin' => $isSystemAdmin,
                'isEventAuthor' => $isEventAuthor,
                'isProjectLeader' => $isProjectLeader
            ], 403);
        }

        $archivoProyecto->delete();
        return response()->json(['message' => 'Archivo de proyecto eliminado correctamente.'], 200);
    }
}
