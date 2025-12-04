<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Expediente;
use App\Models\Documento;
use App\Models\User;
use App\Models\SerieDocumental;
use App\Models\Notificacion;
use App\Models\WorkflowDocumento;
use App\Models\PrestamoConsulta;
use App\Models\DisposicionFinal;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class GenerarDatosPruebaDashboard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashboard:generar-datos-prueba 
                            {--expedientes=10 : Número de expedientes a crear}
                            {--documentos=50 : Número de documentos a crear}
                            {--usuarios=5 : Número de usuarios adicionales a crear}
                            {--notificaciones=20 : Número de notificaciones a crear}
                            {--workflows=15 : Número de workflows a crear}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera datos de prueba para el Dashboard Ejecutivo';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando generación de datos de prueba para Dashboard Ejecutivo...');
        
        $expedientes = $this->option('expedientes');
        $documentos = $this->option('documentos');
        $usuarios = $this->option('usuarios');
        $notificaciones = $this->option('notificaciones');
        $workflows = $this->option('workflows');
        
        // 1. Crear usuarios adicionales
        $this->info("\n👥 Creando {$usuarios} usuarios adicionales...");
        $usuariosCreados = $this->crearUsuarios($usuarios);
        
        // 2. Crear expedientes con diferentes estados y fechas
        $this->info("\n📁 Creando {$expedientes} expedientes...");
        $expedientesCreados = $this->crearExpedientes($expedientes, $usuariosCreados);
        
        // 3. Crear documentos asociados a expedientes
        $this->info("\n📄 Creando {$documentos} documentos...");
        $documentosCreados = $this->crearDocumentos($documentos, $expedientesCreados, $usuariosCreados);
        
        // 4. Crear notificaciones
        $this->info("\n🔔 Creando {$notificaciones} notificaciones...");
        $this->crearNotificaciones($notificaciones, $usuariosCreados);
        
        // 5. Crear workflows
        $this->info("\n⚡ Creando {$workflows} workflows...");
        $this->crearWorkflows($workflows, $documentosCreados, $usuariosCreados);
        
        // 6. Crear algunos préstamos y disposiciones
        $this->info("\n📋 Creando préstamos y disposiciones...");
        $this->crearPrestamosYDisposiciones($expedientesCreados, $documentosCreados, $usuariosCreados);
        
        $this->info("\n✅ ¡Datos de prueba generados exitosamente!");
        $this->info("📊 Dashboard Ejecutivo listo para usar con datos realistas");
        
        return 0;
    }
    
    private function crearUsuarios($cantidad)
    {
        $usuarios = [];
        $roles = ['gestor_documental', 'productor_documental', 'consultor', 'auditor'];
        
        for ($i = 1; $i <= $cantidad; $i++) {
            $email = "usuario{$i}@archiveycloud.test";
            
            // Verificar si el usuario ya existe
            $usuario = User::where('email', $email)->first();
            
            if (!$usuario) {
                $usuario = User::create([
                    'name' => "Usuario Prueba {$i}",
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'active' => true,
                    'estado_cuenta' => User::ESTADO_ACTIVO,
                    'email_verified_at' => now(),
                    'created_at' => Carbon::now()->subDays(rand(1, 90)),
                ]);
            }
            
            $usuarios[] = $usuario;
        }
        
        $this->info("   ✓ {$cantidad} usuarios creados");
        return $usuarios;
    }
    
    private function crearExpedientes($cantidad, $usuarios)
    {
        $expedientes = [];
        $estados = ['tramite', 'gestion', 'central', 'historico', 'eliminado']; // Estados válidos para estado_ciclo_vida
        $prioridades = ['baja', 'media', 'alta', 'critica'];
        $series = SerieDocumental::all();
        
        if ($series->isEmpty()) {
            $this->warn("   ⚠️  No hay series documentales. Creando una serie básica...");
            $serie = SerieDocumental::create([
                'codigo' => 'SD-PRUEBA',
                'nombre' => 'Serie de Prueba',
                'descripcion' => 'Serie documental creada para pruebas',
                'cuadro_clasificacion_id' => 1,
                'tabla_retencion_id' => 1,
                'tiempo_archivo_gestion' => 2,
                'tiempo_archivo_central' => 5,
                'disposicion_final' => 'conservacion_permanente',
                'activa' => true,
                'created_by' => 1,
            ]);
            $series = collect([$serie]);
        }
        
        for ($i = 1; $i <= $cantidad; $i++) {
            $fechaCreacion = Carbon::now()->subDays(rand(1, 365));
            $numeroExpediente = "EXP-" . str_pad($i, 4, '0', STR_PAD_LEFT);
            
            // Verificar si el expediente ya existe
            $expedienteExistente = \DB::table('expedientes')->where('codigo', $numeroExpediente)->first();
            if ($expedienteExistente) {
                $expedientes[] = $expedienteExistente;
                continue;
            }
            
            $expedienteData = [
                'codigo' => $numeroExpediente,
                'titulo' => "Expediente de prueba número {$i}",
                'descripcion' => "Este es un expediente de prueba creado automáticamente para testing del Dashboard Ejecutivo",
                'estado' => $estados[array_rand($estados)],
                'serie_id' => $series->random()->id,
                'responsable_id' => $usuarios[array_rand($usuarios)]->id,
                'ubicacion_fisica' => "Estante {$i}, Archivo Principal",
                'fecha_apertura' => $fechaCreacion,
                'volumen_actual' => 1,
                'volumen_maximo' => rand(5, 20),
                'tamaño_mb' => rand(10, 1000),
                'metadatos_propios' => json_encode([
                    'palabras_clave' => ['prueba', 'testing', 'dashboard', 'expediente'],
                    'nivel_acceso' => ['publico', 'restringido', 'confidencial'][array_rand(['publico', 'restringido', 'confidencial'])],
                    'prioridad' => $prioridades[array_rand($prioridades)]
                ]),
                'observaciones' => "Expediente generado para testing del Dashboard Ejecutivo",
                'created_by' => $usuarios[array_rand($usuarios)]->id,
                'created_at' => $fechaCreacion,
                'updated_at' => $fechaCreacion,
            ];
            
            // Insertar directamente en la base de datos
            $expedienteId = \DB::table('expedientes')->insertGetId($expedienteData);
            
            // Crear un objeto expediente simple para el arreglo
            $expediente = (object) array_merge($expedienteData, ['id' => $expedienteId]);
            $expedientes[] = $expediente;
        }
        
        $this->info("   ✓ {$cantidad} expedientes creados");
        return $expedientes;
    }
    
    private function crearDocumentos($cantidad, $expedientes, $usuarios)
    {
        $documentos = [];
        $extensiones = ['pdf', 'docx', 'xlsx', 'jpg', 'png', 'mp4', 'txt'];
        
        // Buscar una tipología documental existente o crear una
        $tipologia = null;
        try {
            $tipologia = \DB::table('tipologias_documentales')->first();
            
            if (!$tipologia) {
                $tipologiaId = \DB::table('tipologias_documentales')->insertGetId([
                    'nombre' => 'Documento de Prueba',
                    'descripcion' => 'Tipología creada para documentos de prueba',
                    'activa' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $tipologia = (object) ['id' => $tipologiaId];
            }
        } catch (\Exception $e) {
            // Si no existe la tabla tipologias_documentales, omitir campo
            $this->warn("   ⚠️  Error con tipologías documentales: " . $e->getMessage());
            $tipologia = null;
        }
        
        for ($i = 1; $i <= $cantidad; $i++) {
            $fechaCreacion = Carbon::now()->subDays(rand(1, 180));
            $extension = $extensiones[array_rand($extensiones)];
            $codigoDocumento = "DOC-" . str_pad($i, 4, '0', STR_PAD_LEFT);
            
            // Verificar si el documento ya existe
            $documentoExistente = \DB::table('documentos')->where('codigo_documento', $codigoDocumento)->first();
            if ($documentoExistente) {
                $documentos[] = $documentoExistente;
                continue;
            }
            
            $documentoData = [
                'codigo_documento' => $codigoDocumento,
                'titulo' => "Documento de prueba {$i}",
                'descripcion' => "Documento generado automáticamente para testing",
                'expediente_id' => $expedientes[array_rand($expedientes)]->id,
                'productor_id' => $usuarios[array_rand($usuarios)]->id,
                'version_mayor' => 1,
                'version_menor' => 0,
                'nombre_archivo' => "documento_prueba_{$i}.{$extension}",
                'formato' => $extension,
                'tamano_bytes' => rand(1024, 1024 * 1024 * 10), // Entre 1KB y 10MB
                'fecha_documento' => $fechaCreacion,
                'fecha_captura' => $fechaCreacion,
                'activo' => true,
                'firmado_digitalmente' => rand(0, 1),
                'estado_firma' => rand(0, 1) ? 'firmado' : 'sin_firmar',
                'total_firmas' => rand(0, 3),
                'created_by' => $usuarios[array_rand($usuarios)]->id,
                'created_at' => $fechaCreacion,
                'updated_at' => $fechaCreacion,
            ];
            
            // Añadir tipología solo si existe
            if ($tipologia) {
                $documentoData['tipologia_documental_id'] = $tipologia->id;
            }
            
            // Insertar directamente en la base de datos
            $documentoId = \DB::table('documentos')->insertGetId($documentoData);
            
            // Crear un objeto documento simple para el arreglo
            $documento = (object) array_merge($documentoData, ['id' => $documentoId]);
            $documentos[] = $documento;
        }
        
        $this->info("   ✓ {$cantidad} documentos creados");
        return $documentos;
    }
    
    private function crearNotificaciones($cantidad, $usuarios)
    {
        $tipos = ['expediente', 'documento', 'workflow', 'prestamo', 'disposicion', 'sistema'];
        $prioridades = ['baja', 'media', 'alta', 'critica'];
        $estados = ['pendiente', 'leida', 'archivada'];
        
        for ($i = 1; $i <= $cantidad; $i++) {
            $fechaCreacion = Carbon::now()->subDays(rand(0, 30));
            
            Notificacion::create([
                'user_id' => $usuarios[array_rand($usuarios)]->id,
                'tipo' => $tipos[array_rand($tipos)],
                'titulo' => "Notificación de prueba {$i}",
                'mensaje' => "Esta es una notificación generada automáticamente para testing del Dashboard Ejecutivo.",
                'prioridad' => $prioridades[array_rand($prioridades)],
                'estado' => $estados[array_rand($estados)],
                'accion_url' => '/admin/dashboard-ejecutivo',
                'es_automatica' => true,
                'programada_para' => null,
                'leida_en' => rand(0, 1) ? $fechaCreacion->copy()->addHours(rand(1, 48)) : null,
                'archivada_en' => rand(0, 1) ? $fechaCreacion->copy()->addDays(rand(1, 7)) : null,
                'creado_por' => 1,
                'created_at' => $fechaCreacion,
                'updated_at' => $fechaCreacion,
            ]);
        }
        
        $this->info("   ✓ {$cantidad} notificaciones creadas");
    }
    
    private function crearWorkflows($cantidad, $documentos, $usuarios)
    {
        $estados = ['borrador', 'pendiente', 'en_revision', 'aprobado', 'rechazado'];
        $prioridades = [1, 2, 3, 4]; // 1=alta, 2=media, 3=baja, 4=critica
        
        for ($i = 1; $i <= $cantidad; $i++) {
            $fechaCreacion = Carbon::now()->subDays(rand(1, 60));
            $solicitante = $usuarios[array_rand($usuarios)];
            $revisor = $usuarios[array_rand($usuarios)];
            $aprobador = $usuarios[array_rand($usuarios)];
            
            // Crear workflow usando inserción directa por compatibilidad
            \DB::table('workflow_documentos')->insert([
                'documento_id' => $documentos[array_rand($documentos)]->id,
                'estado' => $estados[array_rand($estados)],
                'solicitante_id' => $solicitante->id,
                'revisor_actual_id' => $revisor->id,
                'aprobador_final_id' => $aprobador->id,
                'niveles_aprobacion' => json_encode([
                    $revisor->id,
                    $aprobador->id,
                ]),
                'nivel_actual' => rand(0, 1),
                'requiere_aprobacion_unanime' => rand(0, 1),
                'fecha_solicitud' => $fechaCreacion,
                'fecha_vencimiento' => $fechaCreacion->copy()->addDays(rand(7, 30)),
                'descripcion_solicitud' => "Workflow de prueba {$i} generado para testing del Dashboard Ejecutivo",
                'prioridad' => $prioridades[array_rand($prioridades)],
                'metadata' => json_encode([
                    'tipo_documento' => 'Documento de Prueba',
                    'departamento' => 'Testing'
                ]),
                'created_at' => $fechaCreacion,
                'updated_at' => $fechaCreacion,
            ]);
        }
        
        $this->info("   ✓ {$cantidad} workflows creados");
    }
    
    private function crearPrestamosYDisposiciones($expedientes, $documentos, $usuarios)
    {
        // Crear algunos préstamos usando inserción directa
        for ($i = 1; $i <= 8; $i++) {
            $fechaCreacion = Carbon::now()->subDays(rand(1, 30));
            $fechaPrestamo = $fechaCreacion->copy()->addDays(1);
            
            \DB::table('prestamos')->insert([
                'expediente_id' => $expedientes[array_rand($expedientes)]->id,
                'documento_id' => null, // Préstamo de expediente completo
                'solicitante_id' => $usuarios[array_rand($usuarios)]->id,
                'prestamista_id' => $usuarios[array_rand($usuarios)]->id,
                'tipo_prestamo' => ['expediente', 'documento'][array_rand(['expediente', 'documento'])],
                'motivo' => "Préstamo de prueba para testing del Dashboard Ejecutivo",
                'estado' => ['prestado', 'devuelto', 'cancelado'][array_rand(['prestado', 'devuelto', 'cancelado'])],
                'estado_devolucion' => rand(0, 1) ? ['bueno', 'dañado', 'perdido'][array_rand(['bueno', 'dañado', 'perdido'])] : null,
                'fecha_prestamo' => $fechaPrestamo,
                'fecha_devolucion_esperada' => $fechaPrestamo->copy()->addDays(rand(7, 30)),
                'fecha_devolucion_real' => null,
                'observaciones' => "Préstamo generado automáticamente para testing",
                'renovaciones' => rand(0, 2),
                'created_at' => $fechaCreacion,
                'updated_at' => $fechaCreacion,
            ]);
        }
        
        // Crear algunas disposiciones finales usando inserción directa
        for ($i = 1; $i <= 5; $i++) {
            $fechaCreacion = Carbon::now()->subDays(rand(1, 60));
            
            \DB::table('disposicion_finals')->insert([
                'expediente_id' => $expedientes[array_rand($expedientes)]->id,
                'documento_id' => null, // Disposición de expediente completo
                'responsable_id' => $usuarios[array_rand($usuarios)]->id,
                'aprobado_por' => null,
                'tipo_disposicion' => ['conservacion_permanente', 'eliminacion_controlada', 'transferencia_historica', 'digitalizacion', 'microfilmacion'][array_rand(['conservacion_permanente', 'eliminacion_controlada', 'transferencia_historica', 'digitalizacion', 'microfilmacion'])],
                'estado' => ['pendiente', 'en_revision', 'aprobado', 'rechazado', 'ejecutado', 'cancelado'][array_rand(['pendiente', 'en_revision', 'aprobado', 'rechazado', 'ejecutado', 'cancelado'])],
                'fecha_vencimiento_retencion' => $fechaCreacion->copy()->addMonths(rand(1, 12)),
                'fecha_propuesta' => $fechaCreacion,
                'fecha_aprobacion' => null,
                'fecha_ejecucion' => null,
                'justificacion' => "Disposición de prueba conforme a normativas de testing del Dashboard Ejecutivo",
                'observaciones' => "Disposición final generada para testing del Dashboard Ejecutivo",
                'observaciones_rechazo' => null,
                'documentos_soporte' => json_encode([]),
                'metadata_proceso' => json_encode(['tipo' => 'testing', 'automatico' => true]),
                'cumple_normativa' => rand(0, 1),
                'validacion_legal' => null,
                'acta_comite' => null,
                'metodo_eliminacion' => null,
                'empresa_ejecutora' => null,
                'certificado_destruccion' => null,
                'created_at' => $fechaCreacion,
                'updated_at' => $fechaCreacion,
            ]);
        }
        
        $this->info("   ✓ 8 préstamos y 5 disposiciones creados");
    }
}
