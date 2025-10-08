<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Página principal de búsqueda
     */
    public function index(Request $request)
    {
        return Inertia::render('Search/Index', [
            'query' => $request->query('q', ''),
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
     * Búsqueda avanzada con operadores booleanos
     */
    public function searchAdvanced(Request $request)
    {
        $searchParams = [
            'must' => $request->input('must', []),
            'should' => $request->input('should', []),
            'must_not' => $request->input('must_not', []),
            'fields' => $request->input('fields', []),
            'date_range' => $request->input('date_range', []),
            'filters' => $request->input('filters', []),
            'keywords' => $request->input('keywords', []),
        ];

        $type = $request->input('type', 'documentos');
        $size = $request->input('size', 20);
        $from = $request->input('from', 0);
        $sort = $request->input('sort', []);
        $aggregations = $request->input('aggregations', []);

        $results = $this->searchService->searchAdvanced($searchParams, $type, [
            'size' => $size,
            'from' => $from,
            'sort' => $sort,
            'aggregations' => $aggregations,
        ]);

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Autocompletado
     */
    public function autocomplete(Request $request)
    {
        $query = $request->input('q', '');
        $field = $request->input('field', 'nombre');
        $type = $request->input('type', 'documentos');

        $suggestions = $this->searchService->autocomplete($query, $field, $type);

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
        ]);
    }
}
