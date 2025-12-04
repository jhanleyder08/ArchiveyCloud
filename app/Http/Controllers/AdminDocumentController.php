<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\Expediente;
use App\Models\TipologiaDocumental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para gestión de documentos del SGDEA
 * 
 * Implementa requerimientos de Captura e Ingreso de Documentos:
 * - REQ-CP-001: Gestión de formatos
 * - REQ-CP-002: Contenido multimedia  
 * - REQ-CP-007: Validación de formatos
 * - REQ-CP-012: Gestión de versiones
 * - REQ-CP-015-020: Firmas digitales
 * - REQ-CP-021: Visualización
 * - REQ-CP-028: Conversión de formatos
 */
class AdminDocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Documento::with([
            'expediente:id,codigo,nombre',
            'tipologia:id,nombre,categoria',
            'usuarioCreador:id,name',
            'usuarioModificador:id,name'
        ]);

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%")
                  ->orWhereHas('expediente', function($subQ) use ($search) {
                      $subQ->where('nombre', 'like', "%{$search}%")
                           ->orWhere('codigo', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo_soporte')) {
            $query->where('tipo_soporte', $request->tipo_soporte);
        }

        if ($request->filled('formato')) {
            $query->where('formato', $request->formato);
        }

        if ($request->filled('expediente_id')) {
            $query->where('expediente_id', $request->expediente_id);
        }

        if ($request->filled('tipologia_id')) {
            $query->where('tipologia_id', $request->tipologia_id);
        }

        // Ordenamiento
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $documentos = $query->paginate(15)->withQueryString();

        // Estadísticas para el dashboard
        $estadisticas = [
            'total' => Documento::count(),
            'activos' => Documento::where('estado', Documento::ESTADO_ACTIVO)->count(),
            'borradores' => Documento::where('estado', Documento::ESTADO_BORRADOR)->count(),
            'archivados' => Documento::where('estado', Documento::ESTADO_ARCHIVADO)->count(),
            'por_formato' => Documento::selectRaw('formato, COUNT(*) as total')
                ->groupBy('formato')
                ->pluck('total', 'formato')
                ->toArray(),
            'por_soporte' => Documento::selectRaw('tipo_soporte, COUNT(*) as total')
                ->groupBy('tipo_soporte')
                ->pluck('total', 'tipo_soporte')
                ->toArray(),
        ];

        // Opciones para filtros
        $expedientes = Expediente::select('id', 'codigo', 'titulo')->orderBy('titulo')->get();
        $tipologias = TipologiaDocumental::select('id', 'nombre', 'categoria')->orderBy('nombre')->get();
        
        $formatosDisponibles = collect(array_merge(
            Documento::FORMATOS_TEXTO,
            Documento::FORMATOS_IMAGEN,
            Documento::FORMATOS_HOJA_CALCULO,
            Documento::FORMATOS_PRESENTACION,
            Documento::FORMATOS_AUDIO,
            Documento::FORMATOS_VIDEO,
            Documento::FORMATOS_COMPRIMIDOS
        ))->sort()->values();

        return Inertia::render('admin/documentos/index', [
            'documentos' => $documentos,
            'stats' => $estadisticas,
            'filtros' => $request->only(['search', 'estado', 'tipo_soporte', 'formato', 'expediente_id', 'tipologia_id']),
            'expedientes' => $expedientes,
            'tipologias' => $tipologias,
            'formatosDisponibles' => $formatosDisponibles,
            'estados' => [
                Documento::ESTADO_BORRADOR => 'Borrador',
                Documento::ESTADO_PENDIENTE => 'Pendiente',
                Documento::ESTADO_APROBADO => 'Aprobado',
                Documento::ESTADO_ACTIVO => 'Activo',
                Documento::ESTADO_ARCHIVADO => 'Archivado',
                Documento::ESTADO_OBSOLETO => 'Obsoleto',
            ],
            'tiposSoporte' => [
                Documento::SOPORTE_ELECTRONICO => 'Electrónico',
                Documento::SOPORTE_FISICO => 'Físico',
                Documento::SOPORTE_HIBRIDO => 'Híbrido',
            ],
            'nivelesConfidencialidad' => [
                Documento::CONFIDENCIALIDAD_PUBLICA => 'Pública',
                Documento::CONFIDENCIALIDAD_INTERNA => 'Interna',
                Documento::CONFIDENCIALIDAD_CONFIDENCIAL => 'Confidencial',
                Documento::CONFIDENCIALIDAD_RESERVADA => 'Reservada',
                Documento::CONFIDENCIALIDAD_CLASIFICADA => 'Clasificada',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'expediente_id' => 'required|exists:expedientes,id',
            'tipologia_id' => 'required|exists:tipologias_documentales,id',
            'tipo_soporte' => 'required|in:' . implode(',', [
                Documento::SOPORTE_ELECTRONICO,
                Documento::SOPORTE_FISICO,
                Documento::SOPORTE_HIBRIDO
            ]),
            'confidencialidad' => 'required|in:' . implode(',', [
                Documento::CONFIDENCIALIDAD_PUBLICA,
                Documento::CONFIDENCIALIDAD_INTERNA,
                Documento::CONFIDENCIALIDAD_CONFIDENCIAL,
                Documento::CONFIDENCIALIDAD_RESERVADA,
                Documento::CONFIDENCIALIDAD_CLASIFICADA
            ]),
            'palabras_clave' => 'nullable|array',
            'palabras_clave.*' => 'string|max:100',
            'ubicacion_fisica' => 'nullable|string|max:500',
            'observaciones' => 'nullable|string|max:1000',
            'archivo' => 'nullable|file|max:102400', // 100MB máximo
        ]);

        // Validar formato si hay archivo
        if ($request->hasFile('archivo')) {
            $archivo = $request->file('archivo');
            $extension = strtolower($archivo->getClientOriginalExtension());
            
            // REQ-CP-007: Validación de formatos
            $formatosPermitidos = array_merge(
                Documento::FORMATOS_TEXTO,
                Documento::FORMATOS_IMAGEN,
                Documento::FORMATOS_HOJA_CALCULO,
                Documento::FORMATOS_PRESENTACION,
                Documento::FORMATOS_AUDIO,
                Documento::FORMATOS_VIDEO,
                Documento::FORMATOS_COMPRIMIDOS
            );

            if (!in_array($extension, $formatosPermitidos)) {
                return back()->withErrors([
                    'archivo' => "Formato de archivo no permitido. Formatos válidos: " . implode(', ', $formatosPermitidos)
                ]);
            }

            // Almacenar archivo
            $rutaArchivo = $archivo->store('documentos/' . date('Y/m'), 'public');
            
            $validatedData['formato'] = $extension;
            $validatedData['tamaño'] = $archivo->getSize();
            $validatedData['ruta_archivo'] = $rutaArchivo;
        }

        // Establecer valores por defecto
        $validatedData['estado'] = Documento::ESTADO_BORRADOR;
        $validatedData['version'] = '1.0';
        $validatedData['es_version_principal'] = true;
        $validatedData['usuario_creador_id'] = auth()->id();

        $documento = Documento::create($validatedData);

        return redirect()->route('admin.documentos.index')
            ->with('success', 'Documento creado exitosamente.');
    }

    public function show(Documento $documento): Response
    {
        $documento->load([
            'expediente:id,codigo,nombre,descripcion',
            'tipologia:id,nombre,categoria,descripcion',
            'usuarioCreador:id,name,email',
            'usuarioModificador:id,name,email',
            'versiones' => function($query) {
                $query->orderBy('version', 'desc');
            },
            'firmas',
            'metadatos',
            'auditoria' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(20);
            }
        ]);

        // REQ-CP-021: Información para visualización
        $puedeVisualizar = $documento->existe() && in_array($documento->formato, ['pdf', 'jpg', 'jpeg', 'png', 'txt']);
        
        return Inertia::render('admin/documentos/show', [
            'documento' => $documento,
            'puedeVisualizar' => $puedeVisualizar,
            'estadisticas' => $documento->getEstadisticas(),
        ]);
    }

    public function update(Request $request, Documento $documento)
    {
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'expediente_id' => 'required|exists:expedientes,id',
            'tipologia_id' => 'required|exists:tipologias_documentales,id',
            'tipo_soporte' => 'required|in:' . implode(',', [
                Documento::SOPORTE_ELECTRONICO,
                Documento::SOPORTE_FISICO,
                Documento::SOPORTE_HIBRIDO
            ]),
            'estado' => 'required|in:' . implode(',', [
                Documento::ESTADO_BORRADOR,
                Documento::ESTADO_PENDIENTE,
                Documento::ESTADO_APROBADO,
                Documento::ESTADO_ACTIVO,
                Documento::ESTADO_ARCHIVADO,
                Documento::ESTADO_OBSOLETO
            ]),
            'confidencialidad' => 'required|in:' . implode(',', [
                Documento::CONFIDENCIALIDAD_PUBLICA,
                Documento::CONFIDENCIALIDAD_INTERNA,
                Documento::CONFIDENCIALIDAD_CONFIDENCIAL,
                Documento::CONFIDENCIALIDAD_RESERVADA,
                Documento::CONFIDENCIALIDAD_CLASIFICADA
            ]),
            'palabras_clave' => 'nullable|array',
            'palabras_clave.*' => 'string|max:100',
            'ubicacion_fisica' => 'nullable|string|max:500',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        $validatedData['usuario_modificador_id'] = auth()->id();

        $documento->update($validatedData);

        return back()->with('success', 'Documento actualizado exitosamente.');
    }

    public function destroy(Documento $documento)
    {
        // REQ-CL-026: Control de eliminación según reglas de negocio
        if ($documento->estado === Documento::ESTADO_ACTIVO) {
            return back()->withErrors(['error' => 'No se puede eliminar un documento activo. Primero debe cambiar su estado.']);
        }

        if ($documento->firmas()->exists()) {
            return back()->withErrors(['error' => 'No se puede eliminar un documento con firmas digitales.']);
        }

        $documento->delete();

        return redirect()->route('admin.documentos.index')
            ->with('success', 'Documento eliminado exitosamente.');
    }

    /**
     * REQ-CP-012: Crear nueva versión de documento
     */
    public function crearVersion(Request $request, Documento $documento)
    {
        $request->validate([
            'archivo' => 'required|file|max:102400',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        $archivo = $request->file('archivo');
        $rutaArchivo = $archivo->store('documentos/' . date('Y/m'), 'public');

        $nuevaVersion = $documento->crearNuevaVersion($rutaArchivo, $request->observaciones);

        return back()->with('success', 'Nueva versión creada exitosamente. Versión: ' . $nuevaVersion->version);
    }

    /**
     * REQ-CP-015-020: Firmar documento digitalmente
     */
    public function firmarDigitalmente(Request $request, Documento $documento)
    {
        $request->validate([
            'tipo_firma' => 'required|in:CADES,PADES,XADES',
            'observaciones' => 'nullable|string|max:500',
        ]);

        try {
            $firma = $documento->firmarDigitalmente(auth()->user(), $request->tipo_firma);
            
            return back()->with('success', 'Documento firmado digitalmente exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al firmar el documento: ' . $e->getMessage()]);
        }
    }

    /**
     * REQ-CP-028: Convertir formato de documento
     */
    public function convertirFormato(Request $request, Documento $documento)
    {
        $request->validate([
            'formato_destino' => 'required|string|max:10',
        ]);

        try {
            $documentoConvertido = $documento->convertirFormato($request->formato_destino);
            
            return back()->with('success', 'Documento convertido exitosamente a formato ' . strtoupper($request->formato_destino));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al convertir el documento: ' . $e->getMessage()]);
        }
    }

    /**
     * Descargar documento
     */
    public function descargar(Documento $documento)
    {
        if (!$documento->existe()) {
            return back()->withErrors(['error' => 'El archivo no existe en el sistema.']);
        }

        return Storage::disk('public')->download(
            $documento->ruta_archivo, 
            $documento->nombre . '.' . $documento->formato
        );
    }

    /**
     * Vista previa de documento
     */
    public function preview(Documento $documento)
    {
        if (!$documento->existe()) {
            return response()->json(['error' => 'El archivo no existe'], 404);
        }

        // REQ-CP-021: Visualización sin aplicación nativa
        if (!in_array($documento->formato, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'txt'])) {
            return response()->json(['error' => 'Formato no soportado para vista previa'], 400);
        }

        $url = Storage::disk('public')->url($documento->ruta_archivo);
        
        return response()->json([
            'url' => $url,
            'tipo' => $documento->formato,
            'nombre' => $documento->nombre
        ]);
    }

    /**
     * Exportar documento con metadatos
     */
    public function exportar(Request $request, Documento $documento)
    {
        $formato = $request->get('formato', 'json');
        $incluirVersiones = $request->boolean('incluir_versiones', false);

        $datos = $documento->exportarConMetadatos($formato, $incluirVersiones);

        $nombreArchivo = $documento->codigo . '_metadatos.' . ($formato === 'xml' ? 'xml' : 'json');

        return response($datos)
            ->header('Content-Type', $formato === 'xml' ? 'application/xml' : 'application/json')
            ->header('Content-Disposition', 'attachment; filename="' . $nombreArchivo . '"');
    }

    /**
     * Estadísticas detalladas
     */
    public function estadisticas(): Response
    {
        $estadisticas = [
            'resumen' => [
                'total_documentos' => Documento::count(),
                'total_activos' => Documento::where('estado', Documento::ESTADO_ACTIVO)->count(),
                'total_archivados' => Documento::where('estado', Documento::ESTADO_ARCHIVADO)->count(),
                'total_con_firma' => Documento::whereNotNull('firma_digital')->count(),
            ],
            'por_estado' => Documento::selectRaw('estado, COUNT(*) as total')
                ->groupBy('estado')
                ->pluck('total', 'estado'),
            'por_formato' => Documento::selectRaw('formato, COUNT(*) as total')
                ->groupBy('formato')
                ->orderBy('total', 'desc')
                ->limit(10)
                ->pluck('total', 'formato'),
            'por_soporte' => Documento::selectRaw('tipo_soporte, COUNT(*) as total')
                ->groupBy('tipo_soporte')
                ->pluck('total', 'tipo_soporte'),
            'por_confidencialidad' => Documento::selectRaw('confidencialidad, COUNT(*) as total')
                ->groupBy('confidencialidad')
                ->pluck('total', 'confidencialidad'),
            'almacenamiento' => [
                'total_mb' => round(Documento::sum('tamaño') / (1024 * 1024), 2),
                'promedio_mb' => round(Documento::avg('tamaño') / (1024 * 1024), 2),
            ],
            'actividad_mensual' => Documento::selectRaw('YEAR(created_at) as año, MONTH(created_at) as mes, COUNT(*) as total')
                ->whereYear('created_at', date('Y'))
                ->groupBy('año', 'mes')
                ->orderBy('año', 'desc')
                ->orderBy('mes', 'desc')
                ->limit(12)
                ->get(),
        ];

        return Inertia::render('admin/documentos/estadisticas', [
            'estadisticas' => $estadisticas
        ]);
    }
}
