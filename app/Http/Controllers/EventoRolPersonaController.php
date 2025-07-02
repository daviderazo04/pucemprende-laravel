<?php

namespace App\Http\Controllers;

use App\Models\EventoRolPersona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

class EventoRolPersonaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver este elemento.'], 403);
        }

        $persona = Persona::where('users_id', $request->user()->id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        // Verificar si el usuario tiene rol_id = 1 (administrador del sistema)
        // O si la persona es el autor del evento (rol_id = 1 para este evento en evento_rol_persona)
        $isSystemAdmin = ($request->user()->rol_id == 8);
        $isEventAuthor = EventoRolPersona::where('evento_id', $request->evento_id)
                                        ->where('persona_id', $persona->id)
                                        ->where('rol_id', 1) 
                                        ->exists();

        if (!$isSystemAdmin && !$isEventAuthor) {
            return response()->json(['message' => 'No tienes permiso para ver este elemento.'], 403);
        }

        $eventoRolPersona = EventoRolPersona::all();
        return response()->json($eventoRolPersona);
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Solo permitir si el usuario tiene rol_id = 1 (si es autor) o rol_id = 8 (administrador del sistema)
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para crear este elemento.'], 403);
        }

        $persona = Persona::where('users_id', $request->user()->id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        // Verificar si el usuario tiene rol_id = 1 (administrador del sistema)
        // O si la persona es el autor del evento (rol_id = 1 para este evento en evento_rol_persona)
        $isSystemAdmin = ($request->user()->rol_id == 8);
        $isEventAuthor = EventoRolPersona::where('evento_id', $request->evento_id)
                                        ->where('persona_id', $persona->id)
                                        ->where('rol_id', 1) 
                                        ->exists();

        if (!$isSystemAdmin && !$isEventAuthor) {
            return response()->json(['message' => 'No tienes permiso para crear este elemento.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'evento_id' => 'required|integer|exists:eventos,id',
            'rol_id' => 'required|integer|exists:rolEvento,id',
            'persona_id' => 'required|integer|exists:personas,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $eventoRolPersona = EventoRolPersona::create([
            'evento_id' => $request->evento_id,
            'rol_id' => $request->rol_id,
            'persona_id' => $request->persona_id,
        ]);

        return response()->json($eventoRolPersona, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, EventoRolPersona $eventoRolPersona)
    {
        // Solo Admin puede ver roles de personas en un evento
        // Solo permitir si el usuario tiene rol_id = 1 (si es autor) o rol_id = 8 (administrador del sistema)
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver este elemento.'], 403);
        }

        $persona = Persona::where('users_id', $request->user()->id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        // Verificar si el usuario tiene rol_id = 1 (administrador del sistema)
        // O si la persona es el autor del evento (rol_id = 1 para este evento en evento_rol_persona)
        $isSystemAdmin = ($request->user()->rol_id == 8);
        $isEventAuthor = EventoRolPersona::where('evento_id', $request->evento_id)
                                        ->where('persona_id', $persona->id)
                                        ->where('rol_id', 1) 
                                        ->exists();

        if (!$isSystemAdmin && !$isEventAuthor) {
            return response()->json(['message' => 'No tienes permiso para ver este elemento.'], 403);
        }
        
        if (!$eventoRolPersona) {
            return response()->json(['message' => 'Rol de persona de evento no encontrado'], 404);
        }

        return response()->json($eventoRolPersona);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EventoRolPersona $eventoRolPersona)
    {
        // Solo permitir si el usuario tiene rol_id = 1 (si es autor) o rol_id = 8 (administrador del sistema)
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para actualizar este elemento.'], 403);
        }

        $persona = Persona::where('users_id', $request->user()->id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        // Verificar si el usuario tiene rol_id = 1 (administrador del sistema)
        // O si la persona es el autor del evento (rol_id = 1 para este evento en evento_rol_persona)
        $isSystemAdmin = ($request->user()->rol_id == 8);
        $isEventAuthor = EventoRolPersona::where('evento_id', $request->evento_id)
                                        ->where('persona_id', $persona->id)
                                        ->where('rol_id', 1) 
                                        ->exists();

        if (!$isSystemAdmin && !$isEventAuthor) {
            return response()->json(['message' => 'No tienes permiso para actualizar este elemento.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'evento_id' => 'required|integer|exists:eventos,id',
            'rol_id' => 'required|integer|exists:rolEvento,id',
            'persona_id' => 'required|integer|exists:personas,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $eventoRolPersona->fill($request->only([
            'evento_id',
            'rol_id',
            'persona_id'
        ]));

        $eventoRolPersona->save();

        return response()->json($eventoRolPersona, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EventoRolPersona $eventoRolPersona)
    {
        // Solo permitir si el usuario tiene rol_id = 1 (si es autor) o rol_id = 8 (administrador del sistema)
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para eliminar un rol de persona en un evento.'], 403);
        }

        $persona = Persona::where('users_id', $request->user()->id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        // Verificar si el usuario tiene rol_id = 1 (administrador del sistema)
        // O si la persona es el autor del evento (rol_id = 1 para este evento en evento_rol_persona)
        $isSystemAdmin = ($request->user()->rol_id == 8);
        $isEventAuthor = EventoRolPersona::where('evento_id', $request->evento_id)
                                        ->where('persona_id', $persona->id)
                                        ->where('rol_id', 1) 
                                        ->exists();

        if (!$isSystemAdmin && !$isEventAuthor) {
            return response()->json(['message' => 'No tienes permiso para borrar este elemento.'], 403);
        }

        // Intenta eliminar el documento
        try {
            $eventoRolPersona->delete();
            return response()->json(['message' => 'Rol de persona de evento eliminado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar el rol de persona de evento.', 'error' => $e->getMessage()], 500);
        }
    }
}
