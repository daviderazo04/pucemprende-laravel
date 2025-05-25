<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ActividadesCronograma
 * 
 * @property int $id
 * @property int|null $cronograma_id
 * @property string $titulo
 * @property string|null $descripcion
 * @property Carbon $fecha_inicio
 * @property Carbon $fecha_fin
 * @property int|null $orden
 * @property int|null $dependencia_id
 * @property Carbon|null $creado_en
 * @property Carbon|null $actualizado_en
 * 
 * @property Cronograma|null $cronograma
 * @property ActividadesCronograma|null $actividades_cronograma
 * @property Collection|ActividadesCronograma[] $actividades_cronogramas
 *
 * @package App\Models
 */
class ActividadesCronograma extends Model
{
	protected $table = 'actividades_cronograma';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'id' => 'int',
		'cronograma_id' => 'int',
		'fecha_inicio' => 'datetime',
		'fecha_fin' => 'datetime',
		'orden' => 'int',
		'dependencia_id' => 'int',
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime'
	];

	protected $fillable = [
		'cronograma_id',
		'titulo',
		'descripcion',
		'fecha_inicio',
		'fecha_fin',
		'orden',
		'dependencia_id',
		'creado_en',
		'actualizado_en'
	];

	public function cronograma()
	{
		return $this->belongsTo(Cronograma::class);
	}

	public function actividades_cronograma()
	{
		return $this->belongsTo(ActividadesCronograma::class, 'dependencia_id');
	}

	public function actividades_cronogramas()
	{
		return $this->hasMany(ActividadesCronograma::class, 'dependencia_id');
	}
}
