<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\Proyecto;
use App\Models\Persona;
use App\Models\MiembrosProyecto;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\EventoRolPersona;

class ProyectoQrController extends Controller
{
    /**
     * Genera un código QR en base al ID del proyecto
     * Acceso permitido a: miembros del proyecto, admins del sistema, autores del evento
     */
    public function generarQrProyecto($id, Request $request)
    {
        // Obtener persona asociada al usuario
        $persona = Persona::where('users_id', $request->user()->id)->first();
        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada'], 404);
        }

        // Verificar que el proyecto exista
        $proyecto = Proyecto::find($id);
        if (!$proyecto) {
            return response()->json(['error' => 'Proyecto no encontrado'], 404);
        }

        // Obtener el equipo y evento relacionado
        $equipo = Equipo::find($proyecto->equipo_id);
        if (!$equipo) {
            return response()->json(['error' => 'Equipo no encontrado'], 404);
        }

        $evento = Evento::find($equipo->evento_id);
        if (!$evento) {
            return response()->json(['error' => 'Evento no encontrado'], 404);
        }

        // Verificar si la persona es miembro del proyecto
        $esMiembro = MiembrosProyecto::where('proyecto_id', $proyecto->id)
                                     ->where('persona_id', $persona->id)
                                     ->exists();

        // Verificar si es admin general del sistema (rol_id = 8)
        $esAdminSistema = $request->user()->rol_id == 8;

        // Verificar si es autor del evento (rol_id = 1 en evento_rol_persona)
        $esAutorEvento = EventoRolPersona::where('evento_id', $evento->id)
                                         ->where('persona_id', $persona->id)
                                         ->where('rol_id', 1)
                                         ->exists();

        // Si no cumple ninguno de los roles permitidos, denegar
        if (!$esMiembro && !$esAdminSistema && !$esAutorEvento) {
            return response()->json([
                'error' => 'No tienes permisos para ver o generar el QR de este proyecto.'
            ], 403);
        }

        // Generar el QR
        $urlFrontend = "https://pucemprende.netlify.app/calificar-proyecto/" . $proyecto->id;
        $qr = QrCode::format('svg')->size(300)->generate($urlFrontend);
        $base64 = base64_encode($qr);

        return response()->json([
            'qr' => $base64,
            'url' => $urlFrontend
        ]);
    }
}
