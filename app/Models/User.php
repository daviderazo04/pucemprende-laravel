<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

<<<<<<< HEAD
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
=======
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
>>>>>>> 18c561d (tabla usuarios migrations a users)

/**
 * Class User
 * 
 * @property int $id
 * @property string $usuario
 * @property string $clave
 * @property string|null $email
 * @property int|null $rol_id
 * @property Carbon $creado_en
 * @property Carbon $actualizado_en
 * 
 * @property Role|null $role
 * @property Collection|Persona[] $personas
 *
 * @package App\Models
 */
class User extends Model
{
<<<<<<< HEAD
	protected $table = 'users';
	public $timestamps = false;

	protected $casts = [
		'rol_id' => 'int',
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime'
	];

	protected $fillable = [
		'usuario',
		'clave',
		'email',
		'rol_id',
		'creado_en',
		'actualizado_en'
	];

	public function role()
	{
		return $this->belongsTo(Role::class, 'rol_id');
	}

	public function personas()
	{
		return $this->hasMany(Persona::class, 'users_id');
	}
=======
    use HasFactory, Notifiable;

    // Indica a Laravel que este modelo usa la tabla 'users' (por defecto, pero explícito)
    protected $table = 'users';

    // Mapea las columnas de tu tabla 'usuarios' a los atributos del modelo
    protected $fillable = [
        'usuario', // Tu columna de nombre de usuario
        'clave',   // Tu columna de contraseña
        'email',
        'rol_id',
    ];

    // Las columnas que deben ser ocultadas al serializar el modelo (ej. a JSON)
    protected $hidden = [
        'clave', // Oculta tu columna de contraseña
        // 'remember_token' no está en tu esquema, así que lo quitamos de aquí.
    ];

    // Mapea tus columnas de timestamp personalizadas
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    // Deshabilita la funcionalidad 'remember me' si no tienes la columna 'remember_token'
    // en tu tabla 'users' (que no la tienes según tu esquema 'usuarios').
    public $rememberTokenName = null;

    /**
     * Define los tipos de datos para las columnas.
     * Asegúrate de que 'clave' se hashea automáticamente.
     */
    protected function casts(): array
    {
        return [
            // No tenemos 'email_verified_at' en tu esquema, así que lo quitamos.
            'clave' => 'hashed', // Laravel hasheará automáticamente tu 'clave'
        ];
    }

    // Puedes añadir relaciones aquí, por ejemplo, con tu modelo Rol
    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }
>>>>>>> 18c561d (tabla usuarios migrations a users)
}
