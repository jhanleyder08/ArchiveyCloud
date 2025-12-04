<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpedienteTransferencia extends Model
{
    use HasFactory;

    protected $table = 'expediente_transferencias';

    protected $fillable = [
        'expediente_id',
        'tipo_transferencia',
        'estado',
        'origen_dependencia_id',
        'destino_dependencia_id',
        'ubicacion_origen',
        'ubicacion_destino',
        'fecha_solicitud',
        'fecha_transferencia',
        'fecha_recepcion',
        'observaciones',
        'acta_transferencia',
        'solicitado_por',
        'aprobado_por',
        'recibido_por',
    ];

    protected $casts = [
        'fecha_solicitud' => 'date',
        'fecha_transferencia' => 'date',
        'fecha_recepcion' => 'date',
    ];

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function receptor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recibido_por');
    }
}
