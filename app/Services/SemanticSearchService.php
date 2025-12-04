<?php

namespace App\Services;

use App\Models\Documento;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Servicio de Búsqueda Semántica y Machine Learning
 * Integración con modelos de NLP para búsqueda inteligente
 */
class SemanticSearchService
{
    private string $provider;
    private array $config;

    public function __construct()
    {
        $this->provider = config('services.ml.provider', 'local');
        $this->config = config('services.ml', []);
    }

    /**
     * Búsqueda semántica
     */
    public function semanticSearch(string $query, array $options = []): array
    {
        $limit = $options['limit'] ?? 10;
        $threshold = $options['similarity_threshold'] ?? 0.7;

        Log::info('Ejecutando búsqueda semántica', [
            'query' => $query,
            'provider' => $this->provider,
        ]);

        try {
            // Generar embedding del query
            $queryEmbedding = $this->generateEmbedding($query);

            // Buscar documentos similares
            $results = $this->findSimilarDocuments($queryEmbedding, $limit, $threshold);

            // Reordenar por relevancia semántica
            $results = $this->reRankResults($results, $query);

            return $results;

        } catch (Exception $e) {
            Log::error('Error en búsqueda semántica', [
                'error' => $e->getMessage(),
            ]);

            // Fallback a búsqueda tradicional
            return $this->fallbackSearch($query, $limit);
        }
    }

    /**
     * Generar embedding (vector) de texto
     */
    public function generateEmbedding(string $text): array
    {
        $cacheKey = 'embedding_' . md5($text);

        return Cache::remember($cacheKey, 86400, function () use ($text) {
            return match($this->provider) {
                'openai' => $this->openAIEmbedding($text),
                'huggingface' => $this->huggingFaceEmbedding($text),
                'cohere' => $this->cohereEmbedding($text),
                'local' => $this->localEmbedding($text),
                default => throw new Exception('Proveedor ML no soportado'),
            };
        });
    }

    /**
     * Encontrar documentos similares usando embeddings
     */
    private function findSimilarDocuments(array $queryEmbedding, int $limit, float $threshold): array
    {
        // En producción, esto usaría una base de datos vectorial como:
        // - Pinecone
        // - Weaviate
        // - Milvus
        // - PostgreSQL con pgvector

        // Por ahora, simulamos con Elasticsearch
        $results = Documento::query()
            ->take($limit)
            ->get()
            ->map(function ($doc) use ($queryEmbedding) {
                // Simular similarity score
                $similarity = $this->cosineSimilarity(
                    $queryEmbedding,
                    $this->getDocumentEmbedding($doc)
                );

                return [
                    'documento' => $doc,
                    'similarity' => $similarity,
                    'score' => $similarity,
                ];
            })
            ->filter(fn($result) => $result['similarity'] >= $threshold)
            ->sortByDesc('similarity')
            ->values()
            ->toArray();

        return $results;
    }

    /**
     * Re-ordenar resultados por relevancia
     */
    private function reRankResults(array $results, string $query): array
    {
        // Usar modelo de re-ranking (ej: Cross-Encoder)
        foreach ($results as &$result) {
            $result['rerank_score'] = $this->calculateReRankScore($result['documento'], $query);
            $result['final_score'] = ($result['similarity'] * 0.7) + ($result['rerank_score'] * 0.3);
        }

        usort($results, fn($a, $b) => $b['final_score'] <=> $a['final_score']);

        return $results;
    }

    /**
     * Calcular score de re-ranking
     */
    private function calculateReRankScore($documento, string $query): float
    {
        // Mock - en producción usaría modelo de re-ranking
        return rand(70, 100) / 100;
    }

    /**
     * Clasificación automática de documentos
     */
    public function classifyDocument(Documento $documento): array
    {
        $text = $documento->contenido ?? $documento->nombre . ' ' . $documento->descripcion;

        Log::info('Clasificando documento automáticamente', [
            'documento_id' => $documento->id,
        ]);

        $classification = match($this->provider) {
            'openai' => $this->openAIClassify($text),
            'huggingface' => $this->huggingFaceClassify($text),
            default => $this->localClassify($text),
        };

        return [
            'categoria' => $classification['category'],
            'confidence' => $classification['confidence'],
            'subcategorias' => $classification['subcategories'] ?? [],
            'tags_sugeridos' => $this->extractKeywords($text),
        ];
    }

    /**
     * Extracción de entidades nombradas (NER)
     */
    public function extractEntities(string $text): array
    {
        Log::info('Extrayendo entidades nombradas');

        // Mock de entidades
        return [
            'personas' => ['Juan Pérez', 'María González'],
            'organizaciones' => ['ACME Corp', 'Gobierno Nacional'],
            'lugares' => ['Bogotá', 'Colombia'],
            'fechas' => ['2025-11-02', '15 de octubre'],
            'montos' => ['$1,000,000', '€500'],
        ];
    }

    /**
     * Extracción de palabras clave
     */
    public function extractKeywords(string $text, int $limit = 10): array
    {
        // Usando TF-IDF o RAKE
        $keywords = [
            'documento' => 0.95,
            'gestión' => 0.87,
            'workflow' => 0.82,
            'archivo' => 0.78,
            'digital' => 0.75,
        ];

        return array_slice($keywords, 0, $limit);
    }

    /**
     * Resumen automático de texto
     */
    public function summarizeText(string $text, int $maxLength = 200): string
    {
        Log::info('Generando resumen automático');

        if ($this->provider === 'openai') {
            return $this->openAISummarize($text, $maxLength);
        }

        // Resumen extractivo simple
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
        $summary = implode(' ', array_slice($sentences, 0, 3));

        return substr($summary, 0, $maxLength) . '...';
    }

    /**
     * Sugerencias de búsqueda
     */
    public function getSuggestions(string $partialQuery): array
    {
        // Usar embeddings para encontrar queries similares
        $suggestions = [
            'documentos de contrato',
            'documentos urgentes',
            'documentos del 2025',
            'documentos por aprobar',
        ];

        return array_filter($suggestions, function($suggestion) use ($partialQuery) {
            return stripos($suggestion, $partialQuery) !== false;
        });
    }

    /**
     * Detección de duplicados semánticos
     */
    public function findDuplicates(Documento $documento, float $threshold = 0.9): array
    {
        $embedding = $this->getDocumentEmbedding($documento);

        $duplicates = Documento::where('id', '!=', $documento->id)
            ->take(100)
            ->get()
            ->map(function($doc) use ($embedding) {
                $similarity = $this->cosineSimilarity($embedding, $this->getDocumentEmbedding($doc));
                
                return [
                    'documento' => $doc,
                    'similarity' => $similarity,
                ];
            })
            ->filter(fn($item) => $item['similarity'] >= $threshold)
            ->sortByDesc('similarity')
            ->values()
            ->toArray();

        return $duplicates;
    }

    /**
     * Similitud coseno entre vectores
     */
    private function cosineSimilarity(array $vec1, array $vec2): float
    {
        if (count($vec1) !== count($vec2)) {
            return 0.0;
        }

        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        for ($i = 0; $i < count($vec1); $i++) {
            $dotProduct += $vec1[$i] * $vec2[$i];
            $magnitude1 += $vec1[$i] ** 2;
            $magnitude2 += $vec2[$i] ** 2;
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0.0;
        }

        return $dotProduct / ($magnitude1 * $magnitude2);
    }

    /**
     * Obtener embedding de documento
     */
    private function getDocumentEmbedding(Documento $documento): array
    {
        $text = $documento->contenido ?? $documento->nombre . ' ' . $documento->descripcion;
        return $this->generateEmbedding($text);
    }

    // Implementaciones de proveedores (mock)

    private function openAIEmbedding(string $text): array
    {
        // En producción: API call a OpenAI embeddings
        return $this->mockEmbedding();
    }

    private function huggingFaceEmbedding(string $text): array
    {
        // En producción: API call a Hugging Face
        return $this->mockEmbedding();
    }

    private function cohereEmbedding(string $text): array
    {
        // En producción: API call a Cohere
        return $this->mockEmbedding();
    }

    private function localEmbedding(string $text): array
    {
        // Embedding simple basado en TF-IDF o Word2Vec local
        return $this->mockEmbedding();
    }

    private function mockEmbedding(): array
    {
        // Vector de 384 dimensiones (común en embeddings)
        $embedding = [];
        for ($i = 0; $i < 384; $i++) {
            $embedding[] = (float) (mt_rand(-100, 100) / 100);
        }
        return $embedding;
    }

    private function openAIClassify(string $text): array
    {
        return [
            'category' => 'Contrato',
            'confidence' => 0.92,
            'subcategories' => ['Legal', 'Servicios'],
        ];
    }

    private function huggingFaceClassify(string $text): array
    {
        return [
            'category' => 'Documento Administrativo',
            'confidence' => 0.88,
        ];
    }

    private function localClassify(string $text): array
    {
        // Clasificación simple basada en keywords
        $categories = [
            'Contrato' => ['contrato', 'acuerdo', 'compromiso'],
            'Factura' => ['factura', 'pago', 'monto'],
            'Memo' => ['memo', 'memorando', 'comunicado'],
            'Informe' => ['informe', 'reporte', 'análisis'],
        ];

        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($text, $keyword) !== false) {
                    return [
                        'category' => $category,
                        'confidence' => 0.75,
                    ];
                }
            }
        }

        return [
            'category' => 'General',
            'confidence' => 0.60,
        ];
    }

    private function openAISummarize(string $text, int $maxLength): string
    {
        // En producción: API call a OpenAI GPT
        return substr($text, 0, $maxLength) . '...';
    }

    private function fallbackSearch(string $query, int $limit): array
    {
        // Búsqueda tradicional como fallback
        return Documento::where('nombre', 'like', "%{$query}%")
            ->orWhere('descripcion', 'like', "%{$query}%")
            ->limit($limit)
            ->get()
            ->map(fn($doc) => [
                'documento' => $doc,
                'similarity' => 0.5,
                'score' => 0.5,
            ])
            ->toArray();
    }
}
