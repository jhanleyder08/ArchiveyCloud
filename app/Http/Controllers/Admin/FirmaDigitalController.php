<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Documento;
use App\Models\FirmaDigital;
use App\Services\FirmaDigitalService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class FirmaDigitalController extends Controller
{
    protected $firmaService;

    public function __construct(FirmaDigitalService $firmaService)
    {
        $this->middleware('auth');
        $this->middleware('verified');
        $this->firmaService = $firmaService;
    }

    /**
     * Mostrar formulario de firma para un documento
     */
    public function mostrarFormularioFirma(Documento $documento)
    {
        $documento->load(['expediente.serie', 'tipologia', 'usuarioCreador']);

        // Verificar que el usuario puede firmar este documento
        $this->authorize('firmar', $documento);

        // Obtener firmas existentes
        $firmasExistentes = $this->firmaService->obtenerFirmasDocumento($documento);

        return Inertia::render('admin/documentos/firmar', [
            'documento' => [
                'id' => $documento->id,
                'nombre' => $documento->nombre,
                'codigo' => $documento->codigo,
                'descripcion' => $documento->descripcion,
                'tipo_documental' => $documento->tipo_documental,
                'formato' => $documento->formato,
                'tamaño' => $documento->tamaño,
                'expediente' => $documento->expediente ? [
                    'numero_expediente' => $documento->expediente->numero_expediente,
                    'titulo' => $documento->expediente->titulo,
                ] : null,
                'firmado_digitalmente' => $documento->firmado_digitalmente,
                'estado_firma' => $documento->estado_firma,
                'total_firmas' => $documento->total_firmas,
                'fecha_creacion' => $documento->created_at,
            ],
            'firmasExistentes' => $firmasExistentes,
            'puedeFiremar' => !$firmasExistentes || auth()->user()->can('firmar_multiple', $documento),
        ]);
    }

    /**
     * Procesar la firma digital de un documento
     */
    public function firmarDocumento(Request $request, Documento $documento)
    {
        $request->validate([
            'motivo' => 'required|string|max:500',
            'confirmacion' => 'required|boolean|accepted',
        ]);

        try {
            // Verificar que el usuario puede firmar
            $this->authorize('firmar', $documento);

            // Verificar que el documento existe físicamente
            if (!$documento->existe()) {
                return back()->withErrors([
                    'documento' => 'El archivo del documento no se encuentra disponible.'
                ]);
            }

            // Verificar si el usuario ya firmó este documento
            $firmaExistente = FirmaDigital::where('documento_id', $documento->id)
                ->where('usuario_id', Auth::id())
                ->first();

            if ($firmaExistente && !auth()->user()->can('firmar_multiple', $documento)) {
                return back()->withErrors([
                    'firma' => 'Ya has firmado este documento previamente.'
                ]);
            }

            // Crear la firma digital
            $firma = $this->firmaService->firmarDocumento(
                $documento,
                Auth::user(),
                $request->motivo
            );

            return redirect()
                ->route('admin.documentos.show', $documento)
                ->with('success', '¡Documento firmado digitalmente con éxito! Firma ID: ' . $firma->id);

        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error al firmar el documento: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Verificar la validez de las firmas de un documento
     */
    public function verificarFirmas(Documento $documento)
    {
        $documento->load(['expediente', 'tipologia']);

        $firmas = $this->firmaService->obtenerFirmasDocumento($documento);

        return Inertia::render('admin/documentos/verificar-firmas', [
            'documento' => [
                'id' => $documento->id,
                'nombre' => $documento->nombre,
                'codigo' => $documento->codigo,
                'expediente' => $documento->expediente ? [
                    'numero_expediente' => $documento->expediente->numero_expediente,
                    'titulo' => $documento->expediente->titulo,
                ] : null,
                'firmado_digitalmente' => $documento->firmado_digitalmente,
                'estado_firma' => $documento->estado_firma,
                'total_firmas' => $documento->total_firmas,
            ],
            'firmas' => $firmas,
            'todasValidas' => collect($firmas)->every(fn($f) => $f['valida']),
            'totalFirmas' => count($firmas),
        ]);
    }

    /**
     * Generar certificado de firma para un documento
     */
    public function generarCertificado(Documento $documento)
    {
        try {
            $nombreCertificado = $this->firmaService->generarCertificadoFirma($documento);

            return response()->download(
                storage_path('app/certificados/' . $nombreCertificado),
                $nombreCertificado,
                ['Content-Type' => 'application/json']
            )->deleteFileAfterSend(false);

        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error al generar el certificado: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * API: Verificar una firma específica
     */
    public function verificarFirmaEspecifica(FirmaDigital $firma)
    {
        $resultado = $this->firmaService->verificarFirma($firma);

        return response()->json([
            'firma_id' => $firma->id,
            'valida' => $resultado['valida'],
            'errores' => $resultado['errores'],
            'detalles' => $resultado['detalles'],
            'verificado_en' => now(),
        ]);
    }

    /**
     * Dashboard de firmas digitales del usuario
     */
    public function dashboard(Request $request)
    {
        $usuarioId = Auth::id();

        // Estadísticas del usuario
        $estadisticas = [
            'documentos_firmados' => FirmaDigital::deUsuario($usuarioId)->count(),
            'firmas_este_mes' => FirmaDigital::deUsuario($usuarioId)
                ->whereBetween('fecha_firma', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'firmas_validas' => FirmaDigital::deUsuario($usuarioId)->validas()->count(),
            'ultima_firma' => FirmaDigital::deUsuario($usuarioId)
                ->orderBy('fecha_firma', 'desc')
                ->first()?->fecha_firma,
        ];

        // Firmas recientes
        $firmasRecientes = FirmaDigital::with(['documento.expediente'])
            ->deUsuario($usuarioId)
            ->orderBy('fecha_firma', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($firma) {
                $verificacion = $this->firmaService->verificarFirma($firma);
                return [
                    'id' => $firma->id,
                    'documento_nombre' => $firma->documento->nombre,
                    'expediente' => $firma->documento->expediente?->numero_expediente,
                    'fecha_firma' => $firma->fecha_firma,
                    'motivo' => $firma->motivo_firma,
                    'valida' => $verificacion['valida'],
                    'vigente' => $firma->vigente,
                ];
            });

        return Inertia::render('admin/firmas/dashboard', [
            'estadisticas' => $estadisticas,
            'firmasRecientes' => $firmasRecientes,
        ]);
    }
}
