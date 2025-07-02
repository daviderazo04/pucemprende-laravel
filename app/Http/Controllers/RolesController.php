<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

class RolesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //Solo los administradores pueden acceder a los roles
        if ($request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para acceder a este elemento'], 403);
        }

        $role = Role::all();
        return response()->json($role);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Solo permitir si el usuario tiene rol_id = 1
        if ($request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para añadir un nuevo rol.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'descripcion' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role = Role::create([
            'creado_en' => Carbon::now(),
            'actualizado_en' => Carbon::now(),
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
        ]);

        return response()->json($role, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        // Solo Admin puede ver roles

        if ($request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para acceder a este elemento'], 403);
        }

        // Buscar proyecto por id
        $role = Role::find($id);
        
        if (!$role) {
            return response()->json(['message' => 'Rol no encontrado'], 404);
        }

        return response()->json($role);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Solo permitir si el usuario tiene rol_id = 1
        if ($request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para editar un rol.'], 403);
        }

        // Buscar proyecto por id
        $role = Role::find($id);

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'descripcion' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role->fill($request->only([
            'nombre',
            'descripcion'
        ]));

        $role->actualizado_en = Carbon::now();
        $role->save();

        return response()->json($role);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Solo permitir si el usuario tiene rol_id = 1
        $role = Role::find($id);
        if (!$role) {
            return response()->json(['message' => 'Rol no encontrado'], 404);
        }
    
        // Admin puede borrar
        if (request()->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para eliminar este elemento.'], 403);
        }

        // Intenta eliminar el documento
        try {
            $role->delete();
            return response()->json(['message' => 'Rol eliminado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar rol.', 'error' => $e->getMessage()], 500);
        }
    }
}
