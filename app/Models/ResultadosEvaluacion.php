<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ResultadosEvaluacion
 * 
 * @property int $id
 * @property Carbon|null $creado_en
 * @property Carbon|null $actualizado_en
 * @property int|null $equipo_id
 * @property int|null $criterio_id
 * @property int|null $evaluador_id
 * @property float $puntaje
 * @property string|null $comentarios
 * @property Carbon|null $evaluado_en
 * 
 * @property Equipo|null $equipo
 * @property Criterio|null $criterio
 * @property Persona|null $persona
 *
 * @package App\Models
 */
class ResultadosEvaluacion extends Model
{
	protected $table = 'resultados_evaluacion';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'id' => 'int',
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime',
		'equipo_id' => 'int',
		'criterio_id' => 'int',
		'evaluador_id' => 'int',
		'puntaje' => 'float',
		'evaluado_en' => 'datetime'
	];

	protected $fillable = [
		'creado_en',
		'actualizado_en',
		'equipo_id',
		'criterio_id',
		'evaluador_id',
		'puntaje',
		'comentarios',
		'evaluado_en'
	];

	public function equipo()
	{
		return $this->belongsTo(Equipo::class);
	}

	public function criterio()
	{
		return $this->belongsTo(Criterio::class);
	}

	public function persona()
	{
		return $this->belongsTo(Persona::class, 'evaluador_id');
	}
}
