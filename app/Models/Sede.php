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
 *
 * @package App\Models
 */
class Sede extends Model
{
	protected $table = 'sede';
	public $timestamps = false;

	protected $fillable = [
		'nombre'
	];
}
