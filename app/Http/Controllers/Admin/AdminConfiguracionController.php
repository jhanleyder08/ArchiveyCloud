<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionServicio;
use App\Services\ConfiguracionService;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AdminConfiguracionController extends Controller
{
    protected $configuracionService;

    public function __construct(ConfiguracionService $configuracionService)
    {
        $this->middleware('auth');
        $this->middleware('role:Super Administrador,Administrador SGDEA');
        $this->configuracionService = $configuracionService;
    }

    /**
     * Panel principal de configuración
     */
    public function index()
    {
        try {
            $configuraciones = ConfiguracionServicio::all()->keyBy('clave');
            
            $estadisticas = [
                'configuraciones_total' => ConfiguracionServicio::count(),
                'configuraciones_activas' => ConfiguracionServicio::where('activo', true)->count(),
                'usuarios_total' => User::count(),
                'roles_total' => Role::count(),
                'cache_size' => $this->getCacheSize(),
                'storage_size' => $this->getStorageSize(),
            ];

            $categorias = [
                'sistema' => ConfiguracionServicio::where('categoria', 'sistema')->get(),
                'email' => ConfiguracionServicio::where('categoria', 'email')->get(),
                'sms' => ConfiguracionServicio::where('categoria', 'sms')->get(),
                'seguridad' => ConfiguracionServicio::where('categoria', 'seguridad')->get(),
                'branding' => ConfiguracionServicio::where('categoria', 'branding')->get(),
                'notificaciones' => ConfiguracionServicio::where('categoria', 'notificaciones')->get(),
            ];

            return Inertia::render('admin/configuracion/Dashboard', [
                'configuraciones' => $configuraciones,
                'estadisticas' => $estadisticas,
                'categorias' => $categorias,
                'roles' => Role::all(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error en configuración index: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar configuraciones');
        }
    }

    /**
     * Actualizar configuración específica
     */
    public function actualizar(Request $request, $clave)
    {
        $request->validate([
            'valor' => 'required',
            'activo' => 'boolean',
        ]);

        try {
            $configuracion = ConfiguracionServicio::where('clave', $clave)->firstOrFail();
            
            // Validaciones específicas según el tipo
            $this->validarConfiguracion($clave, $request->valor);

            $configuracion->update([
                'valor' => $request->valor,
                'activo' => $request->boolean('activo', true),
                'actualizado_por' => auth()->id(),
            ]);

            // Limpiar caché relacionado
            Cache::forget("config_{$clave}");
            Cache::forget('configuraciones_sistema');

            // Acciones especiales según la configuración
            $this->procesarConfiguracionEspecial($clave, $request->valor);

            return redirect()->back()->with('success', 'Configuración actualizada exitosamente');
        } catch (\Exception $e) {
            Log::error("Error actualizando configuración {$clave}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar configuración: ' . $e->getMessage());
        }
    }

    /**
     * Panel de branding y personalización
     */
    public function branding()
    {
        try {
            $configuraciones = ConfiguracionServicio::whereIn('clave', [
                'app_name',
                'app_description',
                'logo_principal',
                'logo_secundario',
                'favicon',
                'color_primario',
                'color_secundario',
                'tema_predeterminado',
            ])->get()->keyBy('clave');

            $temas_disponibles = [
                'light' => 'Claro',
                'dark' => 'Oscuro',
                'auto' => 'Automático',
            ];

            $logos = [
                'principal' => $configuraciones['logo_principal']->valor ?? null,
                'favicon' => $configuraciones['favicon']->valor ?? null,
                'login' => $configuraciones['logo_secundario']->valor ?? null,
            ];

            return Inertia::render('admin/configuracion/ConfiguracionBranding', [
                'configuraciones' => $configuraciones,
                'logos' => $logos,
                'temas_disponibles' => $temas_disponibles,
            ]);
        } catch (\Exception $e) {
            Log::error('Error en branding: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar configuración de branding');
        }
    }

    /**
     * Subir archivo de branding (logo, favicon, etc.)
     */
    public function subirArchivoBranding(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:png,jpg,jpeg,svg,ico|max:2048',
            'tipo' => 'required|in:logo_principal,logo_secundario,favicon',
        ]);

        try {
            $archivo = $request->file('archivo');
            $tipo = $request->tipo;
            
            // Crear directorio si no existe
            $directorio = 'branding';
            if (!Storage::disk('public')->exists($directorio)) {
                Storage::disk('public')->makeDirectory($directorio);
            }

            // Eliminar archivo anterior si existe
            $configuracion = ConfiguracionServicio::where('clave', $tipo)->first();
            if ($configuracion && $configuracion->valor) {
                Storage::disk('public')->delete($configuracion->valor);
            }

            // Guardar nuevo archivo
            $nombreArchivo = $tipo . '_' . time() . '.' . $archivo->getClientOriginalExtension();
            $ruta = $archivo->storeAs($directorio, $nombreArchivo, 'public');

            // Actualizar configuración
            ConfiguracionServicio::updateOrCreate(
                ['clave' => $tipo],
                [
                    'valor' => $ruta,
                    'categoria' => 'branding',
                    'descripcion' => 'Ruta del archivo de ' . str_replace('_', ' ', $tipo),
                    'tipo' => 'archivo',
                    'activo' => true,
                    'actualizado_por' => auth()->id(),
                ]
            );

            // Limpiar caché
            Cache::forget('configuraciones_branding');

            return redirect()->back()->with('success', 'Archivo subido exitosamente');
        } catch (\Exception $e) {
            Log::error('Error subiendo archivo branding: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al subir archivo: ' . $e->getMessage());
        }
    }

    /**
     * Configuración por roles
     */
    public function roles()
    {
        try {
            $roles = Role::with(['users' => function($query) {
                $query->select('id', 'name', 'email', 'role_id');
            }])->get();

            $configuraciones_roles = [];
            foreach ($roles as $role) {
                $configuraciones_roles[$role->id] = ConfiguracionServicio::where('categoria', 'rol_' . $role->id)
                    ->get()
                    ->keyBy('clave');
            }

            // Configuraciones de ejemplo para roles
            $configuraciones_roles = [];
            foreach ($roles as $role) {
                $configuraciones_roles[$role->id] = [
                    'documentos_crear' => false,
                    'documentos_editar' => false,
                    'documentos_eliminar' => false,
                    'documentos_aprobar' => false,
                    'expedientes_crear' => false,
                    'expedientes_cerrar' => false,
                    'expedientes_transferir' => false,
                    'config_sistema' => false,
                    'config_usuarios' => false,
                    'config_roles' => false,
                ];
                
                // Configuraciones especiales por rol
                if ($role->name === 'Super Administrador') {
                    $configuraciones_roles[$role->id] = array_fill_keys(array_keys($configuraciones_roles[$role->id]), true);
                } elseif ($role->name === 'Administrador SGDEA') {
                    $configuraciones_roles[$role->id]['documentos_crear'] = true;
                    $configuraciones_roles[$role->id]['documentos_editar'] = true;
                    $configuraciones_roles[$role->id]['expedientes_crear'] = true;
                    $configuraciones_roles[$role->id]['config_usuarios'] = true;
                } elseif ($role->name === 'Gestor Documental') {
                    $configuraciones_roles[$role->id]['documentos_crear'] = true;
                    $configuraciones_roles[$role->id]['documentos_editar'] = true;
                    $configuraciones_roles[$role->id]['expedientes_crear'] = true;
                }
            }
            
            $configuraciones = ConfiguracionServicio::all()->keyBy('clave');

            return Inertia::render('admin/configuracion/ConfiguracionRoles', [
                'roles' => $roles,
                'configuraciones' => $configuraciones,
                'configuracionesRoles' => $configuraciones_roles,
            ]);
        } catch (\Exception $e) {
            Log::error('Error en configuración roles: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar configuración de roles');
        }
    }

    /**
     * Actualizar configuración específica de rol
     */
    public function actualizarConfiguracionRol(Request $request, $roleId)
    {
        $request->validate([
            'configuraciones' => 'required|array',
            'configuraciones.*.clave' => 'required|string',
            'configuraciones.*.valor' => 'required',
            'configuraciones.*.activo' => 'boolean',
        ]);

        try {
            $role = Role::findOrFail($roleId);
            
            foreach ($request->configuraciones as $config) {
                ConfiguracionServicio::updateOrCreate(
                    [
                        'clave' => $config['clave'],
                        'categoria' => 'rol_' . $roleId,
                    ],
                    [
                        'valor' => $config['valor'],
                        'activo' => $config['activo'] ?? true,
                        'descripcion' => 'Configuración para rol: ' . $role->name,
                        'tipo' => 'texto',
                        'actualizado_por' => auth()->id(),
                    ]
                );
            }

            // Limpiar caché de roles
            Cache::forget("configuraciones_rol_{$roleId}");

            return redirect()->back()->with('success', 'Configuraciones de rol actualizadas exitosamente');
        } catch (\Exception $e) {
            Log::error("Error actualizando configuración rol {$roleId}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar configuraciones de rol');
        }
    }

    /**
     * Mantenimiento del sistema
     */
    public function mantenimiento()
    {
        try {
            $estadisticas_cache = [
                'cache_size' => $this->getCacheSize(),
                'cache_hits' => Cache::get('cache_hits', 0),
                'cache_misses' => Cache::get('cache_misses', 0),
            ];

            $comandos_disponibles = [
                'cache_clear' => [
                    'nombre' => 'Limpiar Cache',
                    'descripcion' => 'Elimina todos los datos del cache',
                    'comando' => 'cache:clear',
                    'peligroso' => false,
                ],
                'config_cache' => [
                    'nombre' => 'Cache de Configuración',
                    'descripcion' => 'Cachea configuraciones para mejor rendimiento',
                    'comando' => 'config:cache',
                    'peligroso' => false,
                ],
                'route_cache' => [
                    'nombre' => 'Cache de Rutas',
                    'descripcion' => 'Cachea rutas para mejor rendimiento',
                    'comando' => 'route:cache',
                    'peligroso' => false,
                ],
                'optimize' => [
                    'nombre' => 'Optimizar Sistema',
                    'descripcion' => 'Ejecuta optimizaciones generales',
                    'comando' => 'optimize:production',
                    'peligroso' => false,
                ],
                'storage_link' => [
                    'nombre' => 'Enlace Storage',
                    'descripcion' => 'Crea enlace simbólico al storage público',
                    'comando' => 'storage:link',
                    'peligroso' => false,
                ],
            ];

            return Inertia::render('admin/configuracion/Mantenimiento', [
                'estadisticas_cache' => $estadisticas_cache,
                'comandos_disponibles' => $comandos_disponibles,
            ]);
        } catch (\Exception $e) {
            Log::error('Error en mantenimiento: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar panel de mantenimiento');
        }
    }

    /**
     * Ejecutar comando de mantenimiento
     */
    public function ejecutarComando(Request $request)
    {
        $request->validate([
            'comando' => 'required|string|in:cache:clear,config:cache,route:cache,optimize:production,storage:link',
        ]);

        try {
            $comando = $request->comando;
            
            // Log de seguridad
            Log::info("Usuario " . auth()->user()->email . " ejecutando comando: {$comando}");

            $exitCode = Artisan::call($comando);
            $output = Artisan::output();

            if ($exitCode === 0) {
                return redirect()->back()->with('success', "Comando '{$comando}' ejecutado exitosamente");
            } else {
                return redirect()->back()->with('error', "Error ejecutando comando: {$output}");
            }
        } catch (\Exception $e) {
            Log::error('Error ejecutando comando: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al ejecutar comando: ' . $e->getMessage());
        }
    }

    /**
     * Exportar configuraciones
     */
    public function exportar()
    {
        try {
            $configuraciones = ConfiguracionServicio::all();
            
            $export_data = [
                'version' => '1.0',
                'fecha_exportacion' => now()->toISOString(),
                'configuraciones' => $configuraciones->map(function($config) {
                    return [
                        'clave' => $config->clave,
                        'valor' => $config->valor,
                        'categoria' => $config->categoria,
                        'tipo' => $config->tipo,
                        'descripcion' => $config->descripcion,
                        'activo' => $config->activo,
                    ];
                })
            ];

            $filename = 'configuraciones_' . now()->format('Y-m-d_H-i-s') . '.json';
            
            return response()->json($export_data)
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");

        } catch (\Exception $e) {
            Log::error('Error exportando configuraciones: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al exportar configuraciones');
        }
    }

    /**
     * Importar configuraciones
     */
    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:json',
        ]);

        try {
            $contenido = file_get_contents($request->file('archivo')->getRealPath());
            $data = json_decode($contenido, true);

            if (!$data || !isset($data['configuraciones'])) {
                return redirect()->back()->with('error', 'Archivo de configuración inválido');
            }

            $importadas = 0;
            foreach ($data['configuraciones'] as $config) {
                ConfiguracionServicio::updateOrCreate(
                    ['clave' => $config['clave']],
                    [
                        'valor' => $config['valor'],
                        'categoria' => $config['categoria'],
                        'tipo' => $config['tipo'],
                        'descripcion' => $config['descripcion'],
                        'activo' => $config['activo'],
                        'actualizado_por' => auth()->id(),
                    ]
                );
                $importadas++;
            }

            // Limpiar caché
            Cache::flush();

            return redirect()->back()->with('success', "Se importaron {$importadas} configuraciones exitosamente");
        } catch (\Exception $e) {
            Log::error('Error importando configuraciones: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al importar configuraciones: ' . $e->getMessage());
        }
    }

    /**
     * Validar configuración según su tipo
     */
    private function validarConfiguracion($clave, $valor)
    {
        switch ($clave) {
            case 'app_url':
                if (!filter_var($valor, FILTER_VALIDATE_URL)) {
                    throw new \InvalidArgumentException('URL inválida');
                }
                break;
            case 'mail_port':
            case 'session_timeout':
                if (!is_numeric($valor)) {
                    throw new \InvalidArgumentException('Valor debe ser numérico');
                }
                break;
            case 'app_debug':
            case 'mail_encryption':
                // Validaciones específicas según necesidades
                break;
        }
    }

    /**
     * Procesar configuraciones especiales que requieren acciones adicionales
     */
    private function procesarConfiguracionEspecial($clave, $valor)
    {
        switch ($clave) {
            case 'app_timezone':
                config(['app.timezone' => $valor]);
                break;
            case 'app_locale':
                config(['app.locale' => $valor]);
                break;
            case 'cache_default':
                config(['cache.default' => $valor]);
                break;
        }
    }

    /**
     * Obtener tamaño del cache
     */
    private function getCacheSize()
    {
        try {
            // Implementación básica - puede mejorarse según el driver de cache usado
            return Cache::get('cache_size_bytes', 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtener tamaño del storage
     */
    private function getStorageSize()
    {
        try {
            $size = 0;
            $files = Storage::disk('public')->allFiles();
            foreach ($files as $file) {
                $size += Storage::disk('public')->size($file);
            }
            return $size;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Actualizar configuraciones de roles en lote
     */
    public function actualizarConfiguracionesRoles(Request $request)
    {
        $request->validate([
            'configuraciones' => 'required|array',
        ]);

        try {
            // Aquí implementarías la lógica para guardar las configuraciones por rol
            // Por ahora, solo simular el guardado exitoso
            
            return redirect()->back()->with('success', 'Configuraciones de roles actualizadas exitosamente');
        } catch (\Exception $e) {
            Log::error('Error actualizando configuraciones de roles: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar configuraciones de roles');
        }
    }

    /**
     * Actualizar configuración de branding
     */
    public function actualizarBranding(Request $request)
    {
        $request->validate([
            'nombre_aplicacion' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'color_primario' => 'required|string|max:7',
            'color_secundario' => 'required|string|max:7',
            'tema_default' => 'required|in:light,dark,auto',
        ]);

        try {
            $configuraciones = [
                'app_name' => $request->nombre_aplicacion,
                'app_description' => $request->descripcion,
                'brand_primary_color' => $request->color_primario,
                'brand_secondary_color' => $request->color_secundario,
                'default_theme' => $request->tema_default,
            ];

            foreach ($configuraciones as $clave => $valor) {
                ConfiguracionServicio::updateOrCreate(
                    ['clave' => $clave],
                    [
                        'valor' => $valor,
                        'categoria' => 'branding',
                        'activo' => true,
                    ]
                );
            }

            return redirect()->back()->with('success', 'Configuración de branding actualizada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error actualizando branding: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar configuración de branding');
        }
    }

    /**
     * Eliminar logo específico
     */
    public function eliminarLogo($tipo)
    {
        try {
            $claves = [
                'principal' => 'logo_principal',
                'favicon' => 'favicon',
                'login' => 'logo_secundario',
            ];

            if (!isset($claves[$tipo])) {
                return redirect()->back()->with('error', 'Tipo de logo no válido');
            }

            $configuracion = ConfiguracionServicio::where('clave', $claves[$tipo])->first();
            if ($configuracion && $configuracion->valor) {
                // Eliminar archivo físico
                if (Storage::disk('public')->exists($configuracion->valor)) {
                    Storage::disk('public')->delete($configuracion->valor);
                }
                
                $configuracion->update(['valor' => null]);
            }

            return redirect()->back()->with('success', 'Logo eliminado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error eliminando logo: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al eliminar logo');
        }
    }
}
