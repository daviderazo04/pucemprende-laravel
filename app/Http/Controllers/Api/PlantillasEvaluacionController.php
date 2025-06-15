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
        return PlantillasEvaluacion::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $plantilla = PlantillasEvaluacion::create($request->all());
        return response()->json($plantilla, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return PlantillasEvaluacion::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $plantilla = PlantillasEvaluacion::findOrFail($id);
        $plantilla->update($request->all());
        return response()->json($plantilla, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        PlantillasEvaluacion::destroy($id);
        return response()->json(null, 204);
    }
}
