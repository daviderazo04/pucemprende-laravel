<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MiembrosProyecto
 * 
 * @property int $id
 * @property Carbon|null $creado_en
 * @property Carbon|null $actualizado_en
 * @property int|null $rol_id
 * @property int|null $proyecto_id
 * @property int|null $persona_id
 * 
 * @property RolesProyecto|null $roles_proyecto
 * @property Proyecto|null $proyecto
 * @property Persona|null $persona
 *
 * @package App\Models
 */
class MiembrosProyecto extends Model
{
	protected $table = 'miembros_proyecto';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'id' => 'int',
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime',
		'rol_id' => 'int',
		'proyecto_id' => 'int',
		'persona_id' => 'int'
	];

	protected $fillable = [
		'creado_en',
		'actualizado_en',
		'rol_id',
		'proyecto_id',
		'persona_id'
	];

	public function roles_proyecto()
	{
		return $this->belongsTo(RolesProyecto::class, 'rol_id');
	}

	public function proyecto()
	{
		return $this->belongsTo(Proyecto::class);
	}

	public function persona()
	{
		return $this->belongsTo(Persona::class);
	}
}
