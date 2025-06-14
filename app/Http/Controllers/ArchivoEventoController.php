<?php

namespace App\Http\Controllers;

use App\Models\ArchivoEvento;
use Illuminate\Http\Request;

class ArchivoEventoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $archivosEventos = ArchivoEvento::all();
        return response()->json($archivosEventos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Solo permitir si el usuario tiene rol_id = 1
        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para crear archivos de eventos.'], 403);
        }

        $validator = \Validator::make($request->all(), [
            'archivo_id' => 'required|integer|exists:archivo,id',
            'evento_id' => 'required|integer|exists:eventos,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $archivoEvento = ArchivoEvento::create([
            'archivo_id' => $request->archivo_id,
            'evento_id' => $request->evento_id
        ]);

        return response()->json($archivoEvento, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ArchivoEvento $archivoEvento)
    {
        return response()->json($archivoEvento);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ArchivoEvento $archivoEvento)
    {
        // Solo permitir si el usuario tiene rol_id = 1
        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para actualizar archivos de eventos.'], 403);
        }

        $validator = \Validator::make($request->all(), [
            'archivo_id' => 'required|integer|exists:archivo,id',
            'evento_id' => 'required|integer|exists:eventos,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $archivoEvento->archivo_id = $request->archivo_id;
        $archivoEvento->evento_id = $request->evento_id;
        $archivoEvento->save();

        return response()->json($archivoEvento);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ArchivoEvento $archivoEvento)
    {
        // Solo permitir si el usuario tiene rol_id = 1
        if (request()->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para eliminar archivos de eventos.'], 403);
        }

        $archivoEvento->delete();
        return response()->json(['message' => 'Archivo de evento eliminado correctamente.'], 200);
    }
}
