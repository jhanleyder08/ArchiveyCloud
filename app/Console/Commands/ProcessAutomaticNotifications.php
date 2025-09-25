<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Expediente;
use App\Models\Documento;
use App\Models\Notificacion;
use App\Models\User;
use App\Services\NotificacionEmailService;
use App\Services\NotificacionSmsService;
use Carbon\Carbon;

class ProcessAutomaticNotifications extends Command
{
    /**
     * The name and signature de del comando de consola.
     *
     * @var string
     */
    protected $signature = 'notifications:process-automatic';

    /**
     * La descripción del comando de consola.
     *
     * @var string
     */
    protected $description = 'Procesa notificaciones automáticas del sistema (expedientes próximos a vencer, alertas críticas, etc.)';

    private $emailService;
    private $smsService;

    public function __construct()
    {
        parent::__construct();
        $this->emailService = new NotificacionEmailService();
        $this->smsService = new NotificacionSmsService();
    }

    /**
     * Ejecuta el comando de consola.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando procesamiento de notificaciones automáticas...');
        
        $totalNotificaciones = 0;
        $emailsEnviados = 0;
        $smsEnviados = 0;

        // 1. Verificar expedientes próximos a vencer
        $totalNotificaciones += $this->procesarExpedientesProximosVencer();

        // 2. Verificar expedientes vencidos
        $totalNotificaciones += $this->procesarExpedientesVencidos();

        // 3. Verificar documentos sin procesar
        $totalNotificaciones += $this->procesarDocumentosSinProcesar();

        // 4. Verificar alertas del sistema
        $totalNotificaciones += $this->procesarAlertasSistema();

        // 5. Enviar emails para notificaciones críticas y altas
        $emailsEnviados = $this->enviarEmailsNotificacionesCriticas();

        // 6. Enviar SMS para notificaciones críticas
        $smsEnviados = $this->enviarSmsNotificacionesCriticas();

        $this->info("✅ Procesamiento completado:");
        $this->line("   📊 Total de notificaciones generadas: {$totalNotificaciones}");
        $this->line("   📧 Emails enviados: {$emailsEnviados}");
        $this->line("   📱 SMS enviados: {$smsEnviados}");
        
        return Command::SUCCESS;
    }

    /**
     * Procesa expedientes próximos a vencer (30 días)
     */
    private function procesarExpedientesProximosVencer(): int
    {
        $this->line('📋 Verificando expedientes próximos a vencer...');
        
        $fechaLimite = Carbon::now()->addDays(30);
        $expedientes = Expediente::where('estado_ciclo_vida', 'gestion')
            ->whereDate('created_at', '<=', Carbon::now()->subYears(2)->addDays(30))
            ->whereDoesntHave('notificaciones', function ($query) {
                $query->where('tipo', 'expediente_proximo_vencer')
                      ->where('created_at', '>=', Carbon::now()->subDays(7)); // No enviar si ya se envió en los últimos 7 días
            })
            ->get();

        $count = 0;
        foreach ($expedientes as $expediente) {
            $this->crearNotificacion([
                'tipo' => 'expediente_proximo_vencer',
                'titulo' => 'Expediente próximo a vencer',
                'mensaje' => "El expediente '{$expediente->numero_expediente}' está próximo a pasar a estado central. Revise si requiere acción.",
                'prioridad' => 'alta',
                'relacionado_id' => $expediente->id,
                'relacionado_tipo' => 'App\Models\Expediente',
                'user_id' => $expediente->productor_id,
            ]);
            $count++;
        }

        $this->line("   → {$count} expedientes próximos a vencer procesados");
        return $count;
    }

    /**
     * Procesa expedientes ya vencidos
     */
    private function procesarExpedientesVencidos(): int
    {
        $this->line('⚠️  Verificando expedientes vencidos...');
        
        $expedientes = Expediente::where('estado_ciclo_vida', 'gestion')
            ->whereDate('created_at', '<=', Carbon::now()->subYears(2))
            ->whereDoesntHave('notificaciones', function ($query) {
                $query->where('tipo', 'expediente_vencido')
                      ->where('created_at', '>=', Carbon::now()->subDays(3)); // No enviar si ya se envió en los últimos 3 días
            })
            ->get();

        $count = 0;
        foreach ($expedientes as $expediente) {
            $this->crearNotificacion([
                'tipo' => 'expediente_vencido',
                'titulo' => 'Expediente vencido - Acción requerida',
                'mensaje' => "El expediente '{$expediente->numero_expediente}' ha superado el tiempo de gestión. Debe cambiar a estado central urgentemente.",
                'prioridad' => 'critica',
                'relacionado_id' => $expediente->id,
                'relacionado_tipo' => 'App\Models\Expediente',
                'user_id' => $expediente->productor_id,
            ]);
            $count++;
        }

        $this->line("   → {$count} expedientes vencidos procesados");
        return $count;
    }

    /**
     * Procesa documentos sin procesar hace más de 48 horas
     */
    private function procesarDocumentosSinProcesar(): int
    {
        $this->line('📄 Verificando documentos sin procesar...');
        
        $documentos = Documento::whereNull('expediente_id')
            ->where('created_at', '<=', Carbon::now()->subHours(48))
            ->whereDoesntHave('notificaciones', function ($query) {
                $query->where('tipo', 'documento_sin_procesar')
                      ->where('created_at', '>=', Carbon::now()->subDays(1));
            })
            ->get();

        $count = 0;
        foreach ($documentos as $documento) {
            // Notificar a administradores
            $admins = User::whereHas('role', function ($query) {
                $query->whereIn('name', ['Super Administrador', 'Administrador SGDEA']);
            })->get();

            foreach ($admins as $admin) {
                $this->crearNotificacion([
                    'tipo' => 'documento_sin_procesar',
                    'titulo' => 'Documento sin asignar a expediente',
                    'mensaje' => "El documento '{$documento->nombre_archivo}' lleva más de 48 horas sin ser asignado a un expediente.",
                    'prioridad' => 'media',
                    'relacionado_id' => $documento->id,
                    'relacionado_tipo' => 'App\Models\Documento',
                    'user_id' => $admin->id,
                ]);
            }
            $count++;
        }

        $this->line("   → {$count} documentos sin procesar notificados");
        return $count;
    }

    /**
     * Procesa alertas generales del sistema
     */
    private function procesarAlertasSistema(): int
    {
        $this->line('🔔 Verificando alertas del sistema...');
        
        $count = 0;

        // Verificar espacio en disco (simulado)
        $espacioDisponible = 85; // Porcentaje usado simulado
        if ($espacioDisponible > 80) {
            $admins = User::whereHas('role', function ($query) {
                $query->whereIn('name', ['Super Administrador', 'Administrador SGDEA']);
            })->get();

            foreach ($admins as $admin) {
                // Verificar si no se ha enviado esta alerta en los últimos días
                $alertaExistente = Notificacion::where('tipo', 'sistema_espacio_disco')
                    ->where('user_id', $admin->id)
                    ->where('created_at', '>=', Carbon::now()->subDays(7))
                    ->exists();

                if (!$alertaExistente) {
                    $this->crearNotificacion([
                        'tipo' => 'sistema_espacio_disco',
                        'titulo' => 'Alerta: Espacio en disco',
                        'mensaje' => "El espacio en disco está al {$espacioDisponible}%. Considere limpiar archivos o expandir almacenamiento.",
                        'prioridad' => 'media',
                        'user_id' => $admin->id,
                    ]);
                    $count++;
                }
            }
        }

        $this->line("   → {$count} alertas del sistema procesadas");
        return $count;
    }

    /**
     * Enviar emails para notificaciones críticas y altas
     */
    private function enviarEmailsNotificacionesCriticas(): int
    {
        $this->line('📧 Enviando emails para notificaciones críticas...');

        // Obtener notificaciones críticas y altas recientes no procesadas para email
        $notificaciones = Notificacion::whereIn('prioridad', ['critica', 'alta'])
            ->where('estado', 'pendiente')
            ->where('created_at', '>=', now()->subMinutes(10)) // Solo últimos 10 minutos
            ->whereDoesntHave('relacionado', function ($query) {
                // Evitar duplicados - esto sería mejor con una tabla de emails enviados
                $query->where('created_at', '>=', now()->subHour());
            })
            ->with('user')
            ->get();

        $emailsEnviados = 0;

        foreach ($notificaciones as $notificacion) {
            if ($notificacion->user) {
                try {
                    $enviado = $this->emailService->enviarNotificacion($notificacion);
                    if ($enviado) {
                        $emailsEnviados++;
                    }
                } catch (\Exception $e) {
                    $this->error("Error enviando email para notificación {$notificacion->id}: {$e->getMessage()}");
                }
            }
        }

        $this->line("   → {$emailsEnviados} emails enviados para notificaciones críticas");
        return $emailsEnviados;
    }

    /**
     * Enviar SMS para notificaciones críticas únicamente
     */
    private function enviarSmsNotificacionesCriticas(): int
    {
        $this->line('📱 Enviando SMS para notificaciones críticas...');

        // Obtener solo notificaciones críticas recientes
        $notificaciones = Notificacion::where('prioridad', 'critica')
            ->where('estado', 'pendiente')
            ->where('created_at', '>=', now()->subMinutes(10)) // Solo últimos 10 minutos
            ->with('user')
            ->get();

        $smsEnviados = 0;

        foreach ($notificaciones as $notificacion) {
            if ($notificacion->user && $notificacion->user->telefono) {
                try {
                    $enviado = $this->smsService->enviarSms($notificacion);
                    if ($enviado) {
                        $smsEnviados++;
                    }
                } catch (\Exception $e) {
                    $this->error("Error enviando SMS para notificación {$notificacion->id}: {$e->getMessage()}");
                }
            }
        }

        $this->line("   → {$smsEnviados} SMS enviados para notificaciones críticas");
        return $smsEnviados;
    }

    /**
     * Crea una notificación en la base de datos
     */
    private function crearNotificacion(array $datos): void
    {
        Notificacion::create(array_merge($datos, [
            'estado' => 'pendiente',
            'es_automatica' => true,
        ]));
    }
}
