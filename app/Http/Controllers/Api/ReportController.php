<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

/**
 * Controller de Reportes API
 */
class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService
    ) {}

    /**
     * GET /api/reports/system
     * Reporte completo del sistema
     */
    public function systemReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'format' => 'nullable|in:json,csv,xml',
        ]);

        $filters = [
            'start_date' => $validated['start_date'] ?? Carbon::now()->subDays(30),
            'end_date' => $validated['end_date'] ?? Carbon::now(),
        ];

        $report = $this->reportService->getSystemReport($filters);

        if (isset($validated['format']) && $validated['format'] !== 'json') {
            $exported = $this->reportService->exportReport($report, $validated['format']);
            
            return response()->json([
                'format' => $validated['format'],
                'data' => $exported,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $report,
            'generated_at' => now()->toISOString(),
        ]);
    }

    /**
     * GET /api/reports/documents
     * Reporte de documentos
     */
    public function documentsReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $startDate = $validated['start_date'] ?? Carbon::now()->subDays(30);
        $endDate = $validated['end_date'] ?? Carbon::now();

        $stats = $this->reportService->getDocumentStats($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $stats,
            'periodo' => [
                'inicio' => $startDate,
                'fin' => $endDate,
            ],
        ]);
    }

    /**
     * GET /api/reports/workflows
     * Reporte de workflows
     */
    public function workflowsReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $startDate = $validated['start_date'] ?? Carbon::now()->subDays(30);
        $endDate = $validated['end_date'] ?? Carbon::now();

        $stats = $this->reportService->getWorkflowStats($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * GET /api/reports/users
     * Reporte de usuarios
     */
    public function usersReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $startDate = $validated['start_date'] ?? Carbon::now()->subDays(30);
        $endDate = $validated['end_date'] ?? Carbon::now();

        $stats = $this->reportService->getUserStats($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * GET /api/reports/compliance
     * Reporte de cumplimiento
     */
    public function complianceReport(): JsonResponse
    {
        $stats = $this->reportService->getComplianceStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
            'generated_at' => now()->toISOString(),
        ]);
    }

    /**
     * GET /api/reports/performance
     * Reporte de rendimiento
     */
    public function performanceReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $startDate = $validated['start_date'] ?? Carbon::now()->subDays(7);
        $endDate = $validated['end_date'] ?? Carbon::now();

        $stats = $this->reportService->getPerformanceStats($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
