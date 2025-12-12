<?php

namespace App\Services;

use App\Models\Documento;
use App\Models\User;
use App\Models\FirmaDigital;
use App\Models\CertificadoDigital;
use App\Models\SolicitudFirma;
use App\Models\FirmanteSolicitud;
use App\Models\Notificacion;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FirmaDigitalService
{
    /**
     * Firmar un documento digitalmente
     */
    public function firmarDocumento(Documento $documento, User $usuario, string $motivo = null): FirmaDigital
    {
        // Verificar que el documento existe
        if (!$documento->existe()) {
            throw new \Exception('El documento no existe en el almacenamiento');
        }

        // Generar hash del archivo para integridad
        $contenidoArchivo = Storage::disk('documentos')->get($documento->ruta_archivo);
        $hashArchivo = hash('sha256', $contenidoArchivo);

        // Crear firma digital
        $firma = FirmaDigital::create([
            'documento_id' => $documento->id,
            'usuario_id' => $usuario->id,
            'hash_documento' => $hashArchivo,
            'hash_firma' => $this->generarHashFirma($documento, $usuario),
            'certificado_info' => $this->obtenerInfoCertificado($usuario),
            'motivo_firma' => $motivo,
            'fecha_firma' => now(),
            'algoritmo_hash' => 'SHA-256',
            'tipo_firma' => 'electronica',
            'valida' => true,
            'metadata' => [
                'ip_firma' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp_servidor' => now()->timestamp,
                'version_aplicacion' => config('app.version', '1.0'),
            ]
        ]);

        // Actualizar estado del documento
        $documento->update([
            'firmado_digitalmente' => true,
            'fecha_ultima_firma' => now(),
            'estado_firma' => 'firmado'
        ]);

        // Registrar en auditoría
        $this->registrarAuditoria($documento, $usuario, 'documento_firmado');

        return $firma;
    }

    /**
     * Verificar la validez de una firma digital
     */
    public function verificarFirma(FirmaDigital $firma): array
    {
        $resultado = [
            'valida' => false,
            'errores' => [],
            'detalles' => []
        ];

        try {
            $documento = $firma->documento;
            
            // Verificar que el documento aún existe
            if (!$documento->existe()) {
                $resultado['errores'][] = 'El documento original no existe';
                return $resultado;
            }

            // Verificar integridad del archivo
            $contenidoActual = Storage::disk('documentos')->get($documento->ruta_archivo);
            $hashActual = hash('sha256', $contenidoActual);

            if ($hashActual !== $firma->hash_documento) {
                $resultado['errores'][] = 'El documento ha sido modificado después de la firma';
                return $resultado;
            }

            // Verificar hash de la firma
            $hashFirmaCalculado = $this->generarHashFirma($documento, $firma->usuario);
            if ($hashFirmaCalculado !== $firma->hash_firma) {
                $resultado['errores'][] = 'La firma digital ha sido alterada';
                return $resultado;
            }

            // Verificar que el usuario que firmó aún existe y está activo
            if (!$firma->usuario || !$firma->usuario->estado_cuenta === 'activo') {
                $resultado['errores'][] = 'El usuario que firmó no está disponible o está inactivo';
                return $resultado;
            }

            // Verificar que la firma no ha caducado (opcional, configurar según necesidades)
            $diasValidez = config('archivey.firma_validez_dias', 365);
            if ($firma->fecha_firma->addDays($diasValidez)->isPast()) {
                $resultado['errores'][] = 'La firma digital ha caducado';
                return $resultado;
            }

            $resultado['valida'] = true;
            $resultado['detalles'] = [
                'firmado_por' => $firma->usuario->name,
                'fecha_firma' => $firma->fecha_firma,
                'algoritmo' => $firma->algoritmo_hash,
                'tipo_firma' => $firma->tipo_firma,
                'motivo' => $firma->motivo_firma,
            ];

        } catch (\Exception $e) {
            $resultado['errores'][] = 'Error al verificar la firma: ' . $e->getMessage();
        }

        return $resultado;
    }

    /**
     * Listar todas las firmas de un documento
     */
    public function obtenerFirmasDocumento(Documento $documento): array
    {
        $firmas = $documento->firmasDigitales()->with('usuario')->orderBy('fecha_firma', 'desc')->get();
        
        return $firmas->map(function ($firma) {
            $verificacion = $this->verificarFirma($firma);
            
            return [
                'id' => $firma->id,
                'usuario' => $firma->usuario->name,
                'fecha_firma' => $firma->fecha_firma,
                'motivo' => $firma->motivo_firma,
                'tipo_firma' => $firma->tipo_firma,
                'valida' => $verificacion['valida'],
                'errores' => $verificacion['errores'],
                'detalles' => $verificacion['detalles'] ?? []
            ];
        })->toArray();
    }

    /**
     * Generar certificado de firma para el documento
     */
    public function generarCertificadoFirma(Documento $documento): string
    {
        $firmas = $this->obtenerFirmasDocumento($documento);
        
        $certificado = [
            'documento' => [
                'id' => $documento->id,
                'nombre' => $documento->nombre,
                'hash_sha256' => hash('sha256', Storage::disk('documentos')->get($documento->ruta_archivo)),
                'tamaño' => $documento->tamaño,
                'fecha_creacion' => $documento->created_at,
            ],
            'firmas' => $firmas,
            'fecha_certificado' => now(),
            'validez' => [
                'todas_validas' => collect($firmas)->every(fn($f) => $f['valida']),
                'total_firmas' => count($firmas)
            ]
        ];

        // Guardar certificado como archivo JSON
        $nombreCertificado = "certificado_firma_{$documento->id}_" . now()->format('Y-m-d_H-i-s') . '.json';
        Storage::disk('certificados')->put($nombreCertificado, json_encode($certificado, JSON_PRETTY_PRINT));

        return $nombreCertificado;
    }

    /**
     * Generar hash único de firma
     */
    private function generarHashFirma(Documento $documento, User $usuario): string
    {
        $datos = [
            'documento_id' => $documento->id,
            'usuario_id' => $usuario->id,
            'documento_hash' => hash('sha256', Storage::disk('documentos')->get($documento->ruta_archivo)),
            'usuario_email' => $usuario->email,
            'timestamp' => now()->timestamp,
            'random' => bin2hex(random_bytes(16))
        ];

        return hash('sha256', json_encode($datos));
    }

    /**
     * Obtener información del certificado del usuario
     */
    private function obtenerInfoCertificado(User $usuario): array
    {
        return [
            'nombre_completo' => $usuario->name,
            'email' => $usuario->email,
            'identificacion' => $usuario->identificacion ?? 'No disponible',
            'cargo' => $usuario->cargo ?? 'No especificado',
            'departamento' => $usuario->departamento ?? 'No especificado',
            'fecha_registro' => $usuario->created_at,
        ];
    }

    /**
     * Registrar auditoría de firma
     */
    private function registrarAuditoria(Documento $documento, User $usuario, string $accion): void
    {
        \App\Models\PistaAuditoria::create([
            'user_id' => $usuario->id,
            'tabla_afectada' => 'documentos',
            'registro_id' => $documento->id,
            'accion' => $accion,
            'descripcion' => "Documento '{$documento->nombre}' firmado digitalmente por {$usuario->name}",
            'datos_anteriores' => null,
            'datos_nuevos' => [
                'firmado_digitalmente' => true,
                'fecha_firma' => now()
            ],
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Firmar documento con certificado PKI
     */
    public function firmarConCertificado(
        Documento $documento, 
        User $usuario, 
        CertificadoDigital $certificado,
        string $motivo = null,
        ?SolicitudFirma $solicitud = null
    ): FirmaDigital {
        // Verificar que el certificado es válido y del usuario
        if ($certificado->usuario_id !== $usuario->id) {
            throw new \Exception('El certificado no pertenece al usuario');
        }

        if (!$certificado->vigente) {
            throw new \Exception('El certificado digital no está vigente');
        }

        if (!$certificado->uso_permitido || !in_array(CertificadoDigital::USO_FIRMA_DIGITAL, $certificado->uso_permitido)) {
            throw new \Exception('El certificado no permite firma digital');
        }

        // Verificar que el documento existe
        if (!$documento->existe()) {
            throw new \Exception('El documento no existe en el almacenamiento');
        }

        // Generar hash del archivo para integridad
        $contenidoArchivo = Storage::disk('documentos')->get($documento->ruta_archivo);
        $hashArchivo = hash('sha256', $contenidoArchivo);

        // Generar sello de tiempo si está disponible
        $selloTiempo = $this->generarSelloTiempo($contenidoArchivo);

        // Crear firma digital avanzada
        $firma = FirmaDigital::create([
            'documento_id' => $documento->id,
            'usuario_id' => $usuario->id,
            'certificado_id' => $certificado->id,
            'solicitud_firma_id' => $solicitud?->id,
            'hash_documento' => $hashArchivo,
            'hash_firma' => $this->generarHashFirmaAvanzada($documento, $usuario, $certificado),
            'certificado_info' => $this->obtenerInfoCertificadoCompleta($certificado),
            'motivo_firma' => $motivo,
            'fecha_firma' => now(),
            'algoritmo_hash' => FirmaDigital::HASH_SHA256,
            'tipo_firma' => $this->determinarTipoFirma($certificado),
            'valida' => true,
            'sello_tiempo' => $selloTiempo,
            'cadena_certificacion' => $this->obtenerCadenaCertificacion($certificado),
            'metadata' => [
                'ip_firma' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp_servidor' => now()->timestamp,
                'version_aplicacion' => config('app.version', '1.0'),
                'metodo_firma' => 'pki_avanzada'
            ]
        ]);

        // Actualizar estado del documento
        $documento->update([
            'firmado_digitalmente' => true,
            'fecha_ultima_firma' => now(),
            'estado_firma' => 'firmado'
        ]);

        // Si es parte de una solicitud, actualizar el firmante
        if ($solicitud) {
            $firmante = $solicitud->firmantes()
                                ->where('usuario_id', $usuario->id)
                                ->where('estado', FirmanteSolicitud::ESTADO_PENDIENTE)
                                ->first();
            
            if ($firmante) {
                $firmante->firmar($motivo, [
                    'firma_id' => $firma->id,
                    'certificado_serie' => $certificado->numero_serie
                ]);
            }
        }

        // Registrar en auditoría
        $this->registrarAuditoria($documento, $usuario, 'documento_firmado_pki');

        return $firma;
    }

    /**
     * Crear solicitud de firma múltiple
     */
    public function crearSolicitudFirma(
        Documento $documento,
        User $solicitante,
        array $firmantes,
        array $configuracion = []
    ): SolicitudFirma {
        $solicitud = SolicitudFirma::create([
            'documento_id' => $documento->id,
            'solicitante_id' => $solicitante->id,
            'titulo' => $configuracion['titulo'] ?? "Solicitud de firma - {$documento->nombre}",
            'descripcion' => $configuracion['descripcion'] ?? '',
            'tipo_flujo' => $configuracion['tipo_flujo'] ?? SolicitudFirma::FLUJO_SECUENCIAL,
            'prioridad' => $configuracion['prioridad'] ?? SolicitudFirma::PRIORIDAD_NORMAL,
            'fecha_limite' => isset($configuracion['fecha_limite']) 
                ? Carbon::parse($configuracion['fecha_limite']) 
                : now()->addDays(7),
            'estado' => SolicitudFirma::ESTADO_PENDIENTE,
            'configuracion_flujo' => $configuracion,
            'metadata_solicitud' => [
                'creada_desde' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]
        ]);

        // Crear firmantes
        $firmantesCreados = [];
        foreach ($firmantes as $index => $firmante) {
            $firmanteCreado = FirmanteSolicitud::create([
                'solicitud_firma_id' => $solicitud->id,
                'usuario_id' => $firmante['usuario_id'],
                'orden' => $firmante['orden'] ?? $index + 1,
                'es_obligatorio' => $firmante['es_obligatorio'] ?? true,
                'rol_firmante' => $firmante['rol'] ?? FirmanteSolicitud::ROL_APROBADOR,
                'estado' => FirmanteSolicitud::ESTADO_PENDIENTE
            ]);
            $firmantesCreados[] = $firmanteCreado;
        }
        
        // Notificar a los firmantes según el tipo de flujo
        $this->notificarFirmantes($solicitud, $firmantesCreados, $documento, $solicitante);
        
        // Notificar al solicitante que su solicitud fue enviada
        $this->notificarSolicitante($solicitud, $firmantesCreados, $documento, $solicitante);

        return $solicitud;
    }
    
    /**
     * Notificar a los firmantes sobre una nueva solicitud de firma
     */
    private function notificarFirmantes(
        SolicitudFirma $solicitud,
        array $firmantesCreados,
        Documento $documento,
        User $solicitante
    ): void {
        $tipoFlujo = $solicitud->tipo_flujo;
        
        // Ordenar firmantes por orden
        usort($firmantesCreados, fn($a, $b) => $a->orden <=> $b->orden);
        
        // Determinar a quién notificar según el tipo de flujo
        $firmantesANotificar = [];
        
        if ($tipoFlujo === SolicitudFirma::FLUJO_SECUENCIAL) {
            // Solo notificar al primer firmante
            $firmantesANotificar = [reset($firmantesCreados)];
        } else {
            // Flujo paralelo o mixto: notificar a todos
            $firmantesANotificar = $firmantesCreados;
        }
        
        foreach ($firmantesANotificar as $firmante) {
            try {
                // Crear notificación para el firmante
                Notificacion::create([
                    'user_id' => $firmante->usuario_id,
                    'tipo' => 'solicitud_firma',
                    'titulo' => 'Nueva solicitud de firma',
                    'mensaje' => "Has recibido una solicitud de firma de {$solicitante->name} para el documento: {$documento->titulo}",
                    'prioridad' => $this->mapearPrioridad($solicitud->prioridad),
                    'accion_url' => "/admin/firmas/solicitudes/{$solicitud->id}",
                    'datos' => [
                        'solicitud_id' => $solicitud->id,
                        'documento_id' => $documento->id,
                        'documento_titulo' => $documento->titulo,
                        'solicitante_id' => $solicitante->id,
                        'solicitante_nombre' => $solicitante->name,
                        'prioridad' => $solicitud->prioridad,
                        'fecha_limite' => $solicitud->fecha_limite?->toISOString(),
                        'rol_firmante' => $firmante->rol_firmante,
                        'icono' => 'FileSignature',
                    ],
                    'estado' => 'pendiente',
                    'es_automatica' => true,
                    'relacionado_id' => $solicitud->id,
                    'relacionado_tipo' => SolicitudFirma::class,
                ]);
                
                // Actualizar estado del firmante a notificado
                $firmante->update([
                    'estado' => FirmanteSolicitud::ESTADO_NOTIFICADO,
                    'notificado_en' => now(),
                ]);
                
                Log::info("Notificación de firma enviada al usuario {$firmante->usuario_id} para solicitud {$solicitud->id}");
                
            } catch (\Exception $e) {
                Log::error("Error al notificar firmante {$firmante->usuario_id}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Mapear prioridad de solicitud a prioridad de notificación
     */
    private function mapearPrioridad(string $prioridad): string
    {
        return match($prioridad) {
            'urgente' => 'critica',
            'alta' => 'alta',
            'normal' => 'media',
            'baja' => 'baja',
            default => 'media',
        };
    }
    
    /**
     * Notificar al solicitante que su solicitud fue enviada
     */
    private function notificarSolicitante(
        SolicitudFirma $solicitud,
        array $firmantesCreados,
        Documento $documento,
        User $solicitante
    ): void {
        try {
            $nombresFirmantes = collect($firmantesCreados)->map(function($f) {
                return User::find($f->usuario_id)?->name ?? 'Usuario desconocido';
            })->join(', ');
            
            Notificacion::create([
                'user_id' => $solicitante->id,
                'tipo' => 'solicitud_firma_enviada',
                'titulo' => 'Solicitud de firma enviada',
                'mensaje' => "Tu solicitud de firma para el documento '{$documento->titulo}' ha sido enviada a: {$nombresFirmantes}",
                'prioridad' => 'baja',
                'accion_url' => "/admin/firmas/solicitudes/{$solicitud->id}",
                'datos' => [
                    'solicitud_id' => $solicitud->id,
                    'documento_id' => $documento->id,
                    'documento_titulo' => $documento->titulo,
                    'firmantes' => $nombresFirmantes,
                    'icono' => 'Send',
                ],
                'estado' => 'pendiente',
                'es_automatica' => true,
                'relacionado_id' => $solicitud->id,
                'relacionado_tipo' => SolicitudFirma::class,
            ]);
            
            Log::info("Notificación de confirmación enviada al solicitante {$solicitante->id} para solicitud {$solicitud->id}");
            
        } catch (\Exception $e) {
            Log::error("Error al notificar solicitante {$solicitante->id}: " . $e->getMessage());
        }
    }

    /**
     * Verificar múltiples firmas de un documento
     */
    public function verificarFirmasMultiples(Documento $documento): array
    {
        $firmas = $documento->firmasDigitales()
                           ->with(['usuario', 'certificado'])
                           ->orderBy('fecha_firma')
                           ->get();

        $resultado = [
            'total_firmas' => $firmas->count(),
            'firmas_validas' => 0,
            'firmas_invalidadas' => 0,
            'nivel_seguridad_global' => 'basico',
            'firmas' => []
        ];

        foreach ($firmas as $firma) {
            $verificacion = $firma->verificarIntegridad();
            
            if ($verificacion['valida']) {
                $resultado['firmas_validas']++;
            } else {
                $resultado['firmas_invalidadas']++;
            }

            $resultado['firmas'][] = [
                'id' => $firma->id,
                'usuario' => $firma->usuario->name,
                'fecha' => $firma->fecha_firma,
                'tipo' => $firma->tipo_firma,
                'nivel_seguridad' => $firma->nivel_seguridad,
                'valida' => $verificacion['valida'],
                'errores' => $verificacion['errores'],
                'certificado' => $firma->certificado ? [
                    'numero_serie' => $firma->certificado->numero_serie,
                    'emisor' => $firma->certificado->info_emisor['CN'] ?? 'Desconocido',
                    'vigente' => $firma->certificado->vigente
                ] : null
            ];
        }

        // Determinar nivel de seguridad global
        $nivelesSeguridad = $firmas->pluck('nivel_seguridad')->toArray();
        if (in_array('muy_alto', $nivelesSeguridad)) {
            $resultado['nivel_seguridad_global'] = 'muy_alto';
        } elseif (in_array('alto', $nivelesSeguridad)) {
            $resultado['nivel_seguridad_global'] = 'alto';
        } elseif (in_array('medio', $nivelesSeguridad)) {
            $resultado['nivel_seguridad_global'] = 'medio';
        }

        return $resultado;
    }

    /**
     * Generar hash avanzado de firma con certificado
     */
    private function generarHashFirmaAvanzada(Documento $documento, User $usuario, CertificadoDigital $certificado): string
    {
        $datos = [
            'documento_id' => $documento->id,
            'usuario_id' => $usuario->id,
            'certificado_id' => $certificado->id,
            'documento_hash' => hash('sha256', Storage::disk('documentos')->get($documento->ruta_archivo)),
            'certificado_huella' => $certificado->huella_digital,
            'timestamp' => now()->timestamp,
            'random' => bin2hex(random_bytes(32))
        ];

        return hash('sha256', json_encode($datos));
    }

    /**
     * Obtener información completa del certificado
     */
    private function obtenerInfoCertificadoCompleta(CertificadoDigital $certificado): array
    {
        return [
            'numero_serie' => $certificado->numero_serie,
            'emisor' => $certificado->emisor,
            'sujeto' => $certificado->sujeto,
            'algoritmo_firma' => $certificado->algoritmo_firma,
            'longitud_clave' => $certificado->longitud_clave,
            'huella_digital' => $certificado->huella_digital,
            'fecha_emision' => $certificado->fecha_emision,
            'fecha_vencimiento' => $certificado->fecha_vencimiento,
            'uso_permitido' => $certificado->uso_permitido,
            'tipo_certificado' => $certificado->tipo_certificado
        ];
    }

    /**
     * Determinar tipo de firma según certificado
     */
    private function determinarTipoFirma(CertificadoDigital $certificado): string
    {
        if ($certificado->tipo_certificado === CertificadoDigital::TIPO_CA) {
            return FirmaDigital::TIPO_CUALIFICADA;
        }

        if ($certificado->longitud_clave >= 2048) {
            return FirmaDigital::TIPO_AVANZADA;
        }

        return FirmaDigital::TIPO_ELECTRONICA;
    }

    /**
     * Generar sello de tiempo
     */
    private function generarSelloTiempo(string $contenido): ?array
    {
        try {
            // Implementar integración con servidor TSA
            // Por ahora, generar sello básico
            return [
                'timestamp' => now()->timestamp,
                'authority' => config('archivey.tsa_server', 'internal'),
                'hash_contenido' => hash('sha256', $contenido),
                'algoritmo' => 'SHA-256'
            ];
        } catch (\Exception $e) {
            Log::warning('No se pudo generar sello de tiempo: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener cadena de certificación
     */
    private function obtenerCadenaCertificacion(CertificadoDigital $certificado): array
    {
        $cadena = [];
        
        // Implementar construcción de cadena PKI
        // Por ahora, incluir solo el certificado actual
        $cadena[] = [
            'certificado' => $certificado->certificado_x509,
            'emisor' => $certificado->emisor,
            'numero_serie' => $certificado->numero_serie,
            'huella_digital' => $certificado->huella_digital
        ];

        return $cadena;
    }

    /**
     * Obtener estadísticas de firmas digitales
     */
    public function obtenerEstadisticas(): array
    {
        try {
            $totalFirmas = FirmaDigital::count();
            $firmasHoy = FirmaDigital::whereDate('fecha_firma', today())->count();
            $firmasEsteMes = FirmaDigital::whereMonth('fecha_firma', now()->month)
                                       ->whereYear('fecha_firma', now()->year)
                                       ->count();
            
            $firmasValidas = FirmaDigital::where('valida', true)->count();
            $firmasConCertificado = FirmaDigital::whereNotNull('certificado_id')->count();
            
            $certificadosActivos = CertificadoDigital::activos()->count();
            $certificadosProximosVencer = CertificadoDigital::proximosAVencer()->count();
            
            $solicitudesPendientes = SolicitudFirma::pendientes()->count();
            $solicitudesCompletadas = SolicitudFirma::completadas()->count();

            return [
                'firmas' => [
                    'total' => $totalFirmas,
                    'hoy' => $firmasHoy,
                    'este_mes' => $firmasEsteMes,
                    'validas' => $firmasValidas,
                    'con_certificado' => $firmasConCertificado,
                    'porcentaje_validez' => $totalFirmas > 0 ? ($firmasValidas / $totalFirmas) * 100 : 0
                ],
                'certificados' => [
                    'activos' => $certificadosActivos,
                    'proximos_vencer' => $certificadosProximosVencer,
                    'vencidos' => CertificadoDigital::vencidos()->count()
                ],
                'solicitudes' => [
                    'pendientes' => $solicitudesPendientes,
                    'completadas' => $solicitudesCompletadas,
                    'vencidas' => SolicitudFirma::vencidas()->count()
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error en obtenerEstadisticas: ' . $e->getMessage());
            
            // Valores por defecto en caso de error
            return [
                'firmas' => [
                    'total' => 0,
                    'hoy' => 0,
                    'este_mes' => 0,
                    'validas' => 0,
                    'con_certificado' => 0,
                    'porcentaje_validez' => 0
                ],
                'certificados' => [
                    'activos' => 0,
                    'proximos_vencer' => 0,
                    'vencidos' => 0
                ],
                'solicitudes' => [
                    'pendientes' => 0,
                    'completadas' => 0,
                    'vencidas' => 0
                ]
            ];
        }
    }
}
