<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ResultadoRubrica
 *
 * @property int $id
 * @property int|null $persona_id
 * @property int|null $plantilla_id
 * @property int|null $equipo_id
 * @property float|null $total
 *
 * @property Persona|null $persona
 * @property PlantillasEvaluacion|null $plantilla
 * @property Equipo|null $equipo
 */
class ResultadoRubrica extends Model
{
    protected $table = 'resultadoRubrica';
    public $timestamps = false;

    protected $casts = [
        'persona_id' => 'int',
        'plantilla_id' => 'int',
        'equipo_id' => 'int',
        'total' => 'float'
    ];

    protected $fillable = [
        'persona_id',
        'plantilla_id',
        'equipo_id',
        'total'
    ];

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function plantilla(): BelongsTo
    {
        return $this->belongsTo(PlantillasEvaluacion::class, 'plantilla_id');
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }
}
