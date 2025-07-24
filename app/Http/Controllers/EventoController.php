<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Models\EventoRolPersona;

class EventoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $eventos = Evento::with('categorium')->get()->map(function ($evento) {
            $eventoArray = $evento->toArray();
            $eventoArray['categoria'] = $evento->categorium ? $evento->categorium->nombre : null;
            unset($eventoArray['categoria_id'], $eventoArray['categorium']);
            return $eventoArray;
        });
        return response()->json($eventos);
    }

    /**
     * Obtiene eventos paginados usando el Stored Procedure GetEventosLimitOffset.
     * Los parámetros 'limit' y 'offset' se esperan en la URL de la solicitud.
     * Por ejemplo: /api/eventos/paginated?limit=15&offset=0
     *
     * @param Request $request La instancia de la solicitud HTTP.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaginatedEvents(Request $request)
    {
        // Validar los parámetros de entrada 'limit', 'offset' y 'search'
        $validator = Validator::make($request->all(), [
            'limit' => 'required|integer|min:1',
            'offset' => 'required|integer|min:0',
            'search' => 'nullable|string|max:255', //Parámetro de búsqueda opcional
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $searchQuery = $request->input('search');

        try {
            $baseQuery = Evento::query();

            if ($searchQuery) {
                $baseQuery->where(function ($query) use ($searchQuery) {
                    $query->where('nombre', 'like', '%' . $searchQuery . '%')
                        ->orWhere('descripcion', 'like', '%' . $searchQuery . '%');
                });
            }

            $totalCount = $baseQuery->count();

            $paginatedEvents = $baseQuery->with('categorium')
                ->offset($offset)
                ->limit($limit)
                ->get();

            $formattedEvents = $paginatedEvents->map(function ($evento) {
                $eventoArray = $evento->toArray();
                $eventoArray['categoria'] = $evento->categorium ? $evento->categorium->nombre : null;
                unset($eventoArray['categoria_id'], $eventoArray['categorium']);
                return $eventoArray;
            });

            return response()->json([
                'data' => $formattedEvents,
                'total' => $totalCount,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener eventos paginados.', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para crear eventos.'], 403);
        }

        $persona = Persona::where('users_id', auth()->id())->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'capacidad' => 'nullable|integer|min:1',
            'espacio' => 'nullable|string|max:30',
            'modalidad' => 'required|string|max:10',
            'sede_id' => 'nullable|integer',
            'categoria_id' => 'nullable|integer',
            'hayEquipos' => 'nullable|integer|min:0',
            'hayFormulario' => 'nullable|boolean',
            'estado' => 'required|string|max:15',
            'inscripcionesAbiertas' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Iniciar una transacción de base de datos para asegurar atomicidad
        DB::beginTransaction();
        try {
            $evento = Evento::create([
                'creado_en' => Carbon::now(),
                'actualizado_en' => Carbon::now(),
                'estado_borrado' => false,
                'borrado_en' => null,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'capacidad' => $request->capacidad,
                'espacio' => $request->espacio,
                'modalidad' => $request->modalidad,
                'sede_id' => $request->sede_id,
                'categoria_id' => $request->categoria_id,
                'hayEquipos' => $request->hayEquipos ?? 0,
                'hayFormulario' => $request->hayFormulario ?? 0,
                'estado' => $request->estado,
                'inscripcionesAbiertas' => $request->inscripcionesAbiertas ?? 0,
            ]);

            // Insertar el registro en la tabla evento_rol_persona
            // Asociar el evento recién creado con la persona que lo crea y el rol_evento_id 1
            EventoRolPersona::create([
                'evento_id' => $evento->id, // Acceso correcto al ID del evento recién creado
                'rol_id' => 1, // ID del rol de 'Autor' o 'Creador'
                'persona_id' => $persona->id, // Acceso correcto al ID de la persona
            ]);

            DB::commit(); // Confirmar la transacción si todo fue exitoso
            return response()->json($evento, 201);

        } catch (\Exception $e) {
            DB::rollBack(); // Revertir la transacción en caso de cualquier error
            return response()->json(['message' => 'Error al crear el evento y asignar el rol.', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(Evento $evento)
    {
        $evento->load('categorium');
        $eventoArray = $evento->toArray();
        $eventoArray['categoria'] = $evento->categorium ? $evento->categorium->nombre : null;
        unset($eventoArray['categoria_id'], $eventoArray['categorium']);
        return response()->json($eventoArray);
    }

    public function update(Request $request, Evento $evento)
    {
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para actualizar eventos.'], 403);
        }

        $persona = Persona::where('users_id', $request->user()->id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        // Verificar si el usuario tiene rol_id = 1 (administrador del sistema)
        // O si la persona es el autor del evento (rol_id = 1 para este evento en evento_rol_persona)
        $isSystemAdmin = ($request->user()->rol_id == 8);
        $isEventAuthor = EventoRolPersona::where('evento_id', $evento->id)
                                        ->where('persona_id', $persona->id)
                                        ->where('rol_id', 1) 
                                        ->exists();

        if (!$isSystemAdmin && !$isEventAuthor) {
            return response()->json(['message' => 'No tienes permiso para actualizar este evento.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'capacidad' => 'nullable|integer|min:1',
            'espacio' => 'nullable|string|max:30',
            'modalidad' => 'nullable|string|max:10',
            'sede_id' => 'nullable|integer',
            'categoria_id' => 'nullable|integer',
            'hayEquipos' => 'nullable|integer|min:0',
            'hayFormulario' => 'nullable|boolean',
            'estado' => 'nullable|string|max:15',
            'inscripcionesAbiertas' => 'nullable|boolean',
            'estado_borrado' => 'nullable|boolean',
            'borrado_en' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $evento->fill($request->only([
            'nombre',
            'descripcion',
            'fecha_inicio',
            'fecha_fin',
            'capacidad',
            'espacio',
            'modalidad',
            'sede_id',
            'categoria_id',
            'hayEquipos',
            'hayFormulario',
            'estado',
            'inscripcionesAbiertas',
            'estado_borrado',
            'borrado_en',
        ]));
        $evento->actualizado_en = Carbon::now();
        $evento->save();

        return response()->json($evento);
    }


    /**
     * Obtiene el detalle completo de un evento con sus cronogramas y actividades anidadas.
     * Nueva ruta: /api/eventos-cronogramas/{id}
     *
     * @param int $id El ID del evento.
     * @return \Illuminate\Http\JsonResponse
     */
    public function obtenerConDetallesCompleto(int $id)
    {
        // Opcional: Si el usuario debe tener rol_id = 1 para ver esto
        // if (request()->user()->rol_id !== 1) {
        //     return response()->json(['message' => 'No tienes permiso para ver el detalle completo de eventos.'], 403);
        // }

        // Ejecutar el stored procedure
        $results = DB::select('CALL sp_obtener_detalle_evento_consolidado(?)', [$id]);

        if (empty($results)) {
            return response()->json(['message' => 'Evento no encontrado o borrado.'], 404);
        }

        $evento = null;
        $cronogramas = []; // Usaremos un array asociativo para agrupar por cronograma_id

        foreach ($results as $row) {
            // Inicializar el evento una sola vez con los datos de la primera fila
            if ($evento === null) {
                $evento = [
                    "id" => $row->evento_id,
                    "creado_en" => $row->evento_creado_en,
                    "actualizado_en" => $row->evento_actualizado_en,
                    "estado_borrado" => (bool)$row->evento_estado_borrado,
                    "borrado_en" => $row->evento_borrado_en,
                    "nombre" => $row->evento_nombre,
                    "descripcion" => $row->evento_descripcion,
                    "fecha_inicio" => $row->evento_fecha_inicio,
                    "fecha_fin" => $row->evento_fecha_fin,
                    "capacidad" => $row->evento_capacidad,
                    "espacio" => $row->evento_espacio,
                    "modalidad" => $row->evento_modalidad,
                    "sede_id" => $row->evento_sede_id,
                    "categoria_id" => $row->evento_categoria_id,
                    "hayEquipos" => (int)$row->evento_hayEquipos,
                    "hayFormulario" => (bool)$row->evento_hayFormulario,
                    "estado" => $row->evento_estado,
                    "inscripcionesAbiertas" => (bool)$row->evento_inscripcionesAbiertas,
                    "cronogramas" => []
                ];

                // *********** IMPORTANTE ***********
                // Aquí se carga el nombre de la categoría usando Eloquent.
                // Es una consulta adicional, pero asegura que el nombre de la categoría esté.
                // Si modificas el SP para que devuelva el nombre de la categoría,
                // elimina esta sección y usa directamente $row->categoria si es el alias en el SP.
                $eventoModel = Evento::find($row->evento_id);
                if ($eventoModel) { // Asegura que el modelo exista
                    $eventoModel->load('categorium'); // Carga la relación
                    $evento['categoria'] = $eventoModel->categorium ? $eventoModel->categorium->nombre : null;
                }
                // **********************************
            }

            // Procesar cronogramas si existen para esta fila
            if ($row->cronograma_id !== null) {
                if (!isset($cronogramas[$row->cronograma_id])) {
                    $cronogramas[$row->cronograma_id] = [
                        "id" => $row->cronograma_id,
                        "evento_id" => $row->evento_id,
                        "titulo" => $row->cronograma_titulo,
                        "descripcion" => $row->cronograma_descripcion,
                        "fecha_inicio" => $row->cronograma_fecha_inicio,
                        "fecha_fin" => $row->cronograma_fecha_fin,
                        "creado_en" => $row->cronograma_creado_en,
                        "actualizado_en" => $row->cronograma_actualizado_en,
                        "actividades_cronogramas" => []
                    ];
                }

                // Procesar actividades de cronograma si existen para esta fila
                if ($row->actividad_id !== null) {
                    $actividad = [
                        "id" => $row->actividad_id,
                        "cronograma_id" => $row->cronograma_id,
                        "titulo" => $row->actividad_titulo,
                        "descripcion" => $row->actividad_descripcion,
                        "fecha_inicio" => $row->actividad_fecha_inicio,
                        "fecha_fin" => $row->actividad_fecha_fin,
                        "orden" => $row->actividad_orden,
                        "dependencia_id" => $row->actividad_dependencia_id,
                        "creado_en" => $row->actividad_creado_en,
                        "actualizado_en" => $row->actividad_actualizado_en
                    ];
                    $cronogramas[$row->cronograma_id]['actividades_cronogramas'][] = $actividad;
                }
            }
        }

        // Si se encontró el evento, adjuntar los cronogramas (y sus actividades)
        if ($evento !== null) {
            $evento['cronogramas'] = array_values($cronogramas);
        }

        return response()->json($evento);
    }

    public function destroy(Request $request, Evento $evento)
    {
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para actualizar eventos.'], 403);
        }

        $persona = Persona::where('users_id', $request->user()->id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        // Verificar si el usuario tiene rol_id = 1 (administrador del sistema)
        // O si la persona es el autor del evento (rol_id = 1 para este evento en evento_rol_persona)
        $isSystemAdmin = ($request->user()->rol_id == 8);
        $isEventAuthor = EventoRolPersona::where('evento_id', $evento->id)
                                        ->where('persona_id', $persona->id)
                                        ->where('rol_id', 1) 
                                        ->exists();

        if (!$isSystemAdmin && !$isEventAuthor) {
            return response()->json(['message' => 'No tienes permiso para actualizar este evento.'], 403);
        }

        $evento->estado_borrado = true;
        $evento->borrado_en = Carbon::now();
        $evento->actualizado_en = Carbon::now();
        $evento->save();

        return response()->json(['message' => 'Evento marcado como borrado lógicamente.'], 200);
    }
}
