<?php

namespace App\Http\Controllers\Api;

use App\Models\Documento;
use App\Models\Expediente;
use App\Models\TipologiaDocumental;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

/**
 * @group Documentos
 * 
 * API para gestión de documentos electrónicos
 */
class DocumentoApiController extends BaseApiController
{
    /**
     * Listar documentos
     * 
     * Obtiene una lista paginada de documentos con filtros opcionales
     * 
     * @queryParam per_page int Número de elementos por página (1-100). Default: 15
     * @queryParam page int Página a obtener. Default: 1
     * @queryParam search string Búsqueda por nombre o descripción
     * @queryParam expediente_id int Filtrar por expediente
     * @queryParam tipologia_id int Filtrar por tipología documental
     * @queryParam estado string Filtrar por estado (activo, inactivo, eliminado)
     * @queryParam fecha_desde date Fecha desde (Y-m-d)
     * @queryParam fecha_hasta date Fecha hasta (Y-m-d)
     * @queryParam sort_by string Campo para ordenar. Default: created_at
     * @queryParam sort_direction string Dirección de ordenamiento (asc, desc). Default: desc
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Documentos obtenidos exitosamente",
     *   "data": [
     *     {
     *       "id": 1,
     *       "nombre": "Contrato de Servicios.pdf",
     *       "descripcion": "Contrato de prestación de servicios",
     *       "tipo_documento": "PDF",
     *       "tamano_bytes": 1048576,
     *       "tamano_humano": "1 MB",
     *       "hash_integridad": "abc123...",
     *       "expediente": {
     *         "id": 1,
     *         "codigo": "EXP-2024-001",
     *         "titulo": "Expediente de Contratos"
     *       },
     *       "tipologia": {
     *         "id": 1,
     *         "nombre": "Contratos"
     *       },
     *       "created_at": "2024-01-15T10:30:00.000000Z",
     *       "created_at_human": "hace 2 días"
     *     }
     *   ],
     *   "pagination": {
     *     "current_page": 1,
     *     "last_page": 5,
     *     "per_page": 15,
     *     "total": 75,
     *     "from": 1,
     *     "to": 15,
     *     "has_more_pages": true
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $paginationParams = $this->getPaginationParams($request);
            
            $query = Documento::with(['expediente', 'tipologiaDocumental', 'usuarioCreador'])
                ->select('documentos.*');

            // Aplicar filtros específicos
            $this->applyDocumentFilters($query, $request);
            
            // Aplicar filtros comunes (fechas, búsqueda, ordenamiento)
            $query = $this->applyCommonFilters($query, $request);

            $documentos = $query->paginate($paginationParams['per_page']);

            // Transformar datos para incluir información adicional
            $documentos->getCollection()->transform(function ($documento) {
                return $this->transformDocumento($documento);
            });

            return $this->sendPaginatedResponse($documentos, 'Documentos obtenidos exitosamente');

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Obtener documento específico
     * 
     * @urlParam id int required ID del documento
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Documento obtenido exitosamente",
     *   "data": {
     *     "id": 1,
     *     "nombre": "Contrato de Servicios.pdf",
     *     "descripcion": "Contrato de prestación de servicios",
     *     "ruta_archivo": "documentos/2024/01/contrato.pdf",
     *     "tipo_documento": "PDF",
     *     "tamano_bytes": 1048576,
     *     "tamano_humano": "1 MB",
     *     "hash_integridad": "abc123...",
     *     "metadata": {
     *       "autor": "Juan Pérez",
     *       "titulo": "Contrato de Servicios"
     *     },
     *     "expediente": {...},
     *     "tipologia": {...},
     *     "versiones": [...],
     *     "firmas": [...]
     *   }
     * }
     */
    public function show(int $id): JsonResponse
    {
        try {
            $documento = Documento::with([
                'expediente.serieDocumental',
                'tipologiaDocumental',
                'usuarioCreador',
                'versiones',
                'firmasDigitales.certificado',
                'pistaAuditoria'
            ])->findOrFail($id);

            $data = $this->transformDocumento($documento, true);

            return $this->sendResponse($data, 'Documento obtenido exitosamente');

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Crear nuevo documento
     * 
     * @bodyParam nombre string required Nombre del documento
     * @bodyParam descripcion string Descripción del documento
     * @bodyParam expediente_id int required ID del expediente
     * @bodyParam tipologia_id int ID de la tipología documental
     * @bodyParam archivo file required Archivo a subir
     * @bodyParam metadata object Metadatos adicionales del documento
     * 
     * @response 201 {
     *   "success": true,
     *   "message": "Documento creado exitosamente",
     *   "data": {...}
     * }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $this->validateRequest($request, [
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string|max:1000',
                'expediente_id' => 'required|exists:expedientes,id',
                'tipologia_id' => 'nullable|exists:tipologias_documentales,id',
                'archivo' => 'required|file|max:51200', // 50MB max
                'metadata' => 'nullable|array'
            ]);

            DB::beginTransaction();

            // Verificar permisos del expediente
            $expediente = Expediente::findOrFail($validatedData['expediente_id']);
            
            // Procesar archivo
            $archivo = $request->file('archivo');
            $rutaArchivo = $this->procesarArchivo($archivo, $expediente);

            // Crear documento
            $documento = Documento::create([
                'nombre' => $validatedData['nombre'],
                'descripcion' => $validatedData['descripcion'] ?? null,
                'expediente_id' => $validatedData['expediente_id'],
                'tipologia_documental_id' => $validatedData['tipologia_id'] ?? null,
                'ruta_archivo' => $rutaArchivo,
                'tipo_documento' => strtoupper($archivo->getClientOriginalExtension()),
                'tamano_bytes' => $archivo->getSize(),
                'hash_integridad' => hash_file('sha256', $archivo->getRealPath()),
                'metadata' => $validatedData['metadata'] ?? null,
                'usuario_creador_id' => auth()->id(),
                'estado_ciclo_vida' => 'tramite'
            ]);

            // Registrar en auditoría
            $this->registrarAuditoria('documento_creado', $documento);

            DB::commit();

            $documento->load(['expediente', 'tipologiaDocumental', 'usuarioCreador']);
            
            return $this->sendResponse(
                $this->transformDocumento($documento),
                'Documento creado exitosamente',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    /**
     * Actualizar documento
     * 
     * @urlParam id int required ID del documento
     * 
     * @bodyParam nombre string Nombre del documento
     * @bodyParam descripcion string Descripción del documento
     * @bodyParam tipologia_id int ID de la tipología documental
     * @bodyParam metadata object Metadatos adicionales
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $documento = Documento::findOrFail($id);
            
            $validatedData = $this->validateRequest($request, [
                'nombre' => 'sometimes|required|string|max:255',
                'descripcion' => 'nullable|string|max:1000',
                'tipologia_id' => 'nullable|exists:tipologias_documentales,id',
                'metadata' => 'nullable|array'
            ]);

            DB::beginTransaction();

            $documento->update([
                'nombre' => $validatedData['nombre'] ?? $documento->nombre,
                'descripcion' => $validatedData['descripcion'] ?? $documento->descripcion,
                'tipologia_documental_id' => $validatedData['tipologia_id'] ?? $documento->tipologia_documental_id,
                'metadata' => $validatedData['metadata'] ?? $documento->metadata,
            ]);

            $this->registrarAuditoria('documento_actualizado', $documento);

            DB::commit();

            $documento->load(['expediente', 'tipologiaDocumental', 'usuarioCreador']);
            
            return $this->sendResponse(
                $this->transformDocumento($documento),
                'Documento actualizado exitosamente'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    /**
     * Eliminar documento
     * 
     * @urlParam id int required ID del documento
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $documento = Documento::findOrFail($id);

            DB::beginTransaction();

            // Soft delete
            $documento->delete();
            
            $this->registrarAuditoria('documento_eliminado', $documento);

            DB::commit();

            return $this->sendResponse(null, 'Documento eliminado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    /**
     * Descargar documento
     * 
     * @urlParam id int required ID del documento
     */
    public function download(int $id): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        try {
            $documento = Documento::findOrFail($id);
            
            if (!Storage::exists($documento->ruta_archivo)) {
                abort(404, 'Archivo no encontrado');
            }

            $this->registrarAuditoria('documento_descargado', $documento);

            return Storage::download($documento->ruta_archivo, $documento->nombre);

        } catch (\Exception $e) {
            abort(500, 'Error al descargar el archivo');
        }
    }

    /**
     * Obtener estadísticas de documentos
     */
    public function estadisticas(): JsonResponse
    {
        try {
            $stats = [
                'total' => Documento::count(),
                'por_estado' => Documento::selectRaw('estado_ciclo_vida, COUNT(*) as total')
                    ->groupBy('estado_ciclo_vida')
                    ->pluck('total', 'estado_ciclo_vida'),
                'por_tipo' => Documento::selectRaw('tipo_documento, COUNT(*) as total')
                    ->groupBy('tipo_documento')
                    ->orderByDesc('total')
                    ->limit(10)
                    ->pluck('total', 'tipo_documento'),
                'tamano_total' => Documento::sum('tamano_bytes'),
                'creados_hoy' => Documento::whereDate('created_at', today())->count(),
                'creados_esta_semana' => Documento::whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
            ];

            $stats['tamano_total_humano'] = $this->formatBytes($stats['tamano_total']);

            return $this->sendResponse($stats, 'Estadísticas obtenidas exitosamente');

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Aplicar filtros específicos de documentos
     */
    private function applyDocumentFilters($query, Request $request)
    {
        if ($request->has('expediente_id')) {
            $query->where('expediente_id', $request->expediente_id);
        }

        if ($request->has('tipologia_id')) {
            $query->where('tipologia_documental_id', $request->tipologia_id);
        }

        if ($request->has('estado')) {
            $query->where('estado_ciclo_vida', $request->estado);
        }

        if ($request->has('tipo_documento')) {
            $query->where('tipo_documento', strtoupper($request->tipo_documento));
        }
    }

    /**
     * Aplicar filtro de búsqueda
     */
    protected function applySearchFilter($query, string $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('nombre', 'LIKE', "%{$searchTerm}%")
              ->orWhere('descripcion', 'LIKE', "%{$searchTerm}%")
              ->orWhereHas('expediente', function ($eq) use ($searchTerm) {
                  $eq->where('codigo', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('titulo', 'LIKE', "%{$searchTerm}%");
              });
        });
    }

    /**
     * Transformar documento para respuesta API
     */
    private function transformDocumento(Documento $documento, bool $includeDetails = false): array
    {
        $data = [
            'id' => $documento->id,
            'nombre' => $documento->nombre,
            'descripcion' => $documento->descripcion,
            'tipo_documento' => $documento->tipo_documento,
            'tamano_bytes' => $documento->tamano_bytes,
            'tamano_humano' => $this->formatBytes($documento->tamano_bytes),
            'hash_integridad' => $documento->hash_integridad,
            'estado_ciclo_vida' => $documento->estado_ciclo_vida,
            'created_at' => $documento->created_at,
            'updated_at' => $documento->updated_at,
            'created_at_human' => $documento->created_at?->diffForHumans(),
            'updated_at_human' => $documento->updated_at?->diffForHumans(),
        ];

        // Relaciones básicas
        if ($documento->relationLoaded('expediente') && $documento->expediente) {
            $data['expediente'] = [
                'id' => $documento->expediente->id,
                'codigo' => $documento->expediente->codigo,
                'titulo' => $documento->expediente->titulo,
            ];
        }

        if ($documento->relationLoaded('tipologiaDocumental') && $documento->tipologia) {
            $data['tipologia'] = [
                'id' => $documento->tipologia->id,
                'nombre' => $documento->tipologia->nombre,
            ];
        }

        if ($documento->relationLoaded('usuarioCreador') && $documento->usuarioCreador) {
            $data['usuario_creador'] = [
                'id' => $documento->usuarioCreador->id,
                'name' => $documento->usuarioCreador->name,
                'email' => $documento->usuarioCreador->email,
            ];
        }

        // Detalles adicionales si se requieren
        if ($includeDetails) {
            $data['ruta_archivo'] = $documento->ruta_archivo;
            $data['metadata'] = $documento->metadata;
            
            if ($documento->relationLoaded('versiones')) {
                $data['versiones'] = $documento->versiones->map(function ($version) {
                    return [
                        'id' => $version->id,
                        'numero_version' => $version->numero_version,
                        'created_at' => $version->created_at,
                    ];
                });
            }

            if ($documento->relationLoaded('firmasDigitales')) {
                $data['firmas'] = $documento->firmasDigitales->map(function ($firma) {
                    return [
                        'id' => $firma->id,
                        'valida' => $firma->valida,
                        'fecha_firma' => $firma->fecha_firma,
                        'certificado' => $firma->certificado ? [
                            'nombre' => $firma->certificado->nombre_certificado,
                            'emisor' => $firma->certificado->emisor,
                        ] : null,
                    ];
                });
            }
        }

        return $data;
    }

    /**
     * Procesar archivo subido
     */
    private function procesarArchivo($archivo, Expediente $expediente): string
    {
        $year = date('Y');
        $month = date('m');
        $directory = "documentos/{$year}/{$month}/exp_{$expediente->id}";
        
        $fileName = time() . '_' . $archivo->getClientOriginalName();
        
        return $archivo->storeAs($directory, $fileName, 'public');
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
     * Registrar auditoría
     */
    private function registrarAuditoria(string $accion, Documento $documento)
    {
        // Implementar registro de auditoría
        // Esto se conectaría con PistaAuditoria existente
    }
}
