<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Role
 * 
 * @property int $id
 * @property Carbon|null $creado_en
 * @property Carbon|null $actualizado_en
 * @property string $nombre
 * @property string|null $descripcion
 * 
 * @property Collection|RolesPlantilla[] $roles_plantillas
 * @property Collection|User[] $users
 *
 * @package App\Models
 */
class Role extends Model
{
	protected $table = 'roles';
	public $timestamps = false;

	protected $casts = [
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime'
	];

	protected $fillable = [
		'creado_en',
		'actualizado_en',
		'nombre',
		'descripcion'
	];

	public function users()
	{
		return $this->hasMany(User::class, 'rol_id');
	}
}
