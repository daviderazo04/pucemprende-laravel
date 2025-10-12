<?php

namespace App\Http\Controllers;

use App\Models\ResultadosEvaluacion;
use App\Models\Criterio;
use App\Models\RolEvento;
use App\Models\Persona;
use App\Models\RolesPlantilla;
use App\Models\ResultadoRubrica;              // NUEVO
use App\Models\ResultadoProcesoEvaluacion;    // NUEVO
use App\Models\PlantillasEvaluacion;          // NUEVO
use App\Models\ProcesosEvaluacion;            // NUEVO
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ResultadoEvaluacionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Mostrar una lista de todos los resultados de evaluación
     */
    public function index(Request $request)
    {
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver los resultados de evaluación.'], 403);
        }

        $resultados = ResultadosEvaluacion::with(['equipo', 'criterio', 'persona'])->get();
        return response()->json($resultados);
    }

    /**
     * Crear un nuevo resultado de evaluación
     */
    public function store(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'equipo_id' => 'required|exists:equipos,id',
            'criterio_id' => 'required|exists:criterios,id',
            'evaluador_id' => 'required|exists:personas,id',
            'puntaje' => 'required|numeric|min:0',
            'comentarios' => 'nullable|string',
            'rolEvento_id' => 'required|integer|exists:rolEvento,id'
        ]);

        $plantilla = Criterio::where('id', $request->criterio_id)
            ->first();
        $plantilla = $plantilla->plantilla_id;
        // Verificar si el rol tiene permiso para calificar en esta plantilla
        $califica = RolesPlantilla::where('rol_id', $request->rolEvento_id)
            ->where('plantilla_id', $plantilla)
            ->first();

        if (!$califica) {
            return response()->json(['message' => 'El rol de evento no tiene permiso para calificar en esta plantilla.'], 403);
        }

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verificar si ya existe una evaluación para esta combinación entre equipo, criterio y evaluador
        $existeEvaluacion = ResultadosEvaluacion::where('equipo_id', $request->equipo_id)
            ->where('criterio_id', $request->criterio_id)
            ->where('evaluador_id', $request->evaluador_id)
            ->first();

        if ($existeEvaluacion) {
            return response()->json(['message' => 'Ya existe una evaluación para este equipo con este criterio y evaluador con id ' . $request->evaluador_id . '.'], 409);
        }

        // Obtener el criterio de evaluación
        $criterio = Criterio::where('id', $request->criterio_id)
            ->first();
        if (!$criterio) {
            return response()->json(['error' => 'Criterio no encontrado'], 404);
        }
        // Calcular el puntaje ponderado
        $puntajeCalculado = ($request->puntaje * $criterio->peso)/5;

        // Guardar y recalcular agregados en una transacción
        $resultado = DB::transaction(function () use ($request, $puntajeCalculado) {
            $nuevo = ResultadosEvaluacion::create([
                'creado_en'      => Carbon::now(),
                'actualizado_en' => Carbon::now(),
                'equipo_id'      => $request->equipo_id,
                'criterio_id'    => $request->criterio_id,
                'evaluador_id'   => $request->evaluador_id,
                'puntaje'        => $puntajeCalculado,
                'comentarios'    => $request->comentarios,
                'evaluado_en'    => Carbon::now(),
            ]);

            // Recalcular agregados (rúbrica y proceso)
            $this->recalcularAcumulados($nuevo->equipo_id, $nuevo->criterio_id, $nuevo->evaluador_id);

            return $nuevo;
        });

        return response()->json($resultado, 201);
    }

    /**
     * Mostrar un resultado de evaluación específico
     */
    public function show(Request $request, $id)
    {
        // Solo permitir si el usuario es superadmin y adminEvento (2)
        if ($request->user()->rol_id !== 8 && $request->user()->rol_id !== 2) {
            return response()->json(['message' => 'No tienes permiso para ver este resultado de evaluación.'], 403);
        }

        $resultado = ResultadosEvaluacion::with(['equipo', 'criterio', 'persona'])->find($id);

        if (!$resultado) {
            return response()->json(['message' => 'Resultado de evaluación no encontrado.'], 404);
        }

        // Calcular la calificación original sobre 5
        $calificacionOriginal = 0;
        if ($resultado->criterio && $resultado->criterio->peso > 0) {
            $calificacionOriginal = ($resultado->puntaje * 5) / $resultado->criterio->peso;
        }

        // Agregar los campos calculados al resultado
        $resultado->puntaje_ponderado = $resultado->puntaje;
        $resultado->calificacion_sobre_5 = round($calificacionOriginal, 2);

        return response()->json([
            'id' => $resultado->id,
            'calificacion_sobre_5' => $resultado->calificacion_sobre_5,
            'peso_criterio' => $resultado->criterio->peso,
            'comentarios' => $resultado->comentarios,
        ]);
    }

    /**
     * Obtener todos los resultados de evaluación de un equipo específico
     */
    public function showByEquipo(Request $request, $equipoId, $personaId)
    {

        $resultados = ResultadosEvaluacion::with(['equipo', 'criterio', 'persona'])
            ->where('equipo_id', $equipoId)
            ->where('evaluador_id', $personaId)
            ->get();

        if ($resultados->isEmpty()) {
            return response()->json(['message' => 'No se encontraron evaluaciones para este equipo.'], 404);
        }

        // Calcular calificaciones originales y agregar información adicional
        $resultadosConCalificaciones = $resultados->map(function ($resultado) {
            // Calcular la calificación original sobre 5
            $calificacionOriginal = 0;
            if ($resultado->criterio && $resultado->criterio->peso > 0) {
                $calificacionOriginal = ($resultado->puntaje * 5) / $resultado->criterio->peso;
            }

            return [
                'id' => $resultado->id,
                'criterio_id' => $resultado->criterio->id,
                'calificacion' => round($calificacionOriginal, 2),
                'comentarios' => $resultado->comentarios,
            ];
        });

        return response()->json([
            'calificaciones' => $resultadosConCalificaciones
        ]);
    }

    /**
     * Actualizar un resultado de evaluación
     */
    public function update(Request $request, $id)
    {

        $resultado = ResultadosEvaluacion::find($id);
        if (!$resultado) {
            return response()->json(['message' => 'Resultado de evaluación no encontrado.'], 404);
        }

        // Guardar snapshot de la combinación anterior (por si cambian llaves)
        $oldEquipoId    = $resultado->equipo_id;
        $oldCriterioId  = $resultado->criterio_id;
        $oldEvaluadorId = $resultado->evaluador_id;

        $validator = Validator::make($request->all(), [
            'equipo_id'     => 'sometimes|required|exists:equipos,id',
            'criterio_id'   => 'sometimes|required|exists:criterios,id',
            'evaluador_id'  => 'sometimes|required|exists:personas,id',
            'puntaje'       => 'sometimes|required|numeric|min:0|max:5',
            'comentarios'   => 'nullable|string',
            'evaluado_en'   => 'nullable|date',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Obtener criterio final para ponderación
        $criterioId = $request->has('criterio_id') ? $request->criterio_id : $resultado->criterio_id;
        $criterio   = Criterio::find($criterioId);
        if (!$criterio) {
            return response()->json(['error' => 'Criterio no encontrado'], 404);
        }

        // Recalcular puntaje ponderado solo si viene 'puntaje'
        $puntajeCalculado = $resultado->puntaje;
        if ($request->has('puntaje')) {
            $puntajeCalculado = ($request->puntaje * $criterio->peso) / 5;
        }

        // Actualizar y recalcular agregados en transacción
        $actualizado = DB::transaction(function () use ($request, $resultado, $puntajeCalculado, $oldEquipoId, $oldCriterioId, $oldEvaluadorId) {
            $resultado->update([
                'actualizado_en' => Carbon::now(),
                'equipo_id'      => $request->input('equipo_id', $resultado->equipo_id),
                'criterio_id'    => $request->input('criterio_id', $resultado->criterio_id),
                'evaluador_id'   => $request->input('evaluador_id', $resultado->evaluador_id),
                'puntaje'        => $puntajeCalculado,
                'comentarios'    => $request->input('comentarios', $resultado->comentarios),
                'evaluado_en'    => $request->input('evaluado_en', $resultado->evaluado_en),
            ]);

            // Recalcular para la combinación NUEVA
            $this->recalcularAcumulados($resultado->equipo_id, $resultado->criterio_id, $resultado->evaluador_id);

            // Si cambió alguna de las llaves, también recalcular la combinación ANTERIOR
            if ($oldEquipoId !== $resultado->equipo_id
                || $oldCriterioId !== $resultado->criterio_id
                || $oldEvaluadorId !== $resultado->evaluador_id) {
                $this->recalcularAcumulados($oldEquipoId, $oldCriterioId, $oldEvaluadorId);
            }

            return $resultado->fresh(['equipo', 'criterio', 'persona']);
        });

        return response()->json($actualizado);
    }

    /**
     * Eliminar un resultado de evaluación
     */
    public function destroy(Request $request, $id)
    {
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para eliminar resultados de evaluación.'], 403);
        }

        $resultado = ResultadosEvaluacion::find($id);

        if (!$resultado) {
            return response()->json(['message' => 'Resultado de evaluación no encontrado.'], 404);
        }

        $resultado->delete();
        return response()->json(['message' => 'Resultado de evaluación eliminado correctamente.'], 200);
    }


    /**
     * Recalcula y hace upsert de:
     *  - ResultadoRubrica (persona/equipo/plantilla)
     *  - ResultadoProcesoEvaluacion (proceso/equipo)
     */
    private function recalcularAcumulados(int $equipoId, int $criterioId, int $evaluadorId): void
    {
        // Si algún id es nulo/no válido, salir silenciosamente
        if (!$equipoId || !$criterioId || !$evaluadorId) {
            return;
        }

        $criterio  = Criterio::find($criterioId);
        if (!$criterio) return;

        $plantillaId = (int) $criterio->plantilla_id;
        $plantilla   = PlantillasEvaluacion::find($plantillaId);
        if (!$plantilla) return;

        $procesoId = (int) $plantilla->proceso_id;

        // 1) Rúbrica por persona-plantilla-equipo
        $spRubrica = DB::select("CALL sp_calcular_total_plantilla(?,?,?)", [$equipoId, $plantillaId, $evaluadorId]);
        $totalRubrica = (float) (($spRubrica[0]->total ?? null) ?? 0);

        ResultadoRubrica::updateOrCreate(
            [
                'persona_id'   => $evaluadorId,
                'plantilla_id' => $plantillaId,
                'equipo_id'    => $equipoId,
            ],
            [
                'total'        => $totalRubrica,
            ]
        );

        // 2) Total del proceso por equipo
        $spProceso = DB::select("CALL sp_calcular_resultado_proceso_evaluacion(?,?)", [$procesoId, $equipoId]);
        $rowProceso = $spProceso[0] ?? null;
        $totalProceso = (float) ($rowProceso->total ?? $rowProceso->resultado ?? 0);

        ResultadoProcesoEvaluacion::updateOrCreate(
            [
                'proceso_id' => $procesoId,
                'equipo_id'  => $equipoId,
            ],
            [
                'total'      => $totalProceso,
            ]
        );
    }
}
