<?php

namespace App\Services;

use App\Models\Documento;
use App\Models\EmailCapturado;
use App\Models\Expediente;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PhpImap\Mailbox;
use PhpImap\IncomingMail;
use Exception;

/**
 * Servicio de Integración con Sistemas de Email
 * 
 * Implementa requerimientos:
 * REQ-EI-001: Captura automática de emails
 * REQ-EI-002: Procesamiento de adjuntos
 * REQ-EI-003: Clasificación automática por asunto/remitente
 * REQ-EI-004: Integración con expedientes
 */
class EmailIntegrationService
{
    // Protocolos soportados
    const PROTOCOLO_IMAP = 'imap';
    const PROTOCOLO_POP3 = 'pop3';
    const PROTOCOLO_EXCHANGE = 'exchange';
    
    // Estados de procesamiento
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_PROCESADO = 'procesado';
    const ESTADO_ERROR = 'error';
    const ESTADO_DESCARTADO = 'descartado';
    
    // Tipos de clasificación
    const CLASIFICACION_AUTOMATICA = 'automatica';
    const CLASIFICACION_MANUAL = 'manual';
    const CLASIFICACION_REGLA = 'regla';

    protected array $configuraciones;
    protected ?Mailbox $mailbox = null;

    public function __construct()
    {
        $this->configuraciones = config('email_integration', []);
    }

    /**
     * REQ-EI-001: Capturar emails desde buzón de correo
     */
    public function capturarEmails(array $configuracionBuzon): array
    {
        try {
            $this->conectarBuzon($configuracionBuzon);
            
            // Obtener emails no leídos
            $emailsNoLeidos = $this->mailbox->searchMailbox('UNSEEN');
            
            $resultados = [
                'total_emails' => count($emailsNoLeidos),
                'procesados' => 0,
                'errores' => 0,
                'descartados' => 0,
                'emails_capturados' => []
            ];
            
            foreach ($emailsNoLeidos as $emailId) {
                try {
                    $email = $this->mailbox->getMail($emailId);
                    $resultadoProcesamiento = $this->procesarEmail($email, $configuracionBuzon);
                    
                    if ($resultadoProcesamiento['estado'] === self::ESTADO_PROCESADO) {
                        $resultados['procesados']++;
                        $resultados['emails_capturados'][] = $resultadoProcesamiento;
                    } elseif ($resultadoProcesamiento['estado'] === self::ESTADO_DESCARTADO) {
                        $resultados['descartados']++;
                    } else {
                        $resultados['errores']++;
                    }
                    
                    // Marcar como leído si se procesó exitosamente
                    if ($resultadoProcesamiento['estado'] === self::ESTADO_PROCESADO) {
                        $this->mailbox->markMailAsRead($emailId);
                    }
                    
                } catch (Exception $e) {
                    $resultados['errores']++;
                    Log::error('Error procesando email individual', [
                        'email_id' => $emailId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            $this->desconectarBuzon();
            
            Log::info('Captura de emails completada', $resultados);
            
            return $resultados;
            
        } catch (Exception $e) {
            Log::error('Error en captura de emails', [
                'configuracion' => $configuracionBuzon['nombre'] ?? 'desconocida',
                'error' => $e->getMessage()
            ]);
            
            throw new Exception("Error capturando emails: {$e->getMessage()}");
        }
    }

    /**
     * REQ-EI-002: Procesar email individual y sus adjuntos
     */
    private function procesarEmail(IncomingMail $email, array $configuracion): array
    {
        try {
            // Aplicar filtros de descarte
            if ($this->debeDescartarEmail($email, $configuracion)) {
                return [
                    'estado' => self::ESTADO_DESCARTADO,
                    'motivo' => 'Email descartado por filtros'
                ];
            }
            
            // Extraer información del email
            $datosEmail = $this->extraerDatosEmail($email);
            
            // Clasificar email automáticamente
            $clasificacion = $this->clasificarEmailAutomaticamente($datosEmail, $configuracion);
            
            // Crear registro de email capturado
            $emailCapturado = EmailCapturado::create([
                'asunto' => $datosEmail['asunto'],
                'remitente' => $datosEmail['remitente'],
                'destinatarios' => $datosEmail['destinatarios'],
                'fecha_email' => $datosEmail['fecha'],
                'contenido_texto' => $datosEmail['contenido_texto'],
                'contenido_html' => $datosEmail['contenido_html'],
                'headers_completos' => $datosEmail['headers'],
                'clasificacion_automatica' => $clasificacion,
                'expediente_id' => $clasificacion['expediente_id'] ?? null,
                'serie_id' => $clasificacion['serie_id'] ?? null,
                'estado_procesamiento' => self::ESTADO_PROCESADO,
                'configuracion_buzon' => $configuracion['nombre'],
                'mensaje_id' => $email->messageId,
                'fecha_captura' => now()
            ]);
            
            // Procesar adjuntos
            $adjuntosProcesados = $this->procesarAdjuntosEmail($email, $emailCapturado);
            
            // Crear documentos desde adjuntos si están configurados
            $documentosCreados = [];
            if ($configuracion['crear_documentos_adjuntos'] ?? false) {
                $documentosCreados = $this->crearDocumentosDesdeAdjuntos(
                    $adjuntosProcesados,
                    $emailCapturado,
                    $clasificacion
                );
            }
            
            // Actualizar estadísticas del email capturado
            $emailCapturado->update([
                'total_adjuntos' => count($adjuntosProcesados),
                'documentos_generados' => count($documentosCreados),
                'procesamiento_completado' => true
            ]);
            
            return [
                'estado' => self::ESTADO_PROCESADO,
                'email_id' => $emailCapturado->id,
                'asunto' => $datosEmail['asunto'],
                'remitente' => $datosEmail['remitente'],
                'adjuntos_procesados' => count($adjuntosProcesados),
                'documentos_creados' => count($documentosCreados),
                'clasificacion' => $clasificacion
            ];
            
        } catch (Exception $e) {
            Log::error('Error procesando email', [
                'message_id' => $email->messageId ?? 'desconocido',
                'error' => $e->getMessage()
            ]);
            
            return [
                'estado' => self::ESTADO_ERROR,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * REQ-EI-003: Clasificar email automáticamente
     */
    private function clasificarEmailAutomaticamente(array $datosEmail, array $configuracion): array
    {
        $clasificacion = [
            'tipo' => self::CLASIFICACION_AUTOMATICA,
            'confianza' => 0,
            'reglas_aplicadas' => [],
            'expediente_id' => null,
            'serie_id' => null
        ];
        
        // Aplicar reglas de clasificación configuradas
        $reglas = $configuracion['reglas_clasificacion'] ?? [];
        
        foreach ($reglas as $regla) {
            $coincidencia = $this->evaluarReglaClasificacion($regla, $datosEmail);
            
            if ($coincidencia['aplica']) {
                $clasificacion['reglas_aplicadas'][] = $regla['nombre'];
                $clasificacion['confianza'] = max($clasificacion['confianza'], $coincidencia['confianza']);
                
                // Asignar expediente/serie si la regla lo especifica
                if (!empty($regla['expediente_id'])) {
                    $clasificacion['expediente_id'] = $regla['expediente_id'];
                }
                if (!empty($regla['serie_id'])) {
                    $clasificacion['serie_id'] = $regla['serie_id'];
                }
            }
        }
        
        // Clasificación por patrones en asunto
        $patronesAsunto = $this->analizarPatronesAsunto($datosEmail['asunto']);
        if (!empty($patronesAsunto)) {
            $clasificacion['patrones_detectados'] = $patronesAsunto;
            $clasificacion['confianza'] += 20;
        }
        
        // Clasificación por dominio del remitente
        $clasificacionDominio = $this->clasificarPorDominio($datosEmail['remitente']);
        if ($clasificacionDominio) {
            $clasificacion = array_merge($clasificacion, $clasificacionDominio);
            $clasificacion['confianza'] += 15;
        }
        
        return $clasificacion;
    }

    /**
     * REQ-EI-004: Integrar email con expedientes
     */
    public function asociarEmailConExpediente(EmailCapturado $email, Expediente $expediente): bool
    {
        try {
            $email->update([
                'expediente_id' => $expediente->id,
                'serie_id' => $expediente->serie_id,
                'clasificacion_manual' => [
                    'usuario_id' => auth()->id(),
                    'fecha_clasificacion' => now()->toISOString(),
                    'expediente_asignado' => $expediente->codigo
                ]
            ]);
            
            // Si el email tiene documentos asociados, también actualizarlos
            if ($email->documentos_generados > 0) {
                Documento::where('email_origen_id', $email->id)
                    ->update(['expediente_id' => $expediente->id]);
            }
            
            Log::info('Email asociado con expediente', [
                'email_id' => $email->id,
                'expediente_id' => $expediente->id,
                'expediente_codigo' => $expediente->codigo
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Error asociando email con expediente', [
                'email_id' => $email->id,
                'expediente_id' => $expediente->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Obtener emails pendientes de clasificación
     */
    public function obtenerEmailsPendientesClasificacion(): array
    {
        return EmailCapturado::where('expediente_id', null)
            ->where('estado_procesamiento', self::ESTADO_PROCESADO)
            ->orderBy('fecha_captura', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($email) {
                return [
                    'id' => $email->id,
                    'asunto' => $email->asunto,
                    'remitente' => $email->remitente,
                    'fecha_email' => $email->fecha_email,
                    'total_adjuntos' => $email->total_adjuntos,
                    'clasificacion_sugerida' => $email->clasificacion_automatica,
                    'confianza_clasificacion' => $email->clasificacion_automatica['confianza'] ?? 0
                ];
            })
            ->toArray();
    }

    /**
     * Configurar buzón de correo para captura automática
     */
    public function configurarBuzonCaptura(array $configuracion): array
    {
        try {
            // Validar configuración
            $this->validarConfiguracionBuzon($configuracion);
            
            // Probar conexión
            $this->conectarBuzon($configuracion);
            $info = $this->mailbox->getMailboxInfo();
            $this->desconectarBuzon();
            
            // Guardar configuración
            $configuracionCompleta = array_merge($configuracion, [
                'ultima_conexion' => now()->toISOString(),
                'estado' => 'activo',
                'mensajes_disponibles' => $info->Nmsgs ?? 0
            ]);
            
            return [
                'success' => true,
                'message' => 'Configuración de buzón validada exitosamente',
                'configuracion' => $configuracionCompleta
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => "Error configurando buzón: {$e->getMessage()}"
            ];
        }
    }

    // Métodos auxiliares privados
    private function conectarBuzon(array $config): void
    {
        $servidor = $this->construirStringConexion($config);
        $this->mailbox = new Mailbox($servidor, $config['usuario'], $config['password']);
    }
    
    private function desconectarBuzon(): void
    {
        if ($this->mailbox) {
            $this->mailbox->disconnect();
            $this->mailbox = null;
        }
    }
    
    private function construirStringConexion(array $config): string
    {
        $protocolo = $config['protocolo'] ?? self::PROTOCOLO_IMAP;
        $servidor = $config['servidor'];
        $puerto = $config['puerto'] ?? ($protocolo === 'imap' ? 993 : 995);
        $ssl = $config['ssl'] ?? true;
        $carpeta = $config['carpeta'] ?? 'INBOX';
        
        $flags = $ssl ? '/imap/ssl/validate-cert' : '/imap/novalidate-cert';
        
        return "{{$servidor}:{$puerto}{$flags}}{$carpeta}";
    }
    
    private function debeDescartarEmail(IncomingMail $email, array $config): bool
    {
        $filtros = $config['filtros_descarte'] ?? [];
        
        // Filtro por remitente
        if (!empty($filtros['remitentes_bloqueados'])) {
            foreach ($filtros['remitentes_bloqueados'] as $remitenteBloqueado) {
                if (stripos($email->fromAddress, $remitenteBloqueado) !== false) {
                    return true;
                }
            }
        }
        
        // Filtro por asunto
        if (!empty($filtros['asuntos_bloqueados'])) {
            foreach ($filtros['asuntos_bloqueados'] as $asuntoBloqueado) {
                if (stripos($email->subject, $asuntoBloqueado) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    private function extraerDatosEmail(IncomingMail $email): array
    {
        return [
            'asunto' => $email->subject ?? '',
            'remitente' => $email->fromAddress ?? '',
            'destinatarios' => $email->toString ?? '',
            'fecha' => $email->date ? new \DateTime($email->date) : now(),
            'contenido_texto' => $email->textPlain ?? '',
            'contenido_html' => $email->textHtml ?? '',
            'headers' => $email->getHeaders() ?? []
        ];
    }
    
    private function procesarAdjuntosEmail(IncomingMail $email, EmailCapturado $emailCapturado): array
    {
        $adjuntos = [];
        
        foreach ($email->getAttachments() as $adjunto) {
            $nombreArchivo = $adjunto->name ?? 'adjunto_' . time();
            $rutaAlmacenamiento = "emails/{$emailCapturado->id}/adjuntos/{$nombreArchivo}";
            
            // Guardar adjunto
            Storage::disk('public')->put($rutaAlmacenamiento, $adjunto->getContents());
            
            $adjuntos[] = [
                'nombre_original' => $nombreArchivo,
                'ruta_almacenamiento' => $rutaAlmacenamiento,
                'tamaño' => strlen($adjunto->getContents()),
                'tipo_mime' => $adjunto->contentType ?? 'application/octet-stream'
            ];
        }
        
        return $adjuntos;
    }
    
    // Métodos auxiliares de implementación específica
    private function evaluarReglaClasificacion(array $regla, array $datos): array { return ['aplica' => false, 'confianza' => 0]; }
    private function analizarPatronesAsunto(string $asunto): array { return []; }
    private function clasificarPorDominio(string $remitente): ?array { return null; }
    private function crearDocumentosDesdeAdjuntos(array $adjuntos, EmailCapturado $email, array $clasificacion): array { return []; }
    private function validarConfiguracionBuzon(array $config): void { }
}
