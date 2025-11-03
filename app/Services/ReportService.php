<?php

namespace App\Services;

use App\Models\Documento;
use App\Models\Expediente;
use App\Models\Workflow;
use App\Models\WorkflowInstancia;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Servicio de Reportes Avanzados
 * Genera reportes estadísticos y análisis del sistema
 */
class ReportService
{
    /**
     * Reporte completo del sistema
     */
    public function getSystemReport(array $filters = []): array
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->subDays(30);
        $endDate = $filters['end_date'] ?? Carbon::now();

        return [
            'periodo' => [
                'inicio' => $startDate,
                'fin' => $endDate,
                'dias' => Carbon::parse($startDate)->diffInDays($endDate),
            ],
            'documentos' => $this->getDocumentStats($startDate, $endDate),
            'expedientes' => $this->getExpedienteStats($startDate, $endDate),
            'workflows' => $this->getWorkflowStats($startDate, $endDate),
            'usuarios' => $this->getUserStats($startDate, $endDate),
            'rendimiento' => $this->getPerformanceStats($startDate, $endDate),
            'compliance' => $this->getComplianceStats(),
        ];
    }

    /**
     * Estadísticas de documentos
     */
    public function getDocumentStats($startDate, $endDate): array
    {
        $baseQuery = Documento::whereBetween('created_at', [$startDate, $endDate]);

        return [
            'total' => $baseQuery->count(),
            'por_dia' => $this->getDocumentsByDay($startDate, $endDate),
            'por_tipo' => $this->getDocumentsByType($startDate, $endDate),
            'por_serie' => $this->getDocumentsBySerie($startDate, $endDate),
            'por_estado' => $this->getDocumentsByStatus($startDate, $endDate),
            'firmados' => $baseQuery->whereNotNull('fecha_firma')->count(),
            'con_anexos' => $baseQuery->has('anexos')->count(),
            'total_tamaño' => $this->getTotalDocumentSize($startDate, $endDate),
            'promedio_tamaño' => $this->getAverageDocumentSize($startDate, $endDate),
            'top_usuarios_creadores' => $this->getTopDocumentCreators($startDate, $endDate),
        ];
    }

    /**
     * Documentos por día
     */
    private function getDocumentsByDay($startDate, $endDate): array
    {
        return Documento::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as fecha, COUNT(*) as total')
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get()
            ->map(fn($item) => [
                'fecha' => $item->fecha,
                'total' => $item->total,
            ])
            ->toArray();
    }

    /**
     * Documentos por tipo
     */
    private function getDocumentsByType($startDate, $endDate): array
    {
        return Documento::whereBetween('created_at', [$startDate, $endDate])
            ->select('tipo_documento', DB::raw('COUNT(*) as total'))
            ->groupBy('tipo_documento')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Documentos por serie documental
     */
    private function getDocumentsBySerie($startDate, $endDate): array
    {
        return Documento::whereBetween('created_at', [$startDate, $endDate])
            ->with('serieDocumental:id,nombre')
            ->select('serie_documental_id', DB::raw('COUNT(*) as total'))
            ->groupBy('serie_documental_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'serie' => $item->serieDocumental->nombre ?? 'Sin asignar',
                'total' => $item->total,
            ])
            ->toArray();
    }

    /**
     * Documentos por estado
     */
    private function getDocumentsByStatus($startDate, $endDate): array
    {
        return Documento::whereBetween('created_at', [$startDate, $endDate])
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->get()
            ->toArray();
    }

    /**
     * Tamaño total de documentos
     */
    private function getTotalDocumentSize($startDate, $endDate): string
    {
        $bytes = Documento::whereBetween('created_at', [$startDate, $endDate])
            ->sum('tamanio');
        
        return $this->formatBytes($bytes);
    }

    /**
     * Tamaño promedio de documentos
     */
    private function getAverageDocumentSize($startDate, $endDate): string
    {
        $bytes = Documento::whereBetween('created_at', [$startDate, $endDate])
            ->avg('tamanio');
        
        return $this->formatBytes($bytes);
    }

    /**
     * Top usuarios creadores de documentos
     */
    private function getTopDocumentCreators($startDate, $endDate): array
    {
        return Documento::whereBetween('created_at', [$startDate, $endDate])
            ->with('usuario:id,name,email')
            ->select('usuario_id', DB::raw('COUNT(*) as total'))
            ->groupBy('usuario_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'usuario' => $item->usuario->name ?? 'Desconocido',
                'email' => $item->usuario->email ?? '',
                'total' => $item->total,
            ])
            ->toArray();
    }

    /**
     * Estadísticas de expedientes
     */
    public function getExpedienteStats($startDate, $endDate): array
    {
        $baseQuery = Expediente::whereBetween('created_at', [$startDate, $endDate]);

        return [
            'total' => $baseQuery->count(),
            'abiertos' => $baseQuery->where('estado', 'abierto')->count(),
            'cerrados' => $baseQuery->where('estado', 'cerrado')->count(),
            'en_tramite' => $baseQuery->where('estado', 'en_tramite')->count(),
            'promedio_documentos' => $this->getAverageDocumentsPerExpediente($startDate, $endDate),
            'top_expedientes_mas_documentos' => $this->getTopExpedientesWithMostDocuments($startDate, $endDate),
        ];
    }

    /**
     * Promedio de documentos por expediente
     */
    private function getAverageDocumentsPerExpediente($startDate, $endDate): float
    {
        return round(
            Expediente::whereBetween('created_at', [$startDate, $endDate])
                ->withCount('documentos')
                ->get()
                ->avg('documentos_count'),
            2
        );
    }

    /**
     * Top expedientes con más documentos
     */
    private function getTopExpedientesWithMostDocuments($startDate, $endDate): array
    {
        return Expediente::whereBetween('created_at', [$startDate, $endDate])
            ->withCount('documentos')
            ->orderByDesc('documentos_count')
            ->limit(10)
            ->get()
            ->map(fn($exp) => [
                'codigo' => $exp->codigo,
                'nombre' => $exp->nombre,
                'documentos' => $exp->documentos_count,
            ])
            ->toArray();
    }

    /**
     * Estadísticas de workflows
     */
    public function getWorkflowStats($startDate, $endDate): array
    {
        return [
            'instancias_totales' => WorkflowInstancia::whereBetween('created_at', [$startDate, $endDate])->count(),
            'completadas' => WorkflowInstancia::whereBetween('created_at', [$startDate, $endDate])
                ->where('estado', 'completado')->count(),
            'en_progreso' => WorkflowInstancia::where('estado', 'en_progreso')->count(),
            'pendientes' => WorkflowInstancia::where('estado', 'pendiente')->count(),
            'tiempo_promedio_completado' => $this->getAverageWorkflowCompletionTime($startDate, $endDate),
            'workflows_mas_usados' => $this->getMostUsedWorkflows($startDate, $endDate),
            'workflows_mas_lentos' => $this->getSlowestWorkflows($startDate, $endDate),
            'tasa_completitud' => $this->getWorkflowCompletionRate($startDate, $endDate),
        ];
    }

    /**
     * Tiempo promedio de completado de workflows
     */
    private function getAverageWorkflowCompletionTime($startDate, $endDate): ?string
    {
        $avgMinutes = WorkflowInstancia::whereBetween('created_at', [$startDate, $endDate])
            ->where('estado', 'completado')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, fecha_inicio, fecha_finalizacion)) as avg_time')
            ->value('avg_time');

        if (!$avgMinutes) return null;

        $hours = floor($avgMinutes / 60);
        $minutes = $avgMinutes % 60;

        return sprintf('%d horas, %d minutos', $hours, $minutes);
    }

    /**
     * Workflows más usados
     */
    private function getMostUsedWorkflows($startDate, $endDate): array
    {
        return WorkflowInstancia::whereBetween('created_at', [$startDate, $endDate])
            ->with('workflow:id,nombre')
            ->select('workflow_id', DB::raw('COUNT(*) as total'))
            ->groupBy('workflow_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'workflow' => $item->workflow->nombre ?? 'Desconocido',
                'total_instancias' => $item->total,
            ])
            ->toArray();
    }

    /**
     * Workflows más lentos
     */
    private function getSlowestWorkflows($startDate, $endDate): array
    {
        return WorkflowInstancia::whereBetween('created_at', [$startDate, $endDate])
            ->where('estado', 'completado')
            ->with('workflow:id,nombre')
            ->selectRaw('workflow_id, AVG(TIMESTAMPDIFF(MINUTE, fecha_inicio, fecha_finalizacion)) as avg_time')
            ->groupBy('workflow_id')
            ->orderByDesc('avg_time')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'workflow' => $item->workflow->nombre ?? 'Desconocido',
                'tiempo_promedio' => round($item->avg_time, 2) . ' minutos',
            ])
            ->toArray();
    }

    /**
     * Tasa de completitud de workflows
     */
    private function getWorkflowCompletionRate($startDate, $endDate): float
    {
        $total = WorkflowInstancia::whereBetween('created_at', [$startDate, $endDate])->count();
        $completados = WorkflowInstancia::whereBetween('created_at', [$startDate, $endDate])
            ->where('estado', 'completado')->count();

        return $total > 0 ? round(($completados / $total) * 100, 2) : 0;
    }

    /**
     * Estadísticas de usuarios
     */
    public function getUserStats($startDate, $endDate): array
    {
        return [
            'usuarios_activos' => $this->getActiveUsers($startDate, $endDate),
            'nuevos_usuarios' => User::whereBetween('created_at', [$startDate, $endDate])->count(),
            'usuarios_mas_activos' => $this->getMostActiveUsers($startDate, $endDate),
            'actividad_por_dia' => $this->getUserActivityByDay($startDate, $endDate),
        ];
    }

    /**
     * Usuarios activos (que han creado documentos)
     */
    private function getActiveUsers($startDate, $endDate): int
    {
        return Documento::whereBetween('created_at', [$startDate, $endDate])
            ->distinct('usuario_id')
            ->count('usuario_id');
    }

    /**
     * Usuarios más activos
     */
    private function getMostActiveUsers($startDate, $endDate): array
    {
        return Documento::whereBetween('created_at', [$startDate, $endDate])
            ->with('usuario:id,name,email')
            ->select('usuario_id', DB::raw('COUNT(*) as actividad'))
            ->groupBy('usuario_id')
            ->orderByDesc('actividad')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'usuario' => $item->usuario->name ?? 'Desconocido',
                'email' => $item->usuario->email ?? '',
                'documentos_creados' => $item->actividad,
            ])
            ->toArray();
    }

    /**
     * Actividad de usuarios por día
     */
    private function getUserActivityByDay($startDate, $endDate): array
    {
        return Documento::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as fecha, COUNT(DISTINCT usuario_id) as usuarios_activos')
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get()
            ->toArray();
    }

    /**
     * Estadísticas de rendimiento
     */
    public function getPerformanceStats($startDate, $endDate): array
    {
        return [
            'documentos_por_dia_promedio' => $this->getAverageDocumentsPerDay($startDate, $endDate),
            'workflows_por_dia_promedio' => $this->getAverageWorkflowsPerDay($startDate, $endDate),
            'tiempo_promedio_respuesta' => $this->getAverageResponseTime(),
            'pico_actividad' => $this->getPeakActivityTime($startDate, $endDate),
        ];
    }

    /**
     * Promedio de documentos por día
     */
    private function getAverageDocumentsPerDay($startDate, $endDate): float
    {
        $days = Carbon::parse($startDate)->diffInDays($endDate);
        $total = Documento::whereBetween('created_at', [$startDate, $endDate])->count();

        return $days > 0 ? round($total / $days, 2) : 0;
    }

    /**
     * Promedio de workflows por día
     */
    private function getAverageWorkflowsPerDay($startDate, $endDate): float
    {
        $days = Carbon::parse($startDate)->diffInDays($endDate);
        $total = WorkflowInstancia::whereBetween('created_at', [$startDate, $endDate])->count();

        return $days > 0 ? round($total / $days, 2) : 0;
    }

    /**
     * Tiempo promedio de respuesta (workflows)
     */
    private function getAverageResponseTime(): string
    {
        // Tiempo promedio desde creación hasta primera acción
        return '15 minutos'; // Placeholder - implementar con datos reales
    }

    /**
     * Hora pico de actividad
     */
    private function getPeakActivityTime($startDate, $endDate): array
    {
        $hourly = Documento::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('HOUR(created_at) as hora, COUNT(*) as total')
            ->groupBy('hora')
            ->orderByDesc('total')
            ->first();

        return [
            'hora' => $hourly ? sprintf('%02d:00', $hourly->hora) : 'N/A',
            'documentos' => $hourly ? $hourly->total : 0,
        ];
    }

    /**
     * Estadísticas de cumplimiento
     */
    public function getComplianceStats(): array
    {
        $totalDocs = Documento::count();

        return [
            'documentos_con_trd' => [
                'total' => Documento::whereNotNull('serie_documental_id')->count(),
                'porcentaje' => $totalDocs > 0 ? round((Documento::whereNotNull('serie_documental_id')->count() / $totalDocs) * 100, 2) : 0,
            ],
            'documentos_firmados' => [
                'total' => Documento::whereNotNull('fecha_firma')->count(),
                'porcentaje' => $totalDocs > 0 ? round((Documento::whereNotNull('fecha_firma')->count() / $totalDocs) * 100, 2) : 0,
            ],
            'metadatos_completos' => [
                'total' => $this->getDocumentsWithCompleteMetadata(),
                'porcentaje' => $totalDocs > 0 ? round(($this->getDocumentsWithCompleteMetadata() / $totalDocs) * 100, 2) : 0,
            ],
            'expedientes_cerrados_correctamente' => [
                'total' => Expediente::where('estado', 'cerrado')->whereNotNull('fecha_cierre')->count(),
                'porcentaje' => Expediente::count() > 0 ? round((Expediente::where('estado', 'cerrado')->count() / Expediente::count()) * 100, 2) : 0,
            ],
        ];
    }

    /**
     * Documentos con metadatos completos
     */
    private function getDocumentsWithCompleteMetadata(): int
    {
        return Documento::whereNotNull('serie_documental_id')
            ->whereNotNull('nombre')
            ->whereNotNull('descripcion')
            ->whereNotNull('fecha_documento')
            ->count();
    }

    /**
     * Formatear bytes a formato legible
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Exportar reporte a formato específico
     */
    public function exportReport(array $data, string $format = 'json'): string
    {
        return match($format) {
            'json' => json_encode($data, JSON_PRETTY_PRINT),
            'csv' => $this->convertToCSV($data),
            'xml' => $this->convertToXML($data),
            default => json_encode($data),
        };
    }

    /**
     * Convertir datos a CSV
     */
    private function convertToCSV(array $data): string
    {
        // Implementación básica
        return "CSV Export - Implementar según necesidad";
    }

    /**
     * Convertir datos a XML
     */
    private function convertToXML(array $data): string
    {
        // Implementación básica
        return "<?xml version=\"1.0\"?>\n<report></report>";
    }
}
