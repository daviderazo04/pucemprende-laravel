<?php

namespace App\Http\Controllers;

use App\Models\Categorium;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

class CategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //Solo los administradores pueden acceder a las categorias de eventos
        //(no tiene sentido que cualquier usuario las pueda ver porque solo sirven para asociar un proyecto con ellas)

        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para acceder a este elemento'], 403);
        }

        $categoria = Categorium::all();
        return response()->json($categoria);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Solo permitir si el usuario tiene rol_id = 1 admin de evento o rol_id = 8 admin del sistema
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para añadir una nueva categoría.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:60',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $categoria = Categorium::create([
            'nombre' => $request->nombre,
        ]);

        return response()->json($categoria, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        // Solo admin puede ver las categorias
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para acceder a este elemento'], 403);
        }

        // Buscar la categoría por id
        $categoria = Categorium::find($id);

        if (!$categoria) {
            return response()->json(['message' => 'Categoría no encontrada'], 404);
        }

        return response()->json($categoria);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Categorium $categorium)
    {
        // Solo permitir si el usuario tiene rol_id = 8 (admin del sistema)
        if ($request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para editar una categoria.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $categorium->fill($request->only([
            'nombre'
        ]));

        $categorium->save();

        return response()->json($categorium);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Categorium $categorium)
    {
        // Solo permitir si el usuario tiene rol_id = 8 (admin del sistema)
        if (request()->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para eliminar una categoría.'], 403);
        }

        // Intenta eliminar el documento
        try {
            $categorium->delete();
            return response()->json(['message' => 'Categoría eliminada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar la categoría.', 'error' => $e->getMessage()], 500);
        }
    }
}
