<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones.
     * Esto crea la tabla 'users' con el esquema que coincide más con tus necesidades
     * y con los requerimientos de Laravel para autenticación y verificación de correo.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // 'id' int PRIMARY KEY NOT NULL
            // Laravel's $table->id() crea un BIGINT autoincremental, que es compatible con int.
            $table->id();

            // 'usuario' varchar(100) NOT NULL
            $table->string('usuario', 100)->unique(); // Asumimos que 'usuario' es único para login

            // 'clave' varchar(100) NOT NULL (será la columna de contraseña)
            $table->string('clave', 100);

            // 'email' varchar(255) UNIQUE
            // Laravel requiere un campo 'email' para la verificación de correo.
            // Si tu esquema 'usuarios' permite email nulo, mantén nullable.
            // Para la verificación de correo, el email no debería ser nulo en la práctica.
            $table->string('email', 255)->unique(); // Preferiblemente no nullable para verificación de correo.

            // 'email_verified_at' timestamp (para verificación de correo)
            // Esta columna es esencial para que la funcionalidad MustVerifyEmail funcione correctamente.
            $table->timestamp('email_verified_at')->nullable();

            // 'rol_id' int
            $table->integer('rol_id')->nullable(); // Tu esquema no especifica NOT NULL, así que lo hacemos nullable.

            // 'estado' varchar(30)
            $table->string('estado', 30)->nullable(); // **¡Tu campo 'estado' agregado!**

            // 'creado_en' timestamp DEFAULT (now())
            $table->timestamp('creado_en')->useCurrent();

            // 'actualizado_en' timestamp DEFAULT (now())
            // useCurrentOnUpdate() asegura que se actualice automáticamente en cada modificación.
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            // 'remember_token' varchar(100) (para "recuérdame" en el login)
            // Aunque tu esquema 'usuarios' no lo tiene, es una columna estándar de Laravel para seguridad.
            // Es buena práctica incluirla si usas Auth.
            $table->rememberToken();
        });

        // Estas tablas son estándar de Laravel para funcionalidad de autenticación como reseteo de contraseñas y sesiones.
        // Se recomienda mantenerlas si usas estas características.
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Revierte las migraciones.
     * Elimina las tablas creadas por esta migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};