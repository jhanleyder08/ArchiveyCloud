<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\IndiceElectronicoService;
use App\Models\User;
use App\Models\Expediente;
use App\Models\Documento;
use Illuminate\Support\Facades\Log;

class GenerarIndicesElectronicos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'indices:generar 
                            {--tipo=todos : Tipo de entidades a indexar (expedientes, documentos, todos)}
                            {--solo-faltantes : Solo generar índices para entidades sin índice}
                            {--usuario-id= : ID del usuario que ejecuta la indexación (por defecto: primer admin)}
                            {--dry-run : Ejecutar en modo de prueba sin crear índices}
                            {--limite= : Limitar número de entidades a procesar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera índices electrónicos automáticamente para expedientes y documentos';

    protected $indiceService;

    /**
     * Create a new command instance.
     */
    public function __construct(IndiceElectronicoService $indiceService)
    {
        parent::__construct();
        $this->indiceService = $indiceService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Iniciando generación automática de índices electrónicos...');
        $this->newLine();

        // Obtener parámetros
        $tipo = $this->option('tipo');
        $soloFaltantes = $this->option('solo-faltantes');
        $dryRun = $this->option('dry-run');
        $limite = $this->option('limite') ? (int)$this->option('limite') : null;
        
        // Obtener usuario para la indexación
        $usuarioId = $this->option('usuario-id');
        if ($usuarioId) {
            $usuario = User::find($usuarioId);
            if (!$usuario) {
                $this->error("❌ Usuario con ID {$usuarioId} no encontrado");
                return 1;
            }
        } else {
            // Buscar un usuario administrador (con role_id = 1 o con rol sistema)
            $usuario = User::whereHas('role', function($query) {
                $query->where('sistema', true)->where('nivel_jerarquico', 1);
            })->first();
            
            if (!$usuario) {
                $usuario = User::first();
            }
        }

        if (!$usuario) {
            $this->error('❌ No se encontró ningún usuario para ejecutar la indexación');
            return 1;
        }

        $this->info("👤 Usuario de indexación: {$usuario->name} ({$usuario->email})");
        
        if ($dryRun) {
            $this->warn('⚠️  MODO PRUEBA - No se crearán índices reales');
        }
        
        $this->newLine();

        // Estadísticas generales
        $estadisticasIniciales = $this->mostrarEstadisticasIniciales();

        $resultados = [];

        // Procesar según el tipo seleccionado
        switch ($tipo) {
            case 'expedientes':
                $resultados = $this->procesarExpedientes($usuario, $soloFaltantes, $dryRun, $limite);
                break;
            
            case 'documentos':
                $resultados = $this->procesarDocumentos($usuario, $soloFaltantes, $dryRun, $limite);
                break;
            
            case 'todos':
                $this->info('📂 Procesando expedientes...');
                $resultadosExpedientes = $this->procesarExpedientes($usuario, $soloFaltantes, $dryRun, $limite);
                
                $this->newLine();
                $this->info('📄 Procesando documentos...');
                $resultadosDocumentos = $this->procesarDocumentos($usuario, $soloFaltantes, $dryRun, $limite);
                
                $resultados = [
                    'expedientes' => $resultadosExpedientes,
                    'documentos' => $resultadosDocumentos,
                ];
                break;
            
            default:
                $this->error("❌ Tipo '{$tipo}' no válido. Use: expedientes, documentos, todos");
                return 1;
        }

        // Mostrar resumen final
        $this->mostrarResumenFinal($resultados, $estadisticasIniciales);

        // Log de la operación
        Log::info('Comando indices:generar ejecutado', [
            'usuario' => $usuario->email,
            'tipo' => $tipo,
            'solo_faltantes' => $soloFaltantes,
            'dry_run' => $dryRun,
            'limite' => $limite,
            'resultados' => $resultados
        ]);

        return 0;
    }

    private function mostrarEstadisticasIniciales(): array
    {
        $totalExpedientes = Expediente::count();
        $totalDocumentos = Documento::count();
        $indicesExistentes = \App\Models\IndiceElectronico::count();
        $indicesExpedientes = \App\Models\IndiceElectronico::where('tipo_entidad', 'expediente')->count();
        $indicesDocumentos = \App\Models\IndiceElectronico::where('tipo_entidad', 'documento')->count();

        $estadisticas = [
            'total_expedientes' => $totalExpedientes,
            'total_documentos' => $totalDocumentos,
            'indices_existentes' => $indicesExistentes,
            'indices_expedientes' => $indicesExpedientes,
            'indices_documentos' => $indicesDocumentos,
        ];

        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Total Expedientes', number_format($totalExpedientes)],
                ['Total Documentos', number_format($totalDocumentos)],
                ['Índices Existentes', number_format($indicesExistentes)],
                ['├─ Expedientes', number_format($indicesExpedientes)],
                ['└─ Documentos', number_format($indicesDocumentos)],
            ]
        );

        return $estadisticas;
    }

    private function procesarExpedientes(User $usuario, bool $soloFaltantes, bool $dryRun, ?int $limite): array
    {
        $query = Expediente::with(['serie', 'subserie', 'usuarioResponsable']);
        
        if ($soloFaltantes) {
            $query->whereNotExists(function ($q) {
                $q->select(\DB::raw(1))
                  ->from('indice_electronicos')
                  ->whereColumn('indice_electronicos.entidad_id', 'expedientes.id')
                  ->where('indice_electronicos.tipo_entidad', 'expediente');
            });
        }

        if ($limite) {
            $query->limit($limite);
        }

        $expedientes = $query->get();
        $total = $expedientes->count();

        if ($total === 0) {
            $this->warn('⚠️  No hay expedientes para procesar');
            return ['procesados' => 0, 'creados' => 0, 'actualizados' => 0, 'errores' => []];
        }

        $this->info("📊 Procesando {$total} expediente(s)...");

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');

        $resultados = [
            'procesados' => 0,
            'creados' => 0,
            'actualizados' => 0,
            'errores' => []
        ];

        foreach ($expedientes as $expediente) {
            try {
                $resultados['procesados']++;

                if (!$dryRun) {
                    $indiceExistente = \App\Models\IndiceElectronico::where('tipo_entidad', 'expediente')
                        ->where('entidad_id', $expediente->id)
                        ->first();

                    if ($indiceExistente) {
                        $this->indiceService->actualizarIndice($indiceExistente, $expediente, $usuario);
                        $resultados['actualizados']++;
                    } else {
                        $this->indiceService->indexarExpediente($expediente, $usuario);
                        $resultados['creados']++;
                    }
                }

                $bar->advance();
                
            } catch (\Exception $e) {
                $resultados['errores'][] = "Expediente {$expediente->id}: " . $e->getMessage();
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        return $resultados;
    }

    private function procesarDocumentos(User $usuario, bool $soloFaltantes, bool $dryRun, ?int $limite): array
    {
        $query = Documento::with(['expediente.serie', 'expediente.subserie']);
        
        if ($soloFaltantes) {
            $query->whereNotExists(function ($q) {
                $q->select(\DB::raw(1))
                  ->from('indice_electronicos')
                  ->whereColumn('indice_electronicos.entidad_id', 'documentos.id')
                  ->where('indice_electronicos.tipo_entidad', 'documento');
            });
        }

        if ($limite) {
            $query->limit($limite);
        }

        $documentos = $query->get();
        $total = $documentos->count();

        if ($total === 0) {
            $this->warn('⚠️  No hay documentos para procesar');
            return ['procesados' => 0, 'creados' => 0, 'actualizados' => 0, 'errores' => []];
        }

        $this->info("📊 Procesando {$total} documento(s)...");

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');

        $resultados = [
            'procesados' => 0,
            'creados' => 0,
            'actualizados' => 0,
            'errores' => []
        ];

        foreach ($documentos as $documento) {
            try {
                $resultados['procesados']++;

                if (!$dryRun) {
                    $indiceExistente = \App\Models\IndiceElectronico::where('tipo_entidad', 'documento')
                        ->where('entidad_id', $documento->id)
                        ->first();

                    if ($indiceExistente) {
                        $this->indiceService->actualizarIndice($indiceExistente, $documento, $usuario);
                        $resultados['actualizados']++;
                    } else {
                        $this->indiceService->indexarDocumento($documento, $usuario);
                        $resultados['creados']++;
                    }
                }

                $bar->advance();
                
            } catch (\Exception $e) {
                $resultados['errores'][] = "Documento {$documento->id}: " . $e->getMessage();
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        return $resultados;
    }

    private function mostrarResumenFinal(array $resultados, array $estadisticasIniciales): void
    {
        $this->info('📋 RESUMEN FINAL');
        $this->line('═══════════════════════════════════════');

        if (isset($resultados['expedientes']) && isset($resultados['documentos'])) {
            // Modo "todos"
            $totalProcesados = $resultados['expedientes']['procesados'] + $resultados['documentos']['procesados'];
            $totalCreados = $resultados['expedientes']['creados'] + $resultados['documentos']['creados'];
            $totalActualizados = $resultados['expedientes']['actualizados'] + $resultados['documentos']['actualizados'];
            $totalErrores = count($resultados['expedientes']['errores']) + count($resultados['documentos']['errores']);

            $this->table(
                ['Tipo', 'Procesados', 'Creados', 'Actualizados', 'Errores'],
                [
                    [
                        'Expedientes',
                        $resultados['expedientes']['procesados'],
                        $resultados['expedientes']['creados'],
                        $resultados['expedientes']['actualizados'],
                        count($resultados['expedientes']['errores'])
                    ],
                    [
                        'Documentos',
                        $resultados['documentos']['procesados'],
                        $resultados['documentos']['creados'],
                        $resultados['documentos']['actualizados'],
                        count($resultados['documentos']['errores'])
                    ],
                    [
                        '<comment>TOTAL</comment>',
                        "<comment>{$totalProcesados}</comment>",
                        "<info>{$totalCreados}</info>",
                        "<comment>{$totalActualizados}</comment>",
                        $totalErrores > 0 ? "<error>{$totalErrores}</error>" : "<info>{$totalErrores}</info>"
                    ]
                ]
            );

            // Mostrar errores si los hay
            if ($totalErrores > 0) {
                $this->newLine();
                $this->error('❌ ERRORES ENCONTRADOS:');
                foreach (array_merge($resultados['expedientes']['errores'], $resultados['documentos']['errores']) as $error) {
                    $this->line("   • {$error}");
                }
            }

        } else {
            // Modo individual (expedientes o documentos)
            $this->table(
                ['Métrica', 'Cantidad'],
                [
                    ['Procesados', $resultados['procesados']],
                    ['Creados', "<info>{$resultados['creados']}</info>"],
                    ['Actualizados', "<comment>{$resultados['actualizados']}</comment>"],
                    ['Errores', count($resultados['errores']) > 0 ? "<error>".count($resultados['errores'])."</error>" : "<info>0</info>"]
                ]
            );

            // Mostrar errores si los hay
            if (count($resultados['errores']) > 0) {
                $this->newLine();
                $this->error('❌ ERRORES ENCONTRADOS:');
                foreach ($resultados['errores'] as $error) {
                    $this->line("   • {$error}");
                }
            }
        }

        // Estadísticas finales
        $indicesActuales = \App\Models\IndiceElectronico::count();
        $incremento = $indicesActuales - $estadisticasIniciales['indices_existentes'];

        $this->newLine();
        $this->info("📊 Índices en sistema: {$estadisticasIniciales['indices_existentes']} → {$indicesActuales} (+{$incremento})");
        
        if (isset($totalCreados)) {
            $this->info("✅ Proceso completado: {$totalCreados} índices creados, {$totalActualizados} actualizados");
        } else {
            $this->info("✅ Proceso completado: {$resultados['creados']} índices creados, {$resultados['actualizados']} actualizados");
        }
    }
}
