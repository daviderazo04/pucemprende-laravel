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
        return ProcesosEvaluacion::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $proceso = ProcesosEvaluacion::create($request->all());
        return response()->json($proceso, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return ProcesosEvaluacion::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $proceso = ProcesosEvaluacion::findOrFail($id);
        $proceso->update($request->all());
        return response()->json($proceso, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        ProcesosEvaluacion::destroy($id);
        return response()->json(null, 204);
    }
}
