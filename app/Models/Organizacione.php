<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Organizacione
 *
 * @property int $id
 * @property string|null $org_nom
 * @property string|null $org_abreviatura
 * @property string|null $encar_nombre
 * @property string|null $encar_apellido
 * @property string $encar_identificacion
 * @property string|null $encar_rol
 * @property string|null $org_telf
 * @property string|null $org_email
 *
 * @package App\Models
 */
class Organizacione extends Model
{
	protected $table = 'organizaciones';
	public $timestamps = false; // La tabla no tiene created_at ni updated_at

	protected $fillable = [
		'org_nom',
		'org_abreviatura',
		'encar_nombre',
		'encar_apellido',
		'encar_identificacion',
		'encar_rol',
		'org_telf',
		'org_email'
	];
}
