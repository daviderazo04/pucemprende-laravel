<?php

namespace App\Http\Controllers\Api;

use App\Models\PlantillasEvaluacion;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PlantillasEvaluacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $plantillas = PlantillasEvaluacion::with(['procesos_evaluacion'])->get();
        return response()->json($plantillas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'proceso_id' => 'nullable|integer|exists:procesos_evaluacion,id',
            'peso' => 'nullable|numeric',
        ]);

        $plantilla = PlantillasEvaluacion::create([
            'nombre' => $validated['nombre'],
            'proceso_id' => $validated['proceso_id'] ?? null,
            'peso' => $validated['peso'] ?? null,
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);
        return response()->json($plantilla, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $plantilla = PlantillasEvaluacion::with(['procesos_evaluacion', 'criterios', 'roles_plantillas'])->find($id);
        if (!$plantilla) {
            return response()->json(['message' => 'Plantilla de evaluación no encontrada'], 404);
        }
        return response()->json($plantilla);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $plantilla = PlantillasEvaluacion::find($id);
        if (!$plantilla) {
            return response()->json(['message' => 'Plantilla de evaluación no encontrada'], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'proceso_id' => 'nullable|integer|exists:procesos_evaluacion,id',
            'peso' => 'nullable|numeric',
        ]);

        $plantilla->fill($validated);
        $plantilla->actualizado_en = now();
        $plantilla->save();
        return response()->json($plantilla);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $plantilla = PlantillasEvaluacion::find($id);
        if (!$plantilla) {
            return response()->json(['message' => 'Plantilla de evaluación no encontrada'], 404);
        }
        $plantilla->delete();
        return response()->json(['message' => 'Plantilla de evaluación eliminada correctamente.'], 200);
    }
}
