<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Evento
 * 
 * @property int $id
 * @property Carbon|null $creado_en
 * @property Carbon|null $actualizado_en
 * @property bool $estado_borrado
 * @property Carbon|null $borrado_en
 * @property string $nombre
 * @property string|null $categoria
 * @property string|null $descripcion
 * @property Carbon|null $fecha_inicio
 * @property Carbon|null $fecha_fin
 * @property int|null $capacidad
 * @property string|null $sede
 * @property string|null $espacio
 * @property string|null $modalidad
 * 
 * @property Collection|Archivo[] $archivos
 * @property Collection|Cronograma[] $cronogramas
 * @property Collection|DocHabilitante[] $doc_habilitantes
 * @property Collection|Equipo[] $equipos
 * @property Collection|EquiposGanadore[] $equipos_ganadores
 * @property Collection|PersonasGanadora[] $personas_ganadoras
 * @property Collection|ProcesosEvaluacion[] $procesos_evaluacions
 * @property Collection|Sede[] $sedes
 *
 * @package App\Models
 */
class Evento extends Model
{
	protected $table = 'eventos';
	public $timestamps = false;

	protected $casts = [
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime',
		'estado_borrado' => 'bool',
		'borrado_en' => 'datetime',
		'fecha_inicio' => 'datetime',
		'fecha_fin' => 'datetime',
		'capacidad' => 'int'
	];

	protected $fillable = [
		'creado_en',
		'actualizado_en',
		'estado_borrado',
		'borrado_en',
		'nombre',
		'categoria_id',
		'descripcion',
		'fecha_inicio',
		'fecha_fin',
		'capacidad',
		'sede_id',
		'espacio',
		'modalidad'
	];

	public function archivos()
	{
		return $this->belongsToMany(Archivo::class);
	}

	public function categoria()
	{
		return $this->hasMany(Categorium::class);
	}

	public function cronogramas()
	{
		return $this->hasMany(Cronograma::class);
	}

	public function doc_habilitantes()
	{
		return $this->hasMany(DocHabilitante::class);
	}

	public function equipos()
	{
		return $this->hasMany(Equipo::class);
	}

	public function equipos_ganadores()
	{
		return $this->hasMany(EquiposGanadore::class);
	}

	public function personas_ganadoras()
	{
		return $this->hasMany(PersonasGanadora::class);
	}

	public function procesos_evaluacions()
	{
		return $this->hasMany(ProcesosEvaluacion::class);
	}

	public function sedes()
	{
		return $this->hasMany(Sede::class);
	}
}
