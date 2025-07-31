<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
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
    // funcion para obtener equipos por id de evento
    public function getEquiposByEventoId( Request $request,$eventoId){

        // Verificar si el usuario tiene permisos para ver los equipos admin o superadmin
        if($request->user()->rol_id != 1 && $request->user()->rol_id != 8){
            return response()->json(['message'=>'No tienes permiso para ver equipos'],403);
        }

        // Llamar al procedimiento almacenado que devuelve los equipos del evento con sus miembros
        $resultados = DB::select('CALL sp_buscar_equipo_por_evento(?)', [$eventoId]);

        // Verificar si no se encontraron resultados
        if (empty($resultados)) {
            return response()->json(['message' => 'No se encontraron equipos para el evento especificado.'], 404);
        }

        // Agrupar los resultados por ID de equipo para organizar los datos por equipo
        $equiposAgrupados = collect($resultados)
            ->groupBy('id') // Agrupa todos los resultados que tienen el mismo ID de equipo
            ->map(function($grupo) {
                $primerElemento = $grupo->first(); // Toma el primer elemento del grupo para usar sus datos generales del equipo

                return [
                    'id' => $primerElemento->id, // ID del equipo
                    'nombre_equipo' => $primerElemento->nombre_equipo, // Nombre del equipo
                    'evento_id' => $primerElemento->evento_id, // ID del evento
                    'nombre_evento' => $primerElemento->evento, // Nombre del evento
                    'ranking' => $primerElemento->ranking, // Ranking del equipo
                    'estado_borrado' => $primerElemento->estado_borrado, // Estado de borrado lógico
                    'creado_en' => $primerElemento->creado_en, // Fecha de creación
                    'actualizado_en' => $primerElemento->actualizado_en, // Fecha de última actualización

                    // Listado de miembros del equipo, filtrando nulos y eliminando duplicados
                    'miembros' => $grupo->map(function($item) {
                        return $item->miembro; // Extrae el nombre del miembro
                    })->filter(function($miembro) {
                        return !is_null($miembro) && $miembro !== ''; // Filtra miembros no válidos
                    })->unique()->values()->toArray() // Elimina duplicados y reordena el array
                ];
            })
            ->values(); // Reindexa el array resultante (para que no tenga claves asociativas por ID)

        // Retornar los equipos agrupados como respuesta en formato JSON
        return response()->json($equiposAgrupados);
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
