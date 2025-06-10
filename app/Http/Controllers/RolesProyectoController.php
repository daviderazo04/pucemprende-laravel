<?php

namespace App\Http\Controllers;

use App\Models\RolesProyecto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

class RolesProyectoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 2) {
            return response()->json(['message' => 'No tienes permiso para ver este elemento.'], 403);
        } else{
            $rolesProyecto = RolesProyecto::all();
            return response()->json($rolesProyecto);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Solo admin puede añadir miembros a un proyecto
        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para agregar un nuevo rol de proyecto.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'rol_interno' => 'required|string|max:15',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $rolesProyecto = RolesProyecto::create([
            'creado_en' => Carbon::now(),
            'actualizado_en' => Carbon::now(),
            'rol_interno' => $request->rol_interno,
        ]);

        return response()->json($rolesProyecto, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, RolesProyecto $rolesProyecto)
    {
        // Solo Admin puede ver miembros de un proyecto

        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para acceder a este elemento'], 403);
        }
        
        if (!$rolesProyecto) {
            return response()->json(['message' => 'Rol de proyecto no encontrado'], 404);
        }

        return response()->json($rolesProyecto);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RolesProyecto $rolesProyecto)
    {
        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para agregar un nuevo rol de proyecto.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'rol_interno' => 'required|string|max:15',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $rolesProyecto->fill($request->only([
            'rol_interno'
        ]));

        $rolesProyecto->actualizado_en = Carbon::now();
        $rolesProyecto->save();

        return response()->json($rolesProyecto);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RolesProyecto $rolesProyecto)
    {
        // Solo admin puede borrar
        if (request()->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para eliminar una rol de proyecto.'], 403);
        }

        // Intenta eliminar el documento
        try {
            $rolesProyecto->delete();
            return response()->json(['message' => 'Rol eliminado correctamente del proyecto.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar el rol del proyecto.', 'error' => $e->getMessage()], 500);
        }
    }
}
