<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection; // Although not used directly by 'rolEvento', it's good practice for relationships
use Carbon\Carbon; // Although not used directly by 'rolEvento' timestamps, keeping it for consistency if needed in the future

/**
 * Class RolEvento
 *
 * @property int $id
 * @property string $nombre
 *
 * @package App\Models
 */
class RolEvento extends Model
{
    protected $table = 'rolEvento'; // Name of your table
    public $timestamps = false; // Your table does not have 'created_at' and 'updated_at' columns

    protected $fillable = [
        'nombre', // 'nombre' is the only fillable field from your table structure
    ];

    // If 'rolEvento' has any relationships with other models, you would define them here.
    // For example, if there's an 'Eventos' model that uses 'rol_id' as a foreign key:
    
    public function evento_rol_persona()
    {
        return $this->hasMany(EventoRolPersona::class, 'rol_id');
    }

    public function roles_plantillas()
	{
		return $this->hasMany(RolesPlantilla::class, 'rol_id');
	}
}