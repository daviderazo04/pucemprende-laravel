<?php

namespace App\Http\Controllers\Auth;
use Illuminate\Http\JsonResponse; // Agregar este use
use App\Http\Controllers\Controller;
use App\Models\User; // Asegúrate de que User esté importado
use App\Models\Persona; // Asegúrate de que Persona esté importado
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Response; // ¡Importa Response!
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse // ¡Ahora con el tipo de retorno Response!
    {
        $request->validate([
            'usuario' => ['required', 'string', 'max:100', 'unique:'.User::class],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'clave' => ['required', 'confirmed', Rules\Password::defaults()],
            'rol_id' => ['nullable', 'integer'],
            'estado' => ['nullable', 'string', 'max:30'], // Asegúrate de que tu columna 'estado' en la tabla 'users' sea compatible con string
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'identificacion' => ['nullable', 'string', 'max:50', 'unique:'.Persona::class.',identificacion'],
            'alumni' => ['boolean'],
            'genero' => ['nullable', 'string', 'max:20'], // Asegúrate de que tu columna 'genero' en la tabla 'personas' sea compatible con string
        ]);

        // Crea el usuario
        $user = User::create([
            'usuario' => $request->usuario,
            'email' => $request->email,
            'clave' => $request->clave, // La encriptación se maneja con el cast 'hashed' en el modelo User
            'rol_id' => $request->rol_id,
            'estado' => $request->estado ?? 'Activo', // Asignar un valor por defecto si no se envía
        ]);

        // Crea la persona asociada
        $persona = Persona::create([
            'users_id' => $user->id,
            'email' => $request->email,
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'telefono' => $request->telefono,
            'identificacion' => $request->identificacion,
            'alumni' => $request->alumni ?? false, // Asignar un valor por defecto si no se envía
            'genero' => $request->genero,
            'estado_borrado' => false, // Valor por defecto
        ]);

        event(new Registered($user)); // esto dispara el correo de verificación automáticamente

        Auth::login($user);

        // Genera el token de Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        // Retorna una respuesta JSON con el usuario, persona y token
        return response()->json([
            'user' => $user->load('personas'), // Carga la relación 'personas' para incluirla en la respuesta del usuario
            'persona' => $persona,
            'token' => $token,
        ], 201);
    }
}