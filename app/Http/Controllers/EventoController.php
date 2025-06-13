<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Http\Controllers\Controller; // <-- ASEGÚRATE DE QUE ESTA LÍNEA EXISTA

class EventoController extends Controller // <-- ASEGÚRATE DE QUE EXTIENDA Controller
{
    public function __construct()
    {
        // La línea 16 de tu stack trace se refiere a la primera llamada a ->middleware()
        $this->middleware('auth:sanctum');
        //$this->middleware('checkrole:1')->only(['store', 'update', 'destroy']); // Solo admins (rol_id 1) pueden crear
    }

    public function index()
    {
        $eventos = Evento::all();
        return response()->json($eventos);
    }

    public function store(Request $request)
    {
        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para crear eventos.'], 403);
        }

        $persona = Persona::where('users_id', auth()->id())->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'categoria_id' => 'nullable|integer|exists:categoria,id',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date_format:Y-m-d',
            'fecha_fin' => 'required|date_format:Y-m-d|after_or_equal:fecha_inicio',
            'capacidad' => 'nullable|integer|min:1',
            'sede_id' => 'nullable|integer|exists:sede,id',
            'espacio' => 'nullable|string|max:255',
            'modalidad' => ['required', 'string', Rule::in(['En Línea', 'Presencial'])],
            'hayEquipos' => 'nullable|int|min:0',
            'hayFormulario' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $evento = Evento::create([
            'creado_en' => Carbon::now(),
            'actualizado_en' => Carbon::now(),
            'estado_borrado' => false,
            'borrado_en' => null,
            'autor' => $persona->id, // <-- SOLO este
            'nombre' => $request->nombre,
            'categoria_id' => $request->categoria_id,
            'descripcion' => $request->descripcion,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'capacidad' => $request->capacidad,
            'sede_id' => $request->sede_id,
            'espacio' => $request->espacio,
            'modalidad' => $request->modalidad,
            'hayEquipos' => $request->hayEquipos,
            'hayFormulario' => $request->hayFormulario,
        ]);

        return response()->json($evento, 201);
    }

    public function show(Evento $evento)
    {
        return response()->json($evento);
    }

    public function update(Request $request, Evento $evento)
    {
        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para actualizar eventos.'], 403);
        }

        $persona = Persona::where('users_id', $request->user()->id)->first();

        // Comparar correctamente: id de persona autenticada vs autor del evento
        if (!$persona || $evento->autor != $persona->id) {
            return response()->json(['message' => 'No tienes permiso para actualizar este evento.'], 403);
        }
        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:255',
            'categoria_id' => 'nullable|integer|exists:categoria,id',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'nullable|date_format:Y-m-d',
            'fecha_fin' => 'nullable|date_format:Y-m-d|after_or_equal:fecha_inicio',
            'capacidad' => 'nullable|integer|min:1',
            'sede_id' => 'nullable|integer|exists:sede,id',
            'espacio' => 'nullable|string|max:255',
            'modalidad' => ['nullable', 'string', Rule::in(['En Línea', 'Presencial'])],
            'estado_borrado' => 'nullable|boolean',
            'borrado_en' => 'nullable|date_format:Y-m-d H:i:s',
            'hayEquipos' => 'nullable|int|min:0',
            'hayFormulario' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $evento->fill($request->only([
            'nombre',
            'categoria_id',
            'descripcion',
            'fecha_inicio',
            'fecha_fin',
            'capacidad',
            'sede_id',
            'espacio',
            'modalidad',
            'estado_borrado',
            'borrado_en',
            'hayEquipos',
            'hayFormulario'
        ]));
        $evento->actualizado_en = Carbon::now();
        $evento->save();

        return response()->json($evento);
    }

    public function destroy(Evento $evento)
    {
        if (request()->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para eliminar eventos.'], 403);
        }

        $persona = Persona::where('users_id', request()->user()->id)->first();

        // Comparar correctamente: id de persona autenticada vs autor del evento
        if (!$persona || $evento->autor != $persona->id) {
            return response()->json(['message' => 'No tienes permiso para eliminar este evento.'], 403);
        }

        $evento->estado_borrado = true;
        $evento->borrado_en = Carbon::now();
        $evento->actualizado_en = Carbon::now();
        $evento->save();

        return response()->json(['message' => 'Evento marcado como borrado lógicamente.'], 200);
    }
}