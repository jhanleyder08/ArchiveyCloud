<?php

namespace App\Services;

use App\Models\EmailAccount;
use App\Models\EmailCapture;
use App\Models\EmailAttachment;
use App\Models\Documento;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

/**
 * Servicio de captura de correos electrónicos
 * Implementa REQ-CP-015, REQ-CP-016, REQ-CP-017
 */
class EmailCaptureService
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('email_capture');
    }

    /**
     * Capturar emails de una cuenta
     */
    public function captureFromAccount(EmailAccount $account, int $limit = 100): array
    {
        try {
            $connection = $this->connect($account);
            $emails = $this->fetchEmails($connection, $account, $limit);
            
            $captured = [];
            foreach ($emails as $emailData) {
                $result = $this->processEmail($emailData, $account);
                if ($result) {
                    $captured[] = $result;
                }
            }

            $this->disconnect($connection);

            // Actualizar última captura
            $account->update([
                'last_capture_at' => now(),
                'total_captured' => $account->total_captured + count($captured),
            ]);

            return $captured;
        } catch (Exception $e) {
            Log::error("Error capturando emails de {$account->email}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Conectar al servidor de correo
     */
    public function connect(EmailAccount $account)
    {
        // Verificar que la extensión IMAP esté disponible
        if (!function_exists('imap_open')) {
            throw new Exception('La extensión PHP IMAP no está instalada. Por favor, habilite la extensión php-imap en su servidor.');
        }

        $mailbox = sprintf(
            '{%s:%d/%s/%s}INBOX',
            $account->host,
            $account->port,
            $account->protocol,
            $account->encryption
        );

        $connection = @imap_open(
            $mailbox,
            $account->email,
            $account->getDecryptedPassword(),
            0,
            1
        );

        if (!$connection) {
            $error = imap_last_error();
            throw new Exception('No se pudo conectar al servidor de correo: ' . ($error ?: 'Error desconocido'));
        }

        return $connection;
    }

    /**
     * Obtener emails del servidor
     */
    protected function fetchEmails($connection, EmailAccount $account, int $limit): array
    {
        $emails = [];
        $totalEmails = imap_num_msg($connection);
        
        // Obtener los últimos $limit emails
        $start = max(1, $totalEmails - $limit + 1);
        
        for ($i = $totalEmails; $i >= $start; $i--) {
            $header = imap_headerinfo($connection, $i);
            $structure = imap_fetchstructure($connection, $i);
            
            // Verificar si ya fue capturado
            if ($this->isAlreadyCaptured($header->message_id, $account->id)) {
                continue;
            }

            // Aplicar filtros
            if (!$this->passesFilters($header, $account)) {
                continue;
            }

            $emails[] = [
                'number' => $i,
                'header' => $header,
                'structure' => $structure,
                'body' => $this->getBody($connection, $i, $structure),
            ];
        }

        return $emails;
    }

    /**
     * Procesar un email individual
     */
    protected function processEmail(array $emailData, EmailAccount $account): ?EmailCapture
    {
        try {
            $header = $emailData['header'];
            
            // Crear registro de captura
            $capture = EmailCapture::create([
                'email_account_id' => $account->id,
                'message_id' => $header->message_id ?? uniqid('email_'),
                'subject' => $this->decodeHeader($header->subject ?? ''),
                'from' => $this->formatAddress($header->from ?? []),
                'to' => $this->formatAddress($header->to ?? []),
                'cc' => isset($header->cc) ? $this->formatAddress($header->cc) : null,
                'body_text' => $emailData['body']['text'] ?? '',
                'body_html' => $emailData['body']['html'] ?? '',
                'email_date' => isset($header->date) ? date('Y-m-d H:i:s', strtotime($header->date)) : now(),
                'status' => 'captured',
            ]);

            // Procesar adjuntos si existen
            if (!empty($emailData['body']['attachments'])) {
                $this->processAttachments($emailData['body']['attachments'], $capture, $account);
            }

            // Crear documento automáticamente si está configurado
            if ($this->config['document_creation']['auto_create']) {
                $this->createDocumentFromEmail($capture, $account);
            }

            return $capture;
        } catch (Exception $e) {
            Log::error("Error procesando email: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener cuerpo del email
     */
    protected function getBody($connection, int $msgNumber, $structure): array
    {
        $body = [
            'text' => '',
            'html' => '',
            'attachments' => [],
        ];

        // Obtener partes del mensaje
        if (isset($structure->parts)) {
            foreach ($structure->parts as $partNum => $part) {
                $data = imap_fetchbody($connection, $msgNumber, $partNum + 1);
                
                // Decodificar según encoding
                if ($part->encoding == 3) { // Base64
                    $data = base64_decode($data);
                } elseif ($part->encoding == 4) { // Quoted-printable
                    $data = quoted_printable_decode($data);
                }

                // Identificar tipo
                if ($part->type == 0) { // Text
                    if ($part->subtype == 'PLAIN') {
                        $body['text'] .= $data;
                    } elseif ($part->subtype == 'HTML') {
                        $body['html'] .= $data;
                    }
                } elseif (isset($part->disposition) && strtolower($part->disposition) == 'attachment') {
                    // Es un adjunto
                    $filename = $this->getFilename($part);
                    $body['attachments'][] = [
                        'filename' => $filename,
                        'data' => $data,
                        'mime_type' => $this->getMimeType($part),
                        'size' => strlen($data),
                    ];
                }
            }
        } else {
            // Email sin partes (texto plano simple)
            $body['text'] = imap_body($connection, $msgNumber);
        }

        return $body;
    }

    /**
     * Procesar adjuntos
     */
    protected function processAttachments(array $attachments, EmailCapture $capture, EmailAccount $account): void
    {
        $disk = Storage::disk($this->config['storage']['disk']);
        $basePath = $this->config['storage']['attachments_path'];

        foreach ($attachments as $attachment) {
            // Validar tamaño
            if ($attachment['size'] > $this->config['processing']['max_attachment_size']) {
                Log::warning("Adjunto demasiado grande: {$attachment['filename']}");
                continue;
            }

            // Validar extensión
            $extension = strtolower(pathinfo($attachment['filename'], PATHINFO_EXTENSION));
            if (!in_array($extension, $this->config['processing']['allowed_attachment_types'])) {
                Log::warning("Tipo de archivo no permitido: {$attachment['filename']}");
                continue;
            }

            // Guardar archivo
            $filename = uniqid('email_att_') . '_' . $attachment['filename'];
            $path = $basePath . '/' . $filename;
            $disk->put($path, $attachment['data']);

            // Crear registro
            $emailAttachment = EmailAttachment::create([
                'email_capture_id' => $capture->id,
                'filename' => $attachment['filename'],
                'mime_type' => $attachment['mime_type'],
                'size' => $attachment['size'],
                'path' => $path,
            ]);

            // Crear documento para el adjunto si está configurado
            if ($this->config['processing']['save_attachments']) {
                $this->createDocumentFromAttachment($emailAttachment, $capture, $account);
            }
        }

        $capture->update(['attachments_count' => count($attachments)]);
    }

    /**
     * Crear documento desde email
     */
    protected function createDocumentFromEmail(EmailCapture $capture, EmailAccount $account): ?Documento
    {
        try {
            $documento = Documento::create([
                'nombre' => $this->sanitizeFilename($capture->subject) . '.eml',
                'codigo' => 'EMAIL-' . date('Y-m-d') . '-' . $capture->id,
                'descripcion' => "Email de: {$capture->from}\nAsunto: {$capture->subject}",
                'serie_documental_id' => $account->serie_documental_id,
                'tipo_documento' => 'email',
                'formato' => 'eml',
                'tamanio' => strlen($capture->body_text . $capture->body_html),
                'ruta_archivo' => $this->saveEmailAsFile($capture),
                'usuario_creador_id' => auth()->id() ?? 1,
                'metadatos' => [
                    'email_from' => $capture->from,
                    'email_to' => $capture->to,
                    'email_date' => $capture->email_date,
                    'email_subject' => $capture->subject,
                ],
            ]);

            $capture->update(['documento_id' => $documento->id, 'status' => 'processed']);

            return $documento;
        } catch (Exception $e) {
            Log::error("Error creando documento desde email: " . $e->getMessage());
            $capture->update(['status' => 'error', 'error_message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Crear documento desde adjunto
     */
    protected function createDocumentFromAttachment(EmailAttachment $attachment, EmailCapture $capture, EmailAccount $account): ?Documento
    {
        try {
            $documento = Documento::create([
                'nombre' => $attachment->filename,
                'codigo' => 'EMAIL-ATT-' . date('Y-m-d') . '-' . $attachment->id,
                'descripcion' => "Adjunto de email: {$capture->subject}",
                'serie_documental_id' => $account->serie_documental_id,
                'tipo_documento' => 'adjunto_email',
                'formato' => pathinfo($attachment->filename, PATHINFO_EXTENSION),
                'tamanio' => $attachment->size,
                'ruta_archivo' => $attachment->path,
                'usuario_creador_id' => auth()->id() ?? 1,
            ]);

            $attachment->update(['documento_id' => $documento->id]);

            return $documento;
        } catch (Exception $e) {
            Log::error("Error creando documento desde adjunto: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Guardar email como archivo
     */
    protected function saveEmailAsFile(EmailCapture $capture): string
    {
        $disk = Storage::disk($this->config['storage']['disk']);
        $path = $this->config['storage']['path'] . '/' . uniqid('email_') . '.eml';
        
        $content = "From: {$capture->from}\n";
        $content .= "To: {$capture->to}\n";
        $content .= "Subject: {$capture->subject}\n";
        $content .= "Date: {$capture->email_date}\n\n";
        $content .= $capture->body_text;
        
        $disk->put($path, $content);
        
        return $path;
    }

    /**
     * Verificar si un email ya fue capturado
     */
    protected function isAlreadyCaptured(string $messageId, int $accountId): bool
    {
        return EmailCapture::where('message_id', $messageId)
            ->where('email_account_id', $accountId)
            ->exists();
    }

    /**
     * Verificar si el email pasa los filtros configurados
     */
    protected function passesFilters($header, EmailAccount $account): bool
    {
        if (!$this->config['filters']['enabled'] || empty($account->filters)) {
            return true;
        }

        $filters = $account->filters;

        // Filtro por remitente
        if (!empty($filters['from'])) {
            $from = $this->formatAddress($header->from ?? []);
            $passes = false;
            foreach ($filters['from'] as $allowedFrom) {
                if (str_contains($from, $allowedFrom)) {
                    $passes = true;
                    break;
                }
            }
            if (!$passes) return false;
        }

        // Filtro por asunto
        if (!empty($filters['subject_contains'])) {
            $subject = strtolower($this->decodeHeader($header->subject ?? ''));
            $passes = false;
            foreach ($filters['subject_contains'] as $keyword) {
                if (str_contains($subject, strtolower($keyword))) {
                    $passes = true;
                    break;
                }
            }
            if (!$passes) return false;
        }

        return true;
    }

    /**
     * Decodificar encabezado MIME
     */
    protected function decodeHeader(string $text): string
    {
        $decoded = imap_mime_header_decode($text);
        $result = '';
        foreach ($decoded as $part) {
            $result .= $part->text;
        }
        return $result;
    }

    /**
     * Formatear dirección de email
     */
    protected function formatAddress(array $addresses): string
    {
        $result = [];
        foreach ($addresses as $address) {
            $email = $address->mailbox . '@' . $address->host;
            $personal = isset($address->personal) ? $this->decodeHeader($address->personal) : '';
            $result[] = $personal ? "$personal <$email>" : $email;
        }
        return implode(', ', $result);
    }

    /**
     * Obtener nombre de archivo de una parte
     */
    protected function getFilename($part): string
    {
        $filename = 'unknown';
        
        if (isset($part->parameters)) {
            foreach ($part->parameters as $param) {
                if (strtolower($param->attribute) == 'name') {
                    $filename = $param->value;
                }
            }
        }
        
        if (isset($part->dparameters)) {
            foreach ($part->dparameters as $param) {
                if (strtolower($param->attribute) == 'filename') {
                    $filename = $param->value;
                }
            }
        }
        
        return $this->decodeHeader($filename);
    }

    /**
     * Obtener tipo MIME
     */
    protected function getMimeType($part): string
    {
        $mimeTypes = ['TEXT', 'MULTIPART', 'MESSAGE', 'APPLICATION', 'AUDIO', 'IMAGE', 'VIDEO', 'OTHER'];
        $type = $mimeTypes[$part->type] ?? 'UNKNOWN';
        $subtype = $part->subtype ?? '';
        return strtolower("$type/$subtype");
    }

    /**
     * Sanitizar nombre de archivo
     */
    protected function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
        return substr($filename, 0, 200);
    }

    /**
     * Desconectar del servidor
     */
    protected function disconnect($connection): void
    {
        if ($connection) {
            imap_close($connection);
        }
    }

    /**
     * Capturar de todas las cuentas activas
     */
    public function captureFromAllAccounts(): array
    {
        $accounts = EmailAccount::where('active', true)
            ->where('auto_capture', true)
            ->get();

        $results = [];
        foreach ($accounts as $account) {
            try {
                $captured = $this->captureFromAccount($account);
                $results[$account->email] = [
                    'success' => true,
                    'count' => count($captured),
                ];
            } catch (Exception $e) {
                $results[$account->email] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
