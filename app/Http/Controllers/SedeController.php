<?php

namespace App\Http\Controllers;

use App\Models\Sede;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

class SedeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //Solo los administradores pueden acceder a las sedes 
        //(no tiene sentido que cualquier usuario las pueda ver porque solo sirven para asociar un proyecto con ellas)
        
        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para acceder a este elemento'], 403);
        }

        $sede = Sede::all();
        return response()->json($sede);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        print($request->user());
        // Solo permitir si el usuario tiene rol_id = 1
        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para añadir una nueva sede.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $sede = Sede::create([
            'nombre' => $request->nombre,
        ]);

        return response()->json($sede, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Sede $sede)
    {
        //Solo admin puede ver las sedes

        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para acceder a este elemento'], 403);
        }
        
        return response()->json($sede);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Sede $sede)
    {
        print($request->user());
        // Solo permitir si el usuario tiene rol_id = 1
        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para añadir una nueva sede.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $sede->fill($request->only([
            'nombre'
        ]));
        
        $sede->save();

        return response()->json($sede);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sede $sede)
    {
         // Solo permitir si el usuario tiene rol_id = 1
        if (request()->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para eliminar documentos habilitantes.'], 403);
        }

        // Intenta eliminar el documento
        try {
            $sede->delete();
            return response()->json(['message' => 'Sede eliminada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar la sede.', 'error' => $e->getMessage()], 500);
        }
    }
}
