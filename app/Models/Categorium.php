<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Categorium
 * 
 * @property int $id
 * @property string $nombre
 * @property int $evento_id
 * 
 * @property Evento $evento
 *
 * @package App\Models
 */
class Categorium extends Model
{
	protected $table = 'categoria';
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
