<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;


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
 * @property bool $estado_borrado
 *
 * @property Role|null $role
 * @property Collection|Persona[] $personas
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, Notifiable;

    protected $table = 'users';
    public $timestamps = false;

    public function getAuthPassword()
    {
        return $this->clave;
    }

    protected $casts = [
        'email_verified_at' => 'datetime',
        'rol_id' => 'int',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
        'estado_borrado' => 'bool'
    ];

    protected $hidden = [
        'remember_token',
        'clave',
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
        'remember_token',
        'estado_borrado'
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'rol_id');
    }

    public function personas()
    {
        return $this->hasMany(Persona::class, 'users_id');
    }

    public function hasRole(string $roleName): bool
    {
        return $this->role && $this->role->nombre === $roleName;
    }

    public function hasRoleId(int $roleId): bool
    {
        return $this->role && $this->role->id === $roleId;
    }

}