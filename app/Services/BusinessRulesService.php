<?php

namespace App\Services;

use App\Models\Documento;
use App\Models\Expediente;
use App\Models\Serie;
use App\Models\Subserie;
use App\Models\TipologiaDocumental;
use App\Models\TablaRetencionDocumental;
use App\Models\CuadroClasificacionDocumental;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Exception;

/**
 * Servicio de Reglas de Negocio y Validaciones para SGDEA
 * 
 * Implementa requerimientos:
 * REQ-VN-001: Validaciones TRD/CCD
 * REQ-VN-002: Reglas de negocio para expedientes
 * REQ-VN-003: Validaciones de metadatos obligatorios
 * REQ-VN-004: Control de integridad referencial
 * REQ-VN-005: Asistente de validaciones
 */
class BusinessRulesService
{
    /**
     * Estados válidos para transiciones de expedientes
     */
    const TRANSICIONES_EXPEDIENTE = [
        'abierto' => ['activo', 'suspendido', 'cerrado'],
        'activo' => ['suspendido', 'cerrado'],
        'suspendido' => ['activo', 'cerrado'],
        'cerrado' => ['transferido', 'eliminado'], // Solo con permisos especiales
        'transferido' => ['eliminado'], // Solo con permisos administrativos
        'eliminado' => [] // Estado final
    ];

    /**
     * Estados válidos para documentos según expediente
     */
    const ESTADOS_DOCUMENTO_POR_EXPEDIENTE = [
        'abierto' => ['borrador', 'activo'],
        'activo' => ['borrador', 'activo', 'archivado'],
        'suspendido' => ['archivado'],
        'cerrado' => ['archivado'],
        'transferido' => ['archivado'],
        'eliminado' => []
    ];

    /**
     * REQ-VN-001: Validar estructura TRD/CCD
     */
    public function validarEstructuraTRD(array $data): array
    {
        $errors = [];
        $warnings = [];

        try {
            // 1. Validar Serie
            if (!empty($data['serie_id'])) {
                $serie = Serie::find($data['serie_id']);
                if (!$serie) {
                    $errors[] = 'La serie especificada no existe';
                } elseif (!$serie->activa) {
                    $errors[] = 'La serie especificada está inactiva';
                } else {
                    // Validar tiempos de retención
                    $validacionRetencion = $this->validarTiemposRetencion($serie);
                    if (!$validacionRetencion['valido']) {
                        $errors = array_merge($errors, $validacionRetencion['errores']);
                    }
                    if (!empty($validacionRetencion['advertencias'])) {
                        $warnings = array_merge($warnings, $validacionRetencion['advertencias']);
                    }
                }
            }

            // 2. Validar Subserie (si existe)
            if (!empty($data['subserie_id'])) {
                $subserie = Subserie::find($data['subserie_id']);
                if (!$subserie) {
                    $errors[] = 'La subserie especificada no existe';
                } elseif ($subserie->serie_id != $data['serie_id']) {
                    $errors[] = 'La subserie no pertenece a la serie especificada';
                } elseif (!$subserie->activa) {
                    $errors[] = 'La subserie especificada está inactiva';
                }
            }

            // 3. Validar Tipología Documental
            if (!empty($data['tipologia_id'])) {
                $tipologia = TipologiaDocumental::find($data['tipologia_id']);
                if (!$tipologia) {
                    $errors[] = 'La tipología documental no existe';
                } elseif (!$tipologia->activo) {
                    $errors[] = 'La tipología documental está inactiva';
                } else {
                    // Validar compatibilidad con serie
                    if (!empty($data['serie_id'])) {
                        $compatible = $this->validarCompatibilidadTipologiaSerie(
                            $tipologia, 
                            $data['serie_id']
                        );
                        if (!$compatible['valido']) {
                            $warnings[] = $compatible['mensaje'];
                        }
                    }
                }
            }

            // 4. Validar CCD (Cuadro de Clasificación)
            if (!empty($data['clasificacion_ccd'])) {
                $validacionCCD = $this->validarClasificacionCCD($data['clasificacion_ccd']);
                if (!$validacionCCD['valido']) {
                    $errors = array_merge($errors, $validacionCCD['errores']);
                }
            }

        } catch (Exception $e) {
            Log::error('Error en validación TRD/CCD', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            $errors[] = 'Error interno en validación de estructura documental';
        }

        return [
            'valido' => empty($errors),
            'errores' => $errors,
            'advertencias' => $warnings
        ];
    }

    /**
     * REQ-VN-002: Validar reglas de negocio para expedientes
     */
    public function validarReglasExpediente(Expediente $expediente, array $cambios = []): array
    {
        $errors = [];
        $warnings = [];

        try {
            // 1. Validar transición de estado
            if (!empty($cambios['estado'])) {
                $transicionValida = $this->validarTransicionEstado(
                    $expediente->estado, 
                    $cambios['estado']
                );
                if (!$transicionValida['valido']) {
                    $errors[] = $transicionValida['mensaje'];
                }
            }

            // 2. Validar capacidad máxima de documentos
            $limiteDocs = $this->calcularLimiteDocumentos($expediente);
            $cantidadActual = $expediente->documentos()->count();
            if ($cantidadActual >= $limiteDocs['maximo']) {
                $warnings[] = "El expediente está cerca del límite máximo de documentos ({$limiteDocs['maximo']})";
            }

            // 3. Validar fechas de vigencia
            if (!empty($cambios['fecha_cierre'])) {
                $validacionFechas = $this->validarFechasExpediente($expediente, $cambios);
                if (!$validacionFechas['valido']) {
                    $errors = array_merge($errors, $validacionFechas['errores']);
                }
            }

            // 4. Validar documentos obligatorios según tipología
            $docsObligatorios = $this->validarDocumentosObligatorios($expediente);
            if (!$docsObligatorios['completo']) {
                $warnings[] = "Faltan documentos obligatorios: " . implode(', ', $docsObligatorios['faltantes']);
            }

            // 5. Validar permisos de usuario
            $permisosValidos = $this->validarPermisosExpediente($expediente, $cambios);
            if (!$permisosValidos['valido']) {
                $errors[] = $permisosValidos['mensaje'];
            }

        } catch (Exception $e) {
            Log::error('Error en validación de reglas de expediente', [
                'expediente_id' => $expediente->id,
                'error' => $e->getMessage()
            ]);
            $errors[] = 'Error interno en validación de expediente';
        }

        return [
            'valido' => empty($errors),
            'errores' => $errors,
            'advertencias' => $warnings
        ];
    }

    /**
     * REQ-VN-003: Validar metadatos obligatorios
     */
    public function validarMetadatosObligatorios($entidad, string $tipoEntidad): array
    {
        $errors = [];
        $warnings = [];

        $metadatosRequeridos = $this->getMetadatosRequeridos($tipoEntidad);

        foreach ($metadatosRequeridos as $campo => $config) {
            $valor = $entidad->$campo ?? null;

            // Validar campos obligatorios
            if ($config['obligatorio'] && empty($valor)) {
                $errors[] = "El campo '{$config['etiqueta']}' es obligatorio";
                continue;
            }

            // Validar formato/tipo
            if (!empty($valor) && !$this->validarFormatoCampo($valor, $config)) {
                $errors[] = "El campo '{$config['etiqueta']}' no tiene el formato correcto";
            }

            // Validar longitud
            if (!empty($valor) && isset($config['longitud_max'])) {
                if (strlen($valor) > $config['longitud_max']) {
                    $errors[] = "El campo '{$config['etiqueta']}' excede la longitud máxima ({$config['longitud_max']} caracteres)";
                }
            }

            // Advertencias para campos recomendados
            if ($config['recomendado'] && empty($valor)) {
                $warnings[] = "Se recomienda completar el campo '{$config['etiqueta']}'";
            }
        }

        return [
            'valido' => empty($errors),
            'errores' => $errors,
            'advertencias' => $warnings
        ];
    }

    /**
     * REQ-VN-004: Validar integridad referencial
     */
    public function validarIntegridadReferencial($entidad, string $operacion = 'create'): array
    {
        $errors = [];
        $warnings = [];

        try {
            switch (get_class($entidad)) {
                case Documento::class:
                    $validacion = $this->validarIntegridadDocumento($entidad, $operacion);
                    break;
                    
                case Expediente::class:
                    $validacion = $this->validarIntegridadExpediente($entidad, $operacion);
                    break;
                    
                case Serie::class:
                    $validacion = $this->validarIntegridadSerie($entidad, $operacion);
                    break;
                    
                default:
                    $validacion = ['valido' => true, 'errores' => [], 'advertencias' => []];
            }

            return $validacion;

        } catch (Exception $e) {
            Log::error('Error en validación de integridad referencial', [
                'entidad' => get_class($entidad),
                'operacion' => $operacion,
                'error' => $e->getMessage()
            ]);

            return [
                'valido' => false,
                'errores' => ['Error interno en validación de integridad'],
                'advertencias' => []
            ];
        }
    }

    /**
     * REQ-VN-005: Generar asistente de validaciones
     */
    public function generarAsistenteValidacion($entidad): array
    {
        $recomendaciones = [];
        $erroresCriticos = [];
        $mejoras = [];

        try {
            // Análisis según tipo de entidad
            switch (get_class($entidad)) {
                case Documento::class:
                    $analisis = $this->analizarDocumento($entidad);
                    break;
                    
                case Expediente::class:
                    $analisis = $this->analizarExpediente($entidad);
                    break;
                    
                default:
                    $analisis = ['recomendaciones' => [], 'errores' => [], 'mejoras' => []];
            }

            return [
                'puntuacion_calidad' => $this->calcularPuntuacionCalidad($entidad),
                'recomendaciones' => $analisis['recomendaciones'],
                'errores_criticos' => $analisis['errores'],
                'mejoras_sugeridas' => $analisis['mejoras'],
                'siguiente_accion' => $this->sugerirSiguienteAccion($entidad)
            ];

        } catch (Exception $e) {
            Log::error('Error generando asistente de validación', [
                'entidad' => get_class($entidad),
                'error' => $e->getMessage()
            ]);

            return [
                'puntuacion_calidad' => 0,
                'recomendaciones' => [],
                'errores_criticos' => ['Error interno en análisis'],
                'mejoras_sugeridas' => [],
                'siguiente_accion' => null
            ];
        }
    }

    // =================== MÉTODOS PRIVADOS DE VALIDACIÓN ===================

    /**
     * Validar tiempos de retención de una serie
     */
    private function validarTiemposRetencion(Serie $serie): array
    {
        $errors = [];
        $warnings = [];

        if ($serie->tiempo_archivo_gestion <= 0) {
            $errors[] = 'El tiempo de archivo de gestión debe ser mayor a 0';
        }

        if ($serie->tiempo_archivo_central <= 0) {
            $warnings[] = 'El tiempo de archivo central no está definido';
        }

        if ($serie->disposicion_final === 'conservacion_total' && $serie->tiempo_archivo_central < 5) {
            $warnings[] = 'Para conservación total se recomienda mínimo 5 años en archivo central';
        }

        return [
            'valido' => empty($errors),
            'errores' => $errors,
            'advertencias' => $warnings
        ];
    }

    /**
     * Validar compatibilidad entre tipología y serie
     */
    private function validarCompatibilidadTipologiaSerie(TipologiaDocumental $tipologia, int $serieId): array
    {
        // Verificar si existe una configuración específica
        $compatible = DB::table('tipologias_series')
            ->where('tipologia_id', $tipologia->id)
            ->where('serie_id', $serieId)
            ->exists();

        if (!$compatible) {
            // Verificar compatibilidad por categoría
            $serie = Serie::find($serieId);
            if ($serie && $tipologia->categoria !== $serie->categoria_principal) {
                return [
                    'valido' => false,
                    'mensaje' => "La tipología '{$tipologia->nombre}' no es típica para esta serie"
                ];
            }
        }

        return ['valido' => true, 'mensaje' => ''];
    }

    /**
     * Validar clasificación CCD
     */
    private function validarClasificacionCCD(string $clasificacion): array
    {
        $errors = [];

        // Validar formato (ej: 100.01.001)
        if (!preg_match('/^\d{3}\.\d{2}\.\d{3}$/', $clasificacion)) {
            $errors[] = 'El código CCD debe tener el formato ###.##.###';
        } else {
            // Verificar que existe en el CCD
            $existeCCD = CuadroClasificacionDocumental::where('codigo', $clasificacion)
                ->where('activo', true)
                ->exists();
            
            if (!$existeCCD) {
                $errors[] = 'El código CCD especificado no existe o está inactivo';
            }
        }

        return [
            'valido' => empty($errors),
            'errores' => $errors
        ];
    }

    /**
     * Validar transición de estado de expediente
     */
    private function validarTransicionEstado(string $estadoActual, string $nuevoEstado): array
    {
        $transicionesPermitidas = self::TRANSICIONES_EXPEDIENTE[$estadoActual] ?? [];

        if (!in_array($nuevoEstado, $transicionesPermitidas)) {
            return [
                'valido' => false,
                'mensaje' => "No se puede cambiar de estado '{$estadoActual}' a '{$nuevoEstado}'"
            ];
        }

        // Validaciones adicionales según el estado
        if ($nuevoEstado === 'cerrado') {
            return $this->validarCierreExpediente();
        }

        return ['valido' => true, 'mensaje' => ''];
    }

    /**
     * Calcular límite de documentos por expediente
     */
    private function calcularLimiteDocumentos(Expediente $expediente): array
    {
        $serie = $expediente->serie;
        $baseLimit = 1000; // Límite base

        if ($serie) {
            // Ajustar según el tipo de serie
            $multiplier = match($serie->categoria_principal) {
                'administrativa' => 1.5,
                'legal' => 2.0,
                'tecnica' => 1.2,
                default => 1.0
            };
            
            $baseLimit = (int)($baseLimit * $multiplier);
        }

        return [
            'maximo' => $baseLimit,
            'recomendado' => (int)($baseLimit * 0.8),
            'actual' => $expediente->documentos()->count()
        ];
    }

    /**
     * Validar fechas de expediente
     */
    private function validarFechasExpediente(Expediente $expediente, array $cambios): array
    {
        $errors = [];

        $fechaCierre = Carbon::parse($cambios['fecha_cierre']);
        $fechaApertura = Carbon::parse($expediente->fecha_apertura);

        if ($fechaCierre->lt($fechaApertura)) {
            $errors[] = 'La fecha de cierre no puede ser anterior a la fecha de apertura';
        }

        if ($fechaCierre->gt(Carbon::now())) {
            $errors[] = 'La fecha de cierre no puede ser futura';
        }

        return [
            'valido' => empty($errors),
            'errores' => $errors
        ];
    }

    /**
     * Validar documentos obligatorios según tipología de expediente
     */
    private function validarDocumentosObligatorios(Expediente $expediente): array
    {
        $tipologiasObligatorias = $this->getTipologiasObligatorias($expediente);
        $tipologiasPresentes = $expediente->documentos()
            ->whereNotNull('tipologia_id')
            ->pluck('tipologia_id')
            ->unique()
            ->toArray();

        $faltantes = array_diff($tipologiasObligatorias, $tipologiasPresentes);

        return [
            'completo' => empty($faltantes),
            'faltantes' => TipologiaDocumental::whereIn('id', $faltantes)->pluck('nombre')->toArray(),
            'obligatorias' => count($tipologiasObligatorias),
            'presentes' => count($tipologiasPresentes)
        ];
    }

    /**
     * Obtener tipologías obligatorias para un expediente
     */
    private function getTipologiasObligatorias(Expediente $expediente): array
    {
        // Esta lógica se puede expandir según las reglas específicas
        $obligatorias = [];

        if ($expediente->serie) {
            $obligatorias = DB::table('series_tipologias_obligatorias')
                ->where('serie_id', $expediente->serie_id)
                ->pluck('tipologia_id')
                ->toArray();
        }

        return $obligatorias;
    }

    /**
     * Validar permisos de usuario para operaciones en expediente
     */
    private function validarPermisosExpediente(Expediente $expediente, array $cambios): array
    {
        $usuario = auth()->user();
        
        // Cambios que requieren permisos especiales
        $operacionesEspeciales = ['cerrado', 'transferido', 'eliminado'];
        
        if (!empty($cambios['estado']) && in_array($cambios['estado'], $operacionesEspeciales)) {
            if (!$usuario->hasPermissionTo('expedientes.estados_especiales')) {
                return [
                    'valido' => false,
                    'mensaje' => 'No tiene permisos para cambiar a este estado'
                ];
            }
        }

        return ['valido' => true, 'mensaje' => ''];
    }

    /**
     * Obtener metadatos requeridos por tipo de entidad
     */
    private function getMetadatosRequeridos(string $tipoEntidad): array
    {
        return match($tipoEntidad) {
            'documento' => [
                'nombre' => ['obligatorio' => true, 'recomendado' => false, 'etiqueta' => 'Nombre del documento', 'longitud_max' => 255],
                'descripcion' => ['obligatorio' => false, 'recomendado' => true, 'etiqueta' => 'Descripción'],
                'tipologia_id' => ['obligatorio' => true, 'recomendado' => false, 'etiqueta' => 'Tipología documental'],
                'palabras_clave' => ['obligatorio' => false, 'recomendado' => true, 'etiqueta' => 'Palabras clave'],
            ],
            'expediente' => [
                'nombre' => ['obligatorio' => true, 'recomendado' => false, 'etiqueta' => 'Nombre del expediente', 'longitud_max' => 255],
                'descripcion' => ['obligatorio' => false, 'recomendado' => true, 'etiqueta' => 'Descripción'],
                'serie_id' => ['obligatorio' => true, 'recomendado' => false, 'etiqueta' => 'Serie documental'],
                'ubicacion_fisica' => ['obligatorio' => false, 'recomendado' => true, 'etiqueta' => 'Ubicación física'],
            ],
            default => []
        };
    }

    /**
     * Validar formato de campo según configuración
     */
    private function validarFormatoCampo($valor, array $config): bool
    {
        if (isset($config['tipo'])) {
            return match($config['tipo']) {
                'email' => filter_var($valor, FILTER_VALIDATE_EMAIL) !== false,
                'fecha' => Carbon::hasFormat($valor, 'Y-m-d'),
                'numero' => is_numeric($valor),
                default => true
            };
        }
        
        return true;
    }

    /**
     * Validar integridad de documento
     */
    private function validarIntegridadDocumento(Documento $documento, string $operacion): array
    {
        $errors = [];

        // Verificar que el expediente existe y está activo
        if ($documento->expediente_id) {
            $expediente = Expediente::find($documento->expediente_id);
            if (!$expediente) {
                $errors[] = 'El expediente asociado no existe';
            } elseif (!in_array($expediente->estado, ['abierto', 'activo'])) {
                $errors[] = 'No se pueden agregar documentos a un expediente cerrado';
            }
        }

        // Verificar duplicados por hash
        if ($documento->hash_sha256 && $operacion === 'create') {
            $duplicado = Documento::where('hash_sha256', $documento->hash_sha256)
                ->where('id', '!=', $documento->id ?? 0)
                ->exists();
            
            if ($duplicado) {
                $errors[] = 'Ya existe un documento con el mismo contenido (hash duplicado)';
            }
        }

        return [
            'valido' => empty($errors),
            'errores' => $errors,
            'advertencias' => []
        ];
    }

    /**
     * Validar integridad de expediente
     */
    private function validarIntegridadExpediente(Expediente $expediente, string $operacion): array
    {
        $errors = [];
        $warnings = [];

        // Verificar que la serie existe y está activa
        if ($expediente->serie_id) {
            $serie = Serie::find($expediente->serie_id);
            if (!$serie) {
                $errors[] = 'La serie documental no existe';
            } elseif (!$serie->activa) {
                $errors[] = 'La serie documental está inactiva';
            }
        }

        // Verificar código único si está definido
        if ($expediente->codigo && $operacion === 'create') {
            $existe = Expediente::where('codigo', $expediente->codigo)
                ->where('id', '!=', $expediente->id ?? 0)
                ->exists();
            
            if ($existe) {
                $errors[] = "Ya existe un expediente con el código '{$expediente->codigo}'";
            }
        }

        return [
            'valido' => empty($errors),
            'errores' => $errors,
            'advertencias' => $warnings
        ];
    }

    /**
     * Validar integridad de serie
     */
    private function validarIntegridadSerie(Serie $serie, string $operacion): array
    {
        $errors = [];
        $warnings = [];

        // Si se va a desactivar, verificar que no tenga expedientes activos
        if ($operacion === 'update' && !$serie->activa) {
            $expedientesActivos = Expediente::where('serie_id', $serie->id)
                ->whereIn('estado', ['abierto', 'activo'])
                ->count();
            
            if ($expedientesActivos > 0) {
                $errors[] = "No se puede desactivar la serie: tiene {$expedientesActivos} expedientes activos";
            }
        }

        return [
            'valido' => empty($errors),
            'errores' => $errors,
            'advertencias' => $warnings
        ];
    }

    /**
     * Validar cierre de expediente
     */
    private function validarCierreExpediente(): array
    {
        // Aquí se pueden agregar validaciones específicas para cierre
        // Por ejemplo: verificar que todos los documentos estén aprobados
        
        return ['valido' => true, 'mensaje' => ''];
    }

    /**
     * Analizar documento para asistente
     */
    private function analizarDocumento(Documento $documento): array
    {
        $recomendaciones = [];
        $errores = [];
        $mejoras = [];

        // Análisis de completitud de metadatos
        if (empty($documento->descripcion)) {
            $recomendaciones[] = 'Agregar una descripción detallada del documento';
        }

        if (empty($documento->palabras_clave)) {
            $recomendaciones[] = 'Agregar palabras clave para mejorar la búsqueda';
        }

        // Análisis de calidad del archivo
        if ($documento->tamaño > 50 * 1024 * 1024) { // 50MB
            $mejoras[] = 'Considerar comprimir el archivo para reducir el tamaño';
        }

        if (empty($documento->contenido_ocr) && in_array($documento->formato, ['pdf', 'jpg', 'png'])) {
            $mejoras[] = 'Ejecutar OCR para hacer el contenido buscable';
        }

        return [
            'recomendaciones' => $recomendaciones,
            'errores' => $errores,
            'mejoras' => $mejoras
        ];
    }

    /**
     * Analizar expediente para asistente
     */
    private function analizarExpediente(Expediente $expediente): array
    {
        $recomendaciones = [];
        $errores = [];
        $mejoras = [];

        // Análisis de documentos obligatorios
        $docsObligatorios = $this->validarDocumentosObligatorios($expediente);
        if (!$docsObligatorios['completo']) {
            $errores[] = 'Faltan documentos obligatorios según la tipología del expediente';
        }

        // Análisis de organización
        $totalDocs = $expediente->documentos()->count();
        if ($totalDocs > 50) {
            $mejoras[] = 'Considerar crear subseries para mejor organización';
        }

        return [
            'recomendaciones' => $recomendaciones,
            'errores' => $errores,
            'mejoras' => $mejoras
        ];
    }

    /**
     * Calcular puntuación de calidad
     */
    private function calcularPuntuacionCalidad($entidad): int
    {
        $puntuacion = 0;
        
        if ($entidad instanceof Documento) {
            $puntuacion += !empty($entidad->nombre) ? 20 : 0;
            $puntuacion += !empty($entidad->descripcion) ? 15 : 0;
            $puntuacion += !empty($entidad->tipologia_id) ? 25 : 0;
            $puntuacion += !empty($entidad->palabras_clave) ? 10 : 0;
            $puntuacion += !empty($entidad->contenido_ocr) ? 15 : 0;
            $puntuacion += !empty($entidad->hash_sha256) ? 15 : 0;
        }

        return min($puntuacion, 100);
    }

    /**
     * Sugerir siguiente acción
     */
    private function sugerirSiguienteAccion($entidad): ?string
    {
        if ($entidad instanceof Documento) {
            if (empty($entidad->contenido_ocr) && in_array($entidad->formato, ['pdf', 'jpg', 'png'])) {
                return 'Ejecutar OCR para hacer el documento buscable';
            }
            
            if (empty($entidad->tipologia_id)) {
                return 'Asignar tipología documental';
            }
        }

        return null;
    }
}
