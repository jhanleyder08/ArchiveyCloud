<?php

namespace App\Http\Controllers\Api;

use App\Models\Expediente;
use App\Models\SerieDocumental;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @group Expedientes
 * 
 * API para gestión de expedientes electrónicos
 */
class ExpedienteApiController extends BaseApiController
{
    /**
     * Listar expedientes
     * 
     * @queryParam per_page int Número de elementos por página (1-100). Default: 15
     * @queryParam page int Página a obtener. Default: 1
     * @queryParam search string Búsqueda por número, título o descripción
     * @queryParam serie_id int Filtrar por serie documental
     * @queryParam estado string Filtrar por estado
     * @queryParam responsable_id int Filtrar por usuario responsable
     * @queryParam fecha_desde date Fecha desde (Y-m-d)
     * @queryParam fecha_hasta date Fecha hasta (Y-m-d)
     * @queryParam sort_by string Campo para ordenar. Default: created_at
     * @queryParam sort_direction string Dirección de ordenamiento (asc, desc). Default: desc
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $paginationParams = $this->getPaginationParams($request);
            
            $query = Expediente::with([
                'serieDocumental', 
                'usuarioResponsable', 
                'usuarioCreador'
            ])->select('expedientes.*');

            // Aplicar filtros específicos
            $this->applyExpedienteFilters($query, $request);
            
            // Aplicar filtros comunes
            $query = $this->applyCommonFilters($query, $request);

            $expedientes = $query->paginate($paginationParams['per_page']);

            // Transformar datos
            $expedientes->getCollection()->transform(function ($expediente) {
                return $this->transformExpediente($expediente);
            });

            return $this->sendPaginatedResponse($expedientes, 'Expedientes obtenidos exitosamente');

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Obtener expediente específico
     * 
     * @urlParam id int required ID del expediente
     */
    public function show(int $id): JsonResponse
    {
        try {
            $expediente = Expediente::with([
                'serieDocumental.subseries',
                'usuarioResponsable',
                'usuarioCreador',
                'documentos.tipologiaDocumental',
                'documentos.usuarioCreador',
                'pistaAuditoria'
            ])->findOrFail($id);

            $data = $this->transformExpediente($expediente, true);

            return $this->sendResponse($data, 'Expediente obtenido exitosamente');

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Crear nuevo expediente
     * 
     * @bodyParam numero_expediente string Número del expediente (opcional, se auto-genera)
     * @bodyParam titulo string required Título del expediente
     * @bodyParam descripcion string Descripción del expediente
     * @bodyParam serie_documental_id int required ID de la serie documental
     * @bodyParam usuario_responsable_id int ID del usuario responsable
     * @bodyParam fecha_apertura date Fecha de apertura (opcional, por defecto hoy)
     * @bodyParam palabras_clave array Palabras clave para búsqueda
     * @bodyParam metadata object Metadatos adicionales
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $this->validateRequest($request, [
                'numero_expediente' => 'nullable|string|unique:expedientes,numero_expediente',
                'titulo' => 'required|string|max:255',
                'descripcion' => 'nullable|string|max:2000',
                'serie_documental_id' => 'required|exists:series_documentales,id',
                'usuario_responsable_id' => 'nullable|exists:users,id',
                'fecha_apertura' => 'nullable|date',
                'palabras_clave' => 'nullable|array',
                'palabras_clave.*' => 'string|max:50',
                'metadata' => 'nullable|array'
            ]);

            DB::beginTransaction();

            // Generar número de expediente si no se proporciona
            if (empty($validatedData['numero_expediente'])) {
                $validatedData['numero_expediente'] = $this->generarNumeroExpediente();
            }

            // Crear expediente
            $expediente = Expediente::create([
                'numero_expediente' => $validatedData['numero_expediente'],
                'titulo' => $validatedData['titulo'],
                'descripcion' => $validatedData['descripcion'] ?? null,
                'serie_documental_id' => $validatedData['serie_documental_id'],
                'usuario_responsable_id' => $validatedData['usuario_responsable_id'] ?? auth()->id(),
                'usuario_creador_id' => auth()->id(),
                'fecha_apertura' => $validatedData['fecha_apertura'] ?? now(),
                'estado' => 'abierto',
                'palabras_clave' => $validatedData['palabras_clave'] ?? [],
                'metadata' => $validatedData['metadata'] ?? null,
            ]);

            // Registrar auditoría
            $this->registrarAuditoria('expediente_creado', $expediente);

            DB::commit();

            $expediente->load(['serieDocumental', 'usuarioResponsable', 'usuarioCreador']);
            
            return $this->sendResponse(
                $this->transformExpediente($expediente),
                'Expediente creado exitosamente',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    /**
     * Actualizar expediente
     * 
     * @urlParam id int required ID del expediente
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $expediente = Expediente::findOrFail($id);
            
            $validatedData = $this->validateRequest($request, [
                'titulo' => 'sometimes|required|string|max:255',
                'descripcion' => 'nullable|string|max:2000',
                'usuario_responsable_id' => 'nullable|exists:users,id',
                'estado' => 'sometimes|required|in:abierto,cerrado,eliminado,transferido',
                'palabras_clave' => 'nullable|array',
                'palabras_clave.*' => 'string|max:50',
                'metadata' => 'nullable|array'
            ]);

            DB::beginTransaction();

            $expediente->update($validatedData);
            
            $this->registrarAuditoria('expediente_actualizado', $expediente);

            DB::commit();

            $expediente->load(['serieDocumental', 'usuarioResponsable', 'usuarioCreador']);
            
            return $this->sendResponse(
                $this->transformExpediente($expediente),
                'Expediente actualizado exitosamente'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    /**
     * Cerrar expediente
     * 
     * @urlParam id int required ID del expediente
     * @bodyParam motivo_cierre string required Motivo del cierre
     * @bodyParam observaciones string Observaciones adicionales
     */
    public function cerrar(Request $request, int $id): JsonResponse
    {
        try {
            $expediente = Expediente::findOrFail($id);
            
            if ($expediente->estado !== 'abierto') {
                return $this->sendError('El expediente no está en estado abierto');
            }

            $validatedData = $this->validateRequest($request, [
                'motivo_cierre' => 'required|string|max:500',
                'observaciones' => 'nullable|string|max:1000'
            ]);

            DB::beginTransaction();

            $expediente->update([
                'estado' => 'cerrado',
                'fecha_cierre' => now(),
                'motivo_cierre' => $validatedData['motivo_cierre'],
                'observaciones_cierre' => $validatedData['observaciones'] ?? null,
            ]);

            $this->registrarAuditoria('expediente_cerrado', $expediente, [
                'motivo' => $validatedData['motivo_cierre']
            ]);

            DB::commit();

            $expediente->load(['serieDocumental', 'usuarioResponsable']);
            
            return $this->sendResponse(
                $this->transformExpediente($expediente),
                'Expediente cerrado exitosamente'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    /**
     * Reabrir expediente
     * 
     * @urlParam id int required ID del expediente
     * @bodyParam motivo_reapertura string required Motivo de la reapertura
     */
    public function reabrir(Request $request, int $id): JsonResponse
    {
        try {
            $expediente = Expediente::findOrFail($id);
            
            if ($expediente->estado !== 'cerrado') {
                return $this->sendError('El expediente no está cerrado');
            }

            $validatedData = $this->validateRequest($request, [
                'motivo_reapertura' => 'required|string|max:500'
            ]);

            DB::beginTransaction();

            $expediente->update([
                'estado' => 'abierto',
                'fecha_cierre' => null,
                'motivo_cierre' => null,
                'observaciones_cierre' => null,
            ]);

            $this->registrarAuditoria('expediente_reabierto', $expediente, [
                'motivo' => $validatedData['motivo_reapertura']
            ]);

            DB::commit();

            $expediente->load(['serieDocumental', 'usuarioResponsable']);
            
            return $this->sendResponse(
                $this->transformExpediente($expediente),
                'Expediente reabierto exitosamente'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    /**
     * Eliminar expediente
     * 
     * @urlParam id int required ID del expediente
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $expediente = Expediente::findOrFail($id);

            // Verificar que no tenga documentos asociados
            if ($expediente->documentos()->count() > 0) {
                return $this->sendError(
                    'No se puede eliminar el expediente porque tiene documentos asociados'
                );
            }

            DB::beginTransaction();

            $expediente->delete();
            
            $this->registrarAuditoria('expediente_eliminado', $expediente);

            DB::commit();

            return $this->sendResponse(null, 'Expediente eliminado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    /**
     * Obtener documentos del expediente
     * 
     * @urlParam id int required ID del expediente
     */
    public function documentos(int $id): JsonResponse
    {
        try {
            $expediente = Expediente::findOrFail($id);
            
            $documentos = $expediente->documentos()
                ->with(['tipologiaDocumental', 'usuarioCreador'])
                ->orderBy('created_at', 'desc')
                ->get();

            $data = $documentos->map(function ($documento) {
                return [
                    'id' => $documento->id,
                    'nombre' => $documento->nombre,
                    'tipo_documento' => $documento->tipo_documento,
                    'tamano_humano' => $this->formatBytes($documento->tamano_bytes),
                    'tipologia' => $documento->tipologia ? [
                        'id' => $documento->tipologia->id,
                        'nombre' => $documento->tipologia->nombre
                    ] : null,
                    'usuario_creador' => [
                        'name' => $documento->usuarioCreador->name
                    ],
                    'created_at' => $documento->created_at,
                    'created_at_human' => $documento->created_at->diffForHumans()
                ];
            });

            return $this->sendResponse($data, 'Documentos del expediente obtenidos exitosamente');

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Obtener estadísticas de expedientes
     */
    public function estadisticas(): JsonResponse
    {
        try {
            $stats = [
                'total' => Expediente::count(),
                'por_estado' => Expediente::selectRaw('estado, COUNT(*) as total')
                    ->groupBy('estado')
                    ->pluck('total', 'estado'),
                'creados_hoy' => Expediente::whereDate('created_at', today())->count(),
                'creados_esta_semana' => Expediente::whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
                'creados_este_mes' => Expediente::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'por_serie' => Expediente::with('serieDocumental')
                    ->get()
                    ->groupBy('serieDocumental.nombre')
                    ->map(function ($group) {
                        return $group->count();
                    })
                    ->sortDesc()
                    ->take(10)
            ];

            return $this->sendResponse($stats, 'Estadísticas obtenidas exitosamente');

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Aplicar filtros específicos de expedientes
     */
    private function applyExpedienteFilters($query, Request $request)
    {
        if ($request->has('serie_id')) {
            $query->where('serie_documental_id', $request->serie_id);
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('responsable_id')) {
            $query->where('usuario_responsable_id', $request->responsable_id);
        }
    }

    /**
     * Aplicar filtro de búsqueda
     */
    protected function applySearchFilter($query, string $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('numero_expediente', 'LIKE', "%{$searchTerm}%")
              ->orWhere('titulo', 'LIKE', "%{$searchTerm}%")
              ->orWhere('descripcion', 'LIKE', "%{$searchTerm}%")
              ->orWhereJsonContains('palabras_clave', $searchTerm);
        });
    }

    /**
     * Transformar expediente para respuesta API
     */
    private function transformExpediente(Expediente $expediente, bool $includeDetails = false): array
    {
        $data = [
            'id' => $expediente->id,
            'numero_expediente' => $expediente->numero_expediente,
            'titulo' => $expediente->titulo,
            'descripcion' => $expediente->descripcion,
            'estado' => $expediente->estado,
            'fecha_apertura' => $expediente->fecha_apertura,
            'fecha_cierre' => $expediente->fecha_cierre,
            'palabras_clave' => $expediente->palabras_clave ?? [],
            'created_at' => $expediente->created_at,
            'updated_at' => $expediente->updated_at,
            'created_at_human' => $expediente->created_at?->diffForHumans(),
            'updated_at_human' => $expediente->updated_at?->diffForHumans(),
        ];

        // Relaciones básicas
        if ($expediente->relationLoaded('serieDocumental') && $expediente->serieDocumental) {
            $data['serie_documental'] = [
                'id' => $expediente->serieDocumental->id,
                'codigo' => $expediente->serieDocumental->codigo,
                'nombre' => $expediente->serieDocumental->nombre,
            ];
        }

        if ($expediente->relationLoaded('usuarioResponsable') && $expediente->usuarioResponsable) {
            $data['usuario_responsable'] = [
                'id' => $expediente->usuarioResponsable->id,
                'name' => $expediente->usuarioResponsable->name,
                'email' => $expediente->usuarioResponsable->email,
            ];
        }

        if ($expediente->relationLoaded('usuarioCreador') && $expediente->usuarioCreador) {
            $data['usuario_creador'] = [
                'id' => $expediente->usuarioCreador->id,
                'name' => $expediente->usuarioCreador->name,
                'email' => $expediente->usuarioCreador->email,
            ];
        }

        // Contador de documentos
        $data['total_documentos'] = $expediente->documentos_count ?? $expediente->documentos()->count();

        // Detalles adicionales
        if ($includeDetails) {
            $data['metadata'] = $expediente->metadata;
            $data['motivo_cierre'] = $expediente->motivo_cierre;
            $data['observaciones_cierre'] = $expediente->observaciones_cierre;

            if ($expediente->relationLoaded('documentos')) {
                $data['documentos'] = $expediente->documentos->map(function ($documento) {
                    return [
                        'id' => $documento->id,
                        'nombre' => $documento->nombre,
                        'tipo_documento' => $documento->tipo_documento,
                        'tamano_humano' => $this->formatBytes($documento->tamano_bytes),
                        'created_at' => $documento->created_at,
                    ];
                });
            }
        }

        return $data;
    }

    /**
     * Generar número de expediente único
     */
    private function generarNumeroExpediente(): string
    {
        $year = date('Y');
        $prefix = "EXP-{$year}-";
        
        // Buscar el último número del año
        $lastExpediente = Expediente::where('numero_expediente', 'LIKE', $prefix . '%')
            ->orderBy('numero_expediente', 'desc')
            ->first();
            
        if ($lastExpediente) {
            $lastNumber = (int) str_replace($prefix, '', $lastExpediente->numero_expediente);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Formatear bytes
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
    private function registrarAuditoria(string $accion, Expediente $expediente, array $datos = [])
    {
        // Implementar registro de auditoría
    }
}
