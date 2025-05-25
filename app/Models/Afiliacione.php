<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Afiliacione
 * 
 * @property int $id
 * @property Carbon|null $creado_en
 * @property Carbon|null $actualizado_en
 * @property int|null $persona_id
 * @property int|null $organizacion_id
 * @property string|null $rol_interno
 * 
 * @property Persona|null $persona
 * @property Organizacione|null $organizacione
 *
 * @package App\Models
 */
class Afiliacione extends Model
{
	protected $table = 'afiliaciones';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'id' => 'int',
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime',
		'persona_id' => 'int',
		'organizacion_id' => 'int'
	];

	protected $fillable = [
		'creado_en',
		'actualizado_en',
		'persona_id',
		'organizacion_id',
		'rol_interno'
	];

	public function persona()
	{
		return $this->belongsTo(Persona::class);
	}

	public function organizacione()
	{
		return $this->belongsTo(Organizacione::class, 'organizacion_id');
	}
}
