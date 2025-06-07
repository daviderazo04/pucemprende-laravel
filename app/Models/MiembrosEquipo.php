<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MiembrosEquipo
 * 
 * @property int $id
 * @property Carbon|null $creado_en
 * @property Carbon|null $actualizado_en
 * @property int|null $equipo_id
 * @property int|null $persona_id
 * @property Carbon|null $fecha_inicio
 * @property Carbon|null $fecha_fin
 * 
 * @property Equipo|null $equipo
 * @property Persona|null $persona
 *
 * @package App\Models
 */
class MiembrosEquipo extends Model
{
	protected $table = 'miembros_equipo';
	public $timestamps = false;

	protected $casts = [
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime',
		'equipo_id' => 'int',
		'persona_id' => 'int',
		'fecha_inicio' => 'datetime',
		'fecha_fin' => 'datetime'
	];

	protected $fillable = [
		'creado_en',
		'actualizado_en',
		'equipo_id',
		'persona_id',
		'fecha_inicio',
		'fecha_fin'
	];

	public function equipo()
	{
		return $this->belongsTo(Equipo::class);
	}

	public function persona()
	{
		return $this->belongsTo(Persona::class);
	}
}
