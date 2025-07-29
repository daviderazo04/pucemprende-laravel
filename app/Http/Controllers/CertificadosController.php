<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class CertificadosController extends Controller
{
    /**
     * Genera y retorna el certificado con el nombre dinámico.
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
}