<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Sede
 * 
 * @property int $id
 * @property string $nombre
 * @property int $evento_id
 * 
 * @property Evento $evento
 *
 * @package App\Models
 */
class Sede extends Model
{
	protected $table = 'sede';
	public $timestamps = false;

	protected $casts = [
		'evento_id' => 'int'
	];

	protected $fillable = [
		'nombre',
		'evento_id'
	];

	public function evento()
	{
		return $this->belongsTo(Evento::class);
	}
}
