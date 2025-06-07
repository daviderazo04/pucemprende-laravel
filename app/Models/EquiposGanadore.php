<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EquiposGanadore
 * 
 * @property int $id
 * @property Carbon|null $creado_en
 * @property Carbon|null $actualizado_en
 * @property int|null $equipo_id
 * @property int|null $rank
 * @property int|null $evento_id
 * 
 * @property Equipo|null $equipo
 * @property Evento|null $evento
 *
 * @package App\Models
 */
class EquiposGanadore extends Model
{
	protected $table = 'equipos_ganadores';
	public $timestamps = false;

	protected $casts = [
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime',
		'equipo_id' => 'int',
		'rank' => 'int',
		'evento_id' => 'int'
	];

	protected $fillable = [
		'creado_en',
		'actualizado_en',
		'equipo_id',
		'rank',
		'evento_id'
	];

	public function equipo()
	{
		return $this->belongsTo(Equipo::class);
	}

	public function evento()
	{
		return $this->belongsTo(Evento::class);
	}
}
