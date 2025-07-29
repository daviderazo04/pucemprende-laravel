<?php

namespace App\Http\Controllers;

use App\Models\PersonasGanadora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\EventoRolPersona;
use App\Models\Persona;
use App\Models\Evento;

class PersonasGanadorasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //Solo los administradores pueden acceder a las personas ganadoras
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para acceder a este elemento'], 403);
        }

        $persona = Persona::where('users_id', $request->user()->id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        $personasGanadora = PersonasGanadora::all();
        return response()->json($personasGanadora);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Solo permitir si el usuario tiene rol_id = 1
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para añadir este elemento.'], 403);
        }

        $persona = Persona::where('users_id', $request->user()->id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        // Verificar si el usuario tiene rol_id = 8 (administrador del sistema)
        // O si la persona es el autor del evento (rol_id = 1 para este evento en evento_rol_persona)
        $isSystemAdmin = ($request->user()->rol_id == 8);
        $isEventAuthor = EventoRolPersona::where('evento_id', $request->evento_id)
                                        ->where('persona_id', $persona->id)
                                        ->where('rol_id', 1)
                                        ->exists();

        if (!$isSystemAdmin && !$isEventAuthor) {
            return response()->json(['message' => 'No tienes permiso para ver las personas ganadoras de este evento.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'persona_id' => 'nullable|integer|exists:personas,id',
            'rank' => 'nullable|integer|min:1',
            'evento_id' => 'nullable|integer|exists:eventos,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $personasGanadora = PersonasGanadora::create([
            'creado_en' => Carbon::now(),
            'actualizado_en' => Carbon::now(),
            'persona_id' => $request->persona_id,
            'rank' => $request->rank,
            'evento_id' => $request->evento_id,
        ]);

        return response()->json($personasGanadora, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        //Solo admin puede ver las personas ganadoras
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para acceder a este elemento'], 403);
        }

        $persona = Persona::where('users_id', $request->user()->id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        // Verificar si el usuario tiene rol_id = 8 (administrador del sistema)
        // O si la persona es el autor del evento (rol_id = 1 para este evento en evento_rol_persona)
        $isSystemAdmin = ($request->user()->rol_id == 8);
        $isEventAuthor = EventoRolPersona::where('evento_id', $request->evento_id)
                                        ->where('persona_id', $persona->id)
                                        ->where('rol_id', 1)
                                        ->exists();

        if (!$isSystemAdmin && !$isEventAuthor) {
            return response()->json(['message' => 'No tienes permiso para ver las personas ganadoras de este evento.'], 403);
        }

        // Buscar la sede por id
        $personasGanadora = PersonasGanadora::find($id);

        if (!$personasGanadora) {
            return response()->json(['message' => 'Persona ganadora no encontrada'], 404);
        }

        return response()->json($personasGanadora);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PersonasGanadora $personasGanadora)
    {
        // Solo permitir si el usuario tiene rol_id = 1
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para añadir este elemento.'], 403);
        }

        $persona = Persona::where('users_id', $request->user()->id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        // Verificar si el usuario tiene rol_id = 8 (administrador del sistema)
        // O si la persona es el autor del evento (rol_id = 1 para este evento en evento_rol_persona)
        $isSystemAdmin = ($request->user()->rol_id == 8);
        $isEventAuthor = EventoRolPersona::where('evento_id', $request->evento_id)
                                        ->where('persona_id', $persona->id)
                                        ->where('rol_id', 1)
                                        ->exists();

        if (!$isSystemAdmin && !$isEventAuthor) {
            return response()->json(['message' => 'No tienes permiso para editar las personas ganadoras de este evento.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'persona_id' => 'nullable|integer|exists:personas,id',
            'rank' => 'nullable|integer|min:1',
            'evento_id' => 'nullable|integer|exists:eventos,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $personasGanadora->fill($request->only([
            'persona_id',
            'rank',
            'evento_id'
        ]));

        $personasGanadora->actualizado_en = Carbon::now();
        $personasGanadora->save();

        return response()->json($personasGanadora);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PersonasGanadora $personasGanadora)
    {
         // Admin puede borrar
        if (request()->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para eliminar este elemento.'], 403);
        }

        $persona = Persona::where('users_id', $request->user()->id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        // Verificar si el usuario tiene rol_id = 8 (administrador del sistema)
        // O si la persona es el autor del evento (rol_id = 1 para este evento en evento_rol_persona)
        $isSystemAdmin = ($request->user()->rol_id == 8);
        $isEventAuthor = EventoRolPersona::where('evento_id', $request->evento_id)
                                        ->where('persona_id', $persona->id)
                                        ->where('rol_id', 1)
                                        ->exists();

        if (!$isSystemAdmin && !$isEventAuthor) {
            return response()->json(['message' => 'No tienes permiso para eliminar las personas ganadoras de este evento.'], 403);
        }

        // Intenta eliminar el documento
        try {
            $personasGanadora->delete();
            return response()->json(['message' => 'Persona ganadora eliminada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar persona ganadora.', 'error' => $e->getMessage()], 500);
        }
    }
}
