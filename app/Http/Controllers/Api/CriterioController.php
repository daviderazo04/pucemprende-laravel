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
        return Criterio::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $criterio = Criterio::create($request->all());
        return response()->json($criterio, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return Criterio::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $criterio = Criterio::findOrFail($id);
        $criterio->update($request->all());
        return response()->json($criterio, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        Criterio::destroy($id);
        return response()->json(null, 204);
    }
}
