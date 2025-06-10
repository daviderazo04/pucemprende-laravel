<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class DocHabilitante
 * 
 * @property int $id
 * @property string $nombre
 * @property string $formato
 * 
 * @property Collection|EventoDochabilitante[] $evento_dochabilitantes
 *
 * @package App\Models
 */
class DocHabilitante extends Model
{
	protected $table = 'doc_habilitantes';
	public $timestamps = false;

	protected $fillable = [
		'nombre',
		'formato'
	];

	public function evento_dochabilitantes()
	{
		return $this->hasMany(EventoDochabilitante::class, 'dochab_id');
	}
}
