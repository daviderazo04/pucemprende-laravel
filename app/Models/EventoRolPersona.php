<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot; // Use Pivot for pivot tables

/**
 * Class EventoRolPersona
 *
 * @property int $id
 * @property int $evento_id
 * @property int $rol_id
 * @property int $persona_id
 *
 * @package App\Models
 */
class EventoRolPersona extends Pivot // Extend Pivot instead of Model
{
    protected $table = 'evento_rol_persona'; // Your pivot table name
    public $timestamps = false; // Assuming your pivot table doesn't have created_at/updated_at

    protected $fillable = [
        'evento_id',
        'rol_id',
        'persona_id',
    ];

    // Optional: If you ever need to access the related models directly from the pivot instance
    public function evento()
    {
        return $this->belongsTo(Evento::class, 'evento_id');
    }

    public function rolEvento()
    {
        return $this->belongsTo(RolEvento::class, 'rol_id');
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }
}