<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PlantillaDocumental;
use App\Models\User;

class PlantillaDocumentalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el primer usuario disponible (generalmente el admin)
        $usuario = User::first();
        if (!$usuario) {
            $this->command->error('No hay usuarios en el sistema. Ejecuta primero los seeders de usuarios.');
            return;
        }

        $plantillas = [
            [
                'codigo' => 'MEMO-001',
                'nombre' => 'Memorando Básico',
                'descripcion' => 'Plantilla estándar para memorandos internos',
                'categoria' => 'memorando',
                'contenido_html' => '<div class="memorando">
                    <h2 style="text-align: center; margin-bottom: 30px;">MEMORANDO</h2>
                    <p><strong>Para:</strong> {{DESTINATARIO}}</p>
                    <p><strong>De:</strong> {{REMITENTE}}</p>
                    <p><strong>Fecha:</strong> {{FECHA_ACTUAL}}</p>
                    <p><strong>Asunto:</strong> {{ASUNTO}}</p>
                    <br>
                    <div style="text-align: justify;">{{CONTENIDO}}</div>
                </div>',
                'campos_variables' => json_encode([
                    ['nombre' => 'DESTINATARIO', 'tipo' => 'texto', 'etiqueta' => 'Destinatario', 'requerido' => true],
                    ['nombre' => 'REMITENTE', 'tipo' => 'texto', 'etiqueta' => 'Remitente', 'requerido' => true],
                    ['nombre' => 'ASUNTO', 'tipo' => 'texto', 'etiqueta' => 'Asunto', 'requerido' => true],
                    ['nombre' => 'CONTENIDO', 'tipo' => 'textarea', 'etiqueta' => 'Contenido del memorando', 'requerido' => true]
                ]),
                'estado' => 'activa',
                'es_publica' => true,
                'tags' => json_encode(['memorando', 'interno', 'comunicación']),
                'usuario_creador_id' => $usuario->id
            ],
            [
                'codigo' => 'OFIC-001',
                'nombre' => 'Oficio Formal',
                'descripcion' => 'Plantilla para oficios de comunicación externa',
                'categoria' => 'oficio',
                'contenido_html' => '<div class="oficio">
                    <h2 style="text-align: center; margin-bottom: 30px;">OFICIO No. {{NUMERO_OFICIO}}</h2>
                    <p style="text-align: right;">{{LUGAR}}, {{FECHA_ACTUAL}}</p>
                    <br>
                    <p>Señor(a):<br>
                    <strong>{{DESTINATARIO}}</strong><br>
                    {{CARGO}}<br>
                    {{ENTIDAD}}</p>
                    <br>
                    <p><strong>Ref:</strong> {{REFERENCIA}}</p>
                    <br>
                    <div style="text-align: justify;">{{CONTENIDO}}</div>
                    <br><br>
                    <p>Cordialmente,</p>
                    <br><br>
                    <p><strong>{{REMITENTE}}</strong><br>
                    {{CARGO_REMITENTE}}</p>
                </div>',
                'campos_variables' => json_encode([
                    ['nombre' => 'NUMERO_OFICIO', 'tipo' => 'texto', 'etiqueta' => 'Número de Oficio', 'requerido' => true],
                    ['nombre' => 'LUGAR', 'tipo' => 'texto', 'etiqueta' => 'Ciudad/Lugar', 'requerido' => true, 'valor_defecto' => 'Bogotá D.C.'],
                    ['nombre' => 'DESTINATARIO', 'tipo' => 'texto', 'etiqueta' => 'Nombre del destinatario', 'requerido' => true],
                    ['nombre' => 'CARGO', 'tipo' => 'texto', 'etiqueta' => 'Cargo del destinatario', 'requerido' => false],
                    ['nombre' => 'ENTIDAD', 'tipo' => 'texto', 'etiqueta' => 'Entidad/Empresa', 'requerido' => false],
                    ['nombre' => 'REFERENCIA', 'tipo' => 'texto', 'etiqueta' => 'Referencia/Asunto', 'requerido' => true],
                    ['nombre' => 'CONTENIDO', 'tipo' => 'textarea', 'etiqueta' => 'Contenido del oficio', 'requerido' => true],
                    ['nombre' => 'REMITENTE', 'tipo' => 'texto', 'etiqueta' => 'Nombre del remitente', 'requerido' => true],
                    ['nombre' => 'CARGO_REMITENTE', 'tipo' => 'texto', 'etiqueta' => 'Cargo del remitente', 'requerido' => true]
                ]),
                'estado' => 'activa',
                'es_publica' => true,
                'tags' => json_encode(['oficio', 'externo', 'formal']),
                'usuario_creador_id' => $usuario->id
            ],
            [
                'codigo' => 'ACTA-001',
                'nombre' => 'Acta de Reunión',
                'descripcion' => 'Plantilla para actas de reuniones y comités',
                'categoria' => 'acta',
                'contenido_html' => '<div class="acta">
                    <h2 style="text-align: center; margin-bottom: 30px;">ACTA DE REUNIÓN No. {{NUMERO_ACTA}}</h2>
                    <p><strong>Fecha:</strong> {{FECHA_ACTUAL}}</p>
                    <p><strong>Hora:</strong> {{HORA_REUNION}}</p>
                    <p><strong>Lugar:</strong> {{LUGAR}}</p>
                    <p><strong>Participantes:</strong></p>
                    <div>{{PARTICIPANTES}}</div>
                    <br>
                    <p><strong>Orden del día:</strong></p>
                    <div>{{ORDEN_DIA}}</div>
                    <br>
                    <p><strong>Desarrollo:</strong></p>
                    <div style="text-align: justify;">{{DESARROLLO}}</div>
                    <br>
                    <p><strong>Acuerdos y compromisos:</strong></p>
                    <div>{{COMPROMISOS}}</div>
                </div>',
                'campos_variables' => json_encode([
                    ['nombre' => 'NUMERO_ACTA', 'tipo' => 'texto', 'etiqueta' => 'Número de acta', 'requerido' => true],
                    ['nombre' => 'HORA_REUNION', 'tipo' => 'texto', 'etiqueta' => 'Hora de la reunión', 'requerido' => true],
                    ['nombre' => 'LUGAR', 'tipo' => 'texto', 'etiqueta' => 'Lugar de la reunión', 'requerido' => true],
                    ['nombre' => 'PARTICIPANTES', 'tipo' => 'textarea', 'etiqueta' => 'Lista de participantes', 'requerido' => true],
                    ['nombre' => 'ORDEN_DIA', 'tipo' => 'textarea', 'etiqueta' => 'Orden del día', 'requerido' => true],
                    ['nombre' => 'DESARROLLO', 'tipo' => 'textarea', 'etiqueta' => 'Desarrollo de la reunión', 'requerido' => true],
                    ['nombre' => 'COMPROMISOS', 'tipo' => 'textarea', 'etiqueta' => 'Acuerdos y compromisos', 'requerido' => false]
                ]),
                'estado' => 'activa',
                'es_publica' => true,
                'tags' => json_encode(['acta', 'reunión', 'comité']),
                'usuario_creador_id' => $usuario->id
            ],
            [
                'codigo' => 'CIRC-001',
                'nombre' => 'Circular Informativa',
                'descripcion' => 'Plantilla para circulares y comunicados masivos',
                'categoria' => 'circular',
                'contenido_html' => '<div class="circular">
                    <h2 style="text-align: center; margin-bottom: 30px;">CIRCULAR {{NUMERO_CIRCULAR}}</h2>
                    <p style="text-align: right;">{{FECHA_ACTUAL}}</p>
                    <br>
                    <p><strong>Para:</strong> {{DESTINATARIOS}}</p>
                    <p><strong>De:</strong> {{REMITENTE}}</p>
                    <p><strong>Asunto:</strong> {{ASUNTO}}</p>
                    <br>
                    <div style="text-align: justify;">{{CONTENIDO}}</div>
                    <br>
                    <p>{{DESPEDIDA}}</p>
                    <br><br>
                    <p><strong>{{FIRMA}}</strong><br>
                    {{CARGO_FIRMA}}</p>
                </div>',
                'campos_variables' => json_encode([
                    ['nombre' => 'NUMERO_CIRCULAR', 'tipo' => 'texto', 'etiqueta' => 'Número de circular', 'requerido' => true],
                    ['nombre' => 'DESTINATARIOS', 'tipo' => 'texto', 'etiqueta' => 'Destinatarios', 'requerido' => true, 'valor_defecto' => 'Todo el personal'],
                    ['nombre' => 'REMITENTE', 'tipo' => 'texto', 'etiqueta' => 'Remitente', 'requerido' => true],
                    ['nombre' => 'ASUNTO', 'tipo' => 'texto', 'etiqueta' => 'Asunto', 'requerido' => true],
                    ['nombre' => 'CONTENIDO', 'tipo' => 'textarea', 'etiqueta' => 'Contenido de la circular', 'requerido' => true],
                    ['nombre' => 'DESPEDIDA', 'tipo' => 'texto', 'etiqueta' => 'Despedida', 'requerido' => false, 'valor_defecto' => 'Atentamente,'],
                    ['nombre' => 'FIRMA', 'tipo' => 'texto', 'etiqueta' => 'Nombre para firma', 'requerido' => true],
                    ['nombre' => 'CARGO_FIRMA', 'tipo' => 'texto', 'etiqueta' => 'Cargo de quien firma', 'requerido' => true]
                ]),
                'estado' => 'borrador',
                'es_publica' => false,
                'tags' => json_encode(['circular', 'comunicado', 'masivo']),
                'usuario_creador_id' => $usuario->id
            ]
        ];

        foreach ($plantillas as $plantillaData) {
            PlantillaDocumental::create($plantillaData);
        }

        $this->command->info('Plantillas documentales creadas exitosamente.');
    }
}
