<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificadoDigital;
use App\Models\User;
use App\Models\PistaAuditoria;
use App\Services\PKIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class CertificadoDigitalController extends Controller
{
    protected PKIService $pkiService;

    public function __construct(PKIService $pkiService)
    {
        $this->middleware('auth');
        $this->middleware('role:Super Administrador,Administrador SGDEA,Gestor Documental');
        $this->pkiService = $pkiService;
    }

    /**
     * Dashboard principal de certificados digitales
     */
    public function index(Request $request): Response
    {
        $query = CertificadoDigital::with(['usuario:id,name,email'])
            ->orderBy('created_at', 'desc');

        // Aplicar filtros
        if ($request->filled('buscar')) {
            $buscar = $request->get('buscar');
            $query->where(function($q) use ($buscar) {
                $q->where('nombre_certificado', 'like', "%{$buscar}%")
                  ->orWhere('numero_serie', 'like', "%{$buscar}%")
                  ->orWhere('emisor', 'like', "%{$buscar}%")
                  ->orWhere('sujeto', 'like', "%{$buscar}%")
                  ->orWhereHas('usuario', function($subQ) use ($buscar) {
                      $subQ->where('name', 'like', "%{$buscar}%")
                           ->orWhere('email', 'like', "%{$buscar}%");
                  });
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->get('estado'));
        }

        if ($request->filled('tipo_certificado')) {
            $query->where('tipo_certificado', $request->get('tipo_certificado'));
        }

        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->get('usuario_id'));
        }

        // Filtro por vencimiento
        if ($request->filled('vencimiento')) {
            $filtroVencimiento = $request->get('vencimiento');
            switch ($filtroVencimiento) {
                case 'vencidos':
                    $query->vencidos();
                    break;
                case 'proximos_vencer':
                    $query->proximosAVencer(30);
                    break;
                case 'vigentes':
                    $query->activos();
                    break;
            }
        }

        $certificados = $query->paginate(15)->withQueryString();

        // Estadísticas para el dashboard
        $estadisticas = $this->obtenerEstadisticas();

        // Usuarios para filtros
        $usuarios = User::select('id', 'name', 'email')
            ->whereHas('certificados')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/certificados/index', [
            'certificados' => $certificados,
            'estadisticas' => $estadisticas,
            'usuarios' => $usuarios,
            'filtros' => $request->only(['buscar', 'estado', 'tipo_certificado', 'usuario_id', 'vencimiento'])
        ]);
    }

    /**
     * Mostrar formulario de creación de certificado
     */
    public function create(): Response
    {
        $usuarios = User::select('id', 'name', 'email')
            ->where('activo', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/certificados/create', [
            'usuarios' => $usuarios,
            'tipos_certificado' => $this->getTiposCertificado(),
            'usos_permitidos' => $this->getUsosPermitidos(),
            'algoritmos_firma' => $this->getAlgoritmosFirma()
        ]);
    }

    /**
     * Crear nuevo certificado digital
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'usuario_id' => 'required|exists:users,id',
            'nombre_certificado' => 'required|string|max:255',
            'tipo_certificado' => 'required|in:usuario,servidor,autoridad_certificadora,sello_tiempo',
            'algoritmo_firma' => 'required|in:RSA,DSA,ECDSA',
            'longitud_clave' => 'required|integer|in:1024,2048,4096',
            'uso_permitido' => 'required|array',
            'uso_permitido.*' => 'in:firma_digital,autenticacion,cifrado,sello_tiempo',
            'fecha_vencimiento' => 'required|date|after:today',
            'archivo_certificado' => 'nullable|file|mimes:crt,cer,pem|max:2048',
            'archivo_clave_publica' => 'nullable|file|mimes:key,pem|max:2048'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $datosCertificado = $validator->validated();

            // Si se sube archivo de certificado, procesarlo
            if ($request->hasFile('archivo_certificado')) {
                $certificadoData = $this->procesarArchivoCertificado($request->file('archivo_certificado'));
                $datosCertificado = array_merge($datosCertificado, $certificadoData);
            } else {
                // Generar certificado automáticamente
                $certificadoGenerado = $this->pkiService->generarCertificado($datosCertificado);
                $datosCertificado = array_merge($datosCertificado, $certificadoGenerado);
            }

            // Procesar clave pública si se proporciona
            if ($request->hasFile('archivo_clave_publica')) {
                $datosCertificado['clave_publica'] = file_get_contents($request->file('archivo_clave_publica')->getPathname());
            }

            // Crear el certificado
            $certificado = CertificadoDigital::create([
                'usuario_id' => $datosCertificado['usuario_id'],
                'nombre_certificado' => $datosCertificado['nombre_certificado'],
                'numero_serie' => $datosCertificado['numero_serie'] ?? $this->generarNumeroSerie(),
                'emisor' => $datosCertificado['emisor'] ?? config('app.name'),
                'sujeto' => $datosCertificado['sujeto'] ?? $this->generarSujeto($datosCertificado),
                'algoritmo_firma' => $datosCertificado['algoritmo_firma'],
                'longitud_clave' => $datosCertificado['longitud_clave'],
                'huella_digital' => $datosCertificado['huella_digital'] ?? hash('sha256', $datosCertificado['certificado_x509'] ?? ''),
                'certificado_x509' => $datosCertificado['certificado_x509'] ?? null,
                'clave_publica' => $datosCertificado['clave_publica'] ?? null,
                'fecha_emision' => now(),
                'fecha_vencimiento' => $datosCertificado['fecha_vencimiento'],
                'estado' => CertificadoDigital::ESTADO_ACTIVO,
                'tipo_certificado' => $datosCertificado['tipo_certificado'],
                'uso_permitido' => $datosCertificado['uso_permitido'],
                'metadata_pki' => [
                    'creado_por' => Auth::id(),
                    'metodo_creacion' => $request->hasFile('archivo_certificado') ? 'importado' : 'generado',
                    'created_at' => now()->toISOString()
                ]
            ]);

            // Registrar en auditoría
            PistaAuditoria::create([
                'usuario_id' => Auth::id(),
                'accion' => 'crear',
                'modelo' => 'CertificadoDigital',
                'modelo_id' => $certificado->id,
                'detalles' => [
                    'certificado' => $certificado->nombre_certificado,
                    'usuario_propietario' => $certificado->usuario->name,
                    'tipo' => $certificado->tipo_certificado
                ]
            ]);

            return redirect()->route('admin.certificados.index')
                ->with('success', 'Certificado digital creado exitosamente.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al crear el certificado: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Mostrar certificado específico
     */
    public function show(CertificadoDigital $certificado): Response
    {
        $certificado->load(['usuario:id,name,email', 'firmas.documento:id,nombre']);
        
        // Estadísticas de uso
        $estadisticasUso = $certificado->obtenerEstadisticasUso();
        
        // Verificar validez del certificado
        $validez = $certificado->verificarValidez();
        
        // Historial de auditoría
        $historialAuditoria = PistaAuditoria::where('modelo', 'CertificadoDigital')
            ->where('modelo_id', $certificado->id)
            ->with('usuario:id,name')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return Inertia::render('admin/certificados/show', [
            'certificado' => $certificado,
            'estadisticas_uso' => $estadisticasUso,
            'validez' => $validez,
            'historial_auditoria' => $historialAuditoria
        ]);
    }

    /**
     * Revocar certificado
     */
    public function revocar(Request $request, CertificadoDigital $certificado)
    {
        $validator = Validator::make($request->all(), [
            'razon_revocacion' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            $certificado->revocar($request->razon_revocacion);

            // Registrar en auditoría
            PistaAuditoria::create([
                'usuario_id' => Auth::id(),
                'accion' => 'revocar',
                'modelo' => 'CertificadoDigital',
                'modelo_id' => $certificado->id,
                'detalles' => [
                    'certificado' => $certificado->nombre_certificado,
                    'razon' => $request->razon_revocacion
                ]
            ]);

            return back()->with('success', 'Certificado revocado exitosamente.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al revocar el certificado: ' . $e->getMessage()]);
        }
    }

    /**
     * Renovar certificado
     */
    public function renovar(Request $request, CertificadoDigital $certificado)
    {
        $validator = Validator::make($request->all(), [
            'nueva_fecha_vencimiento' => 'required|date|after:today',
            'mantener_configuracion' => 'boolean'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            // Crear nuevo certificado basado en el actual
            $nuevosDatos = [
                'usuario_id' => $certificado->usuario_id,
                'nombre_certificado' => $certificado->nombre_certificado . ' (Renovado)',
                'tipo_certificado' => $certificado->tipo_certificado,
                'algoritmo_firma' => $certificado->algoritmo_firma,
                'longitud_clave' => $certificado->longitud_clave,
                'uso_permitido' => $certificado->uso_permitido,
                'fecha_vencimiento' => $request->nueva_fecha_vencimiento
            ];

            // Generar nuevo certificado
            if ($request->mantener_configuracion) {
                $certificadoRenovado = $this->pkiService->renovarCertificado($certificado, $nuevosDatos);
            } else {
                $certificadoRenovado = $this->pkiService->generarCertificado($nuevosDatos);
            }

            $nuevoCertificado = CertificadoDigital::create(array_merge($nuevosDatos, $certificadoRenovado, [
                'numero_serie' => $this->generarNumeroSerie(),
                'emisor' => $certificado->emisor,
                'sujeto' => $certificado->sujeto,
                'fecha_emision' => now(),
                'estado' => CertificadoDigital::ESTADO_ACTIVO,
                'metadata_pki' => [
                    'renovado_desde' => $certificado->id,
                    'renovado_por' => Auth::id(),
                    'renovado_en' => now()->toISOString()
                ]
            ]));

            // Marcar certificado anterior como vencido
            $certificado->update(['estado' => CertificadoDigital::ESTADO_VENCIDO]);

            // Registrar en auditoría
            PistaAuditoria::create([
                'usuario_id' => Auth::id(),
                'accion' => 'renovar',
                'modelo' => 'CertificadoDigital',
                'modelo_id' => $certificado->id,
                'detalles' => [
                    'certificado_original' => $certificado->nombre_certificado,
                    'certificado_nuevo' => $nuevoCertificado->id,
                    'nueva_fecha_vencimiento' => $request->nueva_fecha_vencimiento
                ]
            ]);

            return redirect()->route('admin.certificados.show', $nuevoCertificado)
                ->with('success', 'Certificado renovado exitosamente.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al renovar el certificado: ' . $e->getMessage()]);
        }
    }

    /**
     * Descargar certificado
     */
    public function descargar(CertificadoDigital $certificado, string $formato = 'pem')
    {
        if (!$certificado->certificado_x509) {
            return back()->withErrors(['error' => 'No hay certificado X.509 disponible para descargar.']);
        }

        try {
            $contenido = '';
            $extension = '';
            $mimeType = '';

            switch ($formato) {
                case 'pem':
                    $contenido = "-----BEGIN CERTIFICATE-----\n" . 
                               chunk_split($certificado->certificado_x509, 64) . 
                               "-----END CERTIFICATE-----\n";
                    $extension = 'pem';
                    $mimeType = 'application/x-pem-file';
                    break;
                    
                case 'der':
                    $contenido = base64_decode($certificado->certificado_x509);
                    $extension = 'der';
                    $mimeType = 'application/x-x509-ca-cert';
                    break;
                    
                case 'crt':
                    $contenido = base64_decode($certificado->certificado_x509);
                    $extension = 'crt';
                    $mimeType = 'application/x-x509-ca-cert';
                    break;
                    
                default:
                    return back()->withErrors(['error' => 'Formato no soportado.']);
            }

            $nombreArchivo = slugify($certificado->nombre_certificado) . '.' . $extension;

            // Registrar descarga en auditoría
            PistaAuditoria::create([
                'usuario_id' => Auth::id(),
                'accion' => 'descargar',
                'modelo' => 'CertificadoDigital',
                'modelo_id' => $certificado->id,
                'detalles' => [
                    'certificado' => $certificado->nombre_certificado,
                    'formato' => $formato
                ]
            ]);

            return response($contenido)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'attachment; filename="' . $nombreArchivo . '"');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al descargar el certificado: ' . $e->getMessage()]);
        }
    }

    /**
     * Obtener estadísticas del sistema de certificados
     */
    private function obtenerEstadisticas(): array
    {
        return [
            'total' => CertificadoDigital::count(),
            'activos' => CertificadoDigital::activos()->count(),
            'vencidos' => CertificadoDigital::vencidos()->count(),
            'proximos_vencer' => CertificadoDigital::proximosAVencer(30)->count(),
            'revocados' => CertificadoDigital::where('estado', CertificadoDigital::ESTADO_REVOCADO)->count(),
            'por_tipo' => CertificadoDigital::selectRaw('tipo_certificado, COUNT(*) as total')
                ->groupBy('tipo_certificado')
                ->pluck('total', 'tipo_certificado')
                ->toArray(),
            'usuarios_con_certificados' => User::whereHas('certificados')->count(),
            'firmas_realizadas' => \DB::table('firmas_digitales')->count()
        ];
    }

    /**
     * Procesar archivo de certificado subido
     */
    private function procesarArchivoCertificado($archivo): array
    {
        $contenido = file_get_contents($archivo->getPathname());
        
        // Determinar formato del certificado
        if (strpos($contenido, '-----BEGIN CERTIFICATE-----') !== false) {
            // Formato PEM
            $certificadoBase64 = preg_replace('/-----.*?-----/', '', $contenido);
            $certificadoBase64 = preg_replace('/\s/', '', $certificadoBase64);
        } else {
            // Formato DER
            $certificadoBase64 = base64_encode($contenido);
        }

        // Extraer información del certificado
        $info = $this->pkiService->extraerInfoCertificado($certificadoBase64);

        return [
            'certificado_x509' => $certificadoBase64,
            'numero_serie' => $info['serial_number'] ?? $this->generarNumeroSerie(),
            'emisor' => $info['issuer'] ?? '',
            'sujeto' => $info['subject'] ?? '',
            'huella_digital' => hash('sha256', base64_decode($certificadoBase64)),
            'clave_publica' => $info['public_key'] ?? null
        ];
    }

    /**
     * Generar número de serie único
     */
    private function generarNumeroSerie(): string
    {
        return strtoupper(bin2hex(random_bytes(16)));
    }

    /**
     * Generar sujeto del certificado
     */
    private function generarSujeto(array $datos): string
    {
        $usuario = User::find($datos['usuario_id']);
        return "CN={$usuario->name}, EMAIL={$usuario->email}, O=" . config('app.name');
    }

    /**
     * Obtener tipos de certificado disponibles
     */
    private function getTiposCertificado(): array
    {
        return [
            ['value' => CertificadoDigital::TIPO_USUARIO, 'label' => 'Usuario'],
            ['value' => CertificadoDigital::TIPO_SERVIDOR, 'label' => 'Servidor'],
            ['value' => CertificadoDigital::TIPO_CA, 'label' => 'Autoridad Certificadora'],
            ['value' => CertificadoDigital::TIPO_SELLO_TIEMPO, 'label' => 'Sello de Tiempo']
        ];
    }

    /**
     * Obtener usos permitidos
     */
    private function getUsosPermitidos(): array
    {
        return [
            ['value' => CertificadoDigital::USO_FIRMA_DIGITAL, 'label' => 'Firma Digital'],
            ['value' => CertificadoDigital::USO_AUTENTICACION, 'label' => 'Autenticación'],
            ['value' => CertificadoDigital::USO_CIFRADO, 'label' => 'Cifrado'],
            ['value' => CertificadoDigital::USO_SELLO_TIEMPO, 'label' => 'Sello de Tiempo']
        ];
    }

    /**
     * Obtener algoritmos de firma disponibles
     */
    private function getAlgoritmosFirma(): array
    {
        return [
            ['value' => 'RSA', 'label' => 'RSA'],
            ['value' => 'DSA', 'label' => 'DSA'],
            ['value' => 'ECDSA', 'label' => 'ECDSA']
        ];
    }
}

/**
 * Helper function para slugify
 */
if (!function_exists('slugify')) {
    function slugify($text): string
    {
        return preg_replace('/[^A-Za-z0-9-]+/', '-', $text);
    }
}
