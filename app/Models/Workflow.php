<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Workflow extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo_entidad',
        'serie_documental_id',
        'pasos',
        'configuracion',
        'activo',
        'usuario_creador_id',
    ];

    protected $casts = [
        'pasos' => 'array',
        'configuracion' => 'array',
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Instancias del workflow
     */
    public function instancias(): HasMany
    {
        return $this->hasMany(WorkflowInstancia::class);
    }

    /**
     * Serie documental asociada
     */
    public function serieDocumental(): BelongsTo
    {
        return $this->belongsTo(SerieDocumental::class);
    }

    /**
     * Usuario creador
     */
    public function usuarioCreador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_creador_id');
    }

    /**
     * Scope para workflows activos
     */
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Iniciar instancia del workflow
     */
    public function iniciar($entidadId, $usuarioId, array $datos = []): WorkflowInstancia
    {
        return WorkflowInstancia::create([
            'workflow_id' => $this->id,
            'entidad_id' => $entidadId,
            'entidad_type' => $this->tipo_entidad,
            'usuario_iniciador_id' => $usuarioId,
            'paso_actual' => 0,
            'estado' => 'en_proceso',
            'datos' => $datos,
        ]);
    }

    /**
     * Obtener primer paso
     */
    public function getPrimerPaso(): ?array
    {
        return $this->pasos[0] ?? null;
    }

    /**
     * Obtener paso por índice
     */
    public function getPaso(int $indice): ?array
    {
        return $this->pasos[$indice] ?? null;
    }

    /**
     * Contar pasos totales
     */
    public function getTotalPasos(): int
    {
        return count($this->pasos);
    }
}
