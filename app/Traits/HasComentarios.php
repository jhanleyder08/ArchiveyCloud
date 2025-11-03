<?php

namespace App\Traits;

use App\Models\Comentario;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait HasComentarios
 * Agrega funcionalidad de comentarios a cualquier modelo
 */
trait HasComentarios
{
    /**
     * Relación polimórfica con comentarios
     */
    public function comentarios(): MorphMany
    {
        return $this->morphMany(Comentario::class, 'comentable')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Agregar un comentario
     */
    public function agregarComentario(
        string $contenido,
        int $usuarioId,
        bool $esPrivado = false,
        ?int $padreId = null,
        ?array $opciones = []
    ): Comentario {
        return $this->comentarios()->create([
            'contenido' => $contenido,
            'usuario_id' => $usuarioId,
            'es_privado' => $esPrivado,
            'padre_id' => $padreId,
            'pagina' => $opciones['pagina'] ?? null,
            'coordenadas' => $opciones['coordenadas'] ?? null,
        ]);
    }

    /**
     * Agregar una anotación en PDF
     */
    public function agregarAnotacion(
        string $contenido,
        int $usuarioId,
        int $pagina,
        array $coordenadas
    ): Comentario {
        return $this->agregarComentario(
            contenido: $contenido,
            usuarioId: $usuarioId,
            esPrivado: false,
            padreId: null,
            opciones: [
                'pagina' => $pagina,
                'coordenadas' => $coordenadas,
            ]
        );
    }

    /**
     * Obtener comentarios públicos
     */
    public function comentariosPublicos(): MorphMany
    {
        return $this->comentarios()->publicos();
    }

    /**
     * Obtener comentarios privados
     */
    public function comentariosPrivados(): MorphMany
    {
        return $this->comentarios()->privados();
    }

    /**
     * Obtener solo comentarios principales (sin respuestas)
     */
    public function comentariosPrincipales(): MorphMany
    {
        return $this->comentarios()->principales();
    }

    /**
     * Obtener anotaciones en PDF
     */
    public function anotaciones(): MorphMany
    {
        return $this->comentarios()->anotaciones();
    }

    /**
     * Contar total de comentarios
     */
    public function totalComentarios(): int
    {
        return $this->comentarios()->count();
    }

    /**
     * Contar comentarios pendientes de resolver
     */
    public function comentariosPendientes(): int
    {
        return $this->comentarios()->pendientes()->count();
    }

    /**
     * Contar comentarios resueltos
     */
    public function comentariosResueltos(): int
    {
        return $this->comentarios()->resueltos()->count();
    }

    /**
     * Tiene comentarios pendientes
     */
    public function tieneComentariosPendientes(): bool
    {
        return $this->comentariosPendientes() > 0;
    }

    /**
     * Obtener último comentario
     */
    public function ultimoComentario(): ?Comentario
    {
        return $this->comentarios()->latest()->first();
    }

    /**
     * Obtener comentarios con respuestas anidadas
     */
    public function comentariosConRespuestas()
    {
        return $this->comentariosPrincipales()
            ->with(['respuestas.usuario', 'usuario'])
            ->get();
    }
}
