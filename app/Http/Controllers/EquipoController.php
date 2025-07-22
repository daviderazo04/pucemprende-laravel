<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
class EquipoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $equipos = Equipo::all();
        return response()->json($equipos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if($request->user()->rol_id!=1 && $request->user()->rol_id!=8){
            return response()->json(['message'=>'No tienes permiso para crear equipos'],403);
        }
        $validator = Validator::make($request->all(),[
            'nombre'=> 'required|string|max:50',
            'evento_id'=> 'required|integer|exists:eventos,id',
            'ranking'=> 'nullable|integer',
        ]);

        if($validator->fails()){
            return response()->json(['errors'=> $validator->errors()],422);
        }
        $equipos = Equipo::create([
            'creado_en' => Carbon::now(),
            'actualizado_en' => Carbon::now(),
            'estado_borrado' => false,
            'borrado_en' => null,
            'nombre'=>$request->nombre,
            'evento_id'=>$request->evento_id,
            'ranking'=>$request->ranking,

        ]);
        return response()->json($equipos,201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Equipo $equipo)
    {
        return response()->json($equipo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Equipo $equipo)
    {
        if ($request->user()->rol_id != 1 && $request->user()->rol_id != 8) {
            return response()->json(['message' => 'No tienes los permisos suficientes'], 403);
        }
         $validator = Validator::make($request->all(), [
            'nombre' => 'nullable|string|max:50',
            'evento_id'=> 'nullable|integer|exists:eventos,id',
            'ranking'=> 'nullable|integer',
            'estado_borrado' => 'nullable|boolean',
        ]);
         if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $equipo->fill($request->only([
            'nombre',
            'evento_id',
            'ranking',
            'estado_borrado'
        ]));
        $equipo->actualizado_en = Carbon::now();
        $equipo->save();

        return response()->json($equipo);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Equipo $equipo)
    {
         if (request()->user()->rol_id !== 1 && request()->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para eliminar equipos.'], 403);
        }

        $equipo->estado_borrado = true;
        $equipo->creado_en = Carbon::now();
        $equipo->borrado_en = Carbon::now();
        $equipo->actualizado_en = Carbon::now();
        $equipo->save();

        return response()->json(['message' => 'Equipo marcado como borrado lógicamente.'], 200);
    }
}
