<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (! $request->user()) {
            return response()->json(['message' => 'No autenticado. Se requiere un Bearer Token.'], 401);
        }

        $user = $request->user();

        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }

        foreach ($roles as $roleIdentifier) {
            if (is_numeric($roleIdentifier)) {
                if ($user->hasRoleId((int)$roleIdentifier)) {
                    return $next($request);
                }
            } else {
                if ($user->hasRole($roleIdentifier)) {
                    return $next($request);
                }
            }
        }

        return response()->json(['message' => 'No tienes permiso para acceder a esta ruta. (Rol no autorizado)'], 403);
    }
}