<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

class PersonaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $personas = Persona::all();
        return response()->json($personas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Solo permitir si el usuario tiene rol_id = 1 y rol_id = 8 (superadministrador)
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para crear personas.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email|max:255',
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'telefono' => 'nullable|string|max:15',
            'identificacion' => 'required|string|max:20|unique:personas,identificacion',
            'alumni'=> 'nullable|boolean',
            'genero'=> 'nullable|string|max:10',
            'user_id'=> 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $persona = Persona::create([
            'creado_en' => Carbon::now(),
            'actualizado_en' => Carbon::now(),
            'estado_borrado' => false,
            'borrado_en' => null,
            'email' => $request->email,
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'telefono' => $request->telefono,
            'identificacion' => $request->identificacion,
            'alumni' => $request->alumni,
            'genero' => $request->genero,
        ]);

        return response()->json($persona, 201);
    }

    /**
     * Muestra una persona específica por su ID
     *
     * @param Request $request
     * @param Persona $persona Modelo de persona obtenido por route model binding
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Persona $persona)
    {
        // Solo permitir si el usuario tiene rol_id = 1 (admin) o rol_id = 8 (superadmin)
        if($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para acceder a esta persona.'], 403);
        }

        // Verificar si la persona está marcada como eliminada (soft delete)
        if ($persona->estado_borrado) {
            return response()->json(['message' => 'La persona no existe o ha sido eliminada'], 404);
        }

        return response()->json($persona);
    }

     //Busca personas por cédula usando coincidencias parciales

    public function getCedula(Request $request, $cedula)
    {
        // Solo permitir si el usuario tiene rol_id = 1 (admin) o rol_id = 8 (superadmin)
        if ($request->user()->rol_id !== 8 && $request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para acceder a esta persona.'], 403);
        }

        // Ejecutar stored procedure que busca con LIKE %cedula%
        $personas = DB::select('CALL sp_GetByIdentificacion(?)', [$cedula]);

        // Verificar si se encontraron resultados
        if (empty($personas)) {
            return response()->json(['message' => 'No se encontraron personas'], 404);
        }

        return response()->json($personas);
    }

    public function update(Request $request, Persona $persona)
    {
        // Solo permitir si el usuario tiene rol_id = 1
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para actualizar personas.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email|max:255',
            'nombre' => 'sometimes|string|max:100',
            'apellido' => 'sometimes|string|max:100',
            'telefono' => 'nullable|string|max:15',
            'identificacion' => [
                'required',
                'string',
                'max:13',
                Rule::unique('personas')->ignore($persona->id),
            ],
            'alumni'=> 'nullable|boolean',
            'genero'=> 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $persona->update($request->all());
        return response()->json($persona);
    }

    public function destroy(Request $request, Persona $persona)
    {
        // Solo permitir si el usuario tiene rol_id = 1
        if ($persona->estado_borrado) {
            return response()->json(['message' => 'La persona ya está eliminada'], 404);
        }

        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para eliminar personas.'], 403);
        }

        $persona->estado_borrado = true;
        $persona->borrado_en = Carbon::now();
        $persona->save();

        return response()->json(['message' => 'Persona eliminada correctamente'], 200);
    }

}
