<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ArchivoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $archivo = Archivo::all();
        return response()->json($archivo);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para crear archivos.'], 403);
        }
        $validator = Validator::make($request->all(), [
            'url' => 'required|string|max:100',
            'tipo' => 'required|string|max:50'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $archivo = Archivo::create([
            'creado_en' => now(),
            'actualizado_en' => now(),
            'estado_borrado' => false,
            'borrado_en' => null,
            'url' => $request->url,
            'tipo' => $request->tipo
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Archivo $archivo)
    {
        return response()->json($archivo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Archivo $archivo)
    {
        if($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para actualizar archivos.'], 403);
        }
        $validator = Validator::make($request->all(), [
            'url' => 'required|string|max:100',
            'tipo' => 'required|string|max:50'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $archivo->update([
            'actualizado_en' => now(),
            'url' => $request->url,
            'tipo' => $request->tipo
        ]);
        return response()->json($archivo, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Archivo $archivo)
    {
        if(request()->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para eliminar archivos.'], 403);
        }
        $archivo->update([
            'estado_borrado' => true,
            'borrado_en' => now()
        ]);
        return response()->json(['message' => 'Archivo eliminado correctamente.'], 200);
    }
}
