<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class EventoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $eventos = Evento::with('categorium')->get()->map(function ($evento) {
            $eventoArray = $evento->toArray();
            $eventoArray['categoria'] = $evento->categorium ? $evento->categorium->nombre : null;
            unset($eventoArray['categoria_id'], $eventoArray['categorium']);
            return $eventoArray;
        });
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
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'capacidad' => 'nullable|integer|min:1',
            'espacio' => 'nullable|string|max:30',
            'modalidad' => 'required|string|max:10',
            'sede_id' => 'nullable|integer',
            'categoria_id' => 'nullable|integer',
            'hayEquipos' => 'nullable|integer|min:0',
            'hayFormulario' => 'nullable|boolean',
            'estado' => 'required|string|max:8',
            'inscripcionesAbiertas' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $evento = Evento::create([
            'creado_en' => Carbon::now(),
            'actualizado_en' => Carbon::now(),
            'estado_borrado' => false,
            'borrado_en' => null,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'capacidad' => $request->capacidad,
            'espacio' => $request->espacio,
            'modalidad' => $request->modalidad,
            'sede_id' => $request->sede_id,
            'categoria_id' => $request->categoria_id,
            'hayEquipos' => $request->hayEquipos ?? 0,
            'hayFormulario' => $request->hayFormulario ?? 0,
            'estado' => $request->estado,
            'inscripcionesAbiertas' => $request->inscripcionesAbiertas ?? 0,
        ]);

        return response()->json($evento, 201);
    }

    public function show(Evento $evento)
    {
        $evento->load('categorium');
        $eventoArray = $evento->toArray();
        $eventoArray['categoria'] = $evento->categorium ? $evento->categorium->nombre : null;
        unset($eventoArray['categoria_id'], $eventoArray['categorium']);
        return response()->json($eventoArray);
    }

    public function update(Request $request, Evento $evento)
    {
        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para actualizar eventos.'], 403);
        }

        $persona = Persona::where('users_id', $request->user()->id)->first();

        if (!$persona /*|| $evento->autor != $persona->id*/) {
            return response()->json(['message' => 'No tienes permiso para actualizar este evento.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'capacidad' => 'nullable|integer|min:1',
            'espacio' => 'nullable|string|max:30',
            'modalidad' => 'nullable|string|max:10',
            'sede_id' => 'nullable|integer',
            'categoria_id' => 'nullable|integer',
            'hayEquipos' => 'nullable|integer|min:0',
            'hayFormulario' => 'nullable|boolean',
            'estado' => 'nullable|string|max:8',
            'inscripcionesAbiertas' => 'nullable|boolean',
            'estado_borrado' => 'nullable|boolean',
            'borrado_en' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $evento->fill($request->only([
            'nombre',
            'descripcion',
            'fecha_inicio',
            'fecha_fin',
            'capacidad',
            'espacio',
            'modalidad',
            'sede_id',
            'categoria_id',
            'hayEquipos',
            'hayFormulario',
            'estado',
            'inscripcionesAbiertas',
            'estado_borrado',
            'borrado_en',
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

        if (!$persona /*|| $evento->autor != $persona->id*/) {
            return response()->json(['message' => 'No tienes permiso para eliminar este evento.'], 403);
        }

        $evento->estado_borrado = true;
        $evento->borrado_en = Carbon::now();
        $evento->actualizado_en = Carbon::now();
        $evento->save();

        return response()->json(['message' => 'Evento marcado como borrado lógicamente.'], 200);
    }
}
