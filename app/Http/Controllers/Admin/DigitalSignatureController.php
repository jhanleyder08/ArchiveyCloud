<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Documento;
use App\Models\FirmaDigital;
use App\Models\CertificadoDigital;
use App\Services\DigitalSignatureService;
use App\Services\CertificateManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Controlador de Firmas Digitales
 * 
 * Gestiona las funcionalidades de firma digital, validación y certificados
 */
class DigitalSignatureController extends Controller
{
    protected DigitalSignatureService $signatureService;
    protected CertificateManagementService $certificateService;

    public function __construct(
        DigitalSignatureService $signatureService,
        CertificateManagementService $certificateService
    ) {
        $this->signatureService = $signatureService;
        $this->certificateService = $certificateService;
    }

    /**
     * Mostrar panel de firmas digitales
     */
    public function index()
    {
        $user = auth()->user();
        
        // Obtener certificados del usuario
        $certificadosUsuario = $this->certificateService->obtenerCertificadosValidosUsuario($user);
        
        // Obtener firmas recientes del usuario
        $firmasRecientes = FirmaDigital::where('usuario_id', $user->id)
            ->with(['documento', 'certificado'])
            ->orderBy('fecha_firma', 'desc')
            ->limit(10)
            ->get();
        
        // Estadísticas de firmas
        $estadisticas = [
            'total_firmas' => FirmaDigital::where('usuario_id', $user->id)->count(),
            'firmas_validas' => FirmaDigital::where('usuario_id', $user->id)
                ->where('estado', FirmaDigital::ESTADO_VALIDA)->count(),
            'certificados_activos' => count($certificadosUsuario),
            'documentos_firmados' => FirmaDigital::where('usuario_id', $user->id)
                ->distinct('documento_id')->count('documento_id')
        ];
        
        return Inertia::render('admin/firmas/index', [
            'certificados' => $certificadosUsuario,
            'firmas_recientes' => $firmasRecientes,
            'estadisticas' => $estadisticas
        ]);
    }

    /**
     * Mostrar formulario de firma de documento
     */
    public function firmarDocumento(Documento $documento)
    {
        $user = auth()->user();
        
        // Verificar que el usuario esté autenticado
        if (!$user) {
            abort(403, 'No está autenticado');
        }
        
        // Obtener certificados válidos del usuario
        $certificados = $this->certificateService->obtenerCertificadosValidosUsuario($user);
        
        if (empty($certificados)) {
            return redirect()->back()->withErrors([
                'certificado' => 'No tiene certificados válidos para firmar documentos'
            ]);
        }
        
        // Obtener firmas existentes del documento
        $firmasExistentes = FirmaDigital::where('documento_id', $documento->id)
            ->with(['usuario', 'certificado'])
            ->orderBy('fecha_firma', 'desc')
            ->get();
        
        // Determinar opciones de firma disponibles
        $opcionesFirma = $this->determinarOpcionesFirma($documento);
        
        return Inertia::render('admin/firmas/firmar-documento', [
            'documento' => $documento->load(['expediente', 'tipologia']),
            'certificados' => $certificados,
            'firmas_existentes' => $firmasExistentes,
            'opciones_firma' => $opcionesFirma
        ]);
    }

    /**
     * Procesar firma de documento
     */
    public function procesarFirma(Request $request, Documento $documento)
    {
        $request->validate([
            'certificado_id' => 'required|exists:certificados_digitales,id',
            'tipo_firma' => ['required', Rule::in(['CADES', 'PADES', 'XADES'])],
            'nivel_firma' => ['required', Rule::in(['BES', 'EPES', 'T', 'LT', 'LTA'])],
            'incluir_sellado_tiempo' => 'boolean',
            'algoritmo_hash' => ['required', Rule::in(['SHA-256', 'SHA-384', 'SHA-512'])],
            'razon_firma' => 'nullable|string|max:255',
            'ubicacion_firma' => 'nullable|string|max:255',
            'politica_firma' => 'nullable|string'
        ]);

        $user = auth()->user();
        
        // Verificar permisos
        if (!$user->can('firmar_documento', $documento)) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para firmar este documento'
            ], 403);
        }

        // Obtener certificado
        $certificado = CertificadoDigital::where('id', $request->certificado_id)
            ->where('usuario_id', $user->id)
            ->firstOrFail();

        DB::beginTransaction();
        
        try {
            // Preparar opciones de firma
            $opciones = [
                'tipo_firma' => $request->tipo_firma,
                'nivel' => $request->nivel_firma,
                'algoritmo_hash' => $request->algoritmo_hash,
                'incluir_sellado_tiempo' => $request->boolean('incluir_sellado_tiempo'),
                'razon_firma' => $request->razon_firma,
                'ubicacion_firma' => $request->ubicacion_firma,
                'politica_firma' => $request->politica_firma
            ];
            
            // Firmar documento
            $firma = $this->signatureService->firmarDocumento(
                $documento,
                $user,
                $certificado,
                $opciones
            );
            
            // Actualizar estado del documento
            $documento->update([
                'firmado_digitalmente' => true,
                'fecha_ultima_firma' => now(),
                'estado_firma' => 'firmado',
                'total_firmas' => ($documento->total_firmas ?? 0) + 1
            ]);
            
            DB::commit();
            
            return redirect()->route('admin.documentos.show', $documento->id)
                ->with('success', 'Documento firmado exitosamente');
            
        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                ->withErrors(['firma' => "Error al firmar documento: {$e->getMessage()}"]);
        }
    }

    /**
     * Procesar contrafirma
     */
    public function procesarContrafirma(Request $request, FirmaDigital $firmaOriginal)
    {
        $request->validate([
            'certificado_id' => 'required|exists:certificados_digitales,id',
            'tipo_contrafirma' => ['required', Rule::in(['countersignature', 'parallel_signature'])],
            'razon_contrafirma' => 'nullable|string|max:255'
        ]);

        $user = auth()->user();
        
        // Obtener certificado
        $certificado = CertificadoDigital::where('id', $request->certificado_id)
            ->where('usuario_id', $user->id)
            ->firstOrFail();

        DB::beginTransaction();
        
        try {
            $opciones = [
                'tipo_contrafirma' => $request->tipo_contrafirma,
                'razon_contrafirma' => $request->razon_contrafirma
            ];
            
            $contrafirma = $this->signatureService->contrafirmarDocumento(
                $firmaOriginal,
                $user,
                $certificado,
                $opciones
            );
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Contrafirma aplicada exitosamente',
                'contrafirma' => [
                    'id' => $contrafirma->id,
                    'fecha_firma' => $contrafirma->fecha_firma
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => "Error en contrafirma: {$e->getMessage()}"
            ], 500);
        }
    }

    /**
     * Validar firma digital
     */
    public function validarFirma(FirmaDigital $firma)
    {
        try {
            $resultadoValidacion = $this->signatureService->validarFirma($firma);
            
            // Actualizar resultado en base de datos
            $firma->update([
                'resultado_validacion' => $resultadoValidacion,
                'fecha_validacion' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'validacion' => $resultadoValidacion
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Error validando firma: {$e->getMessage()}"
            ], 500);
        }
    }

    /**
     * Mostrar detalles de firma
     */
    public function mostrarFirma(FirmaDigital $firma)
    {
        $firma->load([
            'documento.expediente',
            'usuario',
            'certificado',
            'firmasPadre',
            'contrafirmas'
        ]);
        
        // Obtener validación más reciente
        $validacionReciente = null;
        if ($firma->resultado_validacion) {
            $validacionReciente = $firma->resultado_validacion;
        } else {
            // Realizar validación si no existe
            try {
                $validacionReciente = $this->signatureService->validarFirma($firma);
                $firma->update([
                    'resultado_validacion' => $validacionReciente,
                    'fecha_validacion' => now()
                ]);
            } catch (\Exception $e) {
                $validacionReciente = [
                    'estado_general' => 'error',
                    'errores' => [$e->getMessage()]
                ];
            }
        }
        
        return Inertia::render('admin/firmas/detalle', [
            'firma' => $firma,
            'validacion' => $validacionReciente
        ]);
    }

    /**
     * Descargar archivo firmado
     */
    public function descargarArchivoFirmado(FirmaDigital $firma)
    {
        if (!$firma->ruta_archivo_firmado || !Storage::disk('public')->exists($firma->ruta_archivo_firmado)) {
            abort(404, 'Archivo firmado no encontrado');
        }
        
        $rutaCompleta = storage_path('app/public/' . $firma->ruta_archivo_firmado);
        $nombreArchivo = $this->generarNombreArchivoFirmado($firma);
        
        return response()->download($rutaCompleta, $nombreArchivo);
    }

    /**
     * Gestión de certificados
     */
    public function gestionCertificados()
    {
        $user = auth()->user();
        
        $certificados = CertificadoDigital::where('usuario_id', $user->id)
            ->with(['usuario:id,name,email'])
            ->orderBy('fecha_vencimiento', 'desc')
            ->paginate(15);
        
        // Verificar próximos vencimientos
        $proximosVencimientos = $this->certificateService->verificarProximosVencimientos();
        
        // Estadísticas de certificados
        $estadisticas = [
            'total' => CertificadoDigital::count(),
            'activos' => CertificadoDigital::where('estado', 'activo')
                ->where('fecha_vencimiento', '>', now())->count(),
            'vencidos' => CertificadoDigital::where('fecha_vencimiento', '<=', now())->count(),
            'proximos_vencer' => CertificadoDigital::where('estado', 'activo')
                ->whereBetween('fecha_vencimiento', [now(), now()->addDays(30)])->count(),
            'revocados' => CertificadoDigital::where('estado', 'revocado')->count(),
            'por_tipo' => CertificadoDigital::selectRaw('tipo_certificado, COUNT(*) as total')
                ->groupBy('tipo_certificado')
                ->pluck('total', 'tipo_certificado')
                ->toArray(),
            'usuarios_con_certificados' => \DB::table('certificados_digitales')
                ->distinct('usuario_id')->count('usuario_id'),
            'firmas_realizadas' => \DB::table('firmas_digitales')->count()
        ];
        
        return Inertia::render('admin/certificados/index', [
            'certificados' => $certificados,
            'proximos_vencimientos' => $proximosVencimientos,
            'estadisticas' => $estadisticas,
            'usuarios' => [],
            'filtros' => []
        ]);
    }

    /**
     * Importar certificado
     */
    public function importarCertificado(Request $request)
    {
        $request->validate([
            'archivo_certificado' => 'required|file|mimes:pem,der,p12,pfx|max:10240', // 10MB
            'password' => 'nullable|string',
            'tipo_certificado' => 'nullable|string'
        ]);

        try {
            $archivo = $request->file('archivo_certificado');
            $contenidoCertificado = file_get_contents($archivo->path());
            
            $certificado = $this->certificateService->importarCertificado(
                $contenidoCertificado,
                $request->password,
                auth()->user(),
                [
                    'tipo_certificado' => $request->tipo_certificado,
                    'nombre_archivo_original' => $archivo->getClientOriginalName()
                ]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Certificado importado exitosamente',
                'certificado' => [
                    'id' => $certificado->id,
                    'subject' => $certificado->subject,
                    'fecha_vencimiento' => $certificado->fecha_vencimiento
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Error importando certificado: {$e->getMessage()}"
            ], 500);
        }
    }

    /**
     * Verificar estado de certificado
     */
    public function verificarCertificado(CertificadoDigital $certificado)
    {
        try {
            // Verificar revocación por CRL
            $resultadoCRL = $this->certificateService->verificarRevocacionCRL($certificado);
            
            // Verificar por OCSP si está disponible
            $resultadoOCSP = $this->certificateService->verificarRevocacionOCSP($certificado);
            
            // Validar cadena de certificados
            $cadenaValidacion = $this->certificateService->validarCadenaCertificados($certificado);
            
            $resultado = [
                'verificacion_crl' => $resultadoCRL,
                'verificacion_ocsp' => $resultadoOCSP,
                'validacion_cadena' => $cadenaValidacion,
                'fecha_verificacion' => now()->toISOString()
            ];
            
            // Actualizar estado del certificado
            $estadoActualizado = $this->determinarEstadoCertificado($resultado);
            $certificado->update([
                'estado' => $estadoActualizado,
                'fecha_ultima_verificacion' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'verificacion' => $resultado,
                'estado_actualizado' => $estadoActualizado
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Error verificando certificado: {$e->getMessage()}"
            ], 500);
        }
    }

    /**
     * Determinar opciones de firma disponibles según el documento
     */
    private function determinarOpcionesFirma(Documento $documento): array
    {
        $opciones = [
            'tipos_firma' => [],
            'niveles_firma' => ['BES', 'EPES', 'T', 'LT', 'LTA'],
            'algoritmos_hash' => ['SHA-256', 'SHA-384', 'SHA-512'],
            'sellado_tiempo_disponible' => config('digital_signatures.tsa_url') !== null
        ];
        
        // Determinar tipos de firma según formato
        switch (strtolower($documento->formato)) {
            case 'pdf':
                $opciones['tipos_firma'] = ['PADES'];
                break;
            case 'xml':
                $opciones['tipos_firma'] = ['XADES'];
                break;
            default:
                $opciones['tipos_firma'] = ['CADES'];
                break;
        }
        
        return $opciones;
    }

    /**
     * Generar nombre de archivo firmado
     */
    private function generarNombreArchivoFirmado(FirmaDigital $firma): string
    {
        $documento = $firma->documento;
        $extension = pathinfo($documento->ruta_archivo, PATHINFO_EXTENSION);
        
        $tipoFirma = strtolower($firma->tipo_firma);
        $fechaFirma = $firma->fecha_firma->format('Ymd_His');
        
        return "{$documento->nombre}_{$tipoFirma}_{$fechaFirma}.{$extension}";
    }

    /**
     * Determinar estado del certificado basado en verificaciones
     */
    private function determinarEstadoCertificado(array $verificacion): string
    {
        // Verificar CRL
        if ($verificacion['verificacion_crl']['verificado'] && $verificacion['verificacion_crl']['revocado']) {
            return CertificateManagementService::ESTADO_REVOCADO;
        }
        
        // Verificar OCSP
        if ($verificacion['verificacion_ocsp']['verificado']) {
            $estadoOCSP = $verificacion['verificacion_ocsp']['estado'];
            if ($estadoOCSP === 'revoked') {
                return CertificateManagementService::ESTADO_REVOCADO;
            } elseif ($estadoOCSP === 'suspended') {
                return CertificateManagementService::ESTADO_SUSPENDIDO;
            }
        }
        
        // Verificar cadena
        if (!$verificacion['validacion_cadena']['valida']) {
            return CertificateManagementService::ESTADO_DESCONOCIDO;
        }
        
        return CertificateManagementService::ESTADO_VALIDO;
    }
}
