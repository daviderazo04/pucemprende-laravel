<?php

namespace App\Http\Controllers;

use App\Models\ResultadoProcesoEvaluacion;
use App\Models\ProcesosEvaluacion;
use App\Models\PlantillasEvaluacion;
use App\Models\Equipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ResultadoProcesoEvaluacionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Listado
     */
    public function index(Request $request)
    {
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver los resultados de proceso.'], 403);
        }

        $resultados = ResultadoProcesoEvaluacion::with(['proceso', 'equipo'])->get();
        return response()->json($resultados);
    }

    /**
     * Crear o recalcular (upsert) un resultado de proceso para un equipo
     * Calcula la suma de todas las plantillas del proceso para ese equipo.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'proceso_id' => 'required|integer',
            'equipo_id'  => 'required|exists:equipos,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $proceso = ProcesosEvaluacion::find($request->proceso_id);
        if (!$proceso) {
            return response()->json(['message' => 'Proceso no encontrado.'], 404);
        }

        $resultado = DB::transaction(function () use ($request) {
            $total = $this->calcularTotalProceso($request->proceso_id, $request->equipo_id);

            // Upsert
            $up = ResultadoProcesoEvaluacion::updateOrCreate(
                [
                    'proceso_id' => $request->proceso_id,
                    'equipo_id'  => $request->equipo_id,
                ],
                [
                    'total'        => $total,
                    'actualizado_en' => Carbon::now(),
                    'creado_en'      => DB::raw('IFNULL(creado_en, NOW())'),
                ]
            );

            return $up->fresh(['proceso', 'equipo']);
        });

        return response()->json($resultado, 201);
    }

    /**
     * Mostrar uno
     */
    public function show(Request $request, $id)
    {
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver este resultado.'], 403);
        }

        $resultado = ResultadoProcesoEvaluacion::with(['proceso', 'equipo'])->find($id);
        if (!$resultado) {
            return response()->json(['message' => 'Resultado de proceso no encontrado.'], 404);
        }

        return response()->json($resultado);
    }

    /**
     * Obtener por proceso y equipo
     */
    public function showByProcesoEquipo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'proceso_id' => 'required|integer',
            'equipo_id'  => 'required|exists:equipos,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $resultado = ResultadoProcesoEvaluacion::with(['proceso', 'equipo'])
            ->where('proceso_id', $request->proceso_id)
            ->where('equipo_id', $request->equipo_id)
            ->first();

        if (!$resultado) {
            return response()->json(['message' => 'No existe resultado para esa combinación.'], 404);
        }

        return response()->json($resultado);
    }

    /**
     * Ranking por proceso (ordena por total desc)
     */
    public function showByProceso(Request $request, $procesoId)
    {
        if ($request->user()->rol_id !== 8 && $request->user()->rol_id !== 2) {
            return response()->json(['message' => 'No tienes permiso para ver el ranking.'], 403);
        }

        $proceso = ProcesosEvaluacion::find($procesoId);
        if (!$proceso) {
            return response()->json(['message' => 'Proceso no encontrado.'], 404);
        }

        $resultados = ResultadoProcesoEvaluacion::with(['equipo'])
            ->where('proceso_id', $procesoId)
            ->orderByDesc('total')
            ->get();

        $resultadosConRanking = $resultados->values()->map(function ($r, $i) {
            $r->posicion = $i + 1;
            return $r;
        });

        return response()->json([
            'proceso_id'    => $procesoId,
            'total_equipos' => $resultados->count(),
            'resultados'    => $resultadosConRanking,
        ]);
    }

    /**
     * Todos los resultados de un equipo (en todos los procesos)
     */
    public function showByEquipo(Request $request, $equipoId)
    {
        if ($request->user()->rol_id !== 8 && $request->user()->rol_id !== 2) {
            return response()->json(['message' => 'No tienes permiso para ver estos resultados.'], 403);
        }

        $equipo = Equipo::find($equipoId);
        if (!$equipo) {
            return response()->json(['message' => 'Equipo no encontrado.'], 404);
        }

        $resultados = ResultadoProcesoEvaluacion::with(['proceso'])
            ->where('equipo_id', $equipoId)
            ->orderBy('proceso_id')
            ->get();

        return response()->json([
            'equipo_id'  => $equipoId,
            'resultados' => $resultados
        ]);
    }

    /**
     * Actualizar (o mover) por claves compuestas
     */
    public function update(Request $request, $proceso_id, $equipo_id)
    {

        $resultado = ResultadoProcesoEvaluacion::where('proceso_id', $proceso_id)
            ->where('equipo_id', $equipo_id)
            ->first();

        if (!$resultado) {
            return response()->json(['message' => 'Resultado de proceso no encontrado.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'proceso_id' => 'sometimes|required|integer',
            'equipo_id'  => 'sometimes|required|exists:equipos,id',
            'recalcular' => 'sometimes|boolean',
            'total'      => 'sometimes|numeric|min:0',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $newProcesoId = $request->input('proceso_id', $resultado->proceso_id);
        $newEquipoId  = $request->input('equipo_id', $resultado->equipo_id);

        if (!ProcesosEvaluacion::find($newProcesoId)) {
            return response()->json(['message' => 'Proceso no encontrado.'], 404);
        }

        // Si piden recalcular, usar el SP; de lo contrario se permite actualizar "total" directo.
        $total = $request->has('recalcular') && $request->boolean('recalcular')
            ? $this->calcularTotalProceso($newProcesoId, $newEquipoId)
            : $request->input('total', $resultado->total);

        $updated = DB::transaction(function () use ($resultado, $newProcesoId, $newEquipoId, $total) {
            // Si cambian llaves, hacer upsert y eliminar el anterior para evitar duplicados
            if ($resultado->proceso_id !== $newProcesoId || $resultado->equipo_id !== $newEquipoId) {
                $nuevo = ResultadoProcesoEvaluacion::updateOrCreate(
                    ['proceso_id' => $newProcesoId, 'equipo_id' => $newEquipoId],
                    ['total' => $total, 'actualizado_en' => Carbon::now()]
                );
                $resultado->delete();
                return $nuevo->fresh(['proceso','equipo']);
            }

            $resultado->update([
                'total'         => $total,
                'actualizado_en'=> Carbon::now(),
            ]);

            return $resultado->fresh(['proceso','equipo']);
        });

        return response()->json($updated);
    }

    /**
     * Eliminar
     */
    public function destroy(Request $request, $id)
    {
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para eliminar.'], 403);
        }

        $resultado = ResultadoProcesoEvaluacion::find($id);
        if (!$resultado) {
            return response()->json(['message' => 'Resultado de proceso no encontrado.'], 404);
        }

        $resultado->delete();
        return response()->json(['message' => 'Resultado de proceso eliminado correctamente.']);
    }

    /**
     * Helper: llama al SP y devuelve el total del proceso para el equipo
     */
    private function calcularTotalProceso(int $procesoId, int $equipoId): float
    {
        $sp = DB::select("CALL sp_calcular_resultado_proceso_evaluacion(?,?)", [$procesoId, $equipoId]);
        $row = $sp[0] ?? null;
        return (float) ($row->total ?? $row->resultado ?? 0);
    }

    // Si necesitas filtrar por evento, se puede añadir aquí según tu modelo de eventos.
}
