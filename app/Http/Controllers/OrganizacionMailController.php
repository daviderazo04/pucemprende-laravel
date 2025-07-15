<?php

namespace App\Http\Controllers;

use App\Mail\EnviarCorreoOrganizaciones;
use App\Models\Organizacione;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OrganizacionMailController extends Controller
{
    public function enviarCorreo(Request $request)
    {
        $user = $request->user();

        // Verificar si es rol 1 (Admin) o 8 (Superadmin)
        if (!in_array($user->rol_id, [1, 8])) {
            return response()->json(['message' => 'No tienes permiso para enviar correos.'], 403);
        }
        
        $request->validate([
            'asunto' => 'required|string',
            'contenido' => 'required|string',
        ]);

        $asunto = $request->asunto;
        $contenido = $request->contenido;

        // Obtiene todos los correos
        $correos = Organizacione::whereNotNull('org_email')->pluck('org_email')->toArray();

        if (count($correos) === 0) {
            return response()->json(['message' => 'No hay correos registrados.'], 404);
        }

        foreach ($correos as $correo) {
            Mail::to($correo)->send(new EnviarCorreoOrganizaciones($asunto, $contenido));
        }

        return response()->json(['message' => 'Correos enviados correctamente.']);
    }
}
