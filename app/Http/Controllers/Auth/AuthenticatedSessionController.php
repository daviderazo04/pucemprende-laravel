<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        // Autentica al usuario
        $request->authenticate();

        // Obtén el usuario autenticado
        $user = Auth::user();

        // Genera un token (Sanctum)
        $token = $user->createToken('auth_token')->plainTextToken;

        // Retorna un JSON con el token y los datos del usuario
        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): JsonResponse
    {
        // Si deseas revocar tokens en un API stateless
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}