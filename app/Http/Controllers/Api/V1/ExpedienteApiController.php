<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Expediente;
use App\Models\SerieDocumental;
use App\Models\SubserieDocumental;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExpedienteApiController extends Controller
{
    /**
     * Lista expedientes con paginación y filtros
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 100);
        
        $query = Expediente::query()
            ->with([
                'serie:id,codigo,nombre',
                'subserie:id,codigo,nombre',
                'usuarioResponsable:id,name'
            ])
            ->withCount('documentos')
            ->orderBy('created_at', 'desc');
        
        // Filtros
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                  ->orWhere('asunto', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }
        
        if ($estado = $request->get('estado')) {
            $query->where('estado', $estado);
        }
        
        if ($serieId = $request->get('serie_id')) {
            $query->where('serie_id', $serieId);
        }
        
        if ($usuarioId = $request->get('usuario_id')) {
            $query->where('usuario_responsable_id', $usuarioId);
        }
        
        $expedientes = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $expedientes
        ]);
    }
    
    /**
     * Crear nuevo expediente
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'asunto' => 'required|string|max:500',
            'descripcion' => 'nullable|string|max:1000',
            'serie_id' => 'required|exists:series_documentales,id',
            'subserie_id' => 'nullable|exists:subseries_documentales,id',
            'usuario_responsable_id' => 'nullable|exists:users,id',
            'nivel_acceso' => 'nullable|in:publico,restringido,confidencial,secreto',
            'palabras_clave' => 'nullable|array',
            'palabras_clave.*' => 'string|max:50',
            'ubicacion_fisica' => 'nullable|string|max:255'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_FAILED',
                    'message' => 'Datos de entrada inválidos',
                    'details' => $validator->errors()
                ]
            ], 422);
        }
        
        try {
            // Validar que subserie pertenece a la serie
            if ($request->subserie_id) {
                $subserie = SubserieDocumental::where('id', $request->subserie_id)
                    ->where('serie_id', $request->serie_id)
                    ->first();
                
                if (!$subserie) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'VALIDATION_FAILED',
                            'message' => 'La subserie no pertenece a la serie seleccionada'
                        ]
                    ], 422);
                }
            }
            
            // Generar código único
            $serie = SerieDocumental::findOrFail($request->serie_id);
            $año = now()->year;
            $ultimoNumero = Expediente::where('serie_id', $request->serie_id)
                ->whereYear('created_at', $año)
                ->max('numero_consecutivo') ?? 0;
            
            $numeroConsecutivo = $ultimoNumero + 1;
            $codigo = "{$serie->codigo}-{$año}-" . str_pad($numeroConsecutivo, 4, '0', STR_PAD_LEFT);
            
            $expediente = Expediente::create([
                'codigo' => $codigo,
                'numero_consecutivo' => $numeroConsecutivo,
                'asunto' => $request->asunto,
                'descripcion' => $request->descripcion,
                'serie_id' => $request->serie_id,
                'subserie_id' => $request->subserie_id,
                'usuario_responsable_id' => $request->usuario_responsable_id ?? $request->attributes->get('api_token')->usuario_id,
                'estado' => 'abierto',
                'nivel_acceso' => $request->nivel_acceso ?? 'publico',
                'fecha_apertura' => now(),
                'palabras_clave' => $request->palabras_clave ?? [],
                'ubicacion_fisica' => $request->ubicacion_fisica,
                'metadatos' => [
                    'api_created' => true,
                    'api_token' => $request->attributes->get('api_token')->nombre,
                    'created_at' => now()->toISOString()
                ]
            ]);
            
            // Cargar relaciones
            $expediente->load([
                'serie:id,codigo,nombre',
                'subserie:id,codigo,nombre',
                'usuarioResponsable:id,name'
            ]);
            $expediente->loadCount('documentos');
            
            return response()->json([
                'success' => true,
                'message' => 'Expediente creado exitosamente',
                'data' => $expediente
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_SERVER_ERROR',
                    'message' => 'Error al crear el expediente',
                    'details' => app()->environment('local') ? $e->getMessage() : null
                ]
            ], 500);
        }
    }
    
    /**
     * Mostrar expediente específico
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $expediente = Expediente::with([
                'serie:id,codigo,nombre',
                'subserie:id,codigo,nombre',
                'usuarioResponsable:id,name',
                'documentos' => function($query) {
                    $query->select('id', 'nombre', 'tipo_documento', 'tamaño', 'expediente_id', 'created_at')
                          ->orderBy('created_at', 'desc');
                }
            ])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $expediente
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RESOURCE_NOT_FOUND',
                    'message' => 'Expediente no encontrado'
                ]
            ], 404);
        }
    }
    
    /**
     * Actualizar expediente
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'asunto' => 'sometimes|required|string|max:500',
            'descripcion' => 'nullable|string|max:1000',
            'usuario_responsable_id' => 'nullable|exists:users,id',
            'nivel_acceso' => 'nullable|in:publico,restringido,confidencial,secreto',
            'palabras_clave' => 'nullable|array',
            'palabras_clave.*' => 'string|max:50',
            'ubicacion_fisica' => 'nullable|string|max:255',
            'estado' => 'nullable|in:abierto,tramite,revision,cerrado,archivado'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_FAILED',
                    'message' => 'Datos de entrada inválidos',
                    'details' => $validator->errors()
                ]
            ], 422);
        }
        
        try {
            $expediente = Expediente::findOrFail($id);
            
            // Si se cambia el estado a cerrado, establecer fecha de cierre
            $updateData = $validator->validated();
            if (isset($updateData['estado']) && $updateData['estado'] === 'cerrado' && !$expediente->fecha_cierre) {
                $updateData['fecha_cierre'] = now();
            }
            
            $expediente->update($updateData);
            
            $expediente->load([
                'serie:id,codigo,nombre',
                'subserie:id,codigo,nombre',
                'usuarioResponsable:id,name'
            ]);
            $expediente->loadCount('documentos');
            
            return response()->json([
                'success' => true,
                'message' => 'Expediente actualizado exitosamente',
                'data' => $expediente
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RESOURCE_NOT_FOUND',
                    'message' => 'Expediente no encontrado'
                ]
            ], 404);
        }
    }
    
    /**
     * Eliminar expediente
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $expediente = Expediente::with('documentos')->findOrFail($id);
            
            // Verificar que no tenga documentos
            if ($expediente->documentos->count() > 0) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'EXPEDIENTE_HAS_DOCUMENTS',
                        'message' => 'No se puede eliminar un expediente que contiene documentos'
                    ]
                ], 400);
            }
            
            $expediente->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Expediente eliminado exitosamente'
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RESOURCE_NOT_FOUND',
                    'message' => 'Expediente no encontrado'
                ]
            ], 404);
        }
    }
    
    /**
     * Obtener documentos de un expediente
     */
    public function documentos(Request $request, int $id): JsonResponse
    {
        try {
            $expediente = Expediente::findOrFail($id);
            $perPage = min($request->get('per_page', 15), 100);
            
            $documentos = $expediente->documentos()
                ->with(['usuario:id,name'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $documentos
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RESOURCE_NOT_FOUND',
                    'message' => 'Expediente no encontrado'
                ]
            ], 404);
        }
    }
}
