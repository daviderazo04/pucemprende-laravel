<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PlantillasEvaluacion
 * 
 * @property int $id
 * @property Carbon|null $creado_en
 * @property Carbon|null $actualizado_en
 * @property int|null $proceso_id
 * @property string $nombre
 * @property float|null $peso
 * 
 * @property ProcesosEvaluacion|null $procesos_evaluacion
 * @property Collection|Criterio[] $criterios
 * @property Collection|RolesPlantilla[] $roles_plantillas
 *
 * @package App\Models
 */
class PlantillasEvaluacion extends Model
{
	protected $table = 'plantillas_evaluacion';
	public $timestamps = false;

	protected $casts = [
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime',
		'proceso_id' => 'int',
		'peso' => 'float'
	];

	protected $fillable = [
		'creado_en',
		'actualizado_en',
		'proceso_id',
		'nombre',
		'peso'
	];

	public function procesos_evaluacion()
	{
		return $this->belongsTo(ProcesosEvaluacion::class, 'proceso_id');
	}

	public function criterios()
	{
		return $this->hasMany(Criterio::class, 'plantilla_id');
	}

	public function roles_plantillas()
	{
		return $this->hasMany(RolesPlantilla::class, 'plantilla_id');
	}
}
