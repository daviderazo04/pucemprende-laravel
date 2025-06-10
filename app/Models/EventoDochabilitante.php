<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class EventoDochabilitante
 * 
 * @property int $id
 * @property int $evento_id
 * @property int $dochab_id
 * 
 * @property Evento $evento
 * @property DocHabilitante $doc_habilitante
 *
 * @package App\Models
 */
class EventoDochabilitante extends Model
{
	protected $table = 'evento_dochabilitante';
	
	public $timestamps = false;

	// Definir los campos que se pueden asignar masivamente
    protected $fillable = [
        'evento_id', // Permite la asignación masiva de evento_id
        'dochab_id', // Permite la asignación masiva de dochab_id
    ];

	protected $casts = [
		'evento_id' => 'int',
		'dochab_id' => 'int'
	];

	public function evento()
	{
		return $this->belongsTo(Evento::class);
	}

	public function doc_habilitante()
	{
		return $this->belongsTo(DocHabilitante::class, 'dochab_id');
	}
}
