<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\MustVerifyEmail; // Importa esta interfaz
use Illuminate\Foundation\Auth\User as Authenticatable; // ¡Importa Authenticatable!
use Illuminate\Notifications\Notifiable; // Importa Notifiable para el correo
use Laravel\Sanctum\HasApiTokens; // Importa HasApiTokens para los tokens de API

/**
 * Class User
 *
 * @property int $id
 * @property string $usuario
 * @property string $clave
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property int|null $rol_id
 * @property string|null $estado
 * @property Carbon $creado_en
 * @property Carbon $actualizado_en
 * @property string|null $remember_token
 *
 * @property Role|null $role
 * @property Collection|Persona[] $personas
 *
 * @package App\Models
 */
// ¡Importante: debe extender Authenticatable e implementar MustVerifyEmail!
class User extends Authenticatable implements MustVerifyEmail
{
    // ¡Importante: usar estos traits!
    use HasApiTokens, Notifiable;

    protected $table = 'users';
    public $timestamps = false; // Mantén esto si tus campos son creado_en y actualizado_en

    // Mapea las constantes de Laravel a tus nombres de columna
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $casts = [
        'email_verified_at' => 'datetime',
        'rol_id' => 'int',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
        'clave' => 'hashed', // ¡Importante! Para la encriptación automática de la contraseña
    ];

    protected $hidden = [
        'clave', // Ocultar el campo de la contraseña en las respuestas JSON
        'remember_token'
    ];

    protected $fillable = [
        'usuario',
        'clave',
        'email',
        'email_verified_at',
        'rol_id',
        'estado',
        'creado_en',
        'actualizado_en',
        'remember_token' // Aunque Laravel lo maneja, puede estar en fillable
    ];

    // Si tu campo de contraseña no es 'password', sobrescribe este método
    public function getAuthPassword()
    {
        return $this->clave;
    }

    // Si tu campo de email no es 'email', sobrescribe este método para la verificación
    public function getEmailForVerification()
    {
        return $this->email;
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'rol_id');
    }

    public function personas()
    {
        return $this->hasMany(Persona::class, 'users_id');
    }
}