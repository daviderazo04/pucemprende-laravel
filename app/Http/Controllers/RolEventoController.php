<?php

namespace App\Http\Controllers;

use App\Models\RolEvento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

class RolEventoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver este elemento.'], 403);
        } else{
            $rolEvento = RolEvento::all();
            return response()->json($rolEvento);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Solo admin puede añadir miembros a un proyecto
        if ($request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para agregar un nuevo rol de evento.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $rolEvento = RolEvento::create([
            'nombre' => $request->nombre,
        ]);

        return response()->json($rolEvento, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, RolEvento $rolEvento)
    {
        // Solo Admin puede ver miembros de un proyecto

        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para acceder a este elemento'], 403);
        }
        
        if (!$rolEvento) {
            return response()->json(['message' => 'Rol de evento no encontrado'], 404);
        }

        return response()->json($rolEvento);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RolEvento $rolEvento)
    {
        // Solo admin puede añadir miembros a un proyecto
        if ($request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para agregar un nuevo rol de evento.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $rolEvento->fill($request->only([
            'nombre'
        ]));

        $rolEvento->save();

        return response()->json($rolEvento);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RolEvento $rolEvento)
    {
        // Solo admin puede borrar
        if (request()->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para eliminar una rol de evento.'], 403);
        }

        // Intenta eliminar el documento
        try {
            $rolEvento->delete();
            return response()->json(['message' => 'Rol eliminado correctamente del evento.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar el rol del evento.', 'error' => $e->getMessage()], 500);
        }
    }
}
