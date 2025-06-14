<?php

namespace App\Http\Controllers;

use App\Models\EquiposGanadore;
use Illuminate\Http\Request;

class EquiposGanadoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $equiposGanadores = EquiposGanadore::all();
        return response()->json($equiposGanadores);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->user()->rol_id != 1) {
            return response()->json(['message' => 'No tienes permiso para crear equipos ganadores'], 403);
        }

        $validator = \Validator::make($request->all(), [
            'equipo_id' => 'required|integer|exists:equipos,id',
            'rank' => 'required|integer|min:1|max:10',
            'evento_id' => 'required|integer|exists:eventos,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $equiposGanadore = EquiposGanadore::create([
            'equipo_id' => $request->equipo_id,
            'rank' => $request->rank,
            'evento_id' => $request->evento_id,
        ]);

        return response()->json($equiposGanadore, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(EquiposGanadore $equiposGanadore)
    {
        return response()->json($equiposGanadore);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EquiposGanadore $equiposGanadore)
    {
        if ($request->user()->rol_id != 1) {
            return response()->json(['message' => 'No tienes permiso para actualizar equipos ganadores'], 403);
        }

        $validator = \Validator::make($request->all(), [
            'equipo_id' => 'sometimes|integer|exists:equipos,id',
            'rank' => 'sometimes|integer|min:1|max:10',
            'evento_id' => 'sometimes|integer|exists:eventos,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $equiposGanadore->update($request->only(['equipo_id', 'rank', 'evento_id']));
        return response()->json($equiposGanadore);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EquiposGanadore $equiposGanadore)
    {
        if ($equiposGanadore->delete()) {
            return response()->json(['message' => 'Equipo ganador eliminado correctamente'], 200);
        }
        return response()->json(['message' => 'Error al eliminar el equipo ganador'], 500);
    }
}
