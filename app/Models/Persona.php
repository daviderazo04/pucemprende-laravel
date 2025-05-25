<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Persona
 * 
 * @property int $id
 * @property Carbon|null $creado_en
 * @property Carbon|null $actualizado_en
 * @property bool $estado_borrado
 * @property Carbon|null $borrado_en
 * @property string|null $email
 * @property string $nombre
 * @property string $apellido
 * @property string|null $telefono
 * @property string|null $identificacion
 * @property bool|null $alumni
 * @property string|null $genero
 * @property int|null $users_id
 * 
 * @property User|null $user
 * @property Collection|Afiliacione[] $afiliaciones
 * @property Collection|MiembrosEquipo[] $miembros_equipos
 * @property Collection|MiembrosProyecto[] $miembros_proyectos
 * @property Collection|PersonasGanadora[] $personas_ganadoras
 * @property Collection|ResultadosEvaluacion[] $resultados_evaluacions
 *
 * @package App\Models
 */
class Persona extends Model
{
	protected $table = 'personas';
	public $incrementing = true;
	public $timestamps = false;

	protected $casts = [
		'id' => 'int',
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime',
		'estado_borrado' => 'bool',
		'borrado_en' => 'datetime',
		'alumni' => 'bool',
		'users_id' => 'int'
	];

	protected $fillable = [
		'creado_en',
		'actualizado_en',
		'estado_borrado',
		'borrado_en',
		'email',
		'nombre',
		'apellido',
		'telefono',
		'identificacion',
		'alumni',
		'genero',
		'users_id'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'users_id');
	}

	public function afiliaciones()
	{
		return $this->hasMany(Afiliacione::class);
	}

	public function miembros_equipos()
	{
		return $this->hasMany(MiembrosEquipo::class);
	}

	public function miembros_proyectos()
	{
		return $this->hasMany(MiembrosProyecto::class);
	}

	public function personas_ganadoras()
	{
		return $this->hasMany(PersonasGanadora::class);
	}

	public function resultados_evaluacions()
	{
		return $this->hasMany(ResultadosEvaluacion::class, 'evaluador_id');
	}
}
