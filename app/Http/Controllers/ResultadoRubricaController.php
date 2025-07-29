<?php

namespace App\Http\Controllers;

use App\Models\ResultadoRubrica;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ResultadoRubricaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Mostrar una lista de todos los resultados de rubrica
     */
    public function index(Request $request)
    {
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver los resultados de rubrica.'], 403);
        }

        $resultados = ResultadoRubrica::with(['persona', 'plantilla', 'equipo'])->get();
        return response()->json($resultados);
    }

    /**
     * Crear un nuevo resultado de rubrica
     */
    public function store(Request $request)
    {
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para crear resultados de rubrica.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'persona_id' => 'required|exists:personas,id',
            'plantilla_id' => 'required|exists:plantillas_evaluacion,id',
            'equipo_id' => 'nullable|exists:equipos,id',
            'total' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verificar si ya existe un resultado para esta combinación
        $existingResult = ResultadoRubrica::where('persona_id', $request->persona_id)
            ->where('plantilla_id', $request->plantilla_id)
            ->where('equipo_id', $request->equipo_id)
            ->first();

        if ($existingResult) {
            return response()->json(['message' => 'Ya existe un resultado de rubrica para esta combinación de persona, plantilla y equipo.'], 409);
        }

        $resultado = ResultadoRubrica::create([
            'persona_id' => $request->persona_id,
            'plantilla_id' => $request->plantilla_id,
            'equipo_id' => $request->equipo_id,
            'total' => $request->total,
        ]);

        $resultado->load(['persona', 'plantilla', 'equipo']);
        return response()->json($resultado, 201);
    }

    /**
     * Mostrar un resultado de rubrica específico
     */
    public function show(Request $request, $id)
    {
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver este resultado de rubrica.'], 403);
        }

        $resultado = ResultadoRubrica::with(['persona', 'plantilla', 'equipo'])->find($id);

        if (!$resultado) {
            return response()->json(['message' => 'Resultado de rubrica no encontrado.'], 404);
        }

        return response()->json($resultado);
    }

    /**
     * Actualizar un resultado de rubrica
     */
    public function update(Request $request, $id)
    {
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para actualizar resultados de rubrica.'], 403);
        }

        $resultado = ResultadoRubrica::find($id);

        if (!$resultado) {
            return response()->json(['message' => 'Resultado de rubrica no encontrado.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'persona_id' => 'sometimes|required|exists:personas,id',
            'plantilla_id' => 'sometimes|required|exists:plantillas_evaluacion,id',
            'equipo_id' => 'nullable|exists:equipos,id',
            'total' => 'sometimes|required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Si se están cambiando los campos clave, verificar que no exista duplicado
        if ($request->has('persona_id') || $request->has('plantilla_id') || $request->has('equipo_id')) {
            $persona_id = $request->has('persona_id') ? $request->persona_id : $resultado->persona_id;
            $plantilla_id = $request->has('plantilla_id') ? $request->plantilla_id : $resultado->plantilla_id;
            $equipo_id = $request->has('equipo_id') ? $request->equipo_id : $resultado->equipo_id;

            $existingResult = ResultadoRubrica::where('persona_id', $persona_id)
                ->where('plantilla_id', $plantilla_id)
                ->where('equipo_id', $equipo_id)
                ->where('id', '!=', $id)
                ->first();

            if ($existingResult) {
                return response()->json(['message' => 'Ya existe un resultado de rubrica para esta combinación de persona, plantilla y equipo.'], 409);
            }
        }

        $resultado->fill($request->only([
            'persona_id',
            'plantilla_id',
            'equipo_id',
            'total'
        ]));

        $resultado->save();
        $resultado->load(['persona', 'plantilla', 'equipo']);

        return response()->json($resultado);
    }

    /**
     * Eliminar un resultado de rubrica
     */
    public function destroy(Request $request, $id)
    {
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para eliminar resultados de rubrica.'], 403);
        }

        $resultado = ResultadoRubrica::find($id);

        if (!$resultado) {
            return response()->json(['message' => 'Resultado de rubrica no encontrado.'], 404);
        }

        $resultado->delete();
        return response()->json(['message' => 'Resultado de rubrica eliminado correctamente.'], 200);
    }

    /**
     * Obtener resultados de rubrica por equipo
     */
    public function getByEquipo(Request $request, $equipoId)
    {
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver los resultados de rubrica.'], 403);
        }

        $resultados = ResultadoRubrica::with(['persona', 'plantilla', 'equipo'])
            ->where('equipo_id', $equipoId)
            ->get();

        return response()->json($resultados);
    }

    /**
     * Obtener resultados de rubrica por plantilla
     */
    public function getByPlantilla(Request $request, $plantillaId)
    {
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver los resultados de rubrica.'], 403);
        }

        $resultados = ResultadoRubrica::with(['persona', 'plantilla', 'equipo'])
            ->where('plantilla_id', $plantillaId)
            ->get();

        return response()->json($resultados);
    }

    /**
     * Obtener estadísticas de rubrica por equipo
     */
    public function getEstadisticasByEquipo(Request $request, $equipoId)
    {
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver las estadísticas de rubrica.'], 403);
        }

        $estadisticas = ResultadoRubrica::where('equipo_id', $equipoId)
            ->selectRaw('
                COUNT(*) as total_resultados,
                AVG(total) as promedio_total,
                MIN(total) as total_minimo,
                MAX(total) as total_maximo,
                SUM(total) as suma_total
            ')
            ->first();

        $resultadosPorPlantilla = ResultadoRubrica::with('plantilla')
            ->where('equipo_id', $equipoId)
            ->selectRaw('plantilla_id, AVG(total) as promedio_por_plantilla, COUNT(*) as resultados_por_plantilla')
            ->groupBy('plantilla_id')
            ->get();

        return response()->json([
            'estadisticas_generales' => $estadisticas,
            'resultados_por_plantilla' => $resultadosPorPlantilla
        ]);
    }

    /**
     * Obtener estadísticas de rubrica por plantilla
     */
    public function getEstadisticasByPlantilla(Request $request, $plantillaId)
    {
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver las estadísticas de rubrica.'], 403);
        }

        $estadisticas = ResultadoRubrica::where('plantilla_id', $plantillaId)
            ->selectRaw('
                COUNT(*) as total_resultados,
                AVG(total) as promedio_total,
                MIN(total) as total_minimo,
                MAX(total) as total_maximo,
                SUM(total) as suma_total
            ')
            ->first();

        $resultadosPorEquipo = ResultadoRubrica::with('equipo')
            ->where('plantilla_id', $plantillaId)
            ->selectRaw('equipo_id, AVG(total) as promedio_por_equipo, COUNT(*) as resultados_por_equipo')
            ->groupBy('equipo_id')
            ->get();

        return response()->json([
            'estadisticas_generales' => $estadisticas,
            'resultados_por_equipo' => $resultadosPorEquipo
        ]);
    }
}
