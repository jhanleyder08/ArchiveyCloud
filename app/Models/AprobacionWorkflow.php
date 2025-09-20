<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AprobacionWorkflow extends Model
{
    use HasFactory;

    protected $table = 'aprobacion_workflows';

    protected $fillable = [
        'workflow_documento_id',
        'usuario_id',
        'nivel_aprobacion',
        'accion',
        'comentarios',
        'fecha_accion',
        'tiempo_respuesta_horas',
        'archivos_adjuntos'
    ];

    protected $casts = [
        'fecha_accion' => 'datetime',
        'archivos_adjuntos' => 'array',
    ];

    // Acciones posibles
    const ACCION_APROBADO = 'aprobado';
    const ACCION_RECHAZADO = 'rechazado';
    const ACCION_ENVIADO_REVISION = 'enviado_revision';
    const ACCION_DELEGADO = 'delegado';

    /**
     * Relación con el workflow
     */
    public function workflowDocumento(): BelongsTo
    {
        return $this->belongsTo(WorkflowDocumento::class, 'workflow_documento_id');
    }

    /**
     * Relación con el usuario que tomó la acción
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Scope por acción
     */
    public function scopePorAccion($query, $accion)
    {
        return $query->where('accion', $accion);
    }

    /**
     * Scope para aprobaciones de un usuario
     */
    public function scopeDeUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    /**
     * Obtener etiqueta de acción
     */
    public function getEtiquetaAccionAttribute()
    {
        $acciones = [
            self::ACCION_APROBADO => 'Aprobado',
            self::ACCION_RECHAZADO => 'Rechazado',
            self::ACCION_ENVIADO_REVISION => 'Enviado a Revisión',
            self::ACCION_DELEGADO => 'Delegado',
        ];

        return $acciones[$this->accion] ?? $this->accion;
    }
}
