<?php

namespace App\Http\Controllers;

use App\Models\Organizacione;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
class OrganizacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $organizaciones = Organizacione::all();
        return response()->json($organizaciones);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Solo permitir si el usuario tiene rol_id = 1
        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para crear organizaciones.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'abreviatura' => 'nullable|string|max:15',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $organizacion = Organizacione::create([
            'creado_en' => now(),
            'actualizado_en' => now(),
            'estado_borrado' => false,
            'borrado_en' => null,
            'nombre' => $request->nombre,
            'abreviatura' => $request->abreviatura
        ]);

        return response()->json($organizacion->id, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Organizacione $organizacione)
    {
        return response()->json($organizacione);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Organizacione $organizacione)
    {
        // Solo permitir si el usuario tiene rol_id = 1
        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para actualizar organizaciones.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'abreviatura' => 'nullable|string|max:15',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $organizacione->update([
            'actualizado_en' => now(),
            'nombre' => $request->nombre,
            'abreviatura' => $request->abreviatura
        ]);

        return response()->json($organizacione, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organizacione $organizacione)
    {
        // Solo permitir si el usuario tiene rol_id = 1
        if (request()->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para eliminar organizaciones.'], 403);
        }

        $organizacione->estado_borrado = true;
        $organizacione->borrado_en = now();
        $organizacione->save();

        return response()->json(['message' => 'Organización eliminada correctamente.'], 200);
    }
}
