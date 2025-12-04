<?php

namespace App\Console\Commands;

use App\Models\CertificadoDigital;
use App\Services\CertificateManagementService;
use App\Events\CertificateExpiringEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Comando para verificación periódica de certificados digitales
 */
class VerifyCertificatesCommand extends Command
{
    protected $signature = 'certificates:verify 
                           {--all : Verificar todos los certificados}
                           {--expiring : Solo verificar certificados próximos a vencer}
                           {--days=30 : Días de anticipación para vencimiento}
                           {--revocation : Verificar estado de revocación}
                           {--batch-size=50 : Tamaño del lote para procesamiento}';

    protected $description = 'Verificar estado y validez de certificados digitales';

    protected CertificateManagementService $certificateService;
    protected array $estadisticas = [
        'procesados' => 0,
        'validos' => 0,
        'expirados' => 0,
        'proximos_vencer' => 0,
        'revocados' => 0,
        'errores' => 0
    ];

    public function __construct(CertificateManagementService $certificateService)
    {
        parent::__construct();
        $this->certificateService = $certificateService;
    }

    public function handle(): int
    {
        $this->info('🔐 Iniciando verificación de certificados digitales...');
        $this->line('');

        $inicioVerificacion = now();
        
        try {
            if ($this->option('expiring')) {
                $this->verificarProximosVencimientos();
            } elseif ($this->option('all')) {
                $this->verificarTodosLosCertificados();
            } else {
                $this->verificarCertificadosActivos();
            }

            $duracion = $inicioVerificacion->diffInSeconds(now());
            $this->mostrarResumen($duracion);
            
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error durante la verificación: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Verificar certificados próximos a vencer
     */
    private function verificarProximosVencimientos(): void
    {
        $diasAnticipacion = (int) $this->option('days');
        $this->info("🕐 Verificando certificados que vencen en los próximos {$diasAnticipacion} días...");

        $proximosVencimientos = $this->certificateService->verificarProximosVencimientos($diasAnticipacion);
        
        $this->estadisticas['proximos_vencer'] = $proximosVencimientos['total_proximos'];

        if ($proximosVencimientos['total_proximos'] > 0) {
            $this->warn("⚠️  Encontrados {$proximosVencimientos['total_proximos']} certificados próximos a vencer:");
            
            $table = [];
            foreach ($proximosVencimientos['certificados'] as $cert) {
                $table[] = [
                    substr($cert['subject'], 0, 50),
                    $cert['propietario'] ?? 'N/A',
                    $cert['fecha_vencimiento'],
                    $cert['dias_restantes'],
                    $this->formatearUrgencia($cert['urgencia'])
                ];

                // Disparar evento para notificaciones
                $certificado = CertificadoDigital::find($cert['id']);
                if ($certificado) {
                    event(new CertificateExpiringEvent($certificado, $cert['dias_restantes']));
                }
            }

            $this->table(
                ['Subject', 'Propietario', 'Vencimiento', 'Días', 'Urgencia'],
                $table
            );
        } else {
            $this->info('✅ No hay certificados próximos a vencer');
        }
    }

    /**
     * Verificar todos los certificados
     */
    private function verificarTodosLosCertificados(): void
    {
        $this->info('🔍 Verificando todos los certificados...');
        
        $totalCertificados = CertificadoDigital::count();
        $batchSize = (int) $this->option('batch-size');
        
        $this->info("Total de certificados: {$totalCertificados}");
        $bar = $this->output->createProgressBar($totalCertificados);
        $bar->start();

        CertificadoDigital::with('usuario')
            ->chunk($batchSize, function (Collection $certificados) use ($bar) {
                foreach ($certificados as $certificado) {
                    $this->verificarCertificadoIndividual($certificado);
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->line('');
    }

    /**
     * Verificar solo certificados activos
     */
    private function verificarCertificadosActivos(): void
    {
        $this->info('🔍 Verificando certificados activos...');
        
        $certificadosActivos = CertificadoDigital::where('es_valido', true)
            ->where('fecha_vencimiento', '>', now())
            ->with('usuario')
            ->get();

        $this->info("Certificados activos: {$certificadosActivos->count()}");
        $bar = $this->output->createProgressBar($certificadosActivos->count());
        $bar->start();

        foreach ($certificadosActivos as $certificado) {
            $this->verificarCertificadoIndividual($certificado);
            $bar->advance();
        }

        $bar->finish();
        $this->line('');
    }

    /**
     * Verificar un certificado individual
     */
    private function verificarCertificadoIndividual(CertificadoDigital $certificado): void
    {
        try {
            $this->estadisticas['procesados']++;

            // Verificar expiración
            if ($certificado->fecha_vencimiento <= now()) {
                $this->estadisticas['expirados']++;
                $certificado->update([
                    'es_valido' => false,
                    'estado' => CertificateManagementService::ESTADO_VENCIDO
                ]);
                return;
            }

            // Verificar próximo vencimiento
            $diasRestantes = now()->diffInDays($certificado->fecha_vencimiento);
            if ($diasRestantes <= 30) {
                $this->estadisticas['proximos_vencer']++;
                event(new CertificateExpiringEvent($certificado, $diasRestantes));
            }

            // Verificar revocación si está habilitado
            if ($this->option('revocation')) {
                $this->verificarRevocacionCertificado($certificado);
            }

            if ($certificado->es_valido && $certificado->estado === CertificateManagementService::ESTADO_VALIDO) {
                $this->estadisticas['validos']++;
            }

        } catch (\Exception $e) {
            $this->estadisticas['errores']++;
            $this->warn("Error verificando certificado {$certificado->id}: {$e->getMessage()}");
        }
    }

    /**
     * Verificar estado de revocación
     */
    private function verificarRevocacionCertificado(CertificadoDigital $certificado): void
    {
        // Verificar por CRL
        $resultadoCRL = $this->certificateService->verificarRevocacionCRL($certificado);
        if ($resultadoCRL['verificado'] && $resultadoCRL['revocado']) {
            $this->estadisticas['revocados']++;
            $certificado->update([
                'es_valido' => false,
                'estado' => CertificateManagementService::ESTADO_REVOCADO,
                'fecha_revocacion' => now()
            ]);
            return;
        }

        // Verificar por OCSP
        $resultadoOCSP = $this->certificateService->verificarRevocacionOCSP($certificado);
        if ($resultadoOCSP['verificado'] && $resultadoOCSP['estado'] === 'revoked') {
            $this->estadisticas['revocados']++;
            $certificado->update([
                'es_valido' => false,
                'estado' => CertificateManagementService::ESTADO_REVOCADO,
                'fecha_revocacion' => now()
            ]);
        }
    }

    /**
     * Mostrar resumen de la verificación
     */
    private function mostrarResumen(int $duracion): void
    {
        $this->line('');
        $this->info('📊 Resumen de Verificación:');
        $this->line('');

        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Certificados procesados', $this->estadisticas['procesados']],
                ['Certificados válidos', $this->estadisticas['validos']],
                ['Certificados expirados', $this->estadisticas['expirados']],
                ['Próximos a vencer', $this->estadisticas['proximos_vencer']],
                ['Certificados revocados', $this->estadisticas['revocados']],
                ['Errores encontrados', $this->estadisticas['errores']]
            ]
        );

        $this->line('');
        $this->info("⏱️  Tiempo total: {$duracion} segundos");

        // Mostrar recomendaciones si hay problemas
        $this->mostrarRecomendaciones();
    }

    /**
     * Mostrar recomendaciones basadas en los resultados
     */
    private function mostrarRecomendaciones(): void
    {
        $recomendaciones = [];

        if ($this->estadisticas['expirados'] > 0) {
            $recomendaciones[] = "⚠️  {$this->estadisticas['expirados']} certificados han expirado y necesitan renovación";
        }

        if ($this->estadisticas['proximos_vencer'] > 0) {
            $recomendaciones[] = "🕐 {$this->estadisticas['proximos_vencer']} certificados vencen pronto - planifique renovaciones";
        }

        if ($this->estadisticas['revocados'] > 0) {
            $recomendaciones[] = "🚫 {$this->estadisticas['revocados']} certificados fueron revocados - contacte a los usuarios";
        }

        if ($this->estadisticas['errores'] > 0) {
            $recomendaciones[] = "❌ {$this->estadisticas['errores']} errores durante verificación - revise logs";
        }

        if (!empty($recomendaciones)) {
            $this->line('');
            $this->warn('📋 Recomendaciones:');
            foreach ($recomendaciones as $recomendacion) {
                $this->line("  • {$recomendacion}");
            }
        } else {
            $this->line('');
            $this->info('🎉 Todos los certificados están en buen estado');
        }
    }

    /**
     * Formatear nivel de urgencia
     */
    private function formatearUrgencia(string $urgencia): string
    {
        return match($urgencia) {
            'critica' => '<fg=red;options=bold>CRÍTICA</>',
            'alta' => '<fg=yellow;options=bold>ALTA</>',
            'media' => '<fg=yellow>Media</>',
            'baja' => '<fg=green>Baja</>',
            default => $urgencia
        };
    }
}
