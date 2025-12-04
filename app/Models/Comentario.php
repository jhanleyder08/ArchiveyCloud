<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo de Comentarios y Anotaciones
 * Soporta hilos de conversación y anotaciones en documentos
 */
class Comentario extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'comentarios';

    protected $fillable = [
        'comentable_type',
        'comentable_id',
        'usuario_id',
        'padre_id',
        'contenido',
        'es_privado',
        'es_resuelto',
        'fecha_resolucion',
        'pagina',
        'coordenadas',
        'editado_at',
        'editado_por_id',
    ];

    protected $casts = [
        'es_privado' => 'boolean',
        'es_resuelto' => 'boolean',
        'fecha_resolucion' => 'datetime',
        'coordenadas' => 'array',
        'editado_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Entidad a la que pertenece el comentario (polimórfico)
     */
    public function comentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Usuario que creó el comentario
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Comentario padre (para hilos)
     */
    public function padre(): BelongsTo
    {
        return $this->belongsTo(Comentario::class, 'padre_id');
    }

    /**
     * Respuestas a este comentario
     */
    public function respuestas(): HasMany
    {
        return $this->hasMany(Comentario::class, 'padre_id')->orderBy('created_at');
    }

    /**
     * Usuario que editó el comentario
     */
    public function editadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editado_por_id');
    }

    /**
     * Scope: Comentarios públicos
     */
    public function scopePublicos($query)
    {
        return $query->where('es_privado', false);
    }

    /**
     * Scope: Comentarios privados
     */
    public function scopePrivados($query)
    {
        return $query->where('es_privado', true);
    }

    /**
     * Scope: Comentarios resueltos
     */
    public function scopeResueltos($query)
    {
        return $query->where('es_resuelto', true);
    }

    /**
     * Scope: Comentarios pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('es_resuelto', false);
    }

    /**
     * Scope: Solo comentarios principales (no respuestas)
     */
    public function scopePrincipales($query)
    {
        return $query->whereNull('padre_id');
    }

    /**
     * Scope: Anotaciones en PDFs
     */
    public function scopeAnotaciones($query)
    {
        return $query->whereNotNull('pagina');
    }

    /**
     * Marcar como resuelto
     */
    public function resolver(): void
    {
        $this->update([
            'es_resuelto' => true,
            'fecha_resolucion' => now(),
        ]);
    }

    /**
     * Reabrir comentario
     */
    public function reabrir(): void
    {
        $this->update([
            'es_resuelto' => false,
            'fecha_resolucion' => null,
        ]);
    }

    /**
     * Editar comentario
     */
    public function editar(string $nuevoContenido, int $usuarioId): void
    {
        $this->update([
            'contenido' => $nuevoContenido,
            'editado_at' => now(),
            'editado_por_id' => $usuarioId,
        ]);
    }

    /**
     * Verificar si fue editado
     */
    public function fueEditado(): bool
    {
        return !is_null($this->editado_at);
    }

    /**
     * Contar respuestas
     */
    public function cantidadRespuestas(): int
    {
        return $this->respuestas()->count();
    }

    /**
     * Es una anotación en PDF
     */
    public function esAnotacion(): bool
    {
        return !is_null($this->pagina);
    }
}
