<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CertificadosDestinatario
 * 
 * @property int $certificado_id
 * @property string $entidad_tipo
 * @property int $entidad_id
 * @property string|null $rol_destinatario
 * 
 * @property Certificado $certificado
 *
 * @package App\Models
 */
class CertificadosDestinatario extends Model
{
	protected $table = 'certificados_destinatarios';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'certificado_id' => 'int',
		'entidad_id' => 'int'
	];

	protected $fillable = [
		'rol_destinatario'
	];

	public function certificado()
	{
		return $this->belongsTo(Certificado::class);
	}
}
