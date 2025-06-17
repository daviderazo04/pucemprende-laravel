<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EquipoProyectoController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'nombreEquipo' => 'required|string|max:50',
            'idEvento' => 'required|integer|exists:eventos,id',
            'tituloProyecto' => 'required|string|max:50',
            'descProyecto' => 'required|string',
        ]);

        $msjConfirmacion = '';

        DB::statement('CALL sp_crearEquipoProyecto(?, ?, ?, ?, @msjConfirmacion)', [
            $request->nombreEquipo,
            $request->idEvento,
            $request->tituloProyecto,
            $request->descProyecto
        ]);

        $resultado = DB::select('SELECT @msjConfirmacion as msjConfirmacion');

        return response()->json([
            'mensaje' => $resultado[0]->msjConfirmacion
        ]);
    }
}