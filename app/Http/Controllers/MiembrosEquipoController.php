<?php

namespace App\Http\Controllers;

use App\Models\MiembrosEquipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Http\Controllers\Controller;


class MiembrosEquipoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $miembrosEquipo = MiembrosEquipo::all();
        return response()->json($miembrosEquipo);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8 && $request->user()->rol_id !== 2) {
            return response()->json(['message' => 'No tienes permiso para agregar miembros de equipo.'], 403);
        }
        $validator = \Validator::make($request->all(), [
            'equipo_id' => 'required|integer|exists:equipos,id',
            'persona_id' => 'required|integer|exists:personas,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $miembroEquipo = MiembrosEquipo::create([
            'creado_en' => now(),
            'actualizado_en' => now(),
            'equipo_id' => $request->equipo_id,
            'persona_id' => $request->persona_id,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin
        ]);
        return response()->json("Miembro del equipo agregado correctamente", 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(MiembrosEquipo $miembrosEquipo)
    {
        return response()->json($miembrosEquipo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MiembrosEquipo $miembrosEquipo)
    {
        if($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para actualizar miembros de equipo.'], 403);
        }
        $validator = \Validator::make($request->all(), [
            'equipo_id' => 'required|integer|exists:equipos,id',
            'persona_id' => 'required|integer|exists:personas,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $miembrosEquipo->update([
            'actualizado_en' => now(),
            'equipo_id' => $request->equipo_id,
            'persona_id' => $request->persona_id,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin
        ]);
        return response()->json($miembrosEquipo, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MiembrosEquipo $miembrosEquipo)
    {
        if(request()->user()->rol_id !== 1 && request()->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para eliminar miembros de equipo.'], 403);
        }
        $miembrosEquipo->delete();
        return response()->json(['message' => 'Miembro del equipo eliminado correctamente'], 200);
    }
}
