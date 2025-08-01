<?php

namespace App\Http\Controllers;

use App\Models\ResultadosEvaluacion;
use App\Models\Criterio;
use App\Models\RolEvento;
use App\Models\Persona;
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
            'rolEvento_id' => 'required|integer|exists:rolEvento,id',
        ]);
        // Solo permitir si el usuario es superadmin (8), adminEvento (1), gestorEvento (2), mentor (3), jurado (5)
        if ($request->user()->rol_id !== 8 && $request->rolEvento_id !== 1 && $request->rolEvento_id !== 2 && $request->rolEvento_id !== 3 && $request->rolEvento_id !== 5) {
            return response()->json(['message' => 'No tienes permiso para crear resultados de evaluación.'], 403);
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

        return response()->json($resultado);
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

    /**
     * Obtener resultados de evaluación por equipo
     */
    public function getByEquipo(Request $request, $equipoId)
    {
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver los resultados de evaluación.'], 403);
        }

        $resultados = ResultadosEvaluacion::with(['equipo', 'criterio', 'persona'])
            ->where('equipo_id', $equipoId)
            ->get();

        return response()->json($resultados);
    }

    /**
     * Obtener resultados de evaluación por criterio
     */
    public function getByCriterio(Request $request, $criterioId)
    {
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver los resultados de evaluación.'], 403);
        }

        $resultados = ResultadosEvaluacion::with(['equipo', 'criterio', 'persona'])
            ->where('criterio_id', $criterioId)
            ->get();

        return response()->json($resultados);
    }

    /**
     * Obtener resultados de evaluación por evaluador
     */
    public function getByEvaluador(Request $request, $evaluadorId)
    {
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver los resultados de evaluación.'], 403);
        }

        $resultados = ResultadosEvaluacion::with(['equipo', 'criterio', 'persona'])
            ->where('evaluador_id', $evaluadorId)
            ->get();

        return response()->json($resultados);
    }

    /**
     * Obtener estadísticas de evaluación por equipo
     */
    public function getEstadisticasByEquipo(Request $request, $equipoId)
    {
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver las estadísticas de evaluación.'], 403);
        }

        $estadisticas = ResultadosEvaluacion::where('equipo_id', $equipoId)
            ->selectRaw('
                COUNT(*) as total_evaluaciones,
                AVG(puntaje) as promedio_puntaje,
                MIN(puntaje) as puntaje_minimo,
                MAX(puntaje) as puntaje_maximo,
                SUM(puntaje) as puntaje_total
            ')
            ->first();

        $evaluacionesPorCriterio = ResultadosEvaluacion::with('criterio')
            ->where('equipo_id', $equipoId)
            ->selectRaw('criterio_id, AVG(puntaje) as promedio_por_criterio, COUNT(*) as evaluaciones_por_criterio')
            ->groupBy('criterio_id')
            ->get();

        return response()->json([
            'estadisticas_generales' => $estadisticas,
            'evaluaciones_por_criterio' => $evaluacionesPorCriterio
        ]);
    }

    /**
     * Obtener una vista consolidada de resultados de todos los equipos por evento
     */
    public function getVistaConsolidadaEquipos(Request $request)
    {
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver la vista consolidada de equipos.'], 403);
        }

        // Validar parámetro de evento
        $eventoId = $request->input('evento_id');

        if (!$eventoId) {
            return response()->json(['message' => 'El parámetro evento_id es requerido.'], 400);
        }

        // Estadísticas generales por equipo del evento específico
        $estadisticasPorEquipo = DB::select("
            SELECT
                e.id as equipo_id,
                e.nombre as nombre_equipo,
                ev.id as evento_id,
                ev.nombre as nombre_evento,
                COUNT(re.id) as total_evaluaciones,
                ROUND(AVG(re.puntaje), 2) as promedio_puntaje,
                MIN(re.puntaje) as puntaje_minimo,
                MAX(re.puntaje) as puntaje_maximo,
                ROUND(SUM(re.puntaje), 2) as puntaje_total
            FROM equipos e
            INNER JOIN eventos ev ON e.evento_id = ev.id
            LEFT JOIN resultados_evaluacion re ON e.id = re.equipo_id
            WHERE ev.id = ?
            GROUP BY e.id, e.nombre, ev.id, ev.nombre
            ORDER BY promedio_puntaje DESC, nombre_equipo ASC
        ", [$eventoId]);

        // Ranking de equipos por promedio en el evento
        $rankingEquipos = DB::select("
            SELECT
                e.id as equipo_id,
                e.nombre as nombre_equipo,
                ev.nombre as nombre_evento,
                ROUND(AVG(re.puntaje), 2) as promedio_puntaje,
                COUNT(re.id) as total_evaluaciones,
                RANK() OVER (ORDER BY AVG(re.puntaje) DESC) as ranking
            FROM equipos e
            INNER JOIN eventos ev ON e.evento_id = ev.id
            LEFT JOIN resultados_evaluacion re ON e.id = re.equipo_id
            WHERE ev.id = ?
            GROUP BY e.id, e.nombre, ev.nombre
            HAVING COUNT(re.id) > 0
            ORDER BY ranking ASC
        ", [$eventoId]);

        // Evaluaciones detalladas por equipo y criterio en el evento
        $evaluacionesDetalladas = DB::select("
            SELECT
                e.id as equipo_id,
                e.nombre as nombre_equipo,
                ev.nombre as nombre_evento,
                c.id as criterio_id,
                c.nombre as nombre_criterio,
                COUNT(re.id) as evaluaciones_criterio,
                ROUND(AVG(re.puntaje), 2) as promedio_criterio,
                MIN(re.puntaje) as minimo_criterio,
                MAX(re.puntaje) as maximo_criterio
            FROM equipos e
            INNER JOIN eventos ev ON e.evento_id = ev.id
            LEFT JOIN resultados_evaluacion re ON e.id = re.equipo_id
            LEFT JOIN criterios c ON re.criterio_id = c.id
            WHERE ev.id = ? AND c.id IS NOT NULL
            GROUP BY e.id, e.nombre, ev.nombre, c.id, c.nombre
            ORDER BY e.nombre ASC, promedio_criterio DESC
        ", [$eventoId]);

        // Estadísticas globales del evento
        $estadisticasGlobales = DB::select("
            SELECT
                ev.id as evento_id,
                ev.nombre as nombre_evento,
                COUNT(DISTINCT e.id) as total_equipos,
                COUNT(re.id) as total_evaluaciones,
                COUNT(DISTINCT re.criterio_id) as criterios_evaluados,
                COUNT(DISTINCT re.evaluador_id) as evaluadores_activos,
                ROUND(AVG(re.puntaje), 2) as promedio_global,
                MIN(re.puntaje) as puntaje_minimo_global,
                MAX(re.puntaje) as puntaje_maximo_global
            FROM eventos ev
            LEFT JOIN equipos e ON ev.id = e.evento_id
            LEFT JOIN resultados_evaluacion re ON e.id = re.equipo_id
            WHERE ev.id = ?
            GROUP BY ev.id, ev.nombre
        ", [$eventoId]);

        return response()->json([
            'evento_id' => $eventoId,
            'estadisticas_globales' => $estadisticasGlobales[0] ?? null,
            'estadisticas_por_equipo' => $estadisticasPorEquipo,
            'ranking_equipos' => $rankingEquipos,
            'evaluaciones_detalladas' => $evaluacionesDetalladas
        ]);
    }

    /**
     * Obtener vista comparativa entre equipos de un evento
     */
    public function getVistaComparativaEquipos(Request $request)
    {
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver la vista comparativa de equipos.'], 403);
        }

        // Validar parámetros
        $eventoId = $request->input('evento_id');
        $equipoIds = $request->input('equipo_ids', []); // Array de IDs de equipos
        $criterioId = $request->input('criterio_id'); // ID del criterio específico

        if (!$eventoId) {
            return response()->json(['message' => 'El parámetro evento_id es requerido.'], 400);
        }

        $baseQuery = "
            SELECT
                e.id as equipo_id,
                e.nombre as nombre_equipo,
                ev.id as evento_id,
                ev.nombre as nombre_evento,
                c.id as criterio_id,
                c.nombre as nombre_criterio,
                ROUND(AVG(re.puntaje), 2) as promedio_puntaje,
                COUNT(re.id) as total_evaluaciones,
                GROUP_CONCAT(
                    CONCAT(p.nombres, ' ', p.apellidos, ': ', re.puntaje)
                    ORDER BY re.puntaje DESC
                    SEPARATOR ' | '
                ) as detalle_evaluaciones
            FROM equipos e
            INNER JOIN eventos ev ON e.evento_id = ev.id
            LEFT JOIN resultados_evaluacion re ON e.id = re.equipo_id
            LEFT JOIN criterios c ON re.criterio_id = c.id
            LEFT JOIN personas p ON re.evaluador_id = p.id
            WHERE ev.id = ?
        ";

        $params = [$eventoId];

        // Filtrar por equipos específicos si se proporcionan
        if (!empty($equipoIds) && is_array($equipoIds)) {
            $placeholders = str_repeat('?,', count($equipoIds) - 1) . '?';
            $baseQuery .= " AND e.id IN ($placeholders)";
            $params = array_merge($params, $equipoIds);
        }

        // Filtrar por criterio específico si se proporciona
        if ($criterioId) {
            $baseQuery .= " AND c.id = ?";
            $params[] = $criterioId;
        }

        $baseQuery .= "
            GROUP BY e.id, e.nombre, ev.id, ev.nombre, c.id, c.nombre
            HAVING COUNT(re.id) > 0
            ORDER BY c.nombre ASC, promedio_puntaje DESC
        ";

        $comparacion = DB::select($baseQuery, $params);

        return response()->json([
            'evento_id' => $eventoId,
            'comparacion_equipos' => $comparacion,
            'filtros_aplicados' => [
                'evento_seleccionado' => $eventoId,
                'equipos_seleccionados' => $equipoIds,
                'criterio_seleccionado' => $criterioId
            ]
        ]);
    }

    /**
     * Obtener lista de eventos con equipos que tienen evaluaciones
     */
    public function getEventosConEvaluaciones(Request $request)
    {
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver los eventos con evaluaciones.'], 403);
        }

        $eventos = DB::select("
            SELECT DISTINCT
                ev.id as evento_id,
                ev.nombre as nombre_evento,
                ev.descripcion,
                ev.fecha_inicio,
                ev.fecha_fin,
                ev.estado,
                COUNT(DISTINCT e.id) as total_equipos,
                COUNT(re.id) as total_evaluaciones,
                COUNT(DISTINCT re.criterio_id) as criterios_evaluados,
                ROUND(AVG(re.puntaje), 2) as promedio_evento
            FROM eventos ev
            INNER JOIN equipos e ON ev.id = e.evento_id
            INNER JOIN resultados_evaluacion re ON e.id = re.equipo_id
            GROUP BY ev.id, ev.nombre, ev.descripcion, ev.fecha_inicio, ev.fecha_fin, ev.estado
            ORDER BY ev.fecha_inicio DESC
        ");

        return response()->json($eventos);
    }
}
