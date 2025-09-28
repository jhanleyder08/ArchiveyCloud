<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Documento;
use App\Models\Expediente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DocumentoApiController extends Controller
{
    /**
     * Lista documentos con paginación y filtros
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 100);
        
        $query = Documento::query()
            ->with(['expediente:id,codigo,asunto', 'usuario:id,name'])
            ->orderBy('created_at', 'desc');
        
        // Filtros
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }
        
        if ($tipo = $request->get('tipo')) {
            $query->where('tipo_documento', $tipo);
        }
        
        if ($expedienteId = $request->get('expediente_id')) {
            $query->where('expediente_id', $expedienteId);
        }
        
        $documentos = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $documentos
        ]);
    }
    
    /**
     * Crear nuevo documento
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'tipo_documento' => 'nullable|string|max:100',
            'expediente_id' => 'required|exists:expedientes,id',
            'archivo' => 'required|file|max:51200', // 50MB
            'palabras_clave' => 'nullable|array',
            'palabras_clave.*' => 'string|max:50'
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
            $expediente = Expediente::findOrFail($request->expediente_id);
            $archivo = $request->file('archivo');
            
            // Generar ruta de almacenamiento
            $fecha = now()->format('Y/m');
            $nombreArchivo = time() . '_' . $archivo->getClientOriginalName();
            $rutaArchivo = "documentos/{$fecha}/expediente_{$expediente->id}/{$nombreArchivo}";
            
            // Almacenar archivo
            $path = $archivo->storeAs('', $rutaArchivo, 'public');
            
            // Crear documento
            $documento = Documento::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'tipo_documento' => $request->tipo_documento ?? 'Documento',
                'expediente_id' => $expediente->id,
                'usuario_id' => auth()->id() ?? $request->attributes->get('api_token')->usuario_id,
                'ruta_archivo' => $path,
                'nombre_archivo_original' => $archivo->getClientOriginalName(),
                'tamaño' => $archivo->getSize(),
                'tipo_mime' => $archivo->getMimeType(),
                'hash_integridad' => hash_file('sha256', $archivo->getPathname()),
                'palabras_clave' => $request->palabras_clave ?? [],
                'metadatos' => [
                    'api_created' => true,
                    'api_token' => $request->attributes->get('api_token')->nombre,
                    'uploaded_at' => now()->toISOString()
                ]
            ]);
            
            // Cargar relaciones
            $documento->load(['expediente:id,codigo,asunto', 'usuario:id,name']);
            
            return response()->json([
                'success' => true,
                'message' => 'Documento creado exitosamente',
                'data' => $documento
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_SERVER_ERROR',
                    'message' => 'Error al crear el documento',
                    'details' => app()->environment('local') ? $e->getMessage() : null
                ]
            ], 500);
        }
    }
    
    /**
     * Mostrar documento específico
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $documento = Documento::with(['expediente:id,codigo,asunto', 'usuario:id,name'])
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $documento
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RESOURCE_NOT_FOUND',
                    'message' => 'Documento no encontrado'
                ]
            ], 404);
        }
    }
    
    /**
     * Actualizar documento
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'tipo_documento' => 'nullable|string|max:100',
            'palabras_clave' => 'nullable|array',
            'palabras_clave.*' => 'string|max:50'
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
            $documento = Documento::findOrFail($id);
            
            $documento->update($validator->validated());
            
            $documento->load(['expediente:id,codigo,asunto', 'usuario:id,name']);
            
            return response()->json([
                'success' => true,
                'message' => 'Documento actualizado exitosamente',
                'data' => $documento
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RESOURCE_NOT_FOUND',
                    'message' => 'Documento no encontrado'
                ]
            ], 404);
        }
    }
    
    /**
     * Eliminar documento
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $documento = Documento::findOrFail($id);
            
            // Eliminar archivo físico
            if ($documento->ruta_archivo && Storage::disk('public')->exists($documento->ruta_archivo)) {
                Storage::disk('public')->delete($documento->ruta_archivo);
            }
            
            $documento->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Documento eliminado exitosamente'
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RESOURCE_NOT_FOUND',
                    'message' => 'Documento no encontrado'
                ]
            ], 404);
        }
    }
    
    /**
     * Descargar archivo del documento
     */
    public function download(Request $request, int $id): Response
    {
        try {
            $documento = Documento::findOrFail($id);
            
            if (!$documento->ruta_archivo || !Storage::disk('public')->exists($documento->ruta_archivo)) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'FILE_NOT_FOUND',
                        'message' => 'Archivo no encontrado'
                    ]
                ], 404);
            }
            
            return Storage::disk('public')->download(
                $documento->ruta_archivo,
                $documento->nombre_archivo_original,
                [
                    'Content-Type' => $documento->tipo_mime,
                    'Content-Disposition' => 'attachment; filename="' . $documento->nombre_archivo_original . '"'
                ]
            );
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RESOURCE_NOT_FOUND',
                    'message' => 'Documento no encontrado'
                ]
            ], 404);
        }
    }
}
