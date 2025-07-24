<?php

namespace App\Http\Controllers\Api;

use App\Models\Criterio;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CriterioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $criterios = Criterio::with(['plantillas_evaluacion', 'resultados_evaluacions'])->get();
        return response()->json($criterios);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'plantilla_id' => 'nullable|integer|exists:plantillas_evaluacion,id',
            'peso' => 'nullable|numeric',
        ]);

        $criterio = Criterio::create([
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'plantilla_id' => $validated['plantilla_id'] ?? null,
            'peso' => $validated['peso'] ?? null,
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);
        return response()->json($criterio, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $criterio = Criterio::with(['plantillas_evaluacion', 'resultados_evaluacions'])->find($id);
        if (!$criterio) {
            return response()->json(['message' => 'Criterio no encontrado'], 404);
        }
        return response()->json($criterio);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $criterio = Criterio::find($id);
        if (!$criterio) {
            return response()->json(['message' => 'Criterio no encontrado'], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'plantilla_id' => 'nullable|integer|exists:plantillas_evaluacion,id',
            'peso' => 'nullable|numeric',
        ]);

        $criterio->fill($validated);
        $criterio->actualizado_en = now();
        $criterio->save();
        return response()->json($criterio);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $criterio = Criterio::find($id);
        if (!$criterio) {
            return response()->json(['message' => 'Criterio no encontrado'], 404);
        }
        $criterio->delete();
        return response()->json(['message' => 'Criterio eliminado correctamente.'], 200);
    }
}
