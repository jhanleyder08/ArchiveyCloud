<?php

namespace App\Console\Commands;

use App\Services\BusinessRulesService;
use App\Models\Documento;
use App\Models\Expediente;
use App\Models\Serie;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Comando para validar la integridad completa del sistema SGDEA
 */
class ValidateSystemIntegrity extends Command
{
    protected $signature = 'sgdea:validate-integrity 
                           {--type=all : Tipo de validación (all, documentos, expedientes, series)}
                           {--fix : Intentar corregir problemas menores automáticamente}
                           {--report : Generar reporte detallado}
                           {--limit=100 : Límite de registros a procesar por lote}';

    protected $description = 'Validar la integridad completa del sistema SGDEA según reglas de negocio';

    protected BusinessRulesService $businessRules;
    protected array $reporteValidacion = [
        'documentos' => ['total' => 0, 'validos' => 0, 'errores' => [], 'advertencias' => []],
        'expedientes' => ['total' => 0, 'validos' => 0, 'errores' => [], 'advertencias' => []],
        'series' => ['total' => 0, 'validos' => 0, 'errores' => [], 'advertencias' => []],
        'corregidos' => []
    ];

    public function __construct(BusinessRulesService $businessRules)
    {
        parent::__construct();
        $this->businessRules = $businessRules;
    }

    public function handle(): int
    {
        $tipo = $this->option('type');
        $intentarCorregir = $this->option('fix');
        $generarReporte = $this->option('report');
        $limite = (int) $this->option('limit');

        $this->info('🔍 Iniciando validación de integridad del sistema SGDEA...');
        $this->line('');

        $inicioValidacion = now();

        try {
            switch ($tipo) {
                case 'documentos':
                    $this->validarDocumentos($limite, $intentarCorregir);
                    break;
                    
                case 'expedientes':
                    $this->validarExpedientes($limite, $intentarCorregir);
                    break;
                    
                case 'series':
                    $this->validarSeries($limite, $intentarCorregir);
                    break;
                    
                case 'all':
                default:
                    $this->validarSeries($limite, $intentarCorregir);
                    $this->validarExpedientes($limite, $intentarCorregir);
                    $this->validarDocumentos($limite, $intentarCorregir);
                    break;
            }

            $duracion = now()->diffInSeconds($inicioValidacion);
            
            $this->line('');
            $this->mostrarResumenValidacion($duracion);

            if ($generarReporte) {
                $this->generarReporteDetallado();
            }

            return $this->determinarCodigoSalida();

        } catch (\Exception $e) {
            $this->error("❌ Error durante la validación: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Validar documentos
     */
    private function validarDocumentos(int $limite, bool $intentarCorregir): void
    {
        $this->info('📄 Validando documentos...');
        
        $totalDocumentos = Documento::count();
        $this->reporteValidacion['documentos']['total'] = $totalDocumentos;
        
        $bar = $this->output->createProgressBar($totalDocumentos);
        $bar->start();

        Documento::with(['expediente.serie', 'tipologia'])
            ->chunk($limite, function (Collection $documentos) use ($intentarCorregir, $bar) {
                foreach ($documentos as $documento) {
                    $this->validarDocumento($documento, $intentarCorregir);
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->line('');
    }

    /**
     * Validar un documento específico
     */
    private function validarDocumento(Documento $documento, bool $intentarCorregir): void
    {
        $erroresDocumento = [];
        $advertenciasDocumento = [];

        try {
            // 1. Validar metadatos obligatorios
            $validacionMetadatos = $this->businessRules->validarMetadatosObligatorios($documento, 'documento');
            if (!$validacionMetadatos['valido']) {
                $erroresDocumento = array_merge($erroresDocumento, $validacionMetadatos['errores']);
            }
            $advertenciasDocumento = array_merge($advertenciasDocumento, $validacionMetadatos['advertencias']);

            // 2. Validar integridad referencial
            $validacionIntegridad = $this->businessRules->validarIntegridadReferencial($documento, 'update');
            if (!$validacionIntegridad['valido']) {
                $erroresDocumento = array_merge($erroresDocumento, $validacionIntegridad['errores']);
            }

            // 3. Validar estructura TRD si tiene expediente
            if ($documento->expediente) {
                $validacionTRD = $this->businessRules->validarEstructuraTRD([
                    'serie_id' => $documento->expediente->serie_id,
                    'subserie_id' => $documento->expediente->subserie_id,
                    'tipologia_id' => $documento->tipologia_id
                ]);
                
                if (!$validacionTRD['valido']) {
                    $erroresDocumento = array_merge($erroresDocumento, $validacionTRD['errores']);
                }
                $advertenciasDocumento = array_merge($advertenciasDocumento, $validacionTRD['advertencias']);
            }

            // 4. Validaciones específicas de archivo
            if ($documento->ruta_archivo && !file_exists(storage_path('app/' . $documento->ruta_archivo))) {
                $erroresDocumento[] = "Archivo físico no encontrado: {$documento->ruta_archivo}";
            }

            // 5. Intentar correcciones automáticas
            if ($intentarCorregir && !empty($advertenciasDocumento)) {
                $correccionesAplicadas = $this->aplicarCorreccionesDocumento($documento, $advertenciasDocumento);
                if (!empty($correccionesAplicadas)) {
                    $this->reporteValidacion['corregidos'][] = [
                        'tipo' => 'documento',
                        'id' => $documento->id,
                        'correcciones' => $correccionesAplicadas
                    ];
                }
            }

            // Registrar resultados
            if (empty($erroresDocumento)) {
                $this->reporteValidacion['documentos']['validos']++;
            } else {
                $this->reporteValidacion['documentos']['errores'][] = [
                    'id' => $documento->id,
                    'codigo' => $documento->codigo,
                    'nombre' => $documento->nombre,
                    'errores' => $erroresDocumento
                ];
            }

            if (!empty($advertenciasDocumento)) {
                $this->reporteValidacion['documentos']['advertencias'][] = [
                    'id' => $documento->id,
                    'codigo' => $documento->codigo,
                    'advertencias' => $advertenciasDocumento
                ];
            }

        } catch (\Exception $e) {
            $this->reporteValidacion['documentos']['errores'][] = [
                'id' => $documento->id,
                'codigo' => $documento->codigo ?? 'Sin código',
                'errores' => ["Error de validación: {$e->getMessage()}"]
            ];
        }
    }

    /**
     * Validar expedientes
     */
    private function validarExpedientes(int $limite, bool $intentarCorregir): void
    {
        $this->info('📁 Validando expedientes...');
        
        $totalExpedientes = Expediente::count();
        $this->reporteValidacion['expedientes']['total'] = $totalExpedientes;
        
        $bar = $this->output->createProgressBar($totalExpedientes);
        $bar->start();

        Expediente::with(['serie', 'documentos'])
            ->chunk($limite, function (Collection $expedientes) use ($intentarCorregir, $bar) {
                foreach ($expedientes as $expediente) {
                    $this->validarExpediente($expediente, $intentarCorregir);
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->line('');
    }

    /**
     * Validar un expediente específico
     */
    private function validarExpediente(Expediente $expediente, bool $intentarCorregir): void
    {
        $erroresExpediente = [];
        $advertenciasExpediente = [];

        try {
            // 1. Validar metadatos obligatorios
            $validacionMetadatos = $this->businessRules->validarMetadatosObligatorios($expediente, 'expediente');
            if (!$validacionMetadatos['valido']) {
                $erroresExpediente = array_merge($erroresExpediente, $validacionMetadatos['errores']);
            }
            $advertenciasExpediente = array_merge($advertenciasExpediente, $validacionMetadatos['advertencias']);

            // 2. Validar reglas de negocio
            $validacionReglas = $this->businessRules->validarReglasExpediente($expediente);
            if (!$validacionReglas['valido']) {
                $erroresExpediente = array_merge($erroresExpediente, $validacionReglas['errores']);
            }
            $advertenciasExpediente = array_merge($advertenciasExpediente, $validacionReglas['advertencias']);

            // 3. Validar integridad referencial
            $validacionIntegridad = $this->businessRules->validarIntegridadReferencial($expediente, 'update');
            if (!$validacionIntegridad['valido']) {
                $erroresExpediente = array_merge($erroresExpediente, $validacionIntegridad['errores']);
            }

            // Registrar resultados
            if (empty($erroresExpediente)) {
                $this->reporteValidacion['expedientes']['validos']++;
            } else {
                $this->reporteValidacion['expedientes']['errores'][] = [
                    'id' => $expediente->id,
                    'codigo' => $expediente->codigo,
                    'nombre' => $expediente->nombre,
                    'errores' => $erroresExpediente
                ];
            }

            if (!empty($advertenciasExpediente)) {
                $this->reporteValidacion['expedientes']['advertencias'][] = [
                    'id' => $expediente->id,
                    'codigo' => $expediente->codigo,
                    'advertencias' => $advertenciasExpediente
                ];
            }

        } catch (\Exception $e) {
            $this->reporteValidacion['expedientes']['errores'][] = [
                'id' => $expediente->id,
                'codigo' => $expediente->codigo ?? 'Sin código',
                'errores' => ["Error de validación: {$e->getMessage()}"]
            ];
        }
    }

    /**
     * Validar series
     */
    private function validarSeries(int $limite, bool $intentarCorregir): void
    {
        $this->info('📋 Validando series documentales...');
        
        $totalSeries = Serie::count();
        $this->reporteValidacion['series']['total'] = $totalSeries;
        
        $bar = $this->output->createProgressBar($totalSeries);
        $bar->start();

        Serie::chunk($limite, function (Collection $series) use ($intentarCorregir, $bar) {
            foreach ($series as $serie) {
                $this->validarSerie($serie, $intentarCorregir);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->line('');
    }

    /**
     * Validar una serie específica
     */
    private function validarSerie(Serie $serie, bool $intentarCorregir): void
    {
        $erroresSerie = [];

        try {
            // Validar integridad referencial
            $validacionIntegridad = $this->businessRules->validarIntegridadReferencial($serie, 'update');
            if (!$validacionIntegridad['valido']) {
                $erroresSerie = array_merge($erroresSerie, $validacionIntegridad['errores']);
            }

            // Registrar resultados
            if (empty($erroresSerie)) {
                $this->reporteValidacion['series']['validos']++;
            } else {
                $this->reporteValidacion['series']['errores'][] = [
                    'id' => $serie->id,
                    'codigo' => $serie->codigo,
                    'nombre' => $serie->nombre,
                    'errores' => $erroresSerie
                ];
            }

        } catch (\Exception $e) {
            $this->reporteValidacion['series']['errores'][] = [
                'id' => $serie->id,
                'codigo' => $serie->codigo ?? 'Sin código',
                'errores' => ["Error de validación: {$e->getMessage()}"]
            ];
        }
    }

    /**
     * Aplicar correcciones automáticas a documentos
     */
    private function aplicarCorreccionesDocumento(Documento $documento, array $advertencias): array
    {
        $correccionesAplicadas = [];

        // Ejemplo: Si falta descripción, generar una básica
        if (in_array('Se recomienda completar el campo \'Descripción\'', $advertencias) && empty($documento->descripcion)) {
            $documento->descripcion = "Documento {$documento->formato} - {$documento->nombre}";
            $documento->save();
            $correccionesAplicadas[] = 'Descripción generada automáticamente';
        }

        return $correccionesAplicadas;
    }

    /**
     * Mostrar resumen de validación
     */
    private function mostrarResumenValidacion(int $duracion): void
    {
        $this->info('📊 Resumen de Validación:');
        $this->line('');

        foreach (['series', 'expedientes', 'documentos'] as $tipo) {
            $data = $this->reporteValidacion[$tipo];
            $porcentajeExito = $data['total'] > 0 ? round(($data['validos'] / $data['total']) * 100, 1) : 0;
            
            $this->line("  {$this->getTipoEmoji($tipo)} " . ucfirst($tipo) . ":");
            $this->line("     Total: {$data['total']}");
            $this->line("     ✅ Válidos: {$data['validos']} ({$porcentajeExito}%)");
            $this->line("     ❌ Con errores: " . count($data['errores']));
            $this->line("     ⚠️  Con advertencias: " . count($data['advertencias']));
            $this->line('');
        }

        if (!empty($this->reporteValidacion['corregidos'])) {
            $this->line("  🔧 Correcciones aplicadas: " . count($this->reporteValidacion['corregidos']));
            $this->line('');
        }

        $this->line("  ⏱️  Tiempo total: {$duracion} segundos");
    }

    /**
     * Generar reporte detallado
     */
    private function generarReporteDetallado(): void
    {
        $nombreArchivo = 'validation_report_' . now()->format('Y-m-d_H-i-s') . '.json';
        $rutaArchivo = storage_path("logs/{$nombreArchivo}");
        
        file_put_contents($rutaArchivo, json_encode($this->reporteValidacion, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $this->info("📄 Reporte detallado guardado en: {$rutaArchivo}");
    }

    /**
     * Determinar código de salida
     */
    private function determinarCodigoSalida(): int
    {
        $totalErrores = collect($this->reporteValidacion)
            ->sum(fn($tipo) => is_array($tipo) ? count($tipo['errores'] ?? []) : 0);

        if ($totalErrores > 0) {
            $this->warn("⚠️  Se encontraron {$totalErrores} errores en el sistema");
            return 1;
        }

        $this->info('🎉 Validación completada exitosamente - No se encontraron errores críticos');
        return 0;
    }

    /**
     * Obtener emoji para tipo de entidad
     */
    private function getTipoEmoji(string $tipo): string
    {
        return match($tipo) {
            'documentos' => '📄',
            'expedientes' => '📁',
            'series' => '📋',
            default => '📝'
        };
    }
}
