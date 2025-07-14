<?php

namespace App\Http\Controllers;

use App\Models\Organizacione;
use App\Models\VwOrganizaciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
class OrganizacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $organizaciones = Organizacione::all();
        return response()->json($organizaciones);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Solo permitir si el usuario tiene rol_id = 1
         if ($request->user()->rol_id !== 1) {
             return response()->json(['message' => 'No tienes permiso para crear organizaciones.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'orgNombre' => 'required|string|max:255',
            'orgAbreviatura' => 'nullable|string|max:10',
            'encarNombre' => 'required|string|max:100',
            'encarApellido' => 'required|string|max:100',
            'encarIdentificacion' => 'required|string|max:20',
            'encarRol' => 'required|string|max:50',
            'orgTelf' => 'nullable|string|max:15',
            'orgEmail' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $organizacion = Organizacione::create([
            'org_nom' => $request->orgNombre,
            'org_abreviatura' => $request->orgAbreviatura,
            'encar_nombre' => $request->encarNombre,
            'encar_apellido' => $request->encarApellido,
            'encar_identificacion' => $request->encarIdentificacion,
            'encar_rol' => $request->encarRol,
            'org_telf' => $request->orgTelf,
            'org_email' => $request->orgEmail
        ]);

        return response()->json($organizacion->id, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Organizacione $organizacione)
    {
        return response()->json($organizacione);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Organizacione $organizacione)
    {
        // Solo permitir si el usuario tiene rol_id = 1
         if ($request->user()->rol_id !== 1) {
             return response()->json(['message' => 'No tienes permiso para actualizar organizaciones.'], 403);
     }

        $validator = Validator::make($request->all(), [
            'orgNombre' => 'required|string|max:255',
            'orgAbreviatura' => 'nullable|string|max:10',
            'encarNombre' => 'required|string|max:100',
            'encarApellido' => 'required|string|max:100',
            'encarIdentificacion' => 'required|string|max:20',
            'encarRol' => 'required|string|max:50',
            'orgTelf' => 'nullable|string|max:15',
            'orgEmail' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $organizacione->update([
            'org_nombre' => $request->orgNombre,
            'org_abreviatura' => $request->orgAbreviatura,
            'encar_nombre' => $request->encarNombre,
            'encar_apellido' => $request->encarApellido,
            'encar_identificacion' => $request->encarIdentificacion,
            'encar_rol' => $request->encarRol,
            'org_telf' => $request->orgTelf,
            'org_email' => $request->orgEmail
        ]);

        return response()->json($organizacione, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organizacione $organizacione)
    {
        // Solo permitir si el usuario tiene rol_id = 1
        // Comentado temporalmente para testing
        // if (request()->user()->rol_id !== 1) {
        //     return response()->json(['message' => 'No tienes permiso para eliminar organizaciones.'], 403);
        // }

        $organizacione->delete();

        return response()->json(['message' => 'Organización eliminada correctamente.'], 200);
    }
    // public function vwOrganizaciones()
    // {
    //     $vwOrganizaciones = VWOrganizaciones::all();
    //     return response()->json($vwOrganizaciones);
    // }
}
