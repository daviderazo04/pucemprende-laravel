<?php

namespace App\Http\Controllers;

use App\Models\DocHabilitante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

class DocHabilitanteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para acceder a este elemento'], 403);
        }

        $docHabilitante = DocHabilitante::all();
        return response()->json($docHabilitante);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        print($request->user());
        // Solo permitir si el usuario tiene rol_id = 1
        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para añadir documentos habilitantes a un evento.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:30',
            'formato' => 'required|string|max:6',
            'evento_id' => 'nullable|integer|exists:eventos,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $docHabilitante = DocHabilitante::create([
            'nombre' => $request->nombre,
            'formato' => $request->formato,
            'evento_id' => $request->evento_id,
        ]);

        return response()->json($docHabilitante, 201);

    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, DocHabilitante $docHabilitante)
    {
        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para acceder a este elemento'], 403);
        }
        return response()->json($docHabilitante);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DocHabilitante $docHabilitante)
    {
        print($request->user());
        // Solo permitir si el usuario tiene rol_id = 1
        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para editar documentos habilitantes a un evento.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:30',
            'formato' => 'required|string|max:6',
            'evento_id' => 'nullable|integer|exists:eventos,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $docHabilitante->fill($request->only([
            'nombre',
            'formato',
            'evento_id'
        ]));
        
        $docHabilitante->save();

        return response()->json($docHabilitante);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DocHabilitante $docHabilitante)
    {
        // Solo permitir si el usuario tiene rol_id = 1
        if (request()->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para eliminar documentos habilitantes.'], 403);
        }

        // Intenta eliminar el documento
        try {
            $docHabilitante->delete();
            return response()->json(['message' => 'Documento eliminado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar el documento.', 'error' => $e->getMessage()], 500);
        }

    }
}
