<?php

namespace App\Services;

use App\Models\Documento;
use App\Models\User;
use App\Models\FirmaDigital;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
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
}
