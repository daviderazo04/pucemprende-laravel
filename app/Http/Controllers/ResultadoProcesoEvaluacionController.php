<?php


namespace App\Http\Controllers;

use App\Models\ResultadoProcesoEvaluacion;
use App\Models\ResultadoRubrica;
use App\Models\ProcesosEvaluacion;
use App\Models\PlantillasEvaluacion;
use App\Models\Equipo;
use App\Models\Persona;
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
     * Mostrar una lista de todos los resultados de proceso de evaluación
     */
    public function index(Request $request)
    {
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver los resultados de proceso de evaluación.'], 403);
        }

        $resultados = ResultadoProcesoEvaluacion::with(['persona', 'proceso', 'equipo'])->get();
        return response()->json($resultados);
    }

    /**
     * Crear un nuevo resultado de proceso de evaluación
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'proceso_id' => 'required|exists:procesos_evaluacion,id',
            'equipo_id' => 'required|exists:equipos,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verificar si ya existe un resultado para esta combinación
        $existingResult = ResultadoProcesoEvaluacion::where('proceso_id', $request->proceso_id)
            ->where('equipo_id', $request->equipo_id)
            ->first();

        if ($existingResult) {
            //Actualizar el resultado en lugar de crear uno nuevo
            $totalProceso = DB:: select("CALL sp_calcular_resultado_proceso_evaluacion(?,?)", [$request->proceso_id, $request->equipo_id]);
            $total = $totalProceso[0]->resultado ?? 0;

           $resultado = ResultadoProcesoEvaluacion::updateOrCreate([
               'proceso_id' => $request->proceso_id,
               'equipo_id' => $request->equipo_id,
           ], [
               'total' => $total,
           ]);

           return response()->json($resultado, 200);
       }

        // Verificar que el proceso existe
        $proceso = ProcesosEvaluacion::find($request->proceso_id);
        if (!$proceso) {
            return response()->json(['error' => 'Proceso de evaluación no encontrado'], 404);
        }

        // Verificar que el equipo existe
        $equipo = Equipo::find($request->equipo_id);
        if (!$equipo) {
            return response()->json(['error' => 'Equipo no encontrado'], 404);
        }

        // Obtener plantillas del proceso con sus pesos
        $plantillas = PlantillasEvaluacion::whereIn('proceso_id', [$request->proceso_id])->get();

        if ($plantillas->isEmpty()) {
            return response()->json(['message' => 'No hay plantillas configuradas para este proceso'], 400);
        }

        $totalProceso = DB:: select("CALL sp_calcular_resultado_proceso_evaluacion(?,?)", [$request->proceso_id, $request->equipo_id]);
        $total = $totalProceso[0]->resultado ?? 0;
        $resultado = ResultadoProcesoEvaluacion::create([
            'proceso_id' => $request->proceso_id,
            'equipo_id' => $request->equipo_id,
            'total' => $total,
        ]);

        return response()->json($resultado, 201);
    }

    /**
     * Mostrar un resultado de proceso de evaluación específico
     */
    public function show(Request $request, $id)
    {
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver este resultado de proceso de evaluación.'], 403);
        }

        $resultado = ResultadoProcesoEvaluacion::with(['persona', 'proceso', 'equipo'])->find($id);

        if (!$resultado) {
            return response()->json(['message' => 'Resultado de proceso de evaluación no encontrado.'], 404);
        }

        return response()->json($resultado);
    }

    /**
     * Obtener resultado por proceso y equipo específicos
     */
    public function showByProcesoEquipo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'proceso_id' => 'required|exists:procesos_evaluacion,id',
            'equipo_id' => 'required|exists:equipos,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $resultado = ResultadoProcesoEvaluacion::with(['persona', 'proceso', 'equipo'])
            ->where('proceso_id', $request->proceso_id)
            ->where('equipo_id', $request->equipo_id)
            ->first();

        if (!$resultado) {
            return response()->json(['message' => 'Resultado de proceso de evaluación no encontrado.'], 404);
        }

        return response()->json($resultado);
    }

    /**
     * Obtener todos los resultados de un proceso específico (ranking)
     */
    public function showByProceso(Request $request, $procesoId)
    {
        // Solo permitir si el usuario es superadmin y adminEvento (2)
        if ($request->user()->rol_id !== 8 && $request->user()->rol_id !== 2) {
            return response()->json(['message' => 'No tienes permiso para ver estos resultados.'], 403);
        }

        $proceso = ProcesosEvaluacion::find($procesoId);
        if (!$proceso) {
            return response()->json(['error' => 'Proceso de evaluación no encontrado'], 404);
        }

        $resultados = ResultadoProcesoEvaluacion::with(['equipo', 'persona'])
            ->where('proceso_id', $procesoId)
            ->orderBy('total', 'desc')
            ->get();

        // Agregar ranking
        $resultadosConRanking = $resultados->map(function ($resultado, $index) {
            $resultado->ranking = $index + 1;
            return $resultado;
        });

        return response()->json([
            'proceso' => $proceso,
            'resultados' => $resultadosConRanking,
            'total_equipos' => $resultados->count()
        ]);
    }

    /**
     * Obtener todos los resultados de un equipo específico
     */
    public function showByEquipo(Request $request, $equipoId)
    {
        // Solo permitir si el usuario es superadmin y adminEvento (2)
        if ($request->user()->rol_id !== 8 && $request->user()->rol_id !== 2) {
            return response()->json(['message' => 'No tienes permiso para ver estos resultados.'], 403);
        }

        $equipo = Equipo::find($equipoId);
        if (!$equipo) {
            return response()->json(['error' => 'Equipo no encontrado'], 404);
        }

        $resultados = ResultadoProcesoEvaluacion::with(['proceso', 'persona'])
            ->where('equipo_id', $equipoId)
            ->orderBy('total', 'desc')
            ->get();

        return response()->json([
            'equipo' => $equipo,
            'resultados' => $resultados
        ]);
    }

    /**
     * Actualizar un resultado de proceso de evaluación
     */
    public function update(Request $request, $id)
    {
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para actualizar resultados de proceso de evaluación.'], 403);
        }

        $resultado = ResultadoProcesoEvaluacion::find($id);

        if (!$resultado) {
            return response()->json(['message' => 'Resultado de proceso de evaluación no encontrado.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'persona_id' => 'sometimes|required|exists:personas,id',
            'proceso_id' => 'sometimes|required|exists:procesos_evaluacion,id',
            'equipo_id' => 'sometimes|required|exists:equipos,id',
            'total' => 'sometimes|required|numeric|min:0|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Si se están cambiando los campos clave, verificar que no exista duplicado
        if ($request->has('persona_id') || $request->has('proceso_id') || $request->has('equipo_id')) {
            $persona_id = $request->has('persona_id') ? $request->persona_id : $resultado->persona_id;
            $proceso_id = $request->has('proceso_id') ? $request->proceso_id : $resultado->proceso_id;
            $equipo_id = $request->has('equipo_id') ? $request->equipo_id : $resultado->equipo_id;

            $existingResult = ResultadoProcesoEvaluacion::where('persona_id', $persona_id)
                ->where('proceso_id', $proceso_id)
                ->where('equipo_id', $equipo_id)
                ->where('id', '!=', $id)
                ->first();

            if ($existingResult) {
                return response()->json(['message' => 'Ya existe un resultado de proceso de evaluación para esta combinación de persona, proceso y equipo.'], 409);
            }
        }

        $resultado->fill($request->only([
            'persona_id',
            'proceso_id',
            'equipo_id',
            'total'
        ]));

        $resultado->save();
        $resultado->load(['persona', 'proceso', 'equipo']);

        return response()->json($resultado);
    }

    /**
     * Eliminar un resultado de proceso de evaluación
     */
    public function destroy(Request $request, $id)
    {
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para eliminar resultados de proceso de evaluación.'], 403);
        }

        $resultado = ResultadoProcesoEvaluacion::find($id);

        if (!$resultado) {
            return response()->json(['message' => 'Resultado de proceso de evaluación no encontrado.'], 404);
        }

        $resultado->delete();
        return response()->json(['message' => 'Resultado de proceso de evaluación eliminado correctamente.'], 200);
    }

    public function getResultadosByEvento(Request $request, $eventoId)
    {
        // Verificar permisos
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver resultados de proceso de evaluación.'], 403);
        }

        $resultados = DB::select('CALL sp_resultados_por_evento_ranking(?)', [$eventoId]);

        return response()->json($resultados);
    }

}
