<?php

namespace App\Http\Controllers;

use App\Models\ArchivoProyecto;
use Illuminate\Http\Request;

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
        // Solo permitir si el usuario tiene rol_id = 1
        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para crear archivos de proyectos.'], 403);
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
        // Solo permitir si el usuario tiene rol_id = 1
        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para actualizar archivos de proyectos.'], 403);
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
        // Solo permitir si el usuario tiene rol_id = 1
        if (request()->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para eliminar archivos de proyectos.'], 403);
        }

        $archivoProyecto->delete();
        return response()->json(['message' => 'Archivo de proyecto eliminado correctamente.'], 200);
    }
}
