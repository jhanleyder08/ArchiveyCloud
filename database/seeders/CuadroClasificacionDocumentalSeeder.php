<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CuadroClasificacionDocumental;
use App\Models\User;

class CuadroClasificacionDocumentalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el primer usuario como creador
        $usuario = User::first();
        
        if (!$usuario) {
            $this->command->error('No existe ningún usuario en la base de datos. Ejecute primero php artisan db:seed --class=UserSeeder');
            return;
        }

        // Verificar si ya existen datos
        if (CuadroClasificacionDocumental::count() > 0) {
            $this->command->info('Ya existen datos de CCD. Limpiando...');
            CuadroClasificacionDocumental::query()->delete();
        }

        // Crear datos de prueba para CCD con estructura jerárquica
        
        // NIVEL 1 - FONDO
        $fondo = CuadroClasificacionDocumental::create([
            'codigo' => 'F001',
            'nombre' => 'Fondo Documental de la Alcaldía Municipal',
            'descripcion' => 'Fondo principal que contiene toda la documentación de la entidad',
            'entidad' => 'Alcaldía Municipal de Bogotá',
            'dependencia' => 'Secretaría General',
            'nivel' => 1,
            'padre_id' => null,
            'orden_jerarquico' => 1,
            'estado' => 'activo',
            'activo' => true,
            'notas' => 'Fondo principal de la entidad territorial',
            'alcance' => 'Toda la documentación producida y recibida por la entidad',
            'created_by' => $usuario->id,
        ]);

        // NIVEL 2 - SECCIONES
        $seccionAdministrativa = CuadroClasificacionDocumental::create([
            'codigo' => 'S001',
            'nombre' => 'Sección Administrativa',
            'descripcion' => 'Documentación relacionada con la gestión administrativa',
            'entidad' => 'Alcaldía Municipal de Bogotá',
            'dependencia' => 'Secretaría General',
            'nivel' => 2,
            'padre_id' => $fondo->id,
            'orden_jerarquico' => 1,
            'estado' => 'activo',
            'activo' => true,
            'notas' => 'Incluye gestión humana, contratación, y recursos',
            'created_by' => $usuario->id,
        ]);

        $seccionFinanciera = CuadroClasificacionDocumental::create([
            'codigo' => 'S002',
            'nombre' => 'Sección Financiera',
            'descripcion' => 'Documentación relacionada con la gestión financiera y presupuestal',
            'entidad' => 'Alcaldía Municipal de Bogotá',
            'dependencia' => 'Secretaría de Hacienda',
            'nivel' => 2,
            'padre_id' => $fondo->id,
            'orden_jerarquico' => 2,
            'estado' => 'activo',
            'activo' => true,
            'notas' => 'Incluye presupuesto, contabilidad, y tesorería',
            'created_by' => $usuario->id,
        ]);

        // NIVEL 3 - SUBSECCIONES
        $subseccionRecursosHumanos = CuadroClasificacionDocumental::create([
            'codigo' => 'SS001',
            'nombre' => 'Subsección Recursos Humanos',
            'descripcion' => 'Gestión del talento humano y nómina',
            'entidad' => 'Alcaldía Municipal de Bogotá',
            'dependencia' => 'Oficina de Recursos Humanos',
            'nivel' => 3,
            'padre_id' => $seccionAdministrativa->id,
            'orden_jerarquico' => 1,
            'estado' => 'activo',
            'activo' => true,
            'created_by' => $usuario->id,
        ]);

        $subseccionContratacion = CuadroClasificacionDocumental::create([
            'codigo' => 'SS002',
            'nombre' => 'Subsección Contratación',
            'descripcion' => 'Procesos de contratación estatal',
            'entidad' => 'Alcaldía Municipal de Bogotá',
            'dependencia' => 'Oficina de Contratación',
            'nivel' => 3,
            'padre_id' => $seccionAdministrativa->id,
            'orden_jerarquico' => 2,
            'estado' => 'activo',
            'activo' => true,
            'created_by' => $usuario->id,
        ]);

        // NIVEL 4 - SERIES
        $serieHojasDeVida = CuadroClasificacionDocumental::create([
            'codigo' => 'SE001',
            'nombre' => 'Serie Hojas de Vida',
            'descripcion' => 'Hojas de vida del personal de la entidad',
            'entidad' => 'Alcaldía Municipal de Bogotá',
            'dependencia' => 'Oficina de Recursos Humanos',
            'nivel' => 4,
            'padre_id' => $subseccionRecursosHumanos->id,
            'orden_jerarquico' => 1,
            'estado' => 'activo',
            'activo' => true,
            'vocabularios_controlados' => [
                'tipo_documento' => ['hoja_de_vida', 'curriculum', 'antecedentes'],
                'estado_empleado' => ['activo', 'retirado', 'suspendido']
            ],
            'created_by' => $usuario->id,
        ]);

        $serieContratos = CuadroClasificacionDocumental::create([
            'codigo' => 'SE002',
            'nombre' => 'Serie Contratos de Obra Pública',
            'descripcion' => 'Contratos para ejecución de obras públicas municipales',
            'entidad' => 'Alcaldía Municipal de Bogotá',
            'dependencia' => 'Oficina de Contratación',
            'nivel' => 4,
            'padre_id' => $subseccionContratacion->id,
            'orden_jerarquico' => 1,
            'estado' => 'activo',
            'activo' => true,
            'vocabularios_controlados' => [
                'tipo_contrato' => ['obra_publica', 'suministro', 'servicios'],
                'modalidad' => ['licitacion_publica', 'seleccion_abreviada', 'minima_cuantia']
            ],
            'created_by' => $usuario->id,
        ]);

        // NIVEL 5 - SUBSERIES
        $subserieHojasVidaContratistas = CuadroClasificacionDocumental::create([
            'codigo' => 'SU001',
            'nombre' => 'Subserie Hojas de Vida Contratistas',
            'descripcion' => 'Hojas de vida específicas para personal contratista',
            'entidad' => 'Alcaldía Municipal de Bogotá',
            'dependencia' => 'Oficina de Recursos Humanos',
            'nivel' => 5,
            'padre_id' => $serieHojasDeVida->id,
            'orden_jerarquico' => 1,
            'estado' => 'activo',
            'activo' => true,
            'created_by' => $usuario->id,
        ]);

        // Crear algunos registros en estado borrador para demostrar los filtros
        CuadroClasificacionDocumental::create([
            'codigo' => 'S003',
            'nombre' => 'Sección Jurídica (Borrador)',
            'descripcion' => 'Documentación legal y jurídica - En desarrollo',
            'entidad' => 'Alcaldía Municipal de Bogotá',
            'dependencia' => 'Oficina Jurídica',
            'nivel' => 2,
            'padre_id' => $fondo->id,
            'orden_jerarquico' => 3,
            'estado' => 'borrador',
            'activo' => false,
            'notas' => 'Esta sección está en proceso de estructuración',
            'created_by' => $usuario->id,
        ]);

        $this->command->info('Se han creado ' . CuadroClasificacionDocumental::count() . ' registros de Cuadro de Clasificación Documental');
        $this->command->info('Estructura jerárquica:');
        $this->command->info('├── Fondo Documental');
        $this->command->info('    ├── Sección Administrativa');
        $this->command->info('    │   ├── Subsección Recursos Humanos');
        $this->command->info('    │   │   └── Serie Hojas de Vida');
        $this->command->info('    │   │       └── Subserie Hojas de Vida Contratistas');
        $this->command->info('    │   └── Subsección Contratación');
        $this->command->info('    │       └── Serie Contratos de Obra Pública');
        $this->command->info('    ├── Sección Financiera');
        $this->command->info('    └── Sección Jurídica (Borrador)');
    }
}
