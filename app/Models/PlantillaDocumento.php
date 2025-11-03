<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlantillaDocumento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'plantillas_documento';

    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo_documento',
        'categoria',
        'contenido_html',
        'contenido_json',
        'campos_variables',
        'serie_documental_id',
        'subserie_documental_id',
        'metadatos_predefinidos',
        'es_publica',
        'usuario_creador_id',
        'activa',
        'version',
        'archivo_adjunto',
        'extension',
        'tags',
    ];

    protected $casts = [
        'campos_variables' => 'array',
        'metadatos_predefinidos' => 'array',
        'tags' => 'array',
        'es_publica' => 'boolean',
        'activa' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación con la serie documental
     */
    public function serieDocumental(): BelongsTo
    {
        return $this->belongsTo(SerieDocumental::class, 'serie_documental_id');
    }

    /**
     * Relación con la subserie documental
     */
    public function subserieDocumental(): BelongsTo
    {
        return $this->belongsTo(SubserieDocumental::class, 'subserie_documental_id');
    }

    /**
     * Relación con el usuario creador
     */
    public function usuarioCreador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_creador_id');
    }

    /**
     * Scope para plantillas públicas
     */
    public function scopePublicas($query)
    {
        return $query->where('es_publica', true);
    }

    /**
     * Scope para plantillas activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    /**
     * Scope por categoría
     */
    public function scopeCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    /**
     * Renderizar plantilla con variables
     */
    public function renderizar(array $variables = []): string
    {
        $contenido = $this->contenido_html;

        foreach ($variables as $clave => $valor) {
            $contenido = str_replace('{{' . $clave . '}}', $valor, $contenido);
        }

        return $contenido;
    }

    /**
     * Obtener campos variables de la plantilla
     */
    public function obtenerCamposVariables(): array
    {
        return $this->campos_variables ?? [];
    }

    /**
     * Validar que todas las variables requeridas estén presentes
     */
    public function validarVariables(array $variables): bool
    {
        $camposRequeridos = array_filter(
            $this->campos_variables ?? [],
            fn($campo) => $campo['requerido'] ?? false
        );

        foreach ($camposRequeridos as $campo) {
            if (!isset($variables[$campo['nombre']]) || empty($variables[$campo['nombre']])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Duplicar plantilla
     */
    public function duplicar(array $atributos = []): self
    {
        $nuevaPlantilla = $this->replicate();
        $nuevaPlantilla->nombre = $atributos['nombre'] ?? $this->nombre . ' (Copia)';
        $nuevaPlantilla->version = 1;
        $nuevaPlantilla->fill($atributos);
        $nuevaPlantilla->save();

        return $nuevaPlantilla;
    }

    /**
     * Incrementar versión
     */
    public function incrementarVersion(): void
    {
        $this->version++;
        $this->save();
    }
}
