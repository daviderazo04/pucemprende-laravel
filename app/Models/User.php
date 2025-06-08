<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens; // <--- ¡Asegúrate de que este USE esté presente!
use Illuminate\Auth\Authenticatable as AuthenticatableTrait; // <--- Renombrado para evitar conflicto con la clase

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
class User extends Model implements \Illuminate\Contracts\Auth\Authenticatable // <--- Implementa esta interfaz
{
    use AuthenticatableTrait, HasApiTokens; // <--- Usa estos traits

    protected $table = 'users';
    public $timestamps = false;

    // Sobrescribimos este método para indicar a Laravel que tu columna de contraseña es 'clave'
    // Esto es crucial para que el login y la autenticación funcionen correctamente con Sanctum.
    public function getAuthPassword()
    {
        return $this->clave;
    }

    protected $casts = [
        'email_verified_at' => 'datetime',
        'rol_id' => 'int',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime'
    ];

    protected $hidden = [
        'remember_token',
        'clave', // Oculta la columna 'clave' de las respuestas JSON por seguridad
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
        'remember_token'
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'rol_id');
    }

    public function personas()
    {
        return $this->hasMany(Persona::class, 'users_id');
    }

    // Métodos helper para verificar roles por nombre o ID
    public function hasRole(string $roleName): bool
    {
        return $this->role && $this->role->nombre === $roleName;
    }

    public function hasRoleId(int $roleId): bool
    {
        return $this->role && $this->role->id === $roleId;
    }
}