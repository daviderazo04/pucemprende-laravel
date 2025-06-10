<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

class ProyectoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $proyecto = Proyecto::all();
        return response()->json($proyecto);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        print($request->user());
        // Solo permitir si el usuario tiene rol_id = 1
        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para crear eventos.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'equipo_id' => 'required|integer|exists:equipos,id',
            'titulo' => 'required|string|max:50',
            'descripcion' => 'required|string|max:1000',
            'estado' => 'required|string|max:20',
            'fecha_inicio' => 'required|date_format:Y-m-d',
            'fecha_fin' => 'required|date_format:Y-m-d|after_or_equal:fecha_inicio',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $proyecto = Proyecto::create([
            'creado_en' => Carbon::now(),
            'actualizado_en' => Carbon::now(),
            'equipo_id' => $request->equipo_id,
            'titulo' => $request->titulo,
            'descripcion' => $request->descripcion,
            'estado' => $request->estado,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
        ]);

        return response()->json($proyecto, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        //Solo admin puede ver las sedes

        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para acceder a este elemento'], 403);
        }
        
        // Buscar la sede por id
        $proyecto = Proyecto::find($id);

        if (!$proyecto) {
            return response()->json(['message' => 'Proyecto no encontrado'], 404);
        }

        return response()->json($proyecto);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Proyecto $proyecto)
    {
        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para crear eventos.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'equipo_id' => 'nullable|integer|exists:equipos,id',
            'titulo' => 'required|string|max:50',
            'descripcion' => 'required|string|max:1000',
            'estado' => 'required|string|max:20',
            'fecha_inicio' => 'required|date_format:Y-m-d',
            'fecha_fin' => 'required|date_format:Y-m-d|after_or_equal:fecha_inicio',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $proyecto->fill($request->only([
            'equipo_id',
            'titulo',
            'descripcion',
            'estado',
            'fecha_inicio',
            'fecha_fin'
        ]));

        $proyecto->actualizado_en = Carbon::now();
        $proyecto->save();

        return response()->json($proyecto);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Proyecto $proyecto)
    {
        if (request()->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para eliminar proyectos.'], 403);
        }

        $proyecto->estado = "BORRADO";
        $proyecto->actualizado_en = Carbon::now();
        $proyecto->save();

        return response()->json(['message' => 'Proyecto marcado como borrado lógicamente.'], 200);
    }
}
