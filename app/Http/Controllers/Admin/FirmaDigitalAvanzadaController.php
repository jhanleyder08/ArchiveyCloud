<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Documento;
use App\Models\User;
use App\Models\FirmaDigital;
use App\Models\CertificadoDigital;
use App\Models\SolicitudFirma;
use App\Models\FirmanteSolicitud;
use App\Services\FirmaDigitalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class FirmaDigitalAvanzadaController extends Controller
{
    protected FirmaDigitalService $firmaService;

    public function __construct(FirmaDigitalService $firmaService)
    {
        $this->middleware('auth');
        $this->firmaService = $firmaService;
    }

    /**
     * Dashboard principal de firmas digitales
     */
    public function dashboard(): Response
    {
        try {
            $estadisticas = $this->firmaService->obtenerEstadisticas();
        } catch (\Exception $e) {
            \Log::error('Error obteniendo estadísticas de firmas: ' . $e->getMessage());
            $estadisticas = [
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

        try {
            // Solicitudes pendientes para el usuario actual
            $solicitudesPendientes = SolicitudFirma::paraFirmar(Auth::id())
                                                  ->with(['documento', 'solicitante'])
                                                  ->orderBy('fecha_limite')
                                                  ->take(10)
                                                  ->get();
        } catch (\Exception $e) {
            \Log::error('Error obteniendo solicitudes pendientes: ' . $e->getMessage());
            $solicitudesPendientes = collect([]);
        }

        try {
            // Mis solicitudes creadas
            $misSolicitudes = SolicitudFirma::delSolicitante(Auth::id())
                                           ->with(['documento', 'firmantes.usuario'])
                                           ->orderBy('created_at', 'desc')
                                           ->take(10)
                                           ->get();
        } catch (\Exception $e) {
            \Log::error('Error obteniendo mis solicitudes: ' . $e->getMessage());
            $misSolicitudes = collect([]);
        }

        try {
            // Certificados del usuario
            $certificados = CertificadoDigital::where('usuario_id', Auth::id())
                                             ->activos()
                                             ->get();
        } catch (\Exception $e) {
            \Log::error('Error obteniendo certificados: ' . $e->getMessage());
            $certificados = collect([]);
        }

        try {
            // Certificados próximos a vencer
            $certificadosProximosVencer = CertificadoDigital::where('usuario_id', Auth::id())
                                                           ->proximosAVencer()
                                                           ->get();
        } catch (\Exception $e) {
            \Log::error('Error obteniendo certificados próximos a vencer: ' . $e->getMessage());
            $certificadosProximosVencer = collect([]);
        }

        // Agregar estadísticas del usuario
        try {
            $estadisticas['usuario'] = [
                'certificados_activos' => $certificados->count(),
                'solicitudes_pendientes' => $solicitudesPendientes->count(),
                'firmas_realizadas_mes' => 0 // Placeholder, se puede calcular después
            ];
        } catch (\Exception $e) {
            $estadisticas['usuario'] = [
                'certificados_activos' => 0,
                'solicitudes_pendientes' => 0,
                'firmas_realizadas_mes' => 0
            ];
        }

        return Inertia::render('admin/firmas/dashboard', [
            'estadisticas' => $estadisticas,
            'solicitudes_pendientes' => $solicitudesPendientes->toArray(),
            'mis_solicitudes' => $misSolicitudes->toArray(),
            'certificados' => $certificados->toArray(),
            'certificados_proximos_vencer' => $certificadosProximosVencer->toArray()
        ]);
    }

    /**
     * Gestión de certificados digitales
     */
    public function certificados(Request $request): Response
    {
        $query = CertificadoDigital::with('usuario')->where('usuario_id', Auth::id());

        // Filtros
        if ($request->filled('estado')) {
            if ($request->estado === 'activos') {
                $query->activos();
            } elseif ($request->estado === 'vencidos') {
                $query->vencidos();
            } elseif ($request->estado === 'proximos_vencer') {
                $query->proximosAVencer();
            }
        }

        if ($request->filled('tipo')) {
            $query->porTipo($request->tipo);
        }

        $certificados = $query->orderBy('created_at', 'desc')->paginate(15);

        return Inertia::render('admin/firmas/certificados', [
            'certificados' => $certificados,
            'filtros' => $request->only(['estado', 'tipo']),
            'tipos_certificado' => [
                CertificadoDigital::TIPO_USUARIO => 'Usuario',
                CertificadoDigital::TIPO_SERVIDOR => 'Servidor',
                CertificadoDigital::TIPO_CA => 'Autoridad Certificadora',
                CertificadoDigital::TIPO_SELLO_TIEMPO => 'Sello de Tiempo'
            ]
        ]);
    }

    /**
     * Crear nueva solicitud de firma múltiple
     */
    public function crearSolicitud(): Response
    {
        // Obtener documentos del usuario productor
        $documentos = Documento::where('productor_id', Auth::id())
                              ->where('activo', true)
                              ->select('id', 'titulo', 'expediente_id')
                              ->with('expediente:id,codigo,titulo')
                              ->get();

        $usuarios = User::select('id', 'name', 'email')
                       ->where('id', '!=', Auth::id())
                       ->orderBy('name')
                       ->get();

        return Inertia::render('admin/firmas/crear-solicitud', [
            'documentos' => $documentos,
            'usuarios' => $usuarios,
            'tipos_flujo' => [
                SolicitudFirma::FLUJO_SECUENCIAL => 'Secuencial',
                SolicitudFirma::FLUJO_PARALELO => 'Paralelo',
                SolicitudFirma::FLUJO_MIXTO => 'Mixto'
            ],
            'prioridades' => [
                SolicitudFirma::PRIORIDAD_BAJA => 'Baja',
                SolicitudFirma::PRIORIDAD_NORMAL => 'Normal',
                SolicitudFirma::PRIORIDAD_ALTA => 'Alta',
                SolicitudFirma::PRIORIDAD_URGENTE => 'Urgente'
            ],
            'roles_firmante' => [
                FirmanteSolicitud::ROL_APROBADOR => 'Aprobador',
                FirmanteSolicitud::ROL_REVISOR => 'Revisor',
                FirmanteSolicitud::ROL_TESTIGO => 'Testigo',
                FirmanteSolicitud::ROL_AUTORIDAD => 'Autoridad',
                FirmanteSolicitud::ROL_VALIDADOR => 'Validador'
            ]
        ]);
    }

    /**
     * Almacenar nueva solicitud de firma
     */
    public function almacenarSolicitud(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'documento_id' => 'required|exists:documentos,id',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo_flujo' => 'required|in:secuencial,paralelo,mixto',
            'prioridad' => 'required|in:baja,normal,alta,urgente',
            'fecha_limite' => 'required|date|after:now',
            'firmantes' => 'required|array|min:1',
            'firmantes.*.usuario_id' => 'required|exists:users,id|different:' . Auth::id(),
            'firmantes.*.orden' => 'required|integer|min:1',
            'firmantes.*.es_obligatorio' => 'boolean',
            'firmantes.*.rol' => 'required|in:aprobador,revisor,testigo,autoridad,validador'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $documento = Documento::findOrFail($request->documento_id);
            
            // Verificar permisos sobre el documento
            if ($documento->productor_id !== Auth::id()) {
                return back()->withErrors(['documento_id' => 'No tienes permisos para este documento']);
            }

            $solicitud = $this->firmaService->crearSolicitudFirma(
                $documento,
                Auth::user(),
                $request->firmantes,
                $request->only(['titulo', 'descripcion', 'tipo_flujo', 'prioridad', 'fecha_limite'])
            );

            // Iniciar el proceso de firma
            $solicitud->iniciar();

            return redirect()->route('admin.firmas.solicitud', $solicitud)
                           ->with('success', 'Solicitud de firma creada exitosamente');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Ver detalles de solicitud de firma
     */
    public function verSolicitud(SolicitudFirma $solicitud): Response
    {
        $solicitud->load([
            'documento.expediente',
            'solicitante',
            'firmantes.usuario',
            // 'firmas.usuario' // TODO: Descomentar cuando se ejecute migración PKI
        ]);

        // Verificar permisos
        if ($solicitud->solicitante_id !== Auth::id() && 
            !$solicitud->firmantes()->where('usuario_id', Auth::id())->exists()) {
            abort(403, 'No tienes permisos para ver esta solicitud');
        }

        // Obtener firmante actual si existe
        $firmanteActual = $solicitud->firmantes()
                                  ->where('usuario_id', Auth::id())
                                  ->first();

        return Inertia::render('admin/firmas/ver-solicitud', [
            'solicitud' => $solicitud,
            'firmante_actual' => $firmanteActual,
            'puede_firmar' => $firmanteActual && $solicitud->puedeFiremar(Auth::user()),
            'progreso' => $solicitud->progreso,
            'estadisticas' => $solicitud->obtenerEstadisticas()
        ]);
    }

    /**
     * Firmar documento en solicitud
     */
    public function firmarDocumento(Request $request, SolicitudFirma $solicitud)
    {
        $validator = Validator::make($request->all(), [
            'certificado_id' => 'required|exists:certificados_digitales,id',
            'comentario' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            // Verificar que puede firmar
            if (!$solicitud->puedeFiremar(Auth::user())) {
                return back()->withErrors(['error' => 'No puedes firmar este documento en este momento']);
            }

            $certificado = CertificadoDigital::findOrFail($request->certificado_id);
            
            // Verificar que el certificado pertenece al usuario
            if ($certificado->usuario_id !== Auth::id()) {
                return back()->withErrors(['certificado_id' => 'El certificado no te pertenece']);
            }

            // Firmar documento
            $firma = $this->firmaService->firmarConCertificado(
                $solicitud->documento,
                Auth::user(),
                $certificado,
                $request->comentario,
                $solicitud
            );

            return back()->with('success', 'Documento firmado exitosamente');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Rechazar firma
     */
    public function rechazarFirma(Request $request, SolicitudFirma $solicitud)
    {
        $validator = Validator::make($request->all(), [
            'comentario' => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            $firmante = $solicitud->firmantes()
                                 ->where('usuario_id', Auth::id())
                                 ->where('estado', FirmanteSolicitud::ESTADO_PENDIENTE)
                                 ->firstOrFail();

            $firmante->rechazar($request->comentario);

            return back()->with('success', 'Firma rechazada exitosamente');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Verificar firmas de un documento
     */
    public function verificarFirmas(Documento $documento): Response
    {
        $verificacion = $this->firmaService->verificarFirmasMultiples($documento);

        return Inertia::render('admin/firmas/verificar', [
            'documento' => $documento,
            'verificacion' => $verificacion
        ]);
    }

    /**
     * Solicitudes de firma (listado)
     */
    public function solicitudes(Request $request): Response
    {
        $query = SolicitudFirma::with(['documento', 'solicitante', 'firmantes.usuario']);

        // Filtrar por tipo de vista
        if ($request->vista === 'pendientes') {
            $query->paraFirmar(Auth::id());
        } elseif ($request->vista === 'mis_solicitudes') {
            $query->delSolicitante(Auth::id());
        } else {
            // Vista general: solicitudes donde el usuario está involucrado
            $query->where(function ($q) {
                $q->where('solicitante_id', Auth::id())
                  ->orWhereHas('firmantes', function ($subq) {
                      $subq->where('usuario_id', Auth::id());
                  });
            });
        }

        // Filtros adicionales
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('prioridad')) {
            $query->where('prioridad', $request->prioridad);
        }

        if ($request->filled('buscar')) {
            $query->where('titulo', 'like', '%' . $request->buscar . '%');
        }

        $solicitudes = $query->orderBy('created_at', 'desc')->paginate(15);

        return Inertia::render('admin/firmas/solicitudes', [
            'solicitudes' => $solicitudes,
            'filtros' => $request->only(['vista', 'estado', 'prioridad', 'buscar']),
            'estados' => [
                SolicitudFirma::ESTADO_PENDIENTE => 'Pendiente',
                SolicitudFirma::ESTADO_EN_PROCESO => 'En Proceso',
                SolicitudFirma::ESTADO_COMPLETADA => 'Completada',
                SolicitudFirma::ESTADO_CANCELADA => 'Cancelada',
                SolicitudFirma::ESTADO_VENCIDA => 'Vencida'
            ]
        ]);
    }

    /**
     * Cancelar solicitud de firma
     */
    public function cancelarSolicitud(Request $request, SolicitudFirma $solicitud)
    {
        // Solo el solicitante puede cancelar
        if ($solicitud->solicitante_id !== Auth::id()) {
            abort(403, 'No tienes permisos para cancelar esta solicitud');
        }

        $validator = Validator::make($request->all(), [
            'razon' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            $solicitud->cancelar($request->razon);
            
            return back()->with('success', 'Solicitud cancelada exitosamente');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * API: Obtener certificados del usuario actual
     */
    public function apiCertificados()
    {
        $certificados = CertificadoDigital::where('usuario_id', Auth::id())
                                         ->activos()
                                         ->conUso(CertificadoDigital::USO_FIRMA_DIGITAL)
                                         ->select('id', 'nombre_certificado', 'numero_serie', 'fecha_vencimiento', 'tipo_certificado')
                                         ->get();

        return response()->json($certificados);
    }

    /**
     * API: Obtener estadísticas para dashboard
     */
    public function apiEstadisticas()
    {
        $estadisticas = $this->firmaService->obtenerEstadisticas();
        
        // Agregar estadísticas específicas del usuario
        $estadisticas['usuario'] = [
            'certificados_activos' => CertificadoDigital::where('usuario_id', Auth::id())->activos()->count(),
            'solicitudes_pendientes' => SolicitudFirma::paraFirmar(Auth::id())->count(),
            'firmas_realizadas_mes' => FirmaDigital::where('usuario_id', Auth::id())
                                                 ->whereMonth('fecha_firma', now()->month)
                                                 ->count()
        ];

        return response()->json($estadisticas);
    }
}
