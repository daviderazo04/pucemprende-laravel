<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Certificado
 * 
 * @property int $id
 * @property Carbon|null $creado_en
 * @property Carbon|null $actualizado_en
 * @property int|null $plantilla_id
 * @property string|null $tipo
 * @property string $url_pdf
 * @property bool|null $firmado_digitalmente
 * 
 * @property PlantillasCertificado|null $plantillas_certificado
 * @property Collection|CertificadosDestinatario[] $certificados_destinatarios
 *
 * @package App\Models
 */
class Certificado extends Model
{
	protected $table = 'certificados';
	public $timestamps = false;

	protected $casts = [
		'creado_en' => 'datetime',
		'actualizado_en' => 'datetime',
		'plantilla_id' => 'int',
		'firmado_digitalmente' => 'bool'
	];

	protected $fillable = [
		'creado_en',
		'actualizado_en',
		'plantilla_id',
		'tipo',
		'url_pdf',
		'firmado_digitalmente'
	];

	public function plantillas_certificado()
	{
		return $this->belongsTo(PlantillasCertificado::class, 'plantilla_id');
	}

	public function certificados_destinatarios()
	{
		return $this->hasMany(CertificadosDestinatario::class);
	}
}
