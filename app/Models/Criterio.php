<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Criterio
 * 
 * @property int $id
 * @property Carbon|null $creado_en
 * @property Carbon|null $actualizado_en
 * @property string $nombre
 * @property string|null $descripcion
 * @property int|null $plantilla_id
 * @property float|null $peso
 * 
 * @property PlantillasEvaluacion|null $plantillas_evaluacion
 * @property Collection|ResultadosEvaluacion[] $resultados_evaluacions
 *
 * @package App\Models
 */
class Criterio extends Model
{
	protected $table = 'criterios';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'id' => 'int',
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime',
		'plantilla_id' => 'int',
		'peso' => 'float'
	];

	protected $fillable = [
		'creado_en',
		'actualizado_en',
		'nombre',
		'descripcion',
		'plantilla_id',
		'peso'
	];

	public function plantillas_evaluacion()
	{
		return $this->belongsTo(PlantillasEvaluacion::class, 'plantilla_id');
	}

	public function resultados_evaluacions()
	{
		return $this->hasMany(ResultadosEvaluacion::class);
	}
}
