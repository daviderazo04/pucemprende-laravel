<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DocHabilitante
 * 
 * @property int $id
 * @property string $nombre
 * @property string $formato
 * @property int $evento_id
 * 
 * @property Evento $evento
 *
 * @package App\Models
 */
class DocHabilitante extends Model
{
	protected $table = 'doc_habilitantes';
	public $timestamps = false;

	protected $casts = [
		'evento_id' => 'int'
	];

	protected $fillable = [
		'nombre',
		'formato',
		'evento_id'
	];

	public function evento()
	{
		return $this->belongsTo(Evento::class);
	}
}
