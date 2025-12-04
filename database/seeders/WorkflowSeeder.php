<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Workflow;
use App\Models\User;

class WorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::first();

        if (!$adminUser) {
            $this->command->warn('No hay usuarios en la base de datos. Crea un usuario primero.');
            return;
        }

        $workflows = [
            // 1. Workflow de Aprobación Simple
            [
                'nombre' => 'Aprobación Simple de Documentos',
                'descripcion' => 'Workflow básico con un solo nivel de aprobación',
                'tipo_entidad' => 'App\Models\Documento',
                'pasos' => [
                    [
                        'nombre' => 'Revisión',
                        'descripcion' => 'Revisar y aprobar el documento',
                        'tipo_asignacion' => 'usuario',
                        'asignado_id' => $adminUser->id,
                        'asignado_type' => 'App\Models\User',
                        'dias_vencimiento' => 3,
                    ],
                ],
                'configuracion' => [
                    'requiere_observaciones' => false,
                    'permite_delegacion' => true,
                    'notificar_vencimiento' => true,
                ],
                'activo' => true,
                'usuario_creador_id' => $adminUser->id,
            ],

            // 2. Workflow de Aprobación en Cadena
            [
                'nombre' => 'Aprobación en Cadena (3 niveles)',
                'descripcion' => 'Workflow con tres niveles de aprobación secuencial',
                'tipo_entidad' => 'App\Models\Documento',
                'pasos' => [
                    [
                        'nombre' => 'Revisión Técnica',
                        'descripcion' => 'Primera revisión técnica del documento',
                        'tipo_asignacion' => 'usuario',
                        'asignado_id' => $adminUser->id,
                        'asignado_type' => 'App\Models\User',
                        'dias_vencimiento' => 2,
                    ],
                    [
                        'nombre' => 'Revisión Legal',
                        'descripcion' => 'Revisión legal y de cumplimiento',
                        'tipo_asignacion' => 'usuario',
                        'asignado_id' => $adminUser->id,
                        'asignado_type' => 'App\Models\User',
                        'dias_vencimiento' => 3,
                    ],
                    [
                        'nombre' => 'Aprobación Final',
                        'descripcion' => 'Aprobación final por la dirección',
                        'tipo_asignacion' => 'usuario',
                        'asignado_id' => $adminUser->id,
                        'asignado_type' => 'App\Models\User',
                        'dias_vencimiento' => 2,
                    ],
                ],
                'configuracion' => [
                    'requiere_observaciones' => true,
                    'permite_delegacion' => false,
                    'notificar_vencimiento' => true,
                    'escalamiento_automatico' => true,
                ],
                'activo' => true,
                'usuario_creador_id' => $adminUser->id,
            ],

            // 3. Workflow de Revisión y Corrección
            [
                'nombre' => 'Revisión con Posibilidad de Corrección',
                'descripcion' => 'Permite solicitar correcciones antes de la aprobación final',
                'tipo_entidad' => 'App\Models\Documento',
                'pasos' => [
                    [
                        'nombre' => 'Revisión Inicial',
                        'descripcion' => 'Revisar el documento y solicitar correcciones si es necesario',
                        'tipo_asignacion' => 'usuario',
                        'asignado_id' => $adminUser->id,
                        'asignado_type' => 'App\Models\User',
                        'dias_vencimiento' => 2,
                    ],
                    [
                        'nombre' => 'Verificación de Correcciones',
                        'descripcion' => 'Verificar que las correcciones se hayan realizado',
                        'tipo_asignacion' => 'usuario',
                        'asignado_id' => $adminUser->id,
                        'asignado_type' => 'App\Models\User',
                        'dias_vencimiento' => 2,
                    ],
                ],
                'configuracion' => [
                    'requiere_observaciones' => true,
                    'permite_rechazo_con_correccion' => true,
                    'notificar_vencimiento' => true,
                ],
                'activo' => true,
                'usuario_creador_id' => $adminUser->id,
            ],

            // 4. Workflow de Firma Digital
            [
                'nombre' => 'Proceso de Firma Digital',
                'descripcion' => 'Workflow para firma digital de documentos importantes',
                'tipo_entidad' => 'App\Models\Documento',
                'pasos' => [
                    [
                        'nombre' => 'Preparación de Documento',
                        'descripcion' => 'Verificar que el documento esté listo para firma',
                        'tipo_asignacion' => 'usuario',
                        'asignado_id' => $adminUser->id,
                        'asignado_type' => 'App\Models\User',
                        'dias_vencimiento' => 1,
                    ],
                    [
                        'nombre' => 'Firma del Solicitante',
                        'descripcion' => 'Firma digital del solicitante',
                        'tipo_asignacion' => 'usuario',
                        'asignado_id' => $adminUser->id,
                        'asignado_type' => 'App\Models\User',
                        'dias_vencimiento' => 2,
                    ],
                    [
                        'nombre' => 'Firma del Autorizador',
                        'descripcion' => 'Firma digital del autorizador',
                        'tipo_asignacion' => 'usuario',
                        'asignado_id' => $adminUser->id,
                        'asignado_type' => 'App\Models\User',
                        'dias_vencimiento' => 3,
                    ],
                ],
                'configuracion' => [
                    'requiere_firma_digital' => true,
                    'genera_certificado' => true,
                    'notificar_vencimiento' => true,
                ],
                'activo' => true,
                'usuario_creador_id' => $adminUser->id,
            ],

            // 5. Workflow de Expedientes
            [
                'nombre' => 'Aprobación de Expedientes',
                'descripcion' => 'Workflow específico para la aprobación de expedientes completos',
                'tipo_entidad' => 'App\Models\Expediente',
                'pasos' => [
                    [
                        'nombre' => 'Verificación de Completitud',
                        'descripcion' => 'Verificar que el expediente tenga todos los documentos requeridos',
                        'tipo_asignacion' => 'usuario',
                        'asignado_id' => $adminUser->id,
                        'asignado_type' => 'App\Models\User',
                        'dias_vencimiento' => 3,
                    ],
                    [
                        'nombre' => 'Aprobación del Expediente',
                        'descripcion' => 'Aprobar el expediente para su archivo',
                        'tipo_asignacion' => 'usuario',
                        'asignado_id' => $adminUser->id,
                        'asignado_type' => 'App\Models\User',
                        'dias_vencimiento' => 5,
                    ],
                ],
                'configuracion' => [
                    'requiere_observaciones' => false,
                    'verifica_documentos_completos' => true,
                    'notificar_vencimiento' => true,
                ],
                'activo' => true,
                'usuario_creador_id' => $adminUser->id,
            ],
        ];

        foreach ($workflows as $workflow) {
            Workflow::create($workflow);
        }

        $this->command->info('✅ Se crearon ' . count($workflows) . ' workflows predefinidos');
    }
}
