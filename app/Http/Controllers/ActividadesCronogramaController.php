<?php

namespace App\Http\Controllers;

use App\Models\ActividadesCronograma;
use App\Models\Cronograma;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActividadesCronogramaController extends Controller
{
    // GET: Cualquier usuario puede ver la lista de actividades de un cronograma
    public function index($cronograma_id)
    {
        $actividades = ActividadesCronograma::where('cronograma_id', $cronograma_id)->get();
        return response()->json($actividades);
    }

    // GET: Cualquier usuario puede ver una actividad
    public function show($id)
    {
        $actividad = ActividadesCronograma::with('cronograma')->findOrFail($id);
        return response()->json($actividad);
    }

    // POST: Solo admin o autor del evento asociado al cronograma
    public function store(Request $request)
    {
        $request->validate([
            'cronograma_id' => 'required|exists:cronogramas,id',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $cronograma = Cronograma::findOrFail($request->cronograma_id);
        $evento = $cronograma->evento;
        $user = Auth::user();

        if (!$user->is_admin && $evento->persona_id !== $user->persona_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $actividad = ActividadesCronograma::create($request->all());
        return response()->json($actividad, 201);
    }

    // PUT/PATCH: Solo admin o autor del evento asociado al cronograma
    public function update(Request $request, $id)
    {
        $actividad = ActividadesCronograma::findOrFail($id);
        $cronograma = $actividad->cronograma;
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

        $actividad->update($request->all());
        return response()->json($actividad);
    }

    // DELETE: Solo admin o autor del evento asociado al cronograma
    public function destroy($id)
    {
        $actividad = ActividadesCronograma::findOrFail($id);
        $cronograma = $actividad->cronograma;
        $evento = $cronograma->evento;
        $user = Auth::user();

        if (!$user->is_admin && $evento->persona_id !== $user->persona_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $actividad->delete();
        return response()->json(['message' => 'Eliminado correctamente']);
    }
}
