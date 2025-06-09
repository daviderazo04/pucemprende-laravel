<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Categorium
 * 
 * @property int $id
 * @property string $nombre
 * 
 * @property Collection|Evento[] $eventos
 *
 * @package App\Models
 */
class Categorium extends Model
{
	protected $table = 'categoria';
	public $timestamps = false;

	protected $fillable = [
		'nombre'
	];

	public function eventos()
	{
		return $this->hasMany(Evento::class, 'categoria_id');
	}
}
