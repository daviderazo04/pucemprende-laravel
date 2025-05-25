<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; // ¡Asegúrate de añadir esta línea!

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // El factory por defecto ya no se ajusta a tu esquema,
        // así que lo comentamos o borramos si no se va a modificar el factory.
        // User::factory(10)->create();

        // Creamos un usuario de prueba usando las columnas de tu tabla 'users' (antes 'usuarios')
        User::factory()->create([
            'usuario' => 'testuser', // Usamos tu columna 'usuario' para el nombre de usuario
            'email' => 'test@example.com', // Usamos tu columna 'email'
            'clave' => Hash::make('password'), // Usamos tu columna 'clave' y hasheamos la contraseña
            'rol_id' => 1, // Asigna un ID de rol si es necesario, ajusta este valor
        ]);


    }
}