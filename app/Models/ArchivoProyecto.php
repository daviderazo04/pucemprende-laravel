<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ArchivoProyecto
 * 
 * @property int $id
 * @property int $archivo_id
 * @property int $proyecto_id
 * 
 * @property Archivo $archivo
 * @property Proyecto $proyecto
 *
 * @package App\Models
 */
class ArchivoProyecto extends Model
{
	protected $table = 'archivo_proyecto';
	
	public $timestamps = false;
	protected $fillable = [
        'archivo_id',
        'proyecto_id',
    ];
	protected $casts = [
		'archivo_id' => 'int',
		'proyecto_id' => 'int'
	];

	public function archivo()
	{
		return $this->belongsTo(Archivo::class);
	}

	public function proyecto()
	{
		return $this->belongsTo(Proyecto::class);
	}
}
