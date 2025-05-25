<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Cronograma
 * 
 * @property int $id
 * @property int|null $evento_id
 * @property string $titulo
 * @property string|null $descripcion
 * @property Carbon $fecha_inicio
 * @property Carbon $fecha_fin
 * @property Carbon|null $creado_en
 * @property Carbon|null $actualizado_en
 * 
 * @property Evento|null $evento
 * @property Collection|ActividadesCronograma[] $actividades_cronogramas
 *
 * @package App\Models
 */
class Cronograma extends Model
{
	protected $table = 'cronogramas';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'id' => 'int',
		'evento_id' => 'int',
		'fecha_inicio' => 'datetime',
		'fecha_fin' => 'datetime',
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime'
	];

	protected $fillable = [
		'evento_id',
		'titulo',
		'descripcion',
		'fecha_inicio',
		'fecha_fin',
		'creado_en',
		'actualizado_en'
	];

	public function evento()
	{
		return $this->belongsTo(Evento::class);
	}

	public function actividades_cronogramas()
	{
		return $this->hasMany(ActividadesCronograma::class);
	}
}
