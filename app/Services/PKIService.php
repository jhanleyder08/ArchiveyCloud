<?php

namespace App\Services;

use App\Models\CertificadoDigital;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PKIService
{
    /**
     * Generar un nuevo certificado digital
     */
    public function generarCertificado(array $datos): array
    {
        try {
            // Configuración del certificado
            $config = [
                "digest_alg" => "sha256",
                "private_key_bits" => $datos['longitud_clave'] ?? 2048,
                "private_key_type" => OPENSSL_KEYTYPE_RSA,
                "encrypt_key" => false
            ];

            // Generar par de claves
            $parClaves = openssl_pkey_new($config);
            if (!$parClaves) {
                throw new \Exception('Error al generar el par de claves: ' . openssl_error_string());
            }

            // Extraer clave privada
            openssl_pkey_export($parClaves, $clavePrivada);

            // Extraer clave pública
            $detallesClavePublica = openssl_pkey_get_details($parClaves);
            $clavePublica = $detallesClavePublica['key'];

            // Información del sujeto
            $sujeto = $this->construirSujeto($datos);

            // Crear solicitud de certificado
            $csr = openssl_csr_new($sujeto, $parClaves, $config);
            if (!$csr) {
                throw new \Exception('Error al crear CSR: ' . openssl_error_string());
            }

            // Configurar validez del certificado
            $fechaInicio = time();
            $fechaFin = strtotime($datos['fecha_vencimiento']);
            $diasValidez = ceil(($fechaFin - $fechaInicio) / (60 * 60 * 24));

            // Generar certificado auto-firmado
            $certificado = openssl_csr_sign($csr, null, $parClaves, $diasValidez, $config);
            if (!$certificado) {
                throw new \Exception('Error al generar certificado: ' . openssl_error_string());
            }

            // Exportar certificado
            openssl_x509_export($certificado, $certificadoPEM);

            // Generar número de serie
            $numeroSerie = $this->generarNumeroSerie();

            // Extraer información del certificado
            $infoCertificado = openssl_x509_parse($certificado);

            // Codificar certificado en Base64
            $certificadoBase64 = base64_encode(openssl_x509_read($certificadoPEM));

            // Limpiar recursos
            openssl_pkey_free($parClaves);
            openssl_x509_free($certificado);

            return [
                'certificado_x509' => $certificadoBase64,
                'clave_publica' => $clavePublica,
                'clave_privada' => $clavePrivada, // Solo para almacenamiento seguro temporal
                'numero_serie' => $numeroSerie,
                'emisor' => $this->formatearDN($infoCertificado['issuer'] ?? []),
                'sujeto' => $this->formatearDN($infoCertificado['subject'] ?? []),
                'huella_digital' => hash('sha256', $certificadoBase64),
                'validFrom' => date('Y-m-d H:i:s', $infoCertificado['validFrom_time_t'] ?? $fechaInicio),
                'validTo' => date('Y-m-d H:i:s', $infoCertificado['validTo_time_t'] ?? $fechaFin)
            ];

        } catch (\Exception $e) {
            Log::error('Error en PKIService::generarCertificado', [
                'error' => $e->getMessage(),
                'datos' => $datos
            ]);
            throw $e;
        }
    }

    /**
     * Renovar certificado existente
     */
    public function renovarCertificado(CertificadoDigital $certificadoOriginal, array $nuevosDatos): array
    {
        try {
            // Usar misma configuración del certificado original
            $datosRenovacion = array_merge([
                'algoritmo_firma' => $certificadoOriginal->algoritmo_firma,
                'longitud_clave' => $certificadoOriginal->longitud_clave,
                'usuario_id' => $certificadoOriginal->usuario_id,
                'tipo_certificado' => $certificadoOriginal->tipo_certificado,
                'uso_permitido' => $certificadoOriginal->uso_permitido
            ], $nuevosDatos);

            return $this->generarCertificado($datosRenovacion);

        } catch (\Exception $e) {
            Log::error('Error en PKIService::renovarCertificado', [
                'error' => $e->getMessage(),
                'certificado_original' => $certificadoOriginal->id
            ]);
            throw $e;
        }
    }

    /**
     * Extraer información de un certificado existente
     */
    public function extraerInfoCertificado(string $certificadoBase64): array
    {
        try {
            $certificadoBinario = base64_decode($certificadoBase64);
            $certificado = openssl_x509_read($certificadoBinario);
            
            if (!$certificado) {
                throw new \Exception('Certificado inválido: ' . openssl_error_string());
            }

            $info = openssl_x509_parse($certificado);
            $detalles = openssl_x509_parse($certificado, true);

            // Extraer clave pública
            $clavePublica = openssl_pkey_get_public($certificado);
            $detallesClavePublica = openssl_pkey_get_details($clavePublica);

            openssl_x509_free($certificado);
            if ($clavePublica) {
                openssl_pkey_free($clavePublica);
            }

            return [
                'serial_number' => strtoupper(dechex($info['serialNumber'] ?? 0)),
                'subject' => $this->formatearDN($info['subject'] ?? []),
                'issuer' => $this->formatearDN($info['issuer'] ?? []),
                'valid_from' => date('Y-m-d H:i:s', $info['validFrom_time_t'] ?? 0),
                'valid_to' => date('Y-m-d H:i:s', $info['validTo_time_t'] ?? 0),
                'public_key' => $detallesClavePublica['key'] ?? null,
                'public_key_bits' => $detallesClavePublica['bits'] ?? null,
                'public_key_type' => $this->obtenerTipoClavePublica($detallesClavePublica['type'] ?? null),
                'signature_algorithm' => $info['signatureTypeLN'] ?? 'Unknown',
                'version' => $info['version'] ?? 1,
                'extensions' => $info['extensions'] ?? []
            ];

        } catch (\Exception $e) {
            Log::error('Error en PKIService::extraerInfoCertificado', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Validar certificado contra CRL (Certificate Revocation List)
     */
    public function validarContraCRL(CertificadoDigital $certificado): array
    {
        try {
            // Implementación básica - en producción se conectaría a CRL real
            $resultado = [
                'valido' => true,
                'estado_crl' => 'no_revocado',
                'fecha_verificacion' => now(),
                'fuente_crl' => 'local'
            ];

            // Verificar en lista de revocación local
            if ($certificado->estado === CertificadoDigital::ESTADO_REVOCADO) {
                $resultado['valido'] = false;
                $resultado['estado_crl'] = 'revocado';
                $resultado['razon_revocacion'] = $certificado->razon_revocacion;
                $resultado['fecha_revocacion'] = $certificado->revocado_en;
            }

            return $resultado;

        } catch (\Exception $e) {
            Log::error('Error en PKIService::validarContraCRL', [
                'error' => $e->getMessage(),
                'certificado_id' => $certificado->id
            ]);

            return [
                'valido' => false,
                'estado_crl' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generar cadena de certificados
     */
    public function generarCadenaCertificados(CertificadoDigital $certificado): array
    {
        try {
            $cadena = [$certificado];
            
            // En un PKI real, aquí se buscarían los certificados padre
            // hasta llegar al certificado raíz
            
            return [
                'cadena' => $cadena,
                'es_completa' => true,
                'certificado_raiz' => $certificado, // Simplificado
                'nivel_confianza' => 'alto'
            ];

        } catch (\Exception $e) {
            Log::error('Error en PKIService::generarCadenaCertificados', [
                'error' => $e->getMessage(),
                'certificado_id' => $certificado->id
            ]);
            throw $e;
        }
    }

    /**
     * Verificar firma digital con certificado
     */
    public function verificarFirma(string $datos, string $firma, CertificadoDigital $certificado): array
    {
        try {
            if (!$certificado->vigente) {
                return [
                    'valida' => false,
                    'error' => 'Certificado no vigente',
                    'detalles' => $certificado->verificarValidez()
                ];
            }

            $resultado = $certificado->validarFirma($datos, $firma);

            return [
                'valida' => $resultado,
                'certificado_id' => $certificado->id,
                'fecha_verificacion' => now(),
                'algoritmo_usado' => $certificado->algoritmo_firma,
                'detalles_certificado' => [
                    'sujeto' => $certificado->info_sujeto,
                    'emisor' => $certificado->info_emisor,
                    'vigencia' => [
                        'desde' => $certificado->fecha_emision,
                        'hasta' => $certificado->fecha_vencimiento
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error en PKIService::verificarFirma', [
                'error' => $e->getMessage(),
                'certificado_id' => $certificado->id
            ]);

            return [
                'valida' => false,
                'error' => 'Error técnico en verificación: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Construir sujeto del certificado
     */
    private function construirSujeto(array $datos): array
    {
        $usuario = \App\Models\User::find($datos['usuario_id']);
        
        return [
            "countryName" => "CO",
            "stateOrProvinceName" => "Colombia",
            "localityName" => "Bogotá",
            "organizationName" => config('app.name', 'ArchiveyCloud'),
            "organizationalUnitName" => "PKI",
            "commonName" => $usuario->name,
            "emailAddress" => $usuario->email
        ];
    }

    /**
     * Formatear Distinguished Name
     */
    private function formatearDN(array $dn): string
    {
        $partes = [];
        
        $orden = ['CN', 'OU', 'O', 'L', 'ST', 'C', 'emailAddress'];
        
        foreach ($orden as $componente) {
            if (isset($dn[$componente])) {
                $valor = is_array($dn[$componente]) ? $dn[$componente][0] : $dn[$componente];
                $partes[] = "{$componente}={$valor}";
            }
        }
        
        return implode(', ', $partes);
    }

    /**
     * Generar número de serie único
     */
    private function generarNumeroSerie(): string
    {
        return strtoupper(bin2hex(random_bytes(16)));
    }

    /**
     * Obtener tipo de clave pública
     */
    private function obtenerTipoClavePublica(?int $tipo): string
    {
        switch ($tipo) {
            case OPENSSL_KEYTYPE_RSA:
                return 'RSA';
            case OPENSSL_KEYTYPE_DSA:
                return 'DSA';
            case OPENSSL_KEYTYPE_DH:
                return 'DH';
            case OPENSSL_KEYTYPE_EC:
                return 'ECDSA';
            default:
                return 'Unknown';
        }
    }

    /**
     * Generar CSR (Certificate Signing Request)
     */
    public function generarCSR(array $datos): array
    {
        try {
            $config = [
                "digest_alg" => "sha256",
                "private_key_bits" => $datos['longitud_clave'] ?? 2048,
                "private_key_type" => OPENSSL_KEYTYPE_RSA,
                "encrypt_key" => false
            ];

            // Generar par de claves
            $parClaves = openssl_pkey_new($config);
            if (!$parClaves) {
                throw new \Exception('Error al generar claves: ' . openssl_error_string());
            }

            // Extraer clave privada
            openssl_pkey_export($parClaves, $clavePrivada);

            // Información del sujeto
            $sujeto = $this->construirSujeto($datos);

            // Crear CSR
            $csr = openssl_csr_new($sujeto, $parClaves, $config);
            if (!$csr) {
                throw new \Exception('Error al crear CSR: ' . openssl_error_string());
            }

            // Exportar CSR
            openssl_csr_export($csr, $csrPEM);

            // Limpiar recursos
            openssl_pkey_free($parClaves);

            return [
                'csr_pem' => $csrPEM,
                'clave_privada' => $clavePrivada,
                'sujeto' => $this->formatearDN($sujeto)
            ];

        } catch (\Exception $e) {
            Log::error('Error en PKIService::generarCSR', [
                'error' => $e->getMessage(),
                'datos' => $datos
            ]);
            throw $e;
        }
    }
}
