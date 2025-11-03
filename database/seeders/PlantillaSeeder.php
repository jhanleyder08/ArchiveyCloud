<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PlantillaDocumento;
use App\Models\User;

class PlantillaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::first(); // Asumir que existe al menos un usuario

        if (!$adminUser) {
            $this->command->warn('No hay usuarios en la base de datos. Crea un usuario primero.');
            return;
        }

        $plantillas = [
            // 1. Plantilla de Contrato
            [
                'nombre' => 'Contrato de Servicios Profesionales',
                'descripcion' => 'Plantilla estándar para contratos de servicios profesionales',
                'categoria' => 'contrato',
                'tipo_documento' => 'CONTRATO',
                'contenido_html' => $this->getContratoHTML(),
                'campos_variables' => [
                    ['nombre' => 'contratante', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'Nombre del Contratante'],
                    ['nombre' => 'contratista', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'Nombre del Contratista'],
                    ['nombre' => 'nit_contratante', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'NIT Contratante'],
                    ['nombre' => 'nit_contratista', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'NIT Contratista'],
                    ['nombre' => 'objeto', 'tipo' => 'textarea', 'requerido' => true, 'etiqueta' => 'Objeto del Contrato'],
                    ['nombre' => 'valor', 'tipo' => 'number', 'requerido' => true, 'etiqueta' => 'Valor del Contrato'],
                    ['nombre' => 'plazo', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'Plazo de Ejecución'],
                    ['nombre' => 'fecha_inicio', 'tipo' => 'date', 'requerido' => true, 'etiqueta' => 'Fecha de Inicio'],
                ],
                'es_publica' => true,
                'usuario_creador_id' => $adminUser->id,
                'tags' => ['contrato', 'servicios', 'legal'],
            ],

            // 2. Plantilla de Oficio
            [
                'nombre' => 'Oficio Estándar',
                'descripcion' => 'Plantilla para oficios y comunicaciones formales',
                'categoria' => 'oficio',
                'tipo_documento' => 'OFICIO',
                'contenido_html' => $this->getOficioHTML(),
                'campos_variables' => [
                    ['nombre' => 'numero_oficio', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'Número de Oficio'],
                    ['nombre' => 'destinatario', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'Destinatario'],
                    ['nombre' => 'cargo_destinatario', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'Cargo'],
                    ['nombre' => 'asunto', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'Asunto'],
                    ['nombre' => 'cuerpo', 'tipo' => 'textarea', 'requerido' => true, 'etiqueta' => 'Cuerpo del Oficio'],
                    ['nombre' => 'ciudad', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'Ciudad'],
                    ['nombre' => 'fecha', 'tipo' => 'date', 'requerido' => true, 'etiqueta' => 'Fecha'],
                    ['nombre' => 'remitente', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'Remitente'],
                    ['nombre' => 'cargo_remitente', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'Cargo Remitente'],
                ],
                'es_publica' => true,
                'usuario_creador_id' => $adminUser->id,
                'tags' => ['oficio', 'comunicacion', 'formal'],
            ],

            // 3. Plantilla de Acta de Reunión
            [
                'nombre' => 'Acta de Reunión',
                'descripcion' => 'Plantilla para documentar reuniones y acuerdos',
                'categoria' => 'acta',
                'tipo_documento' => 'ACTA',
                'contenido_html' => $this->getActaHTML(),
                'campos_variables' => [
                    ['nombre' => 'numero_acta', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'Número de Acta'],
                    ['nombre' => 'fecha', 'tipo' => 'date', 'requerido' => true, 'etiqueta' => 'Fecha'],
                    ['nombre' => 'hora_inicio', 'tipo' => 'time', 'requerido' => true, 'etiqueta' => 'Hora de Inicio'],
                    ['nombre' => 'hora_fin', 'tipo' => 'time', 'requerido' => true, 'etiqueta' => 'Hora de Fin'],
                    ['nombre' => 'lugar', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'Lugar'],
                    ['nombre' => 'asistentes', 'tipo' => 'textarea', 'requerido' => true, 'etiqueta' => 'Asistentes'],
                    ['nombre' => 'orden_dia', 'tipo' => 'textarea', 'requerido' => true, 'etiqueta' => 'Orden del Día'],
                    ['nombre' => 'desarrollo', 'tipo' => 'textarea', 'requerido' => true, 'etiqueta' => 'Desarrollo'],
                    ['nombre' => 'acuerdos', 'tipo' => 'textarea', 'requerido' => true, 'etiqueta' => 'Acuerdos y Compromisos'],
                ],
                'es_publica' => true,
                'usuario_creador_id' => $adminUser->id,
                'tags' => ['acta', 'reunion', 'minuta'],
            ],

            // 4. Plantilla de Memorando
            [
                'nombre' => 'Memorando Interno',
                'descripcion' => 'Plantilla para memorandos de comunicación interna',
                'categoria' => 'memorando',
                'tipo_documento' => 'MEMORANDO',
                'contenido_html' => $this->getMemorandoHTML(),
                'campos_variables' => [
                    ['nombre' => 'numero', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'Número'],
                    ['nombre' => 'para', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'Para'],
                    ['nombre' => 'de', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'De'],
                    ['nombre' => 'fecha', 'tipo' => 'date', 'requerido' => true, 'etiqueta' => 'Fecha'],
                    ['nombre' => 'asunto', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'Asunto'],
                    ['nombre' => 'mensaje', 'tipo' => 'textarea', 'requerido' => true, 'etiqueta' => 'Mensaje'],
                ],
                'es_publica' => true,
                'usuario_creador_id' => $adminUser->id,
                'tags' => ['memorando', 'interno', 'comunicacion'],
            ],

            // 5. Plantilla de Certificado
            [
                'nombre' => 'Certificado Laboral',
                'descripcion' => 'Plantilla para certificados laborales',
                'categoria' => 'certificado',
                'tipo_documento' => 'CERTIFICADO',
                'contenido_html' => $this->getCertificadoHTML(),
                'campos_variables' => [
                    ['nombre' => 'numero', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'Número'],
                    ['nombre' => 'empresa', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'Nombre de la Empresa'],
                    ['nombre' => 'nit_empresa', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'NIT Empresa'],
                    ['nombre' => 'empleado', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'Nombre del Empleado'],
                    ['nombre' => 'identificacion', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'Identificación'],
                    ['nombre' => 'cargo', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'Cargo'],
                    ['nombre' => 'fecha_ingreso', 'tipo' => 'date', 'requerido' => true, 'etiqueta' => 'Fecha de Ingreso'],
                    ['nombre' => 'salario', 'tipo' => 'number', 'requerido' => false, 'etiqueta' => 'Salario'],
                    ['nombre' => 'ciudad', 'tipo' => 'text', 'requerido' => true, 'etiqueta' => 'Ciudad'],
                    ['nombre' => 'fecha_expedicion', 'tipo' => 'date', 'requerido' => true, 'etiqueta' => 'Fecha de Expedición'],
                ],
                'es_publica' => true,
                'usuario_creador_id' => $adminUser->id,
                'tags' => ['certificado', 'laboral', 'rrhh'],
            ],
        ];

        foreach ($plantillas as $plantilla) {
            PlantillaDocumento::create($plantilla);
        }

        $this->command->info('✅ Se crearon ' . count($plantillas) . ' plantillas predefinidas');
    }

    private function getContratoHTML(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto;">
    <h1 style="text-align: center;">CONTRATO DE PRESTACIÓN DE SERVICIOS PROFESIONALES</h1>
    
    <p>Entre los suscritos a saber: <strong>{{contratante}}</strong>, identificado con NIT <strong>{{nit_contratante}}</strong>, quien en adelante se denominará <strong>EL CONTRATANTE</strong>, y <strong>{{contratista}}</strong>, identificado con NIT <strong>{{nit_contratista}}</strong>, quien en adelante se denominará <strong>EL CONTRATISTA</strong>, se ha celebrado el presente contrato de prestación de servicios profesionales, el cual se regirá por las siguientes cláusulas:</p>
    
    <h3>PRIMERA: OBJETO</h3>
    <p>{{objeto}}</p>
    
    <h3>SEGUNDA: VALOR</h3>
    <p>El valor del presente contrato es de <strong>{{valor}}</strong> pesos, que serán cancelados de la siguiente manera: [Forma de pago].</p>
    
    <h3>TERCERA: PLAZO</h3>
    <p>El plazo de ejecución del presente contrato es de <strong>{{plazo}}</strong>, contados a partir del <strong>{{fecha_inicio}}</strong>.</p>
    
    <h3>CUARTA: OBLIGACIONES DEL CONTRATISTA</h3>
    <ul>
        <li>Cumplir con el objeto del contrato en las condiciones establecidas.</li>
        <li>Informar oportunamente sobre el desarrollo de las actividades.</li>
        <li>Mantener la confidencialidad de la información suministrada.</li>
    </ul>
    
    <h3>QUINTA: OBLIGACIONES DEL CONTRATANTE</h3>
    <ul>
        <li>Pagar el valor acordado en los términos establecidos.</li>
        <li>Suministrar la información necesaria para la ejecución del contrato.</li>
    </ul>
    
    <div style="margin-top: 80px;">
        <div style="display: inline-block; width: 45%;">
            <p>_______________________________</p>
            <p><strong>EL CONTRATANTE</strong></p>
            <p>{{contratante}}</p>
        </div>
        <div style="display: inline-block; width: 45%; float: right;">
            <p>_______________________________</p>
            <p><strong>EL CONTRATISTA</strong></p>
            <p>{{contratista}}</p>
        </div>
    </div>
</div>
HTML;
    }

    private function getOficioHTML(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto;">
    <div style="text-align: right;">
        <p>{{ciudad}}, {{fecha}}</p>
        <p><strong>Oficio No. {{numero_oficio}}</strong></p>
    </div>
    
    <div style="margin-top: 40px;">
        <p><strong>Señor(a):</strong><br>
        {{destinatario}}<br>
        {{cargo_destinatario}}</p>
    </div>
    
    <div style="margin-top: 30px;">
        <p><strong>Asunto:</strong> {{asunto}}</p>
    </div>
    
    <div style="margin-top: 30px; text-align: justify;">
        {{cuerpo}}
    </div>
    
    <div style="margin-top: 60px;">
        <p>Cordialmente,</p>
        <div style="margin-top: 80px;">
            <p>_______________________________</p>
            <p><strong>{{remitente}}</strong><br>
            {{cargo_remitente}}</p>
        </div>
    </div>
</div>
HTML;
    }

    private function getActaHTML(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto;">
    <h1 style="text-align: center;">ACTA DE REUNIÓN</h1>
    <p style="text-align: center;"><strong>No. {{numero_acta}}</strong></p>
    
    <table style="width: 100%; margin-top: 30px;">
        <tr>
            <td><strong>Fecha:</strong></td>
            <td>{{fecha}}</td>
        </tr>
        <tr>
            <td><strong>Hora de Inicio:</strong></td>
            <td>{{hora_inicio}}</td>
        </tr>
        <tr>
            <td><strong>Hora de Finalización:</strong></td>
            <td>{{hora_fin}}</td>
        </tr>
        <tr>
            <td><strong>Lugar:</strong></td>
            <td>{{lugar}}</td>
        </tr>
    </table>
    
    <h3>ASISTENTES</h3>
    <p style="text-align: justify;">{{asistentes}}</p>
    
    <h3>ORDEN DEL DÍA</h3>
    <p style="text-align: justify;">{{orden_dia}}</p>
    
    <h3>DESARROLLO</h3>
    <p style="text-align: justify;">{{desarrollo}}</p>
    
    <h3>ACUERDOS Y COMPROMISOS</h3>
    <p style="text-align: justify;">{{acuerdos}}</p>
    
    <div style="margin-top: 60px;">
        <p>Siendo las {{hora_fin}} se da por terminada la reunión.</p>
    </div>
</div>
HTML;
    }

    private function getMemorandoHTML(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto;">
    <h1 style="text-align: center;">MEMORANDO</h1>
    <p style="text-align: center;"><strong>No. {{numero}}</strong></p>
    
    <table style="width: 100%; margin-top: 30px; border-collapse: collapse;">
        <tr style="border-bottom: 1px solid #ccc;">
            <td style="padding: 10px;"><strong>PARA:</strong></td>
            <td style="padding: 10px;">{{para}}</td>
        </tr>
        <tr style="border-bottom: 1px solid #ccc;">
            <td style="padding: 10px;"><strong>DE:</strong></td>
            <td style="padding: 10px;">{{de}}</td>
        </tr>
        <tr style="border-bottom: 1px solid #ccc;">
            <td style="padding: 10px;"><strong>FECHA:</strong></td>
            <td style="padding: 10px;">{{fecha}}</td>
        </tr>
        <tr style="border-bottom: 1px solid #ccc;">
            <td style="padding: 10px;"><strong>ASUNTO:</strong></td>
            <td style="padding: 10px;">{{asunto}}</td>
        </tr>
    </table>
    
    <div style="margin-top: 40px; text-align: justify;">
        {{mensaje}}
    </div>
</div>
HTML;
    }

    private function getCertificadoHTML(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto; border: 2px solid #333;">
    <h1 style="text-align: center; margin-bottom: 10px;">CERTIFICADO LABORAL</h1>
    <p style="text-align: center;"><strong>No. {{numero}}</strong></p>
    
    <div style="margin-top: 40px; text-align: justify;">
        <p><strong>{{empresa}}</strong>, identificada con NIT <strong>{{nit_empresa}}</strong></p>
        
        <h3 style="text-align: center;">CERTIFICA QUE:</h3>
        
        <p><strong>{{empleado}}</strong>, identificado(a) con cédula de ciudadanía No. <strong>{{identificacion}}</strong>, labora en esta empresa desde el <strong>{{fecha_ingreso}}</strong>, desempeñando el cargo de <strong>{{cargo}}</strong>.</p>
        
        <p>El trabajador se encuentra vinculado mediante contrato [tipo de contrato], con un salario mensual de <strong>{{salario}}</strong>.</p>
        
        <p>La presente certificación se expide a solicitud del interesado en la ciudad de <strong>{{ciudad}}</strong>, el <strong>{{fecha_expedicion}}</strong>.</p>
    </div>
    
    <div style="margin-top: 100px; text-align: center;">
        <p>_______________________________</p>
        <p><strong>RECURSOS HUMANOS</strong></p>
        <p>{{empresa}}</p>
    </div>
</div>
HTML;
    }
}
