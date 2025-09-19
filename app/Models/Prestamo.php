<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Prestamo extends Model
{
    use HasFactory;
    protected $fillable = [
        'tipo_prestamo',
        'expediente_id',
        'documento_id',
        'solicitante_id',
        'prestamista_id',
        'motivo',
        'fecha_prestamo',
        'fecha_devolucion_esperada',
        'fecha_devolucion_real',
        'observaciones',
        'observaciones_devolucion',
        'estado',
        'estado_devolucion',
        'renovaciones',
    ];

    protected $casts = [
        'fecha_prestamo' => 'datetime',
        'fecha_devolucion_esperada' => 'datetime',
        'fecha_devolucion_real' => 'datetime',
        'renovaciones' => 'integer',
    ];

    // Relaciones
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    public function prestamista(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prestamista_id');
    }

    // Métodos auxiliares
    public function getItemPrestadoAttribute()
    {
        if ($this->tipo_prestamo === 'expediente' && $this->expediente) {
            return $this->expediente->numero_expediente . ' - ' . $this->expediente->titulo;
        }

        if ($this->tipo_prestamo === 'documento' && $this->documento) {
            return $this->documento->nombre;
        }

        return 'Item no disponible';
    }

    public function getDiasPrestamoAttribute()
    {
        $fechaFin = $this->fecha_devolucion_real ?? Carbon::now();
        return $this->fecha_prestamo->diffInDays($fechaFin);
    }

    public function getDiasVencidoAttribute()
    {
        if ($this->estado !== 'prestado') {
            return 0;
        }

        return $this->fecha_devolucion_esperada->isPast() 
            ? $this->fecha_devolucion_esperada->diffInDays(Carbon::now())
            : 0;
    }

    public function getEstaVencidoAttribute()
    {
        return $this->estado === 'prestado' && $this->fecha_devolucion_esperada->isPast();
    }

    public function getDiasRestantesAttribute()
    {
        if ($this->estado !== 'prestado') {
            return null;
        }

        return $this->fecha_devolucion_esperada->isFuture()
            ? Carbon::now()->diffInDays($this->fecha_devolucion_esperada)
            : 0;
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', 'prestado');
    }

    public function scopeVencidos($query)
    {
        return $query->where('estado', 'prestado')
                    ->where('fecha_devolucion_esperada', '<', Carbon::now());
    }

    public function scopeProximosVencer($query, $dias = 7)
    {
        return $query->where('estado', 'prestado')
                    ->whereBetween('fecha_devolucion_esperada', [
                        Carbon::now(),
                        Carbon::now()->addDays($dias)
                    ]);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_prestamo', $tipo);
    }

    public function scopePorSolicitante($query, $solicitanteId)
    {
        return $query->where('solicitante_id', $solicitanteId);
    }
}
