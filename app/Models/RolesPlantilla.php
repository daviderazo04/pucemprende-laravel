<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class RolesPlantilla
 * 
 * @property int $id
 * @property Carbon|null $creado_en
 * @property Carbon|null $actualizado_en
 * @property int $plantilla_id
 * @property int $rol_id
 * 
 * @property PlantillasEvaluacion $plantillas_evaluacion
 * @property Role $role
 *
 * @package App\Models
 */
class RolesPlantilla extends Model
{
	protected $table = 'roles_plantilla';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'id' => 'int',
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime',
		'plantilla_id' => 'int',
		'rol_id' => 'int'
	];

	protected $fillable = [
		'id',
		'creado_en',
		'actualizado_en'
	];

	public function plantillas_evaluacion()
	{
		return $this->belongsTo(PlantillasEvaluacion::class, 'plantilla_id');
	}

	public function role()
	{
		return $this->belongsTo(Role::class, 'rol_id');
	}
}
