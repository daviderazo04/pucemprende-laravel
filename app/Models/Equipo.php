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
 * @property Proyecto|null $proyecto
 * @property Collection|ResultadosEvaluacion[] $resultados_evaluacions
 *
 * @package App\Models
 */
class Equipo extends Model
{
	protected $table = 'equipos';
	public $timestamps = false;

	protected $casts = [
        'proyecto_id' => 'int',
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime',
		'evento_id' => 'int',
		'ranking' => 'int',
		'estado_borrado' => 'bool',
		'borrado_en' => 'datetime'
	];

	protected $fillable = [
		'proyecto_id',
		'creado_en',
		'actualizado_en',
		'nombre',
		'evento_id',
		'ranking',
		'estado_borrado',
		'borrado_en'
	];

    public function proyecto(){
        return $this->belongsTo(Proyecto::class);
    }
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

	public function resultados_evaluacions()
	{
		return $this->hasMany(ResultadosEvaluacion::class);
	}
}
