<?php

namespace App\Http\Controllers;

use App\Models\MiembrosProyecto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

class MiembrosProyectoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 2) {
            return response()->json(['message' => 'No tienes permiso para ver este elemento.'], 403);
        } else{
            $miembrosProyecto = MiembrosProyecto::all();
            return response()->json($miembrosProyecto);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Admin y usuarios pueden añadir miembros a un proyecto
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 2) {
            return response()->json(['message' => 'No tienes permiso para agregar miembros a un proyecto.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'rol_id' => 'required|integer|exists:roles_proyectos,id',
            'proyecto_id' => 'required|integer|exists:proyectos,id',
            'persona_id' => 'required|integer|exists:personas,id',
            'fecha_inicio' => 'required|date_format:Y-m-d',
            'fecha_fin' => 'required|date_format:Y-m-d|after_or_equal:fecha_inicio',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $miembrosProyecto = MiembrosProyecto::create([
            'creado_en' => Carbon::now(),
            'actualizado_en' => Carbon::now(),
            'equipo_id' => $request->equipo_id,
            'persona_id' => $request->persona_id,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
        ]);

        return response()->json($miembrosProyecto, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(MiembrosProyecto $miembrosProyecto)
    {
        // Admin y usuarios pueden ver miembros de un proyecto

        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 2) {
            return response()->json(['message' => 'No tienes permiso para acceder a este elemento'], 403);
        }
        
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
        // Admin y usuarios pueden editar a los miembros del equipo
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 2) {
            return response()->json(['message' => 'No tienes permiso para editar los miembros de un proyecto.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'rol_id' => 'required|integer|exists:roles_proyectos,id',
            'proyecto_id' => 'required|integer|exists:proyectos,id',
            'persona_id' => 'required|integer|exists:personas,id',
            'fecha_inicio' => 'required|date_format:Y-m-d',
            'fecha_fin' => 'required|date_format:Y-m-d|after_or_equal:fecha_inicio',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $miembrosProyecto->fill($request->only([
            'rol_id',
            'proyecto_id',
            'persona_id',
            'fecha_inicio',
            'fecha_fin'
        ]));

        $miembrosProyecto->actualizado_en = Carbon::now();
        $miembrosProyecto->save();

        return response()->json($miembrosProyecto);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MiembrosProyecto $miembrosProyecto)
    {
         // Admin y usuarios pueden borrar
        if (request()->user()->rol_id !== 1 && $request->user()->rol_id !== 2) {
            return response()->json(['message' => 'No tienes permiso para eliminar una miembro de proyecto.'], 403);
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
