<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BaseApiController extends Controller
{
    /**
     * Respuesta exitosa estándar
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    protected function sendResponse($data, string $message = 'Operación exitosa', int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ];

        return response()->json($response, $code);
    }

    /**
     * Respuesta de error estándar
     *
     * @param string $message
     * @param array $errors
     * @param int $code
     * @return JsonResponse
     */
    protected function sendError(string $message, array $errors = [], int $code = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Respuesta paginada estándar
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    protected function sendPaginatedResponse($data, string $message = 'Datos obtenidos exitosamente'): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
                'has_more_pages' => $data->hasMorePages(),
            ],
            'timestamp' => now()->toISOString(),
        ];

        return response()->json($response, 200);
    }

    /**
     * Validar datos de entrada
     *
     * @param Request $request
     * @param array $rules
     * @param array $messages
     * @return array
     * @throws ValidationException
     */
    protected function validateRequest(Request $request, array $rules, array $messages = []): array
    {
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Manejar excepciones y devolver respuesta de error apropiada
     *
     * @param \Exception $exception
     * @return JsonResponse
     */
    protected function handleException(\Exception $exception): JsonResponse
    {
        if ($exception instanceof ValidationException) {
            return $this->sendError(
                'Error de validación',
                $exception->errors(),
                422
            );
        }

        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->sendError('Recurso no encontrado', [], 404);
        }

        if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
            return $this->sendError('No autenticado', [], 401);
        }

        if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return $this->sendError('No autorizado', [], 403);
        }

        // Log del error para debugging
        \Log::error('API Error: ' . $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);

        // En producción, no mostrar detalles del error
        if (app()->environment('production')) {
            return $this->sendError('Error interno del servidor', [], 500);
        }

        return $this->sendError(
            'Error interno del servidor: ' . $exception->getMessage(),
            [],
            500
        );
    }

    /**
     * Transformar modelo para respuesta API
     *
     * @param mixed $model
     * @param array $relations
     * @return array
     */
    protected function transformModel($model, array $relations = []): array
    {
        if (is_null($model)) {
            return [];
        }

        $data = $model->toArray();

        // Cargar relaciones si se especifican
        if (!empty($relations)) {
            $model->load($relations);
            foreach ($relations as $relation) {
                $data[$relation] = $model->$relation;
            }
        }

        // Agregar metadatos útiles
        if (method_exists($model, 'getCreatedAtAttribute')) {
            $data['created_at_human'] = $model->created_at?->diffForHumans();
        }

        if (method_exists($model, 'getUpdatedAtAttribute')) {
            $data['updated_at_human'] = $model->updated_at?->diffForHumans();
        }

        return $data;
    }

    /**
     * Aplicar filtros comunes a query builder
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyCommonFilters($query, Request $request)
    {
        // Filtro por fechas
        if ($request->has('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        // Filtro de búsqueda general
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            // Este método debe ser implementado en cada controlador específico
            if (method_exists($this, 'applySearchFilter')) {
                $query = $this->applySearchFilter($query, $searchTerm);
            }
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        if (in_array($sortDirection, ['asc', 'desc'])) {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query;
    }

    /**
     * Obtener parámetros de paginación
     *
     * @param Request $request
     * @return array
     */
    protected function getPaginationParams(Request $request): array
    {
        $perPage = $request->get('per_page', 15);
        $perPage = min(max($perPage, 1), 100); // Entre 1 y 100

        return [
            'per_page' => $perPage,
            'page' => $request->get('page', 1)
        ];
    }
}
