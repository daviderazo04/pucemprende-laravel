<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Organizacione
 * 
 * @property int $id
 * @property Carbon|null $creado_en
 * @property Carbon|null $actualizado_en
 * @property string $nombre
 * @property string|null $abreviatura
 * 
 * @property Collection|Afiliacione[] $afiliaciones
 *
 * @package App\Models
 */
class Organizacione extends Model
{
	protected $table = 'organizaciones';
	public $timestamps = false;

	protected $casts = [
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime',
		'estado_borrado' => 'bool',
		'borrado_en' => 'datetime'
	];

	protected $fillable = [
		'creado_en',
		'actualizado_en',
		'nombre',
		'abreviatura',
		'estado_borrado', 
		'borrado_en' 
	];

	public function afiliaciones()
	{
		return $this->hasMany(Afiliacione::class, 'organizacion_id');
	}
}
