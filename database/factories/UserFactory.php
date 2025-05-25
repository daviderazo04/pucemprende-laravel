<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Mapeamos 'name' a tu columna 'usuario'
            'usuario' => fake()->unique()->userName(), // Genera un nombre de usuario único

            // Mapeamos 'email' a tu columna 'email'
            'email' => fake()->unique()->safeEmail(),

            // 'email_verified_at' no está en tu esquema, así que lo eliminamos.
            // Si en el futuro lo añades, puedes volver a agregarlo aquí.

            // Mapeamos 'password' a tu columna 'clave' y nos aseguramos de que se hashee
            'clave' => static::$password ??= Hash::make('password'),

            // 'remember_token' no está en tu esquema, así que lo eliminamos.
            // Si en el futuro lo añades, puedes volver a agregarlo aquí.

            // Añadimos 'rol_id' ya que es una columna en tu tabla 'usuarios' (ahora 'users')
            'rol_id' => fake()->numberBetween(1, 3), // Ajusta el rango según los IDs de tus roles
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * Este método se mantiene, aunque 'email_verified_at' no esté en tu tabla actual.
     * Si en el futuro añades esa columna, este método seguirá siendo útil.
     * Por ahora, no tendrá efecto directo en la base de datos si la columna no existe.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            // 'email_verified_at' => null,
            // Esta línea se comenta o elimina si no existe la columna en la BD.
            // Si la columna no existe, no intentará establecerla a null.
            // La dejamos comentada para que sepas dónde ir si la añades.
        ]);
    }
}