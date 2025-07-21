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
        // Solo permitir si el usuario tiene rol_id = 1
        if ($request->user()->rol_id !== 1) {
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

    public function show(Persona $persona)
    {
        return response()->json($persona);
    }
    public function getCedula(Request $request, $cedula)
    {
        // Solo permitir si el usuario tiene rol_id = 1
        // Comentado temporalmente para testing
        // if ($request->user()->rol_id !== 1) {
        //     return response()->json(['message' => 'No tienes permiso para acceder a esta persona.'], 403);
        // }

        $persona = Persona::where('identificacion', $cedula)->first();

        if (!$persona) {
            return response()->json(['message' => 'Persona no encontrada'], 404);
        }

        return response()->json($persona);
    }

    public function update(Request $request, Persona $persona)
    {
        // Solo permitir si el usuario tiene rol_id = 1
        if ($request->user()->rol_id !== 1) {
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

    public function destroy(Persona $persona)
    {
        // Solo permitir si el usuario tiene rol_id = 1
        if ($persona->estado_borrado) {
            return response()->json(['message' => 'La persona ya está eliminada'], 404);
        }

        if (auth()->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para eliminar personas.'], 403);
        }

        $persona->estado_borrado = true;
        $persona->borrado_en = Carbon::now();
        $persona->save();

        return response()->json(['message' => 'Persona eliminada correctamente'], 200);
    }

}
