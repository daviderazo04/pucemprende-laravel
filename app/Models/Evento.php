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
 * @property string|null $descripcion
 * @property Carbon|null $fecha_inicio
 * @property Carbon|null $fecha_fin
 * @property int|null $capacidad
 * @property string|null $espacio
 * @property string|null $modalidad
 * @property int|null $sede_id
 * @property int|null $categoria_id
 * @property bool $hayEquipos
 * @property bool $hayFormulario
 * 
 * @property Categorium|null $categorium
 * @property Collection|Archivo[] $archivos
 * @property Collection|Cronograma[] $cronogramas
 * @property Collection|Equipo[] $equipos
 * @property Collection|EquiposGanadore[] $equipos_ganadores
 * @property Collection|EventoDochabilitante[] $evento_dochabilitantes
 * @property Collection|PersonasGanadora[] $personas_ganadoras
 * @property Collection|ProcesosEvaluacion[] $procesos_evaluacions
 *
 * @package App\Models
 */
class Evento extends Model
{
	protected $table = 'eventos';

	protected $primaryKey = 'id';

	public $incrementing = true;

	protected $keyType = 'int';

	public $timestamps = false; // Usamos campos personalizados

	protected $fillable = [
		'nombre',
		'descripcion',
		'fecha_inicio',
		'fecha_fin',
		'capacidad',
		'espacio',
		'modalidad',
		'sede_id',
		'categoria_id',
		'hayEquipos',
		'hayFormulario',
		'estado',
		'inscripcionesAbiertas',
		'estado_borrado',
		'borrado_en',
		'creado_en',
		'actualizado_en',
	];

	protected $casts = [
		'id' => 'integer',
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime',
		'estado_borrado' => 'boolean',
		'borrado_en' => 'datetime',
		'fecha_inicio' => 'datetime',
		'fecha_fin' => 'datetime',
		'capacidad' => 'integer',
		'sede_id' => 'integer',
		'categoria_id' => 'integer',
		'hayEquipos' => 'integer',
		'hayFormulario' => 'boolean',
		'inscripcionesAbiertas' => 'boolean',
	];

	public function autor()
    {
        return $this->belongsTo(Persona::class, 'autor');
    }
	public function categorium()
	{
		return $this->belongsTo(Categorium::class, 'categoria_id');
	}

	public function archivos()
	{
		return $this->belongsToMany(Archivo::class);
	}

	public function cronogramas()
	{
		return $this->hasMany(Cronograma::class);
	}

	public function equipos()
	{
		return $this->hasMany(Equipo::class);
	}

	public function equipos_ganadores()
	{
		return $this->hasMany(EquiposGanadore::class);
	}

	public function evento_dochabilitantes()
	{
		return $this->hasMany(EventoDochabilitante::class);
	}

	public function personas_ganadoras()
	{
		return $this->hasMany(PersonasGanadora::class);
	}

	public function procesos_evaluacions()
	{
		return $this->hasMany(ProcesosEvaluacion::class);
	}

	public function evento_rol_persona()
	{
		return $this->hasMany(EventoRolPersona::class, 'evento_id');
	}
}
