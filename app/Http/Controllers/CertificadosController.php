<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use App\Models\ArchivoEvento;
use App\Models\EventoRolPersona;
use App\Models\Persona;
use App\Models\Certificado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use setasign\Fpdi\Fpdi;
use Carbon\Carbon;

class CertificadosController extends Controller
{
    /**
     * Obtener y generar certificados para una persona en un evento específico
     * GET /api/eventos/{eventoId}/personas/{personaId}/certificados
     */
    public function getCertificadosPersonaEvento(Request $request, $eventoId, $personaId)
    {
        try {
            // Verificar que la persona exista
            $persona = Persona::find($personaId);
            if (!$persona) {
                return response()->json(['message' => 'Persona no encontrada'], 404);
            }

            // Obtener los roles de la persona en el evento
            $rolesPersona = EventoRolPersona::where('evento_id', $eventoId)
                ->where('persona_id', $personaId)
                ->pluck('rol_id')
                ->toArray();

            if (empty($rolesPersona)) {
                return response()->json(['message' => 'La persona no tiene roles asignados en este evento'], 404);
            }

            // Obtener certificados del evento que coincidan con los roles de la persona
            $certificados = Archivo::join('archivo_evento', 'archivo.id', '=', 'archivo_evento.archivo_id')
                ->where('archivo_evento.evento_id', $eventoId)
                ->where('archivo.es_certificado', true)
                ->where('archivo.estado_borrado', false)
                ->select('archivo.*')
                ->get()
                ->filter(function ($certificado) use ($rolesPersona) {
                    // Convertir roles_destinatarios de string a array
                    $rolesDestinatarios = explode(',', $certificado->roles_destinatarios);
                    // Verificar si hay intersección entre los roles de la persona y los roles destinatarios
                    return !empty(array_intersect($rolesPersona, $rolesDestinatarios));
                });

            if ($certificados->isEmpty()) {
                return response()->json(['message' => 'No hay certificados disponibles para esta persona en este evento'], 404);
            }

            // Generar PDFs para cada certificado
            $certificadosGenerados = [];

            // CORREGIDO: usar 'nombre' y 'apellido' (singular)
            $nombreCompleto = trim($persona->nombre . ' ' . $persona->apellido);

            // Si el nombre está vacío, usar el email como fallback
            if (empty($nombreCompleto)) {
                $nombreCompleto = $persona->email;
            }

            foreach ($certificados as $certificado) {
                try {
                    // Generar PDF personalizado
                    $pdfBasePath = storage_path('app/public/' . $certificado->url);
                    if (!file_exists($pdfBasePath)) {
                        continue; // Saltar este certificado si no existe el archivo
                    }

                    // Crear el PDF dinámico en horizontal (landscape)
                    $pdf = new Fpdi();
                    $pdf->AddPage('L');
                    $pdf->setSourceFile($pdfBasePath);
                    $tplIdx = $pdf->importPage(1);
                    $pdf->useTemplate($tplIdx);

                    // Ajusta la fuente y posición según tu plantilla horizontal
                    $pdf->SetFont('Helvetica', '', 40);
                    $pdf->SetTextColor(0, 0, 0);

                    // Obtener el ancho de la página y del texto
                    $pageWidth = $pdf->GetPageWidth();
                    $textWidth = $pdf->GetStringWidth($nombreCompleto);

                    // Calcular la posición X para centrar el texto
                    $x = ($pageWidth - $textWidth) / 2;

                    // Ajusta la posición Y para bajarlo un poco (por ejemplo, 100)
                    $y = 100;

                    $pdf->SetXY($x, $y);
                    $pdf->Write(0, $nombreCompleto);

                    // Generar el PDF como string
                    $pdfContent = $pdf->Output('S');
                    $pdfBase64 = base64_encode($pdfContent);

                    $certificadosGenerados[] = [
                        'id' => $certificado->id,
                        'tipo' => $certificado->tipo,
                        'roles_destinatarios' => $certificado->roles_destinatarios,
                        'pdf_base64' => $pdfBase64,
                        'filename' => 'certificado_' . $certificado->tipo . '_' . str_replace(['@', '.', ' '], '_', $nombreCompleto) . '.pdf'
                    ];
                } catch (\Exception $e) {
                    // Log del error pero continuar con los otros certificados
                    continue;
                }
            }

            if (empty($certificadosGenerados)) {
                return response()->json(['message' => 'Error al generar los certificados'], 500);
            }

            return response()->json([
                'success' => true,
                'persona' => [
                    'id' => $persona->id,
                    'nombre_completo' => $nombreCompleto,
                    'email' => $persona->email
                ],
                'evento_id' => $eventoId,
                'roles_persona' => $rolesPersona,
                'certificados' => $certificadosGenerados,
                'total_certificados' => count($certificadosGenerados)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los certificados',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar un certificado específico para descarga directa
     * GET /api/eventos/{eventoId}/personas/{personaId}/certificados/{certificadoId}/descargar
     */
    public function descargarCertificado(Request $request, $eventoId, $personaId, $certificadoId)
    {
        try {
            // Verificar que la persona exista
            $persona = Persona::find($personaId);
            if (!$persona) {
                return response()->json(['message' => 'Persona no encontrada'], 404);
            }

            // Obtener los roles de la persona en el evento
            $rolesPersona = EventoRolPersona::where('evento_id', $eventoId)
                ->where('persona_id', $personaId)
                ->pluck('rol_id')
                ->toArray();

            // Obtener el certificado específico
            $certificado = Archivo::join('archivo_evento', 'archivo.id', '=', 'archivo_evento.archivo_id')
                ->where('archivo_evento.evento_id', $eventoId)
                ->where('archivo.id', $certificadoId)
                ->where('archivo.es_certificado', true)
                ->where('archivo.estado_borrado', false)
                ->select('archivo.*')
                ->first();

            if (!$certificado) {
                return response()->json(['message' => 'Certificado no encontrado'], 404);
            }

            // Verificar que la persona tenga los roles necesarios
            $rolesDestinatarios = explode(',', $certificado->roles_destinatarios);
            if (empty(array_intersect($rolesPersona, $rolesDestinatarios))) {
                return response()->json(['message' => 'La persona no tiene los roles requeridos para este certificado'], 403);
            }

            // Generar PDF
            $pdfBasePath = storage_path('app/public/' . $certificado->url);
            if (!file_exists($pdfBasePath)) {
                return response()->json(['message' => 'Archivo base no encontrado'], 404);
            }

            // CORREGIDO: usar 'nombre' y 'apellido' (singular)
            $nombreCompleto = trim($persona->nombre . ' ' . $persona->apellido);

            // Si el nombre está vacío, usar el email como fallback
            if (empty($nombreCompleto)) {
                $nombreCompleto = $persona->email;
            }

            // Crear el PDF dinámico en horizontal (landscape)
            $pdf = new Fpdi();
            $pdf->AddPage('L');
            $pdf->setSourceFile($pdfBasePath);
            $tplIdx = $pdf->importPage(1);
            $pdf->useTemplate($tplIdx);

            // Ajusta la fuente y posición según tu plantilla horizontal
            $pdf->SetFont('Helvetica', '', 40);
            $pdf->SetTextColor(0, 0, 0);

            // Obtener el ancho de la página y del texto
            $pageWidth = $pdf->GetPageWidth();
            $textWidth = $pdf->GetStringWidth($nombreCompleto);

            // Calcular la posición X para centrar el texto
            $x = ($pageWidth - $textWidth) / 2;

            // Ajusta la posición Y para bajarlo un poco (por ejemplo, 100)
            $y = 100;

            $pdf->SetXY($x, $y);
            $pdf->Write(0, $nombreCompleto);

            // Salida del PDF
            return response($pdf->Output('S'), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="certificado_' . $certificado->tipo . '_' . str_replace(['@', '.', ' '], '_', $nombreCompleto) . '.pdf"');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al generar el certificado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Genera y retorna el certificado con el nombre dinámico (método original mantenido).
     * GET /api/certificados/{id}/generar?nombre=Nombre+Persona
     */
    public function generar(Request $request, $id)
    {
        $nombre = $request->query('nombre');
        if (!$nombre) {
            return response()->json(['message' => 'El nombre es requerido'], 400);
        }

        $certificado = Certificado::with('plantillas_certificado')->find($id);
        if (!$certificado || !$certificado->plantillas_certificado) {
            return response()->json(['message' => 'Certificado o plantilla no encontrado'], 404);
        }

        // Ruta del PDF base
        $pdfBasePath = storage_path('app/' . $certificado->plantillas_certificado->archivo_base_url);
        if (!file_exists($pdfBasePath)) {
            return response()->json(['message' => 'Archivo base no encontrado'], 404);
        }

        // Crear el PDF dinámico en horizontal (landscape)
        $pdf = new Fpdi();
        $pdf->AddPage('L');
        $pdf->setSourceFile($pdfBasePath);
        $tplIdx = $pdf->importPage(1);
        $pdf->useTemplate($tplIdx);

        // Ajusta la fuente y posición según tu plantilla horizontal
        $pdf->SetFont('Helvetica', '', 40);
        $pdf->SetTextColor(0, 0, 0);

        // Obtener el ancho de la página y del texto
        $pageWidth = $pdf->GetPageWidth();
        $textWidth = $pdf->GetStringWidth($nombre);

        // Calcular la posición X para centrar el texto
        $x = ($pageWidth - $textWidth) / 2;

        // Ajusta la posición Y para bajarlo un poco (por ejemplo, 110)
        $y = 100;

        $pdf->SetXY($x, $y);
        $pdf->Write(0, $nombre);

        // Salida del PDF
        return response($pdf->Output('S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="certificado.pdf"');
    }

    /**
     * Subir certificado para un evento con roles específicos
     * POST /api/eventos/{eventoId}/certificados
     */
    public function store(Request $request, $eventoId)
    {
        // Verificar permisos
        if ($request->user()->rol_id != 1 && $request->user()->rol_id != 8 && $request->user()->rol_id != 2) {
            return response()->json(['message' => 'No tienes permiso para subir certificados'], 403);
        }

        $validator = Validator::make($request->all(), [
            'archivo' => 'required|file|mimes:pdf|max:10240', // 10MB máximo
            'roles_destinatarios' => 'required|string', // Ejemplo: "1,3,5" (IDs de rol_eventos)
            'tipo' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Verificar que el evento exista
            $evento = \App\Models\Evento::find($eventoId);
            if (!$evento) {
                return response()->json(['message' => 'Evento no encontrado'], 404);
            }

            // Verificar que los roles existan y pertenezcan al evento
            $rolesArray = explode(',', $request->roles_destinatarios);
            $rolesValidos = \App\Models\RolEvento::whereIn('id', $rolesArray)->count();

            if ($rolesValidos != count($rolesArray)) {
                return response()->json(['message' => 'Uno o más roles no son válidos'], 400);
            }

            // Subir archivo
            $file = $request->file('archivo');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = Storage::disk('public')->putFileAs('certificados', $file, $fileName);
            $publicUrl = asset('storage/' . $filePath);

            // Crear registro en tabla archivo
            $archivo = Archivo::create([
                'url' => $publicUrl,
                'tipo' => $request->tipo ?? 'certificado',
                'es_certificado' => true,
                'roles_destinatarios' => $request->roles_destinatarios, // "1,3,5"
                'creado_en' => Carbon::now(),
                'actualizado_en' => Carbon::now(),
                'estado_borrado' => false
            ]);

            // Vincular archivo con evento
            ArchivoEvento::create([
                'archivo_id' => $archivo->id,
                'evento_id' => $eventoId,
                'creado_en' => Carbon::now(),
                'actualizado_en' => Carbon::now(),
                'estado_borrado' => false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Certificado subido exitosamente',
                'certificado' => [
                    'id' => $archivo->id,
                    'url' => $archivo->url,
                    'tipo' => $archivo->tipo,
                    'roles_destinatarios' => $archivo->roles_destinatarios,
                    'evento_id' => $eventoId,
                    'descripcion' => $request->descripcion
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al subir el certificado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener certificados de un evento
     * GET /api/eventos/{eventoId}/certificados
     */
    public function getCertificadosPorEvento($eventoId)
    {
        try {
            $certificados = Archivo::join('archivo_evento', 'archivo.id', '=', 'archivo_evento.archivo_id')
                ->where('archivo_evento.evento_id', $eventoId)
                ->where('archivo.es_certificado', true)
                ->where('archivo.estado_borrado', false)
                ->select('archivo.*')
                ->get()
                ->map(function ($certificado) {
                    return [
                        'id' => $certificado->id,
                        'tipo' => $certificado->tipo,
                        'url' => $certificado->url,
                        'roles_destinatarios' => $certificado->roles_destinatarios,
                        'roles_array' => explode(',', $certificado->roles_destinatarios),
                        'creado_en' => $certificado->creado_en
                    ];
                });

            return response()->json([
                'success' => true,
                'certificados' => $certificados,
                'total' => $certificados->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los certificados',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener certificados del usuario autenticado para un evento específico
     * GET /api/eventos/{eventoId}/mis-certificados
     */
    public function misCertificados(Request $request, $eventoId)
    {
        try {
            // Obtener usuario autenticado
            $usuario = $request->user();
            if (!$usuario) {
                return response()->json(['message' => 'Usuario no autenticado'], 401);
            }

            // Obtener persona asociada al usuario
            $persona = \App\Models\Persona::where('email', $usuario->email)->first();
            if (!$persona) {
                return response()->json(['message' => 'No se encontró la persona asociada al usuario'], 404);
            }

            // Obtener los roles de la persona en el evento
            $rolesPersona = EventoRolPersona::where('evento_id', $eventoId)
                ->where('persona_id', $persona->id)
                ->pluck('rol_id')
                ->toArray();

            if (empty($rolesPersona)) {
                return response()->json(['message' => 'No tienes roles asignados en este evento'], 404);
            }

            // Obtener certificados del evento que coincidan con los roles de la persona
            $certificados = Archivo::join('archivo_evento', 'archivo.id', '=', 'archivo_evento.archivo_id')
                ->where('archivo_evento.evento_id', $eventoId)
                ->where('archivo.es_certificado', true)
                ->where('archivo.estado_borrado', false)
                ->select('archivo.*')
                ->get()
                ->filter(function ($certificado) use ($rolesPersona) {
                    // Convertir roles_destinatarios de string a array
                    $rolesDestinatarios = explode(',', $certificado->roles_destinatarios);
                    // Verificar si hay intersección entre los roles de la persona y los roles destinatarios
                    return !empty(array_intersect($rolesPersona, $rolesDestinatarios));
                });

            if ($certificados->isEmpty()) {
                return response()->json(['message' => 'No tienes certificados disponibles para este evento'], 404);
            }

            // Generar PDFs para cada certificado
            $certificadosGenerados = [];

            // CORREGIDO: usar 'nombre' y 'apellido' (singular)
            $nombreCompleto = trim($persona->nombre . ' ' . $persona->apellido);

            // Si el nombre está vacío, usar el email como fallback
            if (empty($nombreCompleto)) {
                $nombreCompleto = $persona->email;
            }

            foreach ($certificados as $certificado) {
                try {
                    // Generar PDF personalizado
                    $pdfBasePath = storage_path('app/public/' . $certificado->url);
                    if (!file_exists($pdfBasePath)) {
                        continue; // Saltar este certificado si no existe el archivo
                    }

                    // Crear el PDF dinámico en horizontal (landscape)
                    $pdf = new Fpdi();
                    $pdf->AddPage('L');
                    $pdf->setSourceFile($pdfBasePath);
                    $tplIdx = $pdf->importPage(1);
                    $pdf->useTemplate($tplIdx);

                    // Ajusta la fuente y posición según tu plantilla horizontal
                    $pdf->SetFont('Helvetica', '', 40);
                    $pdf->SetTextColor(0, 0, 0);

                    // Obtener el ancho de la página y del texto
                    $pageWidth = $pdf->GetPageWidth();
                    $textWidth = $pdf->GetStringWidth($nombreCompleto);

                    // Calcular la posición X para centrar el texto
                    $x = ($pageWidth - $textWidth) / 2;

                    // Ajusta la posición Y para bajarlo un poco (por ejemplo, 100)
                    $y = 100;

                    $pdf->SetXY($x, $y);
                    $pdf->Write(0, $nombreCompleto);

                    // Generar el PDF como string
                    $pdfContent = $pdf->Output('S');
                    $pdfBase64 = base64_encode($pdfContent);

                    $certificadosGenerados[] = [
                        'id' => $certificado->id,
                        'tipo' => $certificado->tipo,
                        'roles_destinatarios' => $certificado->roles_destinatarios,
                        'pdf_base64' => $pdfBase64,
                        'filename' => 'certificado_' . $certificado->tipo . '_' . str_replace(['@', '.', ' '], '_', $nombreCompleto) . '.pdf'
                    ];
                } catch (\Exception $e) {
                    // Log del error pero continuar con los otros certificados
                    continue;
                }
            }

            if (empty($certificadosGenerados)) {
                return response()->json(['message' => 'Error al generar los certificados'], 500);
            }

            return response()->json([
                'success' => true,
                'persona' => [
                    'id' => $persona->id,
                    'nombre_completo' => $nombreCompleto,
                    'email' => $persona->email
                ],
                'evento_id' => $eventoId,
                'roles_persona' => $rolesPersona,
                'certificados' => $certificadosGenerados,
                'total_certificados' => count($certificadosGenerados)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener tus certificados',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar un certificado específico del usuario autenticado
     * GET /api/eventos/{eventoId}/mis-certificados/{certificadoId}/descargar
     */
    public function descargarMiCertificado(Request $request, $eventoId, $certificadoId)
    {
        try {
            // Obtener usuario autenticado
            $usuario = $request->user();
            if (!$usuario) {
                return response()->json(['message' => 'Usuario no autenticado'], 401);
            }

            // Obtener persona asociada al usuario
            $persona = \App\Models\Persona::where('email', $usuario->email)->first();
            if (!$persona) {
                return response()->json(['message' => 'No se encontró la persona asociada al usuario'], 404);
            }

            // Obtener los roles de la persona en el evento
            $rolesPersona = EventoRolPersona::where('evento_id', $eventoId)
                ->where('persona_id', $persona->id)
                ->pluck('rol_id')
                ->toArray();

            // Obtener el certificado específico
            $certificado = Archivo::join('archivo_evento', 'archivo.id', '=', 'archivo_evento.archivo_id')
                ->where('archivo_evento.evento_id', $eventoId)
                ->where('archivo.id', $certificadoId)
                ->where('archivo.es_certificado', true)
                ->where('archivo.estado_borrado', false)
                ->select('archivo.*')
                ->first();

            if (!$certificado) {
                return response()->json(['message' => 'Certificado no encontrado'], 404);
            }

            // Verificar que la persona tenga los roles necesarios
            $rolesDestinatarios = explode(',', $certificado->roles_destinatarios);
            if (empty(array_intersect($rolesPersona, $rolesDestinatarios))) {
                return response()->json(['message' => 'No tienes los roles requeridos para este certificado'], 403);
            }

            // Generar PDF
            $pdfBasePath = storage_path('app/public/' . $certificado->url);
            if (!file_exists($pdfBasePath)) {
                return response()->json(['message' => 'Archivo base no encontrado'], 404);
            }

            // CORREGIDO: usar 'nombre' y 'apellido' (singular)
            $nombreCompleto = trim($persona->nombre . ' ' . $persona->apellido);

            // Si el nombre está vacío, usar el email como fallback
            if (empty($nombreCompleto)) {
                $nombreCompleto = $persona->email;
            }

            // Crear el PDF dinámico en horizontal (landscape)
            $pdf = new Fpdi();
            $pdf->AddPage('L');
            $pdf->setSourceFile($pdfBasePath);
            $tplIdx = $pdf->importPage(1);
            $pdf->useTemplate($tplIdx);

            // Ajusta la fuente y posición según tu plantilla horizontal
            $pdf->SetFont('Helvetica', '', 40);
            $pdf->SetTextColor(0, 0, 0);

            // Obtener el ancho de la página y del texto
            $pageWidth = $pdf->GetPageWidth();
            $textWidth = $pdf->GetStringWidth($nombreCompleto);

            // Calcular la posición X para centrar el texto
            $x = ($pageWidth - $textWidth) / 2;

            // Ajusta la posición Y para bajarlo un poco (por ejemplo, 100)
            $y = 100;

            $pdf->SetXY($x, $y);
            $pdf->Write(0, $nombreCompleto);

            // Salida del PDF
            return response($pdf->Output('S'), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="certificado_' . $certificado->tipo . '_' . str_replace(['@', '.', ' '], '_', $nombreCompleto) . '.pdf"');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al generar el certificado',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
