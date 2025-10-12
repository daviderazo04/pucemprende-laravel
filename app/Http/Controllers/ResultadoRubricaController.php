<?php

namespace App\Http\Controllers;

use App\Models\ResultadoRubrica;
use App\Models\Persona;
use App\Models\RolesPlantilla;
use App\Models\PlantillasEvaluacion;
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
     * Crear un nuevo resultado de rubrica (con upsert)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'persona_id'   => 'required|exists:personas,id',
            'plantilla_id' => 'required|exists:plantillas_evaluacion,id',
            'equipo_id'    => 'nullable|exists:equipos,id',
            'rolEvento_id' => 'required|integer|exists:rolEvento,id'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verificar si el rol tiene permiso para calificar en esta plantilla
        $califica = RolesPlantilla::where('rol_id', $request->rolEvento_id)
            ->where('plantilla_id', $request->plantilla_id)
            ->first();
        if (!$califica) {
            return response()->json(['message' => 'El rol de evento no tiene permiso para calificar en este resultado rubrica.'], 403);
        }

        // Calcular total con SP
        $sp = DB::select("CALL sp_calcular_total_plantilla(?,?,?)", [
            $request->equipo_id, $request->plantilla_id, $request->persona_id
        ]);
        $total = (float) (($sp[0]->total ?? null) ?? 0);

        // Upsert para evitar duplicados
        $resultado = ResultadoRubrica::updateOrCreate(
            [
                'persona_id'   => $request->persona_id,
                'plantilla_id' => $request->plantilla_id,
                'equipo_id'    => $request->equipo_id,
            ],
            [
                'total'        => $total,
            ]
        );

        return response()->json($resultado, $resultado->wasRecentlyCreated ? 201 : 200);
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
    public function update(Request $request, $persona_id, $plantilla_id, $equipo_id)
    {
        $validator = Validator::make($request->all(), [
            'persona_id'   => 'sometimes|required|exists:personas,id',
            'plantilla_id' => 'sometimes|required|exists:plantillas_evaluacion,id',
            'equipo_id'    => 'nullable|exists:equipos,id',
            'total'        => 'sometimes|required|numeric|min:0',
            'rolEvento_id' => 'sometimes|required|integer|exists:rolEvento,id', // para validar permiso si llega
        ]);
        // Verificar si el rol tiene permiso para calificar en esta plantilla (si viene en el request)
        if ($request->filled('rolEvento_id') || $request->filled('plantilla_id')) {
            $califica = RolesPlantilla::where('rol_id', $request->rolEvento_id)
                ->where('plantilla_id', $request->input('plantilla_id', $plantilla_id))
                ->first();
            if (!$califica) {
                return response()->json(['message' => 'El rol de evento no tiene permiso para calificar en este resultado rubrica.'], 403);
            }
        }

        $resultado = ResultadoRubrica::where('persona_id', $persona_id)
            ->where('plantilla_id', $plantilla_id)
            ->where('equipo_id', $equipo_id)
            ->first();

        if (!$resultado) {
            return response()->json(['message' => 'Resultado de rubrica no encontrado.'], 404);
        }

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Resolver valores efectivos (si no vienen en el request, usar los actuales)
        $newPersonaId   = $request->has('persona_id')   ? $request->persona_id   : $resultado->persona_id;
        $newPlantillaId = $request->has('plantilla_id') ? $request->plantilla_id : $resultado->plantilla_id;
        $newEquipoId    = $request->has('equipo_id')    ? $request->equipo_id    : $resultado->equipo_id;

        // Verificar duplicado con la combinación final
        $exists = ResultadoRubrica::where('persona_id', $newPersonaId)
            ->where('plantilla_id', $newPlantillaId)
            ->where('equipo_id', $newEquipoId)
            ->where('id', '!=', $resultado->id)
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'Ya existe un resultado con la combinación persona/plantilla/equipo indicada.'], 409);
        }

        // Recalcular total usando el SP con los valores efectivos
        $totalRubrica = DB::select("CALL sp_calcular_total_plantilla(?,?,?)", [$newEquipoId, $newPlantillaId, $newPersonaId]);
        $total = (float) ($totalRubrica[0]->total ?? 0);

        // Actualizar
        $resultado->update([
            'persona_id'   => $newPersonaId,
            'plantilla_id' => $newPlantillaId,
            'equipo_id'    => $newEquipoId,
            'total'        => $total,
        ]);

        return response()->json($resultado->fresh(['persona', 'plantilla', 'equipo']));
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


}
