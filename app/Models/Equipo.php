<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Equipo
 * 
 * @property int $id
 * @property Carbon|null $creado_en
 * @property Carbon|null $actualizado_en
 * @property string $nombre
 * @property int|null $evento_id
 * @property int|null $ranking
 * 
 * @property Evento|null $evento
 * @property Collection|EquiposGanadore[] $equipos_ganadores
 * @property Collection|MiembrosEquipo[] $miembros_equipos
 * @property Collection|Proyecto[] $proyectos
 * @property Collection|ResultadosEvaluacion[] $resultados_evaluacions
 *
 * @package App\Models
 */
class Equipo extends Model
{
	protected $table = 'equipos';
	public $timestamps = false;

	protected $casts = [
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime',
		'evento_id' => 'int',
		'ranking' => 'int',
		'estado_borrado' => 'bool',
		'borrado_en' => 'datetime'
	];

	protected $fillable = [
		'creado_en',
		'actualizado_en',
		'nombre',
		'evento_id',
		'ranking',
		'estado_borrado',
		'borrado_en'
	];

	public function evento()
	{
		return $this->belongsTo(Evento::class);
	}

	public function equipos_ganadores()
	{
		return $this->hasMany(EquiposGanadore::class);
	}

	public function miembros_equipos()
	{
		return $this->hasMany(MiembrosEquipo::class);
	}

	public function proyectos()
	{
		return $this->hasMany(Proyecto::class);
	}

	public function resultados_evaluacions()
	{
		return $this->hasMany(ResultadosEvaluacion::class);
	}
}
