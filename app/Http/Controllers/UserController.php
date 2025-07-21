<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

class UserController extends Controller
{

    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }
    // función para crear un nuevo usuario
    public function store(Request $request)
    {
        // Solo permitir si el usuario tiene rol_id = 1 y rol_id = 8 (superadministrador)
        if ($request->user()->rol_id !== 1 || $request->user()->rol_id !== 8) {
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
        ]);

        return response()->json($user, 201);
    }
    // función para mostrar un usuario específico
    public function show(User $user)
    {
        if($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver este usuario.'], 403);
        }
        return response()->json($user);
    }
    // funcion para mostrar un usuario por su ID
    public function getById(Request $request, $id)
    {
        if($request->user()->rol_id !== 1 || $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver este usuario.'], 403);
        }
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }
        if ($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para ver este usuario.'], 403);
        }
        return response()->json($user);
    }
    // función para actualizar un usuario
    public function update(Request $request, User $user)
    {
        if($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para actualizar este usuario.'], 403);
        }
        $validator = Validator::make($request->all(), [
            'usuario' => 'sometimes|required|string|max:100',
            'clave'=> 'sometimes|required|string|min:100',
            'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
            'rol_id' => 'sometimes|required|exists:roles,id',
            'estado'=> 'sometimes|required|string|in:activo,inactivo',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->update($request->all());
        return response()->json($user);
    }
    // función para eliminar un usuario
    public function destroy(Request $request, User $user)
    {
        if($request->user()->rol_id !== 1 && $request->user()->rol_id !== 8) {
            return response()->json(['message' => 'No tienes permiso para eliminar este usuario.'], 403);
        }
        $user->delete();
        return response()->json(['message' => 'Usuario eliminado correctamente.'], 200);
    }
}
