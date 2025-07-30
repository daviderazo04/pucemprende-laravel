<?php

namespace App\Http\Controllers;

use App\Models\ArchivoProyecto;
use Illuminate\Http\Request;
use App\Models\Proyecto;
use App\Models\Equipo;
use App\Models\Persona;
use App\Models\EventoRolPersona;
use App\Models\Evento;
use App\Models\MiembrosProyecto;
use Illuminate\Support\Facades\DB;

class ArchivoProyectoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $archivosProyectos = ArchivoProyecto::all();
        return response()->json($archivosProyectos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validar los datos
        $validator = \Validator::make($request->all(), [
            'archivo_id' => 'required|integer|exists:archivo,id',
            'proyecto_id' => 'required|integer|exists:proyectos,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        // Obtener la persona logueada
        $persona = Persona::where('users_id', $request->user()->id)->first();
        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }
        // Obtener el proyecto del request
        $proyecto = Proyecto::find($request->proyecto_id);
        if (!$proyecto) {
            return response()->json(['error' => 'Proyecto no encontrado'], 404);
        }
        // Verificar si el proyecto pertenece a un equipo
        if (!$proyecto->equipo_id) {
            return response()->json(['error' => 'El proyecto no pertenece a un equipo.'], 404);
        }
        $equipo = Equipo::find($proyecto->equipo_id);
        if (!$equipo) {
            return response()->json(['error' => 'Equipo no encontrado'], 404);
        }
        // Obtener el evento del equipo
        $evento = Evento::find($equipo->evento_id);
        if (!$evento) {
            return response()->json(['error' => 'Evento no encontrado para este equipo'], 404);
        }

        // Permisos
        $isSystemAdmin = ($request->user()->rol_id == 8);
        // Verificar si la persona es admin del evento
        $isEventAuthor = EventoRolPersona::where('evento_id', $evento->id)
            ->where('persona_id', $persona->id)
            ->where('rol_id', 1)
            ->exists();
        // Verificar si la persona es líder del proyecto
        $isProjectLeader = MiembrosProyecto::where('proyecto_id', $proyecto->id)
            ->where('persona_id', $persona->id)
            ->where('rol_id', 1)
            ->exists();
        // Solo permitir si el usuario es superadmin, o admin del evento
        if ($request->user()->rol_id !== 8 && !$isEventAuthor && !$isProjectLeader) {
            return response()->json(['message' => 'No tienes permiso para crear archivos en este proyecto.'], 403);
        }
        // Eliminar archivos existentes del proyecto antes de agregar el nuevo
        ArchivoProyecto::where('proyecto_id', $request->proyecto_id)->delete();
        // Crear el nuevo archivo de proyecto
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

    public function getByProyecto($proyecto_id)
    {
        $archivos = ArchivoProyecto::where('proyecto_id', $proyecto_id)->get();
        return response()->json($archivos);
    }

    public function getByProyectoURL($id)
    {
        try {
            $resultado = DB::select('CALL sp_buscar_archivo_proyecto_url_por_id(?)', [$id]);

            if (empty($resultado)) {
                return response()->json(['message' => 'No se encontró el archivo proyecto.'], 404);
            }

            return response()->json($resultado);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al ejecutar el procedimiento: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ArchivoProyecto $archivoProyecto)
    {
        // Validar los datos
        $validator = \Validator::make($request->all(), [
            'archivo_id' => 'sometimes|integer|exists:archivo,id',
            'proyecto_id' => 'sometimes|integer|exists:proyectos,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        // Obtener la persona logueada
        $persona = Persona::where('users_id', $request->user()->id)->first();
        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }
        // Obtener el proyecto del archivo
        $proyecto = Proyecto::find($archivoProyecto->proyecto_id);
        if (!$proyecto) {
            return response()->json(['error' => 'Proyecto no encontrado'], 404);
        }
        // Verificar si el proyecto pertenece a un equipo
        if (!$proyecto->equipo_id) {
            return response()->json(['error' => 'El proyecto no pertenece a un equipo.'], 404);
        }
        $equipo = Equipo::find($proyecto->equipo_id);
        if (!$equipo) {
            return response()->json(['error' => 'Equipo no encontrado'], 404);
        }
        // Obtener el evento del equipo
        $evento = Evento::find($equipo->evento_id);
        if (!$evento) {
            return response()->json(['error' => 'Evento no encontrado para este equipo'], 404);
        }

        // Permisos

        $isEventAuthor = EventoRolPersona::where('evento_id', $evento->id)
            ->where('persona_id', $persona->id)
            ->where('rol_id', 1)
            ->exists();

        $isProjectLeader = MiembrosProyecto::where('proyecto_id', $proyecto->id)
            ->where('persona_id', $persona->id)
            ->where('rol_id', 1)
            ->exists();

       // Solo permitir si el usuario es superadmin o admin del evento
        if ($request->user()->rol_id !== 8 && !$isEventAuthor && !$isProjectLeader) {
            return response()->json(['message' => 'No tienes permiso para actualizar archivos en este evento.'], 403);
        }

        $archivoEvento->archivo_id = $request->archivo_id;
        $archivoEvento->evento_id = $request->evento_id;
        $archivoEvento->save();


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
    public function destroy(Request $request, $id)
    {
        $archivoProyecto = ArchivoProyecto::find($id);
        if (!$archivoProyecto) {
            return response()->json(['error' => 'Archivo de proyecto no encontrado'], 404);
        }

        // Obtener la persona logueada
        $persona = Persona::where('users_id', $request->user()->id)->first();
        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada'], 404);
        }

        // Obtener el proyecto del archivo
        $proyecto = Proyecto::find($archivoProyecto->proyecto_id);
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

        // Verificar si la persona es admin del evento o líder del proyecto
        $isEventAuthor = EventoRolPersona::where('evento_id', $evento->id)
                                         ->where('persona_id', $persona->id)
                                         ->where('rol_id', 1)
                                         ->exists();

        $isProjectLeader = MiembrosProyecto::where('proyecto_id', $proyecto->id)
                                           ->where('persona_id', $persona->id)
                                           ->where('rol_id', 1)
                                           ->exists();

        // Solo permitir si el usuario es superadmin, admin del evento o líder del proyecto
        if ($request->user()->rol_id !== 8 && !$isEventAuthor && !$isProjectLeader) {
            return response()->json(['message' => 'No tienes permiso para eliminar archivos en este proyecto.'], 403);
        }

        $archivoProyecto->delete();
        return response()->json(['message' => 'Archivo de proyecto eliminado correctamente.'], 200);
    }
}
