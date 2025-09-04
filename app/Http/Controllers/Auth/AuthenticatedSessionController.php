<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Persona;
use App\Models\EventoRolPersona;
use App\Models\MiembrosProyecto;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): JsonResponse
    {
        // Validar los campos necesarios
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Intentar autenticar con email y password
        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            return response()->json([
                'message' => 'Credenciales inválidas.',
                'errors' => ['email' => ['Estas credenciales no coinciden con nuestros registros.']]
            ], 422);
        }

        // Usuario autenticado correctamente
        $user = Auth::user();

        // Verificar si el usuario está activo (estado_borrado debe ser false)
        if ($user->estado_borrado) {
        // Cerrar la sesión si el usuario está inactivo
        Auth::logout();

        return response()->json([
            'message' => 'El usuario está inactivo.',
            'errors' => ['email' => ['El usuario está inactivo.']]
        ], 403);
    }

        // Crear token con Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        // Obtener persona asociada al usuario
        $persona = Persona::where('users_id', $user->id)->first();

        $eventos = [];
        if ($persona) {
            $eventos = EventoRolPersona::where('persona_id', $persona->id)
                ->get()
                ->map(function ($item) {
                    return [
                        'evento_id' => $item->evento_id,
                        'rol_id' => $item->rol_id,
                    ];
                });
        }

        // Proyectos donde es líder (rol_id = 1 en miembros_proyecto)
        $proyectosLiderados = MiembrosProyecto::where('persona_id', $persona->id)
            ->where('rol_id', 1)
            ->get()
            ->map(function ($item) {
                return [
                    'proyecto_id' => $item->proyecto_id,
                ];
            });

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'eventos' => $eventos,
            'proyectos_liderados' => $proyectosLiderados,
        ]);
    }


    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): JsonResponse
    {
        // Si se deseas revocar tokens en un API stateless
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
