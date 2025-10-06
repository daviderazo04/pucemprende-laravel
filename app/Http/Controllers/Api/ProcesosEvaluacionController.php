<?php

namespace App\Http\Controllers\Api;

use App\Models\ProcesosEvaluacion;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ProcesosEvaluacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $procesos = ProcesosEvaluacion::with('evento')->get();
        return response()->json($procesos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'evento_id' => 'nullable|integer|exists:eventos,id',
        ]);

        $proceso = ProcesosEvaluacion::create([
            'titulo' => $validated['titulo'],
            'evento_id' => $validated['evento_id'] ?? null,
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);
        return response()->json($proceso, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $proceso = ProcesosEvaluacion::with('evento')->find($id);
        if (!$proceso) {
            return response()->json(['message' => 'Proceso de evaluación no encontrado'], 404);
        }
        return response()->json($proceso);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $proceso = ProcesosEvaluacion::find($id);
        if (!$proceso) {
            return response()->json(['message' => 'Proceso de evaluación no encontrado'], 404);
        }

        $validated = $request->validate([
            'titulo' => 'sometimes|required|string|max:255',
            'evento_id' => 'nullable|integer|exists:eventos,id',
        ]);

        $proceso->fill($validated);
        $proceso->actualizado_en = now();
        $proceso->save();
        return response()->json($proceso);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $proceso = ProcesosEvaluacion::find($id);
        if (!$proceso) {
            return response()->json(['message' => 'Proceso de evaluación no encontrado'], 404);
        }
        $proceso->delete();
        return response()->json(['message' => 'Proceso de evaluación eliminado correctamente.'], 200);
    }

    /**
     * Retrieves all evaluation processes with their nested templates and criteria.
     * Calls SP_ObtenerProcesosEvaluacionDetalle().
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProcesosEvaluacionDetalle()
    {
        try {
            // Llama al Stored Procedure para obtener todos los procesos con sus plantillas y criterios anidados.
            // El resultado del SP es un JSON string en la columna 'plantillas'.
            $result = DB::select('CALL SP_ObtenerProcesosEvaluacionDetalle()');

            // Procesar el resultado para decodificar el JSON de 'plantillas'
            $procesos = collect($result)->map(function ($proceso) {
                // Decodificar la columna 'plantillas' que viene como JSON string
                // Si 'plantillas' es null o vacío, json_decode devolverá null, lo cual es manejado
                $proceso->plantillas = $proceso->plantillas ? json_decode($proceso->plantillas, true) : [];

                // Asegurarse de que las propiedades del proceso tengan nombres camelCase para consistencia con JSON
                $formattedProceso = [
                    'procesoId' => $proceso->procesoId,
                    'procesoTitulo' => $proceso->procesoTitulo,
                    'procesoCreadoEn' => $proceso->procesoCreadoEn,
                    'procesoActualizadoEn' => $proceso->procesoActualizadoEn,
                    'procesoEventoId' => $proceso->procesoEventoId,
                    'plantillas' => collect($proceso->plantillas)->map(function ($plantilla) {
                        // Formatear los nombres de las propiedades de la plantilla a camelCase
                        $formattedPlantilla = [
                            'plantillaId' => $plantilla['plantillaId'],
                            'plantillaNombre' => $plantilla['plantillaNombre'],
                            'plantillaCreadoEn' => $plantilla['plantillaCreadoEn'],
                            'plantillaActualizadoEn' => $plantilla['plantillaActualizadoEn'],
                            // Decodificar la columna 'criterios' que viene como JSON string
                            // Asegurarse de que 'criterios' existe y no es nulo antes de decodificar
                            'criterios' => (isset($plantilla['criterios']) && is_string($plantilla['criterios']) && $plantilla['criterios'])
    ? json_decode($plantilla['criterios'], true)
    : (is_array($plantilla['criterios']) ? $plantilla['criterios'] : []),
                        ];
                        // Mapear los criterios internos para camelCase
                        $formattedPlantilla['criterios'] = collect($formattedPlantilla['criterios'])->map(function ($criterio) {
                            return [
                                'criterioId' => $criterio['criterioId'],
                                'criterioNombre' => $criterio['criterioNombre'],
                                'criterioDescripcion' => $criterio['criterioDescripcion'],
                                'criterioPeso' => $criterio['criterioPeso'],
                                'criterioCreadoEn' => $criterio['criterioCreadoEn'],
                                'criterioActualizadoEn' => $criterio['criterioActualizadoEn'],
                            ];
                        })->all(); // Convertir la colección de criterios de vuelta a un array
                        return $formattedPlantilla;
                    })->all(), // Convertir la colección de plantillas de vuelta a un array
                ];
                return $formattedProceso;
            });

            return response()->json($procesos);

        } catch (\Exception $e) {
            // Manejo de errores en caso de que el SP falle o haya un problema con la base de datos
            return response()->json(['message' => 'Error al obtener los procesos de evaluación: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Retrieves a specific evaluation process with its nested templates and criteria by ID.
     * Calls SP_ObtenerProcesoEvaluacionDetallePorId().
     *
     * @param  int  $id The ID of the evaluation process.
     * @return \Illuminate\Http\JsonResponse
     */
    public function showDetalle($id)
    {
        try {
            // Llama al Stored Procedure para obtener los detalles del proceso con el ID dado.
            $result = DB::select('CALL SP_ObtenerProcesoEvaluacionDetallePorId(?)', [$id]);

            // Si no se encuentra ningún resultado, el proceso no existe.
            if (empty($result)) {
                return response()->json(['message' => 'Proceso de evaluación no encontrado'], 404);
            }

            // El SP debe devolver una única fila para un ID específico.
            $proceso = $result[0];

            // Decodificar la columna 'plantillas' que viene como JSON string
            $proceso->plantillas = $proceso->plantillas ? json_decode($proceso->plantillas, true) : [];

            // Formatear las propiedades del proceso y sus anidados a camelCase para la respuesta JSON
            $formattedProceso = [
                'procesoId' => $proceso->procesoId,
                'procesoTitulo' => $proceso->procesoTitulo,
                'procesoCreadoEn' => $proceso->procesoCreadoEn,
                'procesoActualizadoEn' => $proceso->procesoActualizadoEn,
                'procesoEventoId' => $proceso->procesoEventoId,
                'plantillas' => collect($proceso->plantillas)->map(function ($plantilla) {
                    $formattedPlantilla = [
                        'plantillaId' => $plantilla['plantillaId'],
                        'plantillaNombre' => $plantilla['plantillaNombre'],
                        'plantillaPeso' => $plantilla['plantillaPeso'],
                        'plantillaCreadoEn' => $plantilla['plantillaCreadoEn'],
                        'plantillaActualizadoEn' => $plantilla['plantillaActualizadoEn'],
                        // Asegurarse de que 'criterios' existe y no es nulo antes de decodificar
                        'criterios' => (isset($plantilla['criterios']) && is_string($plantilla['criterios']) && $plantilla['criterios'])
                            ? json_decode($plantilla['criterios'], true)
                            : (is_array($plantilla['criterios']) ? $plantilla['criterios'] : []),
                    ];
                    // Mapear los criterios internos para camelCase
                    $formattedPlantilla['criterios'] = collect($formattedPlantilla['criterios'])->map(function ($criterio) {
                        return [
                            'criterioId' => $criterio['criterioId'],
                            'criterioNombre' => $criterio['criterioNombre'],
                            'criterioDescripcion' => $criterio['criterioDescripcion'],
                            'criterioPeso' => $criterio['criterioPeso'],
                            'criterioCreadoEn' => $criterio['criterioCreadoEn'],
                            'criterioActualizadoEn' => $criterio['criterioActualizadoEn'],
                        ];
                    })->all();
                    return $formattedPlantilla;
                })->all(),
            ];

            return response()->json($formattedProceso);

        } catch (\Exception $e) {
            // Manejo de errores en caso de que el SP falle o haya un problema con la base de datos
            return response()->json(['message' => 'Error al obtener el detalle del proceso de evaluación: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Store a new evaluation template with its criteria.
     * Calls SP_CrearPlantillaYCriterios().
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storePlantillaYCriterios(Request $request)
    {
        $validated = $request->validate([
            'proceso_id' => 'required|integer|exists:procesos_evaluacion,id',
            'nombre_plantilla' => 'required|string|max:100',
            'peso' => 'nullable|numeric|min:0|max:100',
            'criterios' => 'nullable|array',
            'criterios.*.nombre' => 'required_with:criterios|string|max:100',
            'criterios.*.descripcion' => 'nullable|string',
            'criterios.*.peso' => 'nullable|numeric|min:0',
        ]);

        try {
            // Convertir el array de criterios a JSON string para pasarlo al SP
            $criteriosJson = json_encode($validated['criterios'] ?? []);

            // Llama al Stored Procedure
            $result = DB::select(
                'CALL SP_CrearPlantillaYCriterios(?, ?, ?, ?)',
                [
                    $validated['proceso_id'],
                    $validated['nombre_plantilla'],
                    $validated['peso'] ?? null,
                    $criteriosJson
                ]
            );

            // El SP devuelve la plantilla recién creada.
            // Si el SP devuelve una fila, toma la primera.
            $plantillaCreada = count($result) > 0 ? $result[0] : null;

            // Opcional: Formatear las claves a camelCase para la respuesta JSON
            if ($plantillaCreada) {
                $plantillaCreada = [
                    'plantillaId' => $plantillaCreada->plantillaId,
                    'plantillaNombre' => $plantillaCreada->plantillaNombre,
                    'procesoId' => $plantillaCreada->procesoId,
                    'peso' => $plantillaCreada->peso,
                    'creadoEn' => $plantillaCreada->creadoEn,
                    'actualizadoEn' => $plantillaCreada->actualizadoEn,
                ];
            }

            return response()->json($plantillaCreada, 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear la plantilla y criterios: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update an existing evaluation template and its criteria.
     * Calls SP_ActualizarPlantillaYCriterios().
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $plantillaId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePlantillaYCriterios(Request $request, $plantillaId)
    {
        $validated = $request->validate([
            'nombre_plantilla' => 'required|string|max:100',
            'criterios' => 'nullable|array',
            'peso' => 'nullable|numeric|min:0|max:100',
            'criterios.*.nombre' => 'required_with:criterios|string|max:100',
            'criterios.*.descripcion' => 'nullable|string',
            'criterios.*.peso' => 'nullable|numeric|min:0',
        ]);

        try {
            // Convertir el array de criterios a JSON string para pasarlo al SP
            $criteriosJson = json_encode($validated['criterios'] ?? []);

            // Llama al Stored Procedure
            $result = DB::select(
                'CALL SP_ActualizarPlantillaYCriterios(?, ?, ?, ?)',
                [
                    $plantillaId,
                    $validated['nombre_plantilla'],
                    $validated['peso'],
                    $criteriosJson
                ]
            );

            // El SP devuelve la plantilla actualizada.
            // Si el SP devuelve una fila, toma la primera.
            $plantillaActualizada = count($result) > 0 ? $result[0] : null;

            // Opcional: Formatear las claves a camelCase para la respuesta JSON
            if ($plantillaActualizada) {
                $plantillaActualizada = [
                    'plantillaId' => $plantillaActualizada->plantillaId,
                    'plantillaNombre' => $plantillaActualizada->plantillaNombre,
                    'procesoId' => $plantillaActualizada->procesoId,
                    'creadoEn' => $plantillaActualizada->creadoEn,
                    'actualizadoEn' => $plantillaActualizada->actualizadoEn,
                ];
            }

            return response()->json($plantillaActualizada);

        } catch (\Exception $e) {
            // MySQL error code 1644 is for SIGNAL SQLSTATE '45000'
            if ($e->getCode() == 1644) {
                return response()->json(['message' => $e->getMessage()], 404);
            }
            return response()->json(['message' => 'Error al actualizar la plantilla y criterios: ' . $e->getMessage()], 500);
        }
    }

    /**
    * Update the weight of an evaluation template.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  int  $plantillaId
    * @return \Illuminate\Http\JsonResponse
    */
    public function updatePesoPlantilla(Request $request, $plantillaId)
    {
        $validated = $request->validate([
            'peso' => 'required|numeric|min:0|max:100',
        ]);

        try {
            $result = DB::update(
                'UPDATE plantillas_evaluacion SET peso = ?, actualizado_en = NOW() WHERE id = ?',
                [$validated['peso'], $plantillaId]
            );

            if ($result === 0) {
                return response()->json(['message' => 'Plantilla no encontrada'], 404);
            }

            // Obtener la plantilla actualizada
            $plantilla = DB::selectOne(
                'SELECT id as plantillaId, nombre as plantillaNombre, peso as plantillaPeso, proceso_id as procesoId, creado_en as creadoEn, actualizado_en as actualizadoEn FROM plantillas_evaluacion WHERE id = ?',
                [$plantillaId]
            );

            return response()->json([
                'message' => 'Peso actualizado correctamente',
                'plantilla' => $plantilla
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar el peso: ' . $e->getMessage()], 500);
        }
    }
}
