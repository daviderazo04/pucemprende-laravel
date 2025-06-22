<?php

namespace App\Http\Controllers;

use App\Models\Afiliacione;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
class AfiliacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $afiliaciones = Afiliacione::all();
        return response()->json($afiliaciones);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        
        // Solo permitir si el usuario tiene rol_id = 1
        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para crear afiliaciones.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'persona_id' => 'nullable|integer|exists:personas,id',
            'organizacion_id' => 'nullable|integer|exists:organizaciones,id',
            'rol_interno' => 'nullable|string|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $afiliaciones = Afiliacione::create([
            'creado_en' => Carbon::now(),
            'actualizado_en' => Carbon::now(),
            'estado_borrado' => false,
            'borrado_en' => null,
            'persona_id' => $request->persona_id,
            'organizacion_id' => $request->organizacion_id,
            'rol_interno' => $request->rol_interno,
        ]);

        return response()->json($afiliaciones->id, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Afiliacione $afiliacione)
    {
        return response()->json($afiliacione);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Afiliacione $afiliacione)
    {
        if ($request->user()->rol_id != 1) {
            return response()->json(['message' => 'No tienes los permisos suficientes'], 403);
        }
         $validator = Validator::make($request->all(), [
            'persona_id' => 'nullable|integer|exists:personas,id',
            'organizacion_id' => 'nullable|integer|exists:organizaciones,id',
            'rol_interno' => 'nullable|string|max:20',
            'estado_borrado' => 'nullable|boolean',
        ]);
         if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $afiliacione->fill($request->only([
            'persona_id',
            'organizacion_id',
            'rol_interno',
            'estado_borrado'
        ]));
        $afiliacione->actualizado_en = Carbon::now();
        $afiliacione->save();

        return response()->json($afiliacione);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Afiliacione $afiliacione)
    {
         // Solo permitir si el usuario tiene rol_id = 1
        if (request()->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para eliminar afiliaciones.'], 403);
        }

        $afiliacione->estado_borrado = true;
        $afiliacione->creado_en = Carbon::now();
        $afiliacione->borrado_en = Carbon::now();
        $afiliacione->actualizado_en = Carbon::now();
        $afiliacione->save();

        return response()->json(['message' => 'Afiliacion marcado como borrado lógicamente.'], 200);
    }
}
