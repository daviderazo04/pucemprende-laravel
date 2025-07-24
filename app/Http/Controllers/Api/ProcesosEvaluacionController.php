<?php

namespace App\Http\Controllers\Api;

use App\Models\ProcesosEvaluacion;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProcesosEvaluacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $procesos = ProcesosEvaluacion::with('evento')->get();
        return response()->json($procesos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'evento_id' => 'nullable|integer|exists:eventos,id',
        ]);

        $proceso = ProcesosEvaluacion::create([
            'titulo' => $validated['titulo'],
            'evento_id' => $validated['evento_id'] ?? null,
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);
        return response()->json($proceso, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $proceso = ProcesosEvaluacion::with('evento')->find($id);
        if (!$proceso) {
            return response()->json(['message' => 'Proceso de evaluación no encontrado'], 404);
        }
        return response()->json($proceso);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $proceso = ProcesosEvaluacion::find($id);
        if (!$proceso) {
            return response()->json(['message' => 'Proceso de evaluación no encontrado'], 404);
        }

        $validated = $request->validate([
            'titulo' => 'sometimes|required|string|max:255',
            'evento_id' => 'nullable|integer|exists:eventos,id',
        ]);

        $proceso->fill($validated);
        $proceso->actualizado_en = now();
        $proceso->save();
        return response()->json($proceso);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $proceso = ProcesosEvaluacion::find($id);
        if (!$proceso) {
            return response()->json(['message' => 'Proceso de evaluación no encontrado'], 404);
        }
        $proceso->delete();
        return response()->json(['message' => 'Proceso de evaluación eliminado correctamente.'], 200);
    }
}
