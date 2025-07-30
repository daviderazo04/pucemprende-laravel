<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use App\Models\Evento;
use App\Models\EventoRolPersona;
use App\Models\Persona;
use App\Models\Proyecto;
use App\Models\MiembrosProyecto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArchivoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $archivo = Archivo::all();
        return response()->json($archivo);
    }
    /**
     * Maneja la subida de archivos desde el frontend.
     * Almacena el archivo y guarda su URL en la base de datos.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeFile(Request $request)
    {
        // Código comentado de pruebas anteriores
        // Storage::disk('public')->put("texto.txt", "Hola");
        // if($req -> isMethod('POST')){
        //     $file = $req->file('file');
        //     $name = $req->input('name');
        //     $file -> storeAs('',$name.".".$file -> extension(),'public');
        // }
        // Validar el archivo recibido
        $request->validate([
            'file' => 'required|file|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240', // Máximo 10MB (10240 KB)
            'name' => 'sometimes|string|max:255', // Nombre opcional
            'evento_id' => 'required|integer|exists:eventos,id',
        ]);

          // Obtener la persona logueada
        $persona = Persona::where('users_id', $request->user()->id)->first();
        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada'], 404);
        }

        // Obtener el evento del request
        $evento = Evento::find($request->evento_id);
        if (!$evento) {
            return response()->json(['error' => 'Evento no encontrado'], 404);
        }

        // Verificar si la persona es admin del evento
        $esAutorEvento = EventoRolPersona::where('evento_id', $evento->id)
                                         ->where('persona_id', $persona->id)
                                         ->where('rol_id', 1)
                                         ->exists();

        // Solo permitir si el usuario es superadmin o autor del evento
        if ( $request->user()->rol_id !== 8 && !$esAutorEvento) {
            return response()->json(['message' => 'No tienes permiso para subir archivos.'], 403);
        }


        if ($request->hasFile('file')) {
            $uploadedFile = $request->file('file');

            // Generar un nombre único usando UUID y la extensión original
            $originalExtension = $uploadedFile->getClientOriginalExtension();
            $fileName = Str::uuid() . '.' . $originalExtension;

            try {
                // Almacenar el archivo en el directorio 'uploads' dentro del disco 'public'
                // Esto significa que se guardará en storage/app/public/uploads/
                $path = Storage::disk('public')->putFileAs('uploads', $uploadedFile, $fileName);

                // Construir la URL pública para el archivo
                // Esto asume que has ejecutado `php artisan storage:link`
                $publicUrl = asset('storage/' . $path);

                // Guardar la información del archivo en la tabla 'archivos'
                $archivo = Archivo::create([
                    'creado_en' => now(),
                    'actualizado_en' => now(),
                    'estado_borrado' => false,
                    'borrado_en' => null,
                    'url' => $publicUrl, // Almacenar la URL pública
                    'tipo' => $originalExtension // Almacenar el tipo de archivo (ej: '.png', '.jpg')
                ]);

                return response()->json([
                    'message' => 'Archivo subido y registro creado exitosamente!',
                    'file' => [
                        'id' => $archivo->id,
                        'original_name' => $uploadedFile->getClientOriginalName(),
                        'stored_name' => $fileName,
                        'path' => $path,
                        'url' => $publicUrl,
                        'tipo' => $archivo->tipo,
                    ]
                ], 201); // 201 Creado

            } catch (\Exception $e) {
                // Registrar el error para depuración
                return response()->json([
                    'message' => 'Error al subir el archivo.',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        return response()->json([
            'message' => 'No se encontró archivo en la solicitud.'
        ], 400); // Solicitud incorrecta
    }
    // función para crear un archivo en un proyecto
    public function storeFileProyecto(Request $request)
    {
        // Validar el archivo recibido
        $request->validate([
            'file' => 'required|file|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240', // Máximo 10MB (10240 KB)
            'name' => 'sometimes|string|max:255', // Nombre opcional
            'proyecto_id' => 'required|integer|exists:proyectos,id',
        ]);

          // Obtener la persona logueada
        $persona = Persona::where('users_id', $request->user()->id)->first();
        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada'], 404);
        }

        // Obtener el proyecto del request
        $proyecto = Proyecto::find($request->proyecto_id);
        if (!$proyecto) {
            return response()->json(['error' => 'Proyecto no encontrado'], 404);
        }

        // Verificar si la persona es admin del proyecto
        $esLiderProyecto = MiembrosProyecto::where('proyecto_id', $proyecto->id)
                                         ->where('persona_id', $persona->id)
                                         ->where('rol_id', 1)
                                         ->exists();

        // Solo permitir si el usuario es superadmin o autor del proyecto
        if ( $request->user()->rol_id !== 8 && !$esLiderProyecto) {
            return response()->json(['message' => 'No tienes permiso para subir archivos.'], 403);
        }


        if ($request->hasFile('file')) {
            $uploadedFile = $request->file('file');

            // Generar un nombre único usando UUID y la extensión original
            $originalExtension = $uploadedFile->getClientOriginalExtension();
            $fileName = Str::uuid() . '.' . $originalExtension;

            try {
                // Almacenar el archivo en el directorio 'uploads' dentro del disco 'public'
                // Esto significa que se guardará en storage/app/public/uploads/
                $path = Storage::disk('public')->putFileAs('uploads', $uploadedFile, $fileName);

                // Construir la URL pública para el archivo
                // Esto asume que has ejecutado `php artisan storage:link`
                $publicUrl = asset('storage/' . $path);

                // Guardar la información del archivo en la tabla 'archivos'
                $archivo = Archivo::create([
                    'creado_en' => now(),
                    'actualizado_en' => now(),
                    'estado_borrado' => false,
                    'borrado_en' => null,
                    'url' => $publicUrl, // Almacenar la URL pública
                    'tipo' => $originalExtension // Almacenar el tipo de archivo (ej: '.png', '.jpg')
                ]);

                return response()->json([
                    'message' => 'Archivo subido y registro creado exitosamente!',
                    'file' => [
                        'id' => $archivo->id,
                        'original_name' => $uploadedFile->getClientOriginalName(),
                        'stored_name' => $fileName,
                        'path' => $path,
                        'url' => $publicUrl,
                        'tipo' => $archivo->tipo,
                    ]
                ], 201); // 201 Creado

            } catch (\Exception $e) {
                // Registrar el error para depuración
                return response()->json([
                    'message' => 'Error al subir el archivo.',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        return response()->json([
            'message' => 'No se encontró archivo en la solicitud.'
        ], 400); // Solicitud incorrecta
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para crear archivos.'], 403);
        }
        $validator = Validator::make($request->all(), [
            'url' => 'required|string|max:100',
            'tipo' => 'required|string|max:50'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $archivo = Archivo::create([
            'creado_en' => now(),
            'actualizado_en' => now(),
            'estado_borrado' => false,
            'borrado_en' => null,
            'url' => $request->url,
            'tipo' => $request->tipo
        ]);
        return response()->json($archivo->id, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Archivo $archivo)
    {
        return response()->json($archivo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Archivo $archivo)
    {
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para actualizar archivos.'], 403);
        }
        $validator = Validator::make($request->all(), [
            'url' => 'required|string|max:100',
            'tipo' => 'required|string|max:50'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $archivo->update([
            'actualizado_en' => now(),
            'url' => $request->url,
            'tipo' => $request->tipo
        ]);
        return response()->json($archivo, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Archivo $archivo)
    {
        // Solo permitir si el usuario es admin o superadmin
        if (request()->user()->rol_id !== 1 && request()->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para eliminar archivos.'], 403);
        }
        $archivo->update([
            'estado_borrado' => true,
            'borrado_en' => now()
        ]);
        return response()->json(['message' => 'Archivo eliminado correctamente.'], 200);
    }
}
