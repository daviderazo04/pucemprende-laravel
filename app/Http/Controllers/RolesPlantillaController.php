<?php

namespace App\Http\Controllers;

use App\Models\RolesPlantilla;
use App\Models\PlantillasEvaluacion;
use App\Models\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;



class RolesPlantillaController extends Controller
{
    /**
     * Muestra una lista de todos los roles plantilla
     */
    public function index(Request $request)
    {

        // Obtener todos los roles de plantilla de la base de datos
        $rolesPlantilla = RolesPlantilla::all();

        // Retornar los datos en formato JSON
        return response()->json($rolesPlantilla);
    }

    /**
     * Muestra un rol plantilla por ID de plantilla
     */
    public function getPlantillaById(Request $request, $id)
    {

        // Buscar el rol de plantilla por ID de plantilla
        $plantilla = RolesPlantilla::where('plantilla_id', $id)->get();

        // Verificar si el rol existe
        if ($plantilla->isEmpty()) {
            return response()->json(['message' => 'Rol plantilla no encontrado'], 404);
        }

        // Retornar el rol encontrado
        return response()->json($plantilla);
    }

    /**
     * Almacena un nuevo rol plantilla en la base de datos.
     */
    public function store(Request $request)
    {
        // Solo permitir si el usuario tiene rol_id = 8
        if ($request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para añadir un nuevo rol plantilla.'], 403);
        }
        // Validar los datos de entrada
        $validator = Validator::make($request->all(), [
            'plantilla_id' => 'required|integer|exists:plantillas_evaluacion,id',
            'rol_id' => 'required|integer|exists:rolEvento,id',
        ]);
        // Excepcion si hay errores de validación
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        // Crear el nuevo rol plantilla
        $rolesPlantilla = RolesPlantilla::create([
            'creado_en' => Carbon::now(),
            'actualizado_en' => null,
            'plantilla_id' => $request->plantilla_id,
            'rol_id' => $request->rol_id,
        ]);

        return response()->json($rolesPlantilla, 201);
    }
    public function update(Request $request, $id)
    {
        // Solo permitir si el usuario tiene rol_id = 8
        if ($request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para actualizar un rol plantilla.'], 403);
        }
        // Buscar el rol plantilla por ID
        $rolesPlantilla = RolesPlantilla::find($id);
        if (!$rolesPlantilla) {
            return response()->json(['message' => 'Rol plantilla no encontrado'], 404);
        }
        // Validar los datos de entrada
        $validator = Validator::make($request->all(), [
            'plantilla_id' => 'required|integer|exists:plantillas_evaluacion,id',
            'rol_id' => 'required|integer|exists:rolEvento,id',
        ]);
        // Excepcion si hay errores de validación
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        // Actualizar el rol plantilla
        $rolesPlantilla->update([
            'actualizado_en' => Carbon::now(),
            'plantilla_id' => $request->plantilla_id,
            'rol_id' => $request->rol_id,
        ]);
        // Retornar el rol plantilla actualizado
        return response()->json($rolesPlantilla, 200);
    }
    public function destroy(Request $request, $id)
    {
        // Solo permitir si el usuario tiene rol_id = 8
        if ($request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para eliminar un rol plantilla.'], 403);
        }
        // Buscar el rol plantilla por ID
        // Si no existe, retornar un error 404
        $rolesPlantilla = RolesPlantilla::find($id);
        if (!$rolesPlantilla) {
            return response()->json(['message' => 'Rol plantilla no encontrado'], 404);
        }
        // Eliminar el rol plantilla
        $rolesPlantilla->delete();
        return response()->json(['message' => 'Rol plantilla eliminado correctamente'], 200);
    }
}

