<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class RolesPlantilla
 *
 * @property int $id
 * @property Carbon|null $creado_en
 * @property Carbon|null $actualizado_en
 * @property int $plantilla_id
 * @property int $rol_id
 *
 * @property PlantillasEvaluacion $plantillas_evaluacion
 * @property RolEvento $rolEvento // Actualizado para reflejar la relación con RolEvento
 *
 * @package App\Models
 */
class RolesPlantilla extends Model
{
    protected $table = 'roles_plantilla';
    protected $primaryKey = 'id'; // Asegurarse de que la clave primaria sea 'id'
    public $incrementing = true; // Cambiado a true porque 'id' es auto_increment
    public $timestamps = true; // Cambiado a true para que Laravel gestione creado_en y actualizado_en

    protected $casts = [
        'id' => 'integer',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
        'plantilla_id' => 'integer',
        'rol_id' => 'integer'
    ];

    protected $fillable = [
        'plantilla_id', // Añadido para asignación masiva
        'rol_id' // Añadido para asignación masiva
    ];

    public function plantillas_evaluacion()
    {
        return $this->belongsTo(PlantillasEvaluacion::class, 'plantilla_id');
    }

    /**
     * Define la relación con el modelo RolEvento.
     * 'rol_id' en 'roles_plantilla' se une con 'id' en 'rolEvento'.
     */
    public function rolEvento() // Renombrado de 'role' a 'rolEvento'
    {
        return $this->belongsTo(RolEvento::class, 'rol_id');
    }
}