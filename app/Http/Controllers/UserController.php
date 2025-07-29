<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\Persona;

class UserController extends Controller
{

    public function index(Request $request)
    {
        // Solo permitir si el usuario es admin o superadmin
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver usuarios.'], 403);
        }

        $users = DB::select('SELECT * FROM vw_users');
        return response()->json($users);
    }
    // función para crear un nuevo usuario
    public function store(Request $request)
    {
        // Solo permitir si el usuario tiene rol_id = 1 y rol_id = 8 (superadministrador)
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para crear usuarios.'], 403);
        }
        $validator = Validator::make($request->all(), [
            'usuario' => 'required|string|max:100',
            'clave'=> 'required|string|min:100',
            'email' => 'required|email|unique:users,email',
            'rol_id' => 'required|exists:roles,id',
            'estado'=> 'required|string|in:activo,inactivo',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'usuario' => $request->usuario,
            'clave' => bcrypt($request->clave),
            'email' => $request->email,
            'email_verified_at' => null,
            'rol_id' => $request->rol_id,
            'estado' => $request->estado,
            'creado_en' => Carbon::now(),
            'actualizado_en' => Carbon::now(),
            'remember_token' => null,
            'estado_borrado' => false,
        ]);

        return response()->json($user, 201);
    }
    public function show(Request $request, $cedula)
    {
        if($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver este usuario.'], 403);
        }

        try {
            // Llamar al stored procedure para buscar usuario por cédula
            $users = DB::select('CALL sp_buscar_users_cedula(?)', [$cedula]);

            if (empty($users)) {
                return response()->json(['message' => 'Usuario no encontrado con esa cédula'], 404);
            }

            return response()->json($users);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al ejecutar la búsqueda: ' . $e->getMessage()
            ], 500);
        }
    }

    // función para actualizar un usuario
    public function update(Request $request, $id)
    {
        // Solo permitir si el usuario es admin o superadmin
        if($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para actualizar este usuario.'], 403);
        }

        // Buscar el usuario específicamente
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'usuario' => 'sometimes|required|string|max:100',
            'clave'=> 'sometimes|required|string|min:8',
            'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
            'rol_id' => 'sometimes|required|exists:roles,id',
            'estado'=> 'sometimes|required|string|in:activo,inactivo',
            'estado_borrado' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Actualizar los campos del usuario
        $user->fill($request->only([
            'usuario',
            'email',
            'rol_id',
            'estado',
            'estado_borrado'
        ]));

        // Manejar la contraseña por separado si se proporciona
        if ($request->has('clave')) {
            $user->clave = bcrypt($request->clave);
        }

        $user->actualizado_en = Carbon::now();
        $user->save();

        return response()->json($user, 200);
    }
    // función para obtener las estadísticas del usuario
    public function getUserEstadisticas(Request $request)
    {

        try {
            // Obtener el ID del usuario autenticado
            $userId = $request->user()->id;

            $estadisticas = DB::select('CALL GetUserEstadisticas(?)', [$userId]);

            if (empty($estadisticas)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudieron obtener las estadísticas',
                    'data' => [
                        'total_proyectos' => 0,
                        'total_eventos' => 0,
                        'total_equipos' => 0
                    ]
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Estadísticas obtenidas correctamente',
                'data' => $estadisticas[0]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo estadísticas: ' . $e->getMessage(),
                'data' => [
                    'total_proyectos' => 0,
                    'total_eventos' => 0,
                    'total_equipos' => 0
                ]
            ], 500);
        }
    }
    // función para eliminar un usuario
    public function destroy(Request $request, $id)
    {
        // Solo permitir si el usuario es admin o superadmin
        if($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para eliminar este usuario.'], 403);
        }

        // Buscar el usuario específicamente
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        if ($user->estado_borrado) {
            return response()->json(['message' => 'El usuario ya está eliminado'], 404);
        }

        $persona = Persona::where('users_id', $id)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada para este usuario'], 404);
        }

        $persona->estado_borrado = true;
        $persona->borrado_en = Carbon::now();
        $persona->save();

        $user->estado_borrado = true;
        $user->save();

        return response()->json(['message' => 'Usuario eliminado correctamente.'], 200);
    }
}
