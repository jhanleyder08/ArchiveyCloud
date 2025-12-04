<?php

namespace App\Http\Controllers;

use App\Services\BusinessRulesService;
use App\Models\Documento;
use App\Models\Expediente;
use App\Models\Serie;
use App\Models\Subserie;
use App\Models\TipologiaDocumental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Controlador de Validaciones y Reglas de Negocio
 * 
 * Expone las validaciones del BusinessRulesService como APIs REST
 */
class ValidationController extends Controller
{
    protected BusinessRulesService $businessRules;

    public function __construct(BusinessRulesService $businessRules)
    {
        $this->businessRules = $businessRules;
    }

    /**
     * REQ-VN-001: Validar estructura TRD/CCD
     */
    public function validarEstructuraTRD(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'serie_id' => 'nullable|integer|exists:series,id',
            'subserie_id' => 'nullable|integer|exists:subseries,id',
            'tipologia_id' => 'nullable|integer|exists:tipologias_documentales,id',
            'clasificacion_ccd' => 'nullable|string|regex:/^\d{3}\.\d{2}\.\d{3}$/'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Datos de entrada inválidos'
            ], 422);
        }

        $resultado = $this->businessRules->validarEstructuraTRD($request->all());

        return response()->json([
            'success' => $resultado['valido'],
            'data' => [
                'valido' => $resultado['valido'],
                'errores' => $resultado['errores'],
                'advertencias' => $resultado['advertencias']
            ],
            'message' => $resultado['valido'] ? 'Estructura TRD/CCD válida' : 'Se encontraron errores en la estructura'
        ]);
    }

    /**
     * REQ-VN-002: Validar reglas de negocio para expedientes
     */
    public function validarReglasExpediente(Request $request, int $expedienteId)
    {
        $expediente = Expediente::findOrFail($expedienteId);

        $validator = Validator::make($request->all(), [
            'estado' => 'nullable|string|in:abierto,activo,suspendido,cerrado,transferido,eliminado',
            'fecha_cierre' => 'nullable|date',
            'serie_id' => 'nullable|integer|exists:series,id',
            'subserie_id' => 'nullable|integer|exists:subseries,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Datos de entrada inválidos'
            ], 422);
        }

        $cambios = $request->only(['estado', 'fecha_cierre', 'serie_id', 'subserie_id']);
        $resultado = $this->businessRules->validarReglasExpediente($expediente, $cambios);

        return response()->json([
            'success' => $resultado['valido'],
            'data' => [
                'valido' => $resultado['valido'],
                'errores' => $resultado['errores'],
                'advertencias' => $resultado['advertencias'],
                'expediente_id' => $expedienteId,
                'estado_actual' => $expediente->estado
            ],
            'message' => $resultado['valido'] ? 'Reglas de negocio válidas' : 'Se encontraron violaciones de reglas de negocio'
        ]);
    }

    /**
     * REQ-VN-003: Validar metadatos obligatorios
     */
    public function validarMetadatos(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tipo_entidad' => 'required|string|in:documento,expediente,serie',
            'entidad_id' => 'required|integer',
            'metadatos' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Datos de entrada inválidos'
            ], 422);
        }

        $tipoEntidad = $request->input('tipo_entidad');
        $entidadId = $request->input('entidad_id');

        // Obtener la entidad según el tipo
        $entidad = match($tipoEntidad) {
            'documento' => Documento::findOrFail($entidadId),
            'expediente' => Expediente::findOrFail($entidadId),
            'serie' => Serie::findOrFail($entidadId),
            default => null
        };

        if (!$entidad) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de entidad no soportado'
            ], 400);
        }

        $resultado = $this->businessRules->validarMetadatosObligatorios($entidad, $tipoEntidad);

        return response()->json([
            'success' => $resultado['valido'],
            'data' => [
                'valido' => $resultado['valido'],
                'errores' => $resultado['errores'],
                'advertencias' => $resultado['advertencias'],
                'tipo_entidad' => $tipoEntidad,
                'entidad_id' => $entidadId
            ],
            'message' => $resultado['valido'] ? 'Metadatos válidos' : 'Faltan metadatos obligatorios'
        ]);
    }

    /**
     * REQ-VN-004: Validar integridad referencial
     */
    public function validarIntegridad(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tipo_entidad' => 'required|string|in:documento,expediente,serie',
            'entidad_id' => 'required|integer',
            'operacion' => 'required|string|in:create,update,delete'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Datos de entrada inválidos'
            ], 422);
        }

        $tipoEntidad = $request->input('tipo_entidad');
        $entidadId = $request->input('entidad_id');
        $operacion = $request->input('operacion');

        // Obtener la entidad según el tipo
        $entidad = match($tipoEntidad) {
            'documento' => Documento::findOrFail($entidadId),
            'expediente' => Expediente::findOrFail($entidadId),
            'serie' => Serie::findOrFail($entidadId),
            default => null
        };

        if (!$entidad) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de entidad no soportado'
            ], 400);
        }

        $resultado = $this->businessRules->validarIntegridadReferencial($entidad, $operacion);

        return response()->json([
            'success' => $resultado['valido'],
            'data' => [
                'valido' => $resultado['valido'],
                'errores' => $resultado['errores'],
                'advertencias' => $resultado['advertencias'],
                'tipo_entidad' => $tipoEntidad,
                'entidad_id' => $entidadId,
                'operacion' => $operacion
            ],
            'message' => $resultado['valido'] ? 'Integridad referencial válida' : 'Se encontraron problemas de integridad'
        ]);
    }

    /**
     * REQ-VN-005: Generar asistente de validaciones
     */
    public function generarAsistente(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tipo_entidad' => 'required|string|in:documento,expediente',
            'entidad_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Datos de entrada inválidos'
            ], 422);
        }

        $tipoEntidad = $request->input('tipo_entidad');
        $entidadId = $request->input('entidad_id');

        // Obtener la entidad según el tipo
        $entidad = match($tipoEntidad) {
            'documento' => Documento::with(['expediente.serie', 'tipologia'])->findOrFail($entidadId),
            'expediente' => Expediente::with(['serie', 'documentos.tipologia'])->findOrFail($entidadId),
            default => null
        };

        if (!$entidad) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de entidad no soportado'
            ], 400);
        }

        $asistente = $this->businessRules->generarAsistenteValidacion($entidad);

        return response()->json([
            'success' => true,
            'data' => [
                'tipo_entidad' => $tipoEntidad,
                'entidad_id' => $entidadId,
                'puntuacion_calidad' => $asistente['puntuacion_calidad'],
                'nivel_calidad' => $this->getNivelCalidad($asistente['puntuacion_calidad']),
                'recomendaciones' => $asistente['recomendaciones'],
                'errores_criticos' => $asistente['errores_criticos'],
                'mejoras_sugeridas' => $asistente['mejoras_sugeridas'],
                'siguiente_accion' => $asistente['siguiente_accion'],
                'resumen' => $this->generarResumenAsistente($asistente)
            ],
            'message' => 'Asistente de validación generado exitosamente'
        ]);
    }

    /**
     * Validación completa de entidad (combina todas las validaciones)
     */
    public function validacionCompleta(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tipo_entidad' => 'required|string|in:documento,expediente',
            'entidad_id' => 'required|integer',
            'incluir_asistente' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Datos de entrada inválidos'
            ], 422);
        }

        $tipoEntidad = $request->input('tipo_entidad');
        $entidadId = $request->input('entidad_id');
        $incluirAsistente = $request->input('incluir_asistente', true);

        // Obtener la entidad con relaciones
        $entidad = match($tipoEntidad) {
            'documento' => Documento::with(['expediente.serie', 'tipologia'])->findOrFail($entidadId),
            'expediente' => Expediente::with(['serie', 'documentos'])->findOrFail($entidadId),
            default => null
        };

        if (!$entidad) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de entidad no soportado'
            ], 400);
        }

        $resultados = [];

        // 1. Validar metadatos obligatorios
        $resultados['metadatos'] = $this->businessRules->validarMetadatosObligatorios($entidad, $tipoEntidad);

        // 2. Validar integridad referencial
        $resultados['integridad'] = $this->businessRules->validarIntegridadReferencial($entidad, 'update');

        // 3. Validaciones específicas según tipo
        if ($tipoEntidad === 'expediente' && $entidad instanceof Expediente) {
            $resultados['reglas_negocio'] = $this->businessRules->validarReglasExpediente($entidad);
        }

        if ($tipoEntidad === 'documento' && $entidad instanceof Documento && $entidad->expediente) {
            // Validar estructura TRD/CCD para el documento
            $datosEstructura = [
                'serie_id' => $entidad->expediente->serie_id,
                'subserie_id' => $entidad->expediente->subserie_id,
                'tipologia_id' => $entidad->tipologia_id
            ];
            $resultados['estructura_trd'] = $this->businessRules->validarEstructuraTRD($datosEstructura);
        }

        // 4. Generar asistente si se solicita
        if ($incluirAsistente) {
            $resultados['asistente'] = $this->businessRules->generarAsistenteValidacion($entidad);
        }

        // Calcular resultado general
        $todosValidos = collect($resultados)->every(function ($resultado) {
            return isset($resultado['valido']) ? $resultado['valido'] : true;
        });

        $erroresGlobales = collect($resultados)->flatMap(function ($resultado) {
            return $resultado['errores'] ?? [];
        })->toArray();

        $advertenciasGlobales = collect($resultados)->flatMap(function ($resultado) {
            return $resultado['advertencias'] ?? [];
        })->toArray();

        return response()->json([
            'success' => $todosValidos,
            'data' => [
                'valido_general' => $todosValidos,
                'tipo_entidad' => $tipoEntidad,
                'entidad_id' => $entidadId,
                'validaciones' => $resultados,
                'resumen' => [
                    'total_errores' => count($erroresGlobales),
                    'total_advertencias' => count($advertenciasGlobales),
                    'errores' => $erroresGlobales,
                    'advertencias' => $advertenciasGlobales
                ]
            ],
            'message' => $todosValidos ? 'Todas las validaciones pasaron correctamente' : 'Se encontraron problemas en las validaciones'
        ]);
    }

    /**
     * Obtener opciones de validación disponibles
     */
    public function getValidationOptions()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'tipos_entidad' => [
                    'documento' => 'Documento',
                    'expediente' => 'Expediente',
                    'serie' => 'Serie documental'
                ],
                'operaciones' => [
                    'create' => 'Crear',
                    'update' => 'Actualizar',
                    'delete' => 'Eliminar'
                ],
                'estados_expediente' => [
                    'abierto' => 'Abierto',
                    'activo' => 'Activo',
                    'suspendido' => 'Suspendido',
                    'cerrado' => 'Cerrado',
                    'transferido' => 'Transferido',
                    'eliminado' => 'Eliminado'
                ],
                'niveles_calidad' => [
                    'excellent' => ['min' => 90, 'label' => 'Excelente', 'color' => 'green'],
                    'good' => ['min' => 70, 'label' => 'Bueno', 'color' => 'blue'],
                    'fair' => ['min' => 50, 'label' => 'Regular', 'color' => 'yellow'],
                    'poor' => ['min' => 0, 'label' => 'Deficiente', 'color' => 'red']
                ]
            ],
            'message' => 'Opciones de validación obtenidas exitosamente'
        ]);
    }

    /**
     * Obtener nivel de calidad basado en puntuación
     */
    private function getNivelCalidad(int $puntuacion): array
    {
        if ($puntuacion >= 90) {
            return ['nivel' => 'excellent', 'label' => 'Excelente', 'color' => 'green'];
        } elseif ($puntuacion >= 70) {
            return ['nivel' => 'good', 'label' => 'Bueno', 'color' => 'blue'];
        } elseif ($puntuacion >= 50) {
            return ['nivel' => 'fair', 'label' => 'Regular', 'color' => 'yellow'];
        } else {
            return ['nivel' => 'poor', 'label' => 'Deficiente', 'color' => 'red'];
        }
    }

    /**
     * Generar resumen del asistente
     */
    private function generarResumenAsistente(array $asistente): string
    {
        $puntuacion = $asistente['puntuacion_calidad'];
        $errores = count($asistente['errores_criticos']);
        $recomendaciones = count($asistente['recomendaciones']);

        if ($errores > 0) {
            return "Se encontraron {$errores} errores críticos que requieren atención inmediata.";
        } elseif ($puntuacion >= 90) {
            return "Excelente calidad. Solo hay {$recomendaciones} sugerencias menores.";
        } elseif ($puntuacion >= 70) {
            return "Buena calidad con {$recomendaciones} recomendaciones para mejorar.";
        } else {
            return "Calidad deficiente. Se necesitan mejoras significativas ({$recomendaciones} recomendaciones).";
        }
    }
}
