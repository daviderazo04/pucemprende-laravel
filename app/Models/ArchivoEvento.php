<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ArchivoEvento
 * 
 * @property int $id
 * @property int $archivo_id
 * @property int $evento_id
 * 
 * @property Archivo $archivo
 * @property Evento $evento
 *
 * @package App\Models
 */
class ArchivoEvento extends Model
{
	protected $table = 'archivo_evento';
	
	public $timestamps = false;
	protected $fillable = [
        'archivo_id',
        'evento_id',
    ];
	protected $casts = [
		'archivo_id' => 'int',
		'evento_id' => 'int'
	];

	public function archivo()
	{
		return $this->belongsTo(Archivo::class);
	}

	public function evento()
	{
		return $this->belongsTo(Evento::class);
	}
}
