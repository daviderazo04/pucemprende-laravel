<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

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
}
