<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use App\Services\AdvancedSearchService;
use App\Models\Serie;
use App\Models\Expediente;
use App\Models\TipologiaDocumental;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    protected SearchService $searchService;
    protected AdvancedSearchService $advancedSearchService;

    public function __construct(SearchService $searchService, AdvancedSearchService $advancedSearchService)
    {
        $this->searchService = $searchService;
        $this->advancedSearchService = $advancedSearchService;
    }

    /**
     * Página principal de búsqueda
     */
    public function index(Request $request)
    {
        // Obtener opciones para filtros
        $filterOptions = [
            'series' => Serie::select('id', 'codigo', 'nombre')->where('activa', true)->get(),
            'expedientes' => Expediente::select('id', 'codigo', 'nombre', 'serie_id')
                ->where('estado', '!=', 'cerrado')
                ->with('serie:id,nombre')
                ->limit(50)
                ->get(),
            'tipologias' => TipologiaDocumental::select('id', 'nombre', 'categoria')->where('activo', true)->get(),
            'usuarios' => User::select('id', 'name', 'email')->where('active', true)->get(),
            'formatos' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'mp4', 'mp3', 'xls', 'xlsx'],
            'estados' => [
                ['value' => 'borrador', 'label' => 'Borrador'],
                ['value' => 'activo', 'label' => 'Activo'],
                ['value' => 'archivado', 'label' => 'Archivado'],
            ],
            'confidencialidad' => [
                ['value' => 'publica', 'label' => 'Pública'],
                ['value' => 'interna', 'label' => 'Interna'],
                ['value' => 'confidencial', 'label' => 'Confidencial'],
                ['value' => 'reservada', 'label' => 'Reservada'],
            ]
        ];

        $searchStats = $this->advancedSearchService->getSearchStats();

        return Inertia::render('Search/Index', [
            'query' => $request->query('q', ''),
            'filterOptions' => $filterOptions,
            'searchStats' => $searchStats,
            'examples' => [
                'boolean' => 'contrato AND empresa NOT temporal',
                'wildcard' => 'factura*',
                'phrase' => '"informe mensual"',
                'field' => 'tipo:contrato',
            ]
        ]);
    }

    /**
     * Búsqueda simple
     */
    public function search(Request $request)
    {
        $query = $request->input('q', '');
        $type = $request->input('type', 'documentos');
        $size = $request->input('size', 20);
        $from = $request->input('from', 0);
        $sort = $request->input('sort', []);

        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'Query requerido',
            ], 400);
        }

        $results = $this->searchService->searchSimple($query, $type, [
            'size' => $size,
            'from' => $from,
            'sort' => $sort,
        ]);

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * REQ-BP-002: Búsqueda avanzada con operadores booleanos
     */
    public function searchAdvanced(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:500',
            'must' => 'nullable|array',
            'should' => 'nullable|array', 
            'must_not' => 'nullable|array',
            'keywords' => 'nullable|array',
            'date_range' => 'nullable|array',
            'expediente_id' => 'nullable|integer|exists:expedientes,id',
            'serie_id' => 'nullable|integer|exists:series,id',
            'usuario_creador_id' => 'nullable|integer|exists:users,id',
            'estado' => 'nullable|array',
            'formato' => 'nullable|array',
            'confidencialidad' => 'nullable|string',
            'tipologia_id' => 'nullable|integer|exists:tipologias_documentales,id',
            'has_ocr' => 'nullable|boolean',
            'file_size_range' => 'nullable|array',
            'size' => 'nullable|integer|min:1|max:100',
            'from' => 'nullable|integer|min:0',
            'sort' => 'nullable|array'
        ]);

        $searchParams = [
            'q' => $request->input('q'),
            'must' => $request->input('must', []),
            'should' => $request->input('should', []),
            'must_not' => $request->input('must_not', []),
            'keywords' => $request->input('keywords', []),
            'ocr_text' => $request->input('ocr_text'),
            'date_range' => $request->input('date_range', []),
            'expediente_id' => $request->input('expediente_id'),
            'serie_id' => $request->input('serie_id'),
            'usuario_creador_id' => $request->input('usuario_creador_id'),
            'estado' => $request->input('estado'),
            'formato' => $request->input('formato'),
            'confidencialidad' => $request->input('confidencialidad'),
            'tipologia_id' => $request->input('tipologia_id'),
            'has_ocr' => $request->input('has_ocr'),
            'file_size_range' => $request->input('file_size_range'),
        ];

        $options = [
            'size' => $request->input('size', 20),
            'from' => $request->input('from', 0),
            'sort' => $request->input('sort', []),
            'fields' => $request->input('fields', [])
        ];

        $results = $this->advancedSearchService->searchAdvanced($searchParams, $options);

        return response()->json([
            'success' => true,
            'data' => $results,
            'query_info' => [
                'parsed_params' => $searchParams,
                'options' => $options,
                'execution_time' => $results['took'] ?? 0
            ]
        ]);
    }

    /**
     * REQ-BP-005: Autocompletado inteligente
     */
    public function autocomplete(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1|max:100',
            'field' => 'nullable|string|in:nombre,codigo,descripcion,tipo_documental',
            'size' => 'nullable|integer|min:1|max:20'
        ]);

        $query = $request->input('q');
        $field = $request->input('field', 'nombre');
        $size = $request->input('size', 10);

        $suggestions = $this->advancedSearchService->autocomplete($query, [
            'field' => $field,
            'size' => $size
        ]);

        return response()->json([
            'success' => true,
            'data' => $suggestions
        ]);
    }

    /**
     * Búsqueda por similitud (More Like This)
     */
    public function searchSimilar(Request $request, int $documentId)
    {
        $request->validate([
            'size' => 'nullable|integer|min:1|max:50'
        ]);

        $results = $this->advancedSearchService->searchSimilar($documentId, [
            'size' => $request->input('size', 10)
        ]);

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    /**
     * Obtener estadísticas de búsqueda
     */
    public function getSearchStats()
    {
        $stats = $this->advancedSearchService->getSearchStats();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Búsqueda con sugerencias de corrección
     */
    public function searchWithSuggestions(Request $request)
    {
        $query = $request->input('q', '');
        $type = $request->input('type', 'documentos');

        $suggestions = $this->searchService->autocomplete($query, $field, $type);

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
        ]);
    }
}
