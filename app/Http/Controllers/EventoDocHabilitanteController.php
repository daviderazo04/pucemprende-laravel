<?php

namespace App\Http\Controllers;

use App\Models\EventoDocHabilitante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

class EventoDocHabilitanteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $eventosDocHabilitante = EventoDocHabilitante::all();
        return response()->json($eventosDocHabilitante);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
         print($request->user());
        // Solo permitir si el usuario tiene rol_id = 1
        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para ligar un documento habilitante con un evento.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'evento_id' => 'required|integer|exists:eventos,id',
            'dochab_id' => 'required|integer|exists:doc_habilitantes,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $eventoDocHabilitante = EventoDocHabilitante::create([
            'evento_id' => $request->evento_id,
            'dochab_id' => $request->dochab_id,
        ]);

        return response()->json($eventoDocHabilitante, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($evento_id, $dochab_id)
    {
        // Solo permitir si el usuario tiene rol_id = 1
        if (request()->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para ver este elemento.'], 403);
        }

        // Buscar la relación utilizando ambos identificadores
        $eventoDocHabilitante = EventoDocHabilitante::where('evento_id', $evento_id)->where('dochab_id', $dochab_id)->first();

        if (!$eventoDocHabilitante) {
            return response()->json(['message' => 'Relación no encontrada'], 404);
        }

        return response()->json($eventoDocHabilitante);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $evento_id, $dochab_id)
    {
        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para ligar un documento habilitante con un evento.'], 403);
        }

        // Validar que ambos IDs existan en sus respectivas tablas
        $validator = Validator::make($request->all(), [
            'evento_id' => 'required|integer|exists:eventos,id',
            'dochab_id' => 'required|integer|exists:doc_habilitantes,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Buscar la relación utilizando ambos identificadores
        $eventoDocHabilitante = EventoDocHabilitante::where('evento_id', $evento_id)->where('dochab_id', $dochab_id)->first();

        if (!$eventoDocHabilitante) {
            return response()->json(['message' => 'Relación no encontrada'], 404);
        }

        // Actualizar la relación si es necesario
        $eventoDocHabilitante->evento_id = $request->evento_id;
        $eventoDocHabilitante->dochab_id = $request->dochab_id;

        $eventoDocHabilitante->save();

        return response()->json($eventoDocHabilitante);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($evento_id, $dochab_id)
    {
        // Solo permitir si el usuario tiene rol_id = 1
        if (request()->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para eliminar este elemento.'], 403);
        }

        // Buscar la relación a eliminar
        $eventoDocHabilitante = EventoDocHabilitante::where('evento_id', $evento_id)->where('dochab_id', $dochab_id)->first();

        if (!$eventoDocHabilitante) {
            return response()->json(['message' => 'Relación no encontrada'], 404);
        }

        // Intentar eliminar el registro
        try {
            $eventoDocHabilitante->delete();
            return response()->json(['message' => 'Documento eliminado del evento correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar el documento del evento.', 'error' => $e->getMessage()], 500);
        }
    }

}
