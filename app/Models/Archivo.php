<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Archivo
 * 
 * @property int $id
 * @property string|null $url
 * @property string|null $tipo
 * @property Carbon|null $creado_en
 * @property Carbon|null $actualizado_en
 * @property bool $estado_borrado
 * @property Carbon|null $borrado_en
 * 
 * @property Collection|Evento[] $eventos
 * @property Collection|Proyecto[] $proyectos
 *
 * @package App\Models
 */
class Archivo extends Model
{
	protected $table = 'archivo';
	public $timestamps = false;

	protected $casts = [
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime',
		'estado_borrado' => 'bool',
		'borrado_en' => 'datetime'
	];

	protected $fillable = [
		'url',
		'tipo',
		'creado_en',
		'actualizado_en',
		'estado_borrado',
		'borrado_en'
	];

	public function eventos()
	{
		return $this->belongsToMany(Evento::class);
	}

	public function proyectos()
	{
		return $this->belongsToMany(Proyecto::class);
	}
}
