<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones.
     * Esto crea la tabla 'users' con el esquema de tu tabla 'usuarios'.
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
            $table->string('email', 255)->unique()->nullable(); // Tu esquema permite email nulo, Laravel lo hace único.

            // 'rol_id' int
            $table->integer('rol_id')->nullable(); // Tu esquema no especifica NOT NULL, así que lo hacemos nullable.

            // 'creado_en' timestamp DEFAULT (now())
            // 'actualizado_en' timestamp DEFAULT (now())
            // En lugar de $table->timestamps(), creamos las columnas explícitamente.
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            // Las columnas 'email_verified_at' y 'remember_token' no están en tu esquema 'usuarios'.
            // Por lo tanto, las quitamos de esta migración.
            // Si las necesitas, tendrías que añadirlas a tu esquema 'usuarios' o crearlas aquí.
        });

        // Estas tablas son estándar de Laravel y no están en tu esquema 'usuarios'.
        // Se mantienen si las necesitas para el reseteo de contraseñas y sesiones.
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
