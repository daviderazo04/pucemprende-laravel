<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class RolesProyecto
 * 
 * @property int $id
 * @property Carbon|null $creado_en
 * @property Carbon|null $actualizado_en
 * @property string|null $rol_interno
 * 
 * @property Collection|MiembrosProyecto[] $miembros_proyectos
 *
 * @package App\Models
 */
class RolesProyecto extends Model
{
	protected $table = 'roles_proyectos';
	public $timestamps = false;

	protected $casts = [
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime'
	];

	protected $fillable = [
		'creado_en',
		'actualizado_en',
		'rol_interno'
	];

	public function miembros_proyectos()
	{
		return $this->hasMany(MiembrosProyecto::class, 'rol_id');
	}
}
