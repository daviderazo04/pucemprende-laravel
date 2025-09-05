<?php

namespace App\Http\Controllers;

use App\Models\ResultadosEvaluacion;
use App\Models\Criterio;
use App\Models\RolEvento;
use App\Models\Persona;
use App\Models\RolesPlantilla;
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
        // Calcular el puntaje en base al criterio con el peso, se divide entre 5 dado que el puntaje máximo es 5
        $puntajeCalculado = ($request->puntaje * $criterio->peso)/5;
        $resultado = ResultadosEvaluacion::create([
            'creado_en' => Carbon::now(),
            'actualizado_en' => Carbon::now(),
            'equipo_id' => $request->equipo_id,
            'criterio_id' => $request->criterio_id,
            'evaluador_id' => $request->evaluador_id,
            'puntaje' => $puntajeCalculado,
            'comentarios' => $request->comentarios,
            'evaluado_en' => Carbon::now(),
        ]);

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
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para actualizar resultados de evaluación.'], 403);
        }

        $resultado = ResultadosEvaluacion::find($id);

        if (!$resultado) {
            return response()->json(['message' => 'Resultado de evaluación no encontrado.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'equipo_id' => 'sometimes|required|exists:equipos,id',
            'criterio_id' => 'sometimes|required|exists:criterios,id',
            'evaluador_id' => 'sometimes|required|exists:personas,id',
            'puntaje' => 'sometimes|required|numeric|min:0',
            'comentarios' => 'nullable|string',
            'evaluado_en' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Si se están cambiando los campos clave, verificar que no exista duplicado
        if ($request->has('equipo_id') || $request->has('criterio_id') || $request->has('evaluador_id')) {
            $equipo_id = $request->has('equipo_id') ? $request->equipo_id : $resultado->equipo_id;
            $criterio_id = $request->has('criterio_id') ? $request->criterio_id : $resultado->criterio_id;
            $evaluador_id = $request->has('evaluador_id') ? $request->evaluador_id : $resultado->evaluador_id;

            $existingEvaluation = ResultadosEvaluacion::where('equipo_id', $equipo_id)
                ->where('criterio_id', $criterio_id)
                ->where('evaluador_id', $evaluador_id)
                ->where('id', '!=', $id)
                ->first();

            if ($existingEvaluation) {
                return response()->json(['message' => 'Ya existe una evaluación para esta combinación de equipo, criterio y evaluador.'], 409);
            }
        }

        $resultado->fill($request->only([
            'equipo_id',
            'criterio_id',
            'evaluador_id',
            'puntaje',
            'comentarios'
        ]));

        if ($request->has('evaluado_en')) {
            $resultado->evaluado_en = $request->evaluado_en ? Carbon::parse($request->evaluado_en) : null;
        }

        $resultado->actualizado_en = Carbon::now();
        $resultado->save();
        $resultado->load(['equipo', 'criterio', 'persona']);

        return response()->json($resultado);
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


}
