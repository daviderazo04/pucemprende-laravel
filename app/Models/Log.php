<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Log
 * 
 * @property int $log_id
 * @property string|null $log_usuario
 * @property string|null $log_tabla
 * @property string|null $log_accion
 * @property string|null $log_data_old
 * @property string|null $log_data_new
 * @property Carbon|null $log_fecha_hora
 *
 * @package App\Models
 */
class Log extends Model
{
	protected $table = 'logs';
	protected $primaryKey = 'log_id';
	public $timestamps = false;

	protected $casts = [
		'log_fecha_hora' => 'datetime'
	];

	protected $fillable = [
		'log_usuario',
		'log_tabla',
		'log_accion',
		'log_data_old',
		'log_data_new',
		'log_fecha_hora'
	];
}
