<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
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
     * Handles file storage from frontend.
     * Stores the file and its URL in the database.
     */
    public function storeFile(Request $request)
    {
        // Storage::disk('public')->put("texto.txt", "Hola");

        // if($req -> isMethod('POST')){
        //     $file = $req->file('file');
        //     $name = $req->input('name');
        //     $file -> storeAs('',$name.".".$file -> extension(),'public');
        // }
        // Validate the incoming file
        $request->validate([
            'file' => 'required|file|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240', // Max 10MB (10240 KB)
            'name' => 'sometimes|string|max:255',
            'tipo' => 'required|string|max:50',
        ]);

        if ($request->hasFile('file')) {
            $uploadedFile = $request->file('file');

            // Generate a unique file name using UUID and its original extension
            $originalExtension = $uploadedFile->getClientOriginalExtension();
            $fileName = Str::uuid() . '.' . $originalExtension;

            try {
                // Store the file in the 'uploads' directory within the 'public' disk
                // This means it will be saved in storage/app/public/uploads/
                $path = Storage::disk('public')->putFileAs('uploads', $uploadedFile, $fileName);

                // Construct the public URL for the file
                // This assumes you have run `php artisan storage:link`
                $publicUrl = asset('storage/' . $path);

                // Save the file information to your 'archivos' table
                $archivo = Archivo::create([
                    'creado_en' => now(),
                    'actualizado_en' => now(),
                    'estado_borrado' => false,
                    'borrado_en' => null,
                    'url' => $publicUrl, // Store the public URL
                    'tipo' => $request->tipo, // Store the file type (e.g., 'cover', 'additional')
                ]);

                return response()->json([
                    'message' => 'File uploaded and record created successfully!',
                    'file' => [
                        'id' => $archivo->id,
                        'original_name' => $uploadedFile->getClientOriginalName(),
                        'stored_name' => $fileName,
                        'path' => $path,
                        'url' => $publicUrl,
                        'tipo' => $archivo->tipo,
                    ]
                ], 201); // 201 Created

            } catch (\Exception $e) {
                // Log the error for debugging
                return response()->json([
                    'message' => 'Error uploading file.',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        return response()->json([
            'message' => 'No file found in the request.'
        ], 400); // Bad Request
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if($request->user()->rol_id !== 1) {
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
        if($request->user()->rol_id !== 1) {
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
        if(request()->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permiso para eliminar archivos.'], 403);
        }
        $archivo->update([
            'estado_borrado' => true,
            'borrado_en' => now()
        ]);
        return response()->json(['message' => 'Archivo eliminado correctamente.'], 200);
    }
}
