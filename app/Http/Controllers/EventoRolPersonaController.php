<?php

namespace App\Http\Controllers;

use App\Models\EventoRolPersona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\Persona;
use App\Models\Evento;

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

        // Verificar si el usuario tiene rol_id = 8 (administrador del sistema)
        // O si la persona es el autor del evento al que está queriando añadir un rol a una persona (rol_id = 1 para este evento en evento_rol_persona)
        $isSystemAdmin = ($request->user()->rol_id == 8);
        $isEventAuthor = EventoRolPersona::where('evento_id', $request->evento_id)
                                        ->where('persona_id', $persona->id)
                                        ->where('rol_id', 1)
                                        ->where('estado_borrado', false)
                                        ->exists();

        if (!$isSystemAdmin && !$isEventAuthor) {
            return response()->json(['message' => 'No tienes permiso para crear este elemento.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'evento_id' => 'required|integer|exists:eventos,id',
            'rol_id' => 'required|integer|exists:rolEvento,id',
            'persona_id' => 'required|integer|exists:personas,id',
            'estado_borrado' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $eventoRolPersona = EventoRolPersona::create([
            'evento_id' => $request->evento_id,
            'rol_id' => $request->rol_id,
            'persona_id' => $request->persona_id,
            'estado_borrado' => $request->estado_borrado,
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

        $evento = Evento::find($request->evento_id);

        // Verificar si el usuario tiene rol_id = 1 (administrador del sistema)
        // O si la persona es el autor del evento (rol_id = 1 para este evento en evento_rol_persona)
        $isSystemAdmin = ($request->user()->rol_id == 8);
        $isEventAuthor = EventoRolPersona::where('evento_id', $request->evento_id)
                                        ->where('persona_id', $persona->id)
                                        ->where('rol_id', 1)
                                        ->where('estado_borrado', false)
                                        ->exists();

        if (!$isSystemAdmin && !$isEventAuthor) {
            return response()->json(['message' => 'No tienes permiso para actualizar este elemento, no eres autor del evento o administrador general.'], 403);
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

    public function indexConDetalles(Request $request)
    {
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver este elemento.'], 403);
        }

        $persona = Persona::where('users_id', $request->user()->id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        $eventosRoles = EventoRolPersona::with([
            'rolEvento:id,nombre',         // Asegúrate de que esta relación esté definida en el modelo EventoRolPersona
            'evento:id,nombre',            // Relación con evento
            'persona:id,nombre,apellido,identificacion,alumni' // Relación con persona
        ])->get();

        $resultado = $eventosRoles->map(function ($item) {
            return [
                'id' => $item->id,
                'evento' => $item->evento->nombre ?? null,
                'rol' => $item->rolEvento->nombre ?? null,
                'persona' => [
                    'nombre' => $item->persona->nombre ?? null,
                    'apellido' => $item->persona->apellido ?? null,
                    'identificacion' => $item->persona->identificacion ?? null,
                    'alumni' => $item->persona->alumni ?? null,
                ],
                'estado_borrado' => $item->estado_borrado,
            ];
        });

        return response()->json($resultado);
    }

    public function showConDetalles(Request $request, $id)
    {
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver este elemento.'], 403);
        }

        $persona = Persona::where('users_id', $request->user()->id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        $registro = EventoRolPersona::with([
            'rolEvento:id,nombre',
            'evento:id,nombre',
            'persona:id,nombre,apellido,identificacion,alumni'
        ])->find($id);

        if (!$registro) {
            return response()->json(['message' => 'No se encontró el registro con ID ' . $id], 404);
        }

        $resultado = [
            'id' => $registro->id,
            'evento' => $registro->evento->nombre ?? null,
            'rol' => $registro->rolEvento->nombre ?? null,
            'persona' => [
                'nombre' => $registro->persona->nombre ?? null,
                'apellido' => $registro->persona->apellido ?? null,
                'identificacion' => $registro->persona->identificacion ?? null,
                'alumni' => $registro->persona->alumni ?? null,
            ],
            'estado_borrado' => $registro->estado_borrado,
        ];

        return response()->json($resultado);
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, EventoRolPersona $eventoRolPersona)
    {
        // Solo permitir si el usuario tiene rol_id = 1 (si es autor) o rol_id = 8 (administrador del sistema)
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para eliminar un rol de persona en un evento.'], 403);
        }

        $persona = Persona::where('users_id', $request->user()->id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        $evento_id = $eventoRolPersona->evento_id;

        // Verificar si el usuario tiene rol_id = 1 (administrador del sistema)
        // O si la persona es el autor del evento (rol_id = 1 para este evento en evento_rol_persona)
        $isSystemAdmin = ($request->user()->rol_id == 8);
        $isEventAuthor = EventoRolPersona::where('evento_id', $evento_id)
                                        ->where('persona_id', $persona->id)
                                        ->where('rol_id', 1)
                                        ->where('estado_borrado', false)
                                        ->exists();

        if (!$isSystemAdmin && !$isEventAuthor) {
            return response()->json(['message' => 'No tienes permiso para borrar este elemento.'], 403);
        }

        // Intenta eliminar el documento
        try {
            $eventoRolPersona->estado_borrado = true;
            $eventoRolPersona->save();
            return response()->json(['message' => 'Rol de persona de evento marcado como eliminado.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar el rol de persona de evento.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Vuelve a activar el usuario.
     */
    public function activar(Request $request, EventoRolPersona $eventoRolPersona)
    {
        // Solo permitir si el usuario tiene rol_id = 1 (si es autor) o rol_id = 8 (administrador del sistema)
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para activar un rol de persona en un evento.'], 403);
        }

        $persona = Persona::where('users_id', $request->user()->id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        $evento_id = $eventoRolPersona->evento_id;

        // Verificar si el usuario tiene rol_id = 1 (administrador del sistema)
        // O si la persona es el autor del evento (rol_id = 1 para este evento en evento_rol_persona)
        $isSystemAdmin = ($request->user()->rol_id == 8);
        $isEventAuthor = EventoRolPersona::where('evento_id', $evento_id)
                                        ->where('persona_id', $persona->id)
                                        ->where('rol_id', 1)
                                        ->where('estado_borrado', false)
                                        ->exists();

        if (!$isSystemAdmin && !$isEventAuthor) {
            return response()->json(['message' => 'No tienes permiso para activar este usuario.'], 403);
        }

        // Intenta eliminar el documento
        try {
            $eventoRolPersona->estado_borrado = false;
            $eventoRolPersona->save();
            return response()->json(['message' => 'Rol de persona de evento activado.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al activar el rol de persona de evento.', 'error' => $e->getMessage()], 500);
        }
    }

    public function InscribirEvento(Request $request)
    {
        $persona = Persona::where('users_id', $request->user()->id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        // Validar que el evento exista
        $evento = Evento::find($request->evento_id);
        if (!$evento) {
            return response()->json(['error' => 'Evento no encontrado'], 404);
        }

        // Verificar si ya está inscrito en el evento
        $inscrito = EventoRolPersona::where('evento_id', $evento->id)
            ->where('persona_id', $persona->id)
            ->exists();

        if ($inscrito) {
            return response()->json(['message' => 'Ya estás inscrito en este evento.'], 409);
        }

        // Validar los campos necesarios
        $validator = Validator::make($request->all(), [
            'evento_id' => 'required|integer|exists:eventos,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $eventoRolPersona = EventoRolPersona::create([
            'evento_id' => $request->evento_id,
            'rol_id' => 4,                      // Asignar rol_id 4 para inscripción (miembro)
            'persona_id' => $persona->id,
            'estado_borrado' => false, // No marcado como borrado
        ]);

        return response()->json($eventoRolPersona, 201);
    }

}
