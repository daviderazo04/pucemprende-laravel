<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PersonasGanadora
 * 
 * @property int $id
 * @property Carbon|null $creado_en
 * @property Carbon|null $actualizado_en
 * @property int|null $persona_id
 * @property int|null $rank
 * @property int|null $evento_id
 * 
 * @property Persona|null $persona
 * @property Evento|null $evento
 *
 * @package App\Models
 */
class PersonasGanadora extends Model
{
	protected $table = 'personas_ganadoras';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'id' => 'int',
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime',
		'persona_id' => 'int',
		'rank' => 'int',
		'evento_id' => 'int'
	];

	protected $fillable = [
		'creado_en',
		'actualizado_en',
		'persona_id',
		'rank',
		'evento_id'
	];

	public function persona()
	{
		return $this->belongsTo(Persona::class);
	}

	public function evento()
	{
		return $this->belongsTo(Evento::class);
	}
}
