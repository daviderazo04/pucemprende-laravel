<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ResultadoProcesoEvaluacion
 *
 * @property int $id
 * @property int $persona_id
 * @property int $proceso_id
 * @property int $equipo_id
 * @property float $total
 *
 * @property Persona $persona
 * @property ProcesosEvaluacion $proceso
 * @property Equipo $equipo
 *
 * @package App\Models
 */
//*
class ResultadoProcesoEvaluacion extends Model
{
    protected $table = 'resultado_proceso_evaluacion';

    protected $fillable = [
        'proceso_id',
        'equipo_id',
        'total'
    ];

    protected $casts = [
        'total' => 'decimal:2'
    ];

    public $timestamps = false;

    // Relaciones

    public function proceso()
    {
        return $this->belongsTo(ProcesosEvaluacion::class, 'proceso_id');
    }

    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'equipo_id');
    }

}
