<?php

namespace App\Http\Controllers;

use App\Models\Cronograma;
use App\Models\Evento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CronogramaController extends Controller
{
    // GET: Cualquier usuario puede ver la lista
    public function index()
    {
        $cronogramas = Cronograma::with('evento')->get();
        return response()->json($cronogramas);
    }

    // GET: Cualquier usuario puede ver uno
    public function show($id)
    {
        $cronograma = Cronograma::with('evento', 'actividades_cronogramas')->findOrFail($id);
        return response()->json($cronograma);
    }

    // POST: Solo admin o autor del evento
    public function store(Request $request)
    {
        $request->validate([
            'evento_id' => 'required|exists:eventos,id',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $evento = Evento::findOrFail($request->evento_id);
        $user = Auth::user();

        if (!$user->is_admin && $evento->persona_id !== $user->persona_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $cronograma = Cronograma::create($request->all());
        return response()->json($cronograma, 201);
    }

    // PUT/PATCH: Solo admin o autor del evento
    public function update(Request $request, $id)
    {
        $cronograma = Cronograma::findOrFail($id);
        $evento = $cronograma->evento;
        $user = Auth::user();

        if (!$user->is_admin && $evento->persona_id !== $user->persona_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $request->validate([
            'titulo' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'sometimes|required|date',
            'fecha_fin' => 'sometimes|required|date|after_or_equal:fecha_inicio',
        ]);

        $cronograma->update($request->all());
        return response()->json($cronograma);
    }

    // DELETE: Solo admin o autor del evento
    public function destroy($id)
    {
        $cronograma = Cronograma::findOrFail($id);
        $evento = $cronograma->evento;
        $user = Auth::user();

        if (!$user->is_admin && $evento->persona_id !== $user->persona_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $cronograma->delete();
        return response()->json(['message' => 'Eliminado correctamente']);
    }
}
