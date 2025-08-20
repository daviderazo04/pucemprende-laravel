<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Proyecto
 *
 * @property int $id
 * @property Carbon|null $creado_en
 * @property Carbon|null $actualizado_en
 * @property int|null $equipo_id
 * @property string $titulo
 * @property string|null $descripcion
 * @property string|null $estado
 * @property Carbon|null $fecha_inicio
 * @property Carbon|null $fecha_fin
 *
 * @property Equipo|null $equipo
 * @property Collection|Archivo[] $archivos
 * @property Collection|MiembrosProyecto[] $miembros_proyectos
 *
 * @package App\Models
 */
class Proyecto extends Model
{
	protected $table = 'proyectos';
	public $timestamps = false;

	protected $casts = [
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime',
		'equipo_id' => 'int',
		'fecha_inicio' => 'datetime',
		'fecha_fin' => 'datetime'
	];

	protected $fillable = [
		'creado_en',
		'actualizado_en',
		'equipo_id',
		'titulo',
		'descripcion',
		'estado',
		'fecha_inicio',
		'fecha_fin'
	];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function evento()
    {
        return $this->hasOneThrough(Evento::class, Equipo::class, 'id', 'id', 'equipo_id', 'evento_id');
    }
	public function archivos()
	{
		return $this->belongsToMany(Archivo::class);
	}

	public function miembros_proyectos()
	{
		return $this->hasMany(MiembrosProyecto::class);
	}
}
