<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PlantillasCertificado
 * 
 * @property int $id
 * @property Carbon|null $creado_en
 * @property Carbon|null $actualizado_en
 * @property string $nombre
 * @property string|null $descripcion
 * @property string|null $archivo_base_url
 * 
 * @property Collection|Certificado[] $certificados
 *
 * @package App\Models
 */
class PlantillasCertificado extends Model
{
	protected $table = 'plantillas_certificado';
	public $timestamps = false;

	protected $casts = [
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime'
	];

	protected $fillable = [
		'creado_en',
		'actualizado_en',
		'nombre',
		'descripcion',
		'archivo_base_url'
	];

	public function certificados()
	{
		return $this->hasMany(Certificado::class, 'plantilla_id');
	}
}
