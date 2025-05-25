<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProcesosEvaluacion
 * 
 * @property int $id
 * @property Carbon|null $creado_en
 * @property Carbon|null $actualizado_en
 * @property int|null $evento_id
 * @property string $titulo
 * 
 * @property Evento|null $evento
 * @property Collection|PlantillasEvaluacion[] $plantillas_evaluacions
 *
 * @package App\Models
 */
class ProcesosEvaluacion extends Model
{
	protected $table = 'procesos_evaluacion';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'id' => 'int',
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime',
		'evento_id' => 'int'
	];

	protected $fillable = [
		'creado_en',
		'actualizado_en',
		'evento_id',
		'titulo'
	];

	public function evento()
	{
		return $this->belongsTo(Evento::class);
	}

	public function plantillas_evaluacions()
	{
		return $this->hasMany(PlantillasEvaluacion::class, 'proceso_id');
	}
}
