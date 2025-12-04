<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource para Workflows
 * Transforma el modelo en una respuesta JSON consistente
 */
class WorkflowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'tipo_entidad' => $this->tipo_entidad,
            'tipo_entidad_legible' => $this->getTipoEntidadLegible(),
            
            // Pasos
            'pasos' => $this->pasos,
            'total_pasos' => count($this->pasos ?? []),
            
            // Configuración
            'configuracion' => $this->configuracion,
            'activo' => (bool) $this->activo,
            
            // Usuario creador
            'creador' => [
                'id' => $this->creador->id ?? null,
                'name' => $this->creador->name ?? null,
                'email' => $this->creador->email ?? null,
            ],
            
            // Estadísticas (solo si está cargado)
            'estadisticas' => $this->when($this->relationLoaded('instancias'), function () {
                return [
                    'total_instancias' => $this->instancias->count(),
                    'instancias_activas' => $this->instancias->whereIn('estado', ['pendiente', 'en_progreso'])->count(),
                    'instancias_completadas' => $this->instancias->where('estado', 'completado')->count(),
                ];
            }),
            
            // Fechas
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'created_at_legible' => $this->created_at?->diffForHumans(),
            
            // Links
            'links' => [
                'self' => route('api.workflows.show', $this->id),
                'iniciar' => route('api.workflows.iniciar', $this->id),
                'instancias' => route('api.workflows.instancias', $this->id),
                'estadisticas' => route('api.workflows.estadisticas', $this->id),
            ],
        ];
    }

    /**
     * Obtener tipo de entidad legible
     */
    private function getTipoEntidadLegible(): string
    {
        $tipos = [
            'App\Models\Documento' => 'Documento',
            'App\Models\Expediente' => 'Expediente',
            'App\Models\Contrato' => 'Contrato',
        ];

        return $tipos[$this->tipo_entidad] ?? 'Desconocido';
    }

    /**
     * Agregar metadatos adicionales
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => '1.0',
                'timestamp' => now()->toISOString(),
            ],
        ];
    }
}
