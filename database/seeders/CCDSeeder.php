<?php

namespace Database\Seeders;

use App\Models\CCD;
use App\Models\CCDNivel;
use App\Models\User;
use Illuminate\Database\Seeder;

class CCDSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el primer usuario administrador
        $adminUser = User::where('email', 'admin@archiveycloud.com')->first();
        if (!$adminUser) {
            $adminUser = User::first();
        }

        if (!$adminUser) {
            $this->command->error('No se encontró ningún usuario. Por favor, crea un usuario primero.');
            return;
        }

        // Crear CCD de ejemplo 1: Hospital Universitario del Valle
        $ccd1 = CCD::create([
            'codigo' => 'CCD-HUV-2025',
            'nombre' => 'Cuadro de Clasificación Documental - Hospital Universitario del Valle',
            'descripcion' => 'CCD principal del Hospital Universitario del Valle basado en la estructura organizacional y funcional',
            'version' => '1.0',
            'estado' => 'activo',
            'fecha_vigencia_inicio' => now()->subMonths(3),
            'fecha_vigencia_fin' => now()->addYears(2),
            'created_by' => $adminUser->id,
        ]);

        // Niveles raíz (Fondos)
        $fondoAdministrativo = CCDNivel::create([
            'ccd_id' => $ccd1->id,
            'codigo' => '01',
            'nombre' => 'Fondo Administrativo',
            'descripcion' => 'Documentos generados por las actividades administrativas',
            'tipo_nivel' => 'fondo',
            'nivel' => 1,
            'orden' => 1,
            'activo' => true,
        ]);

        $fondoAsistencial = CCDNivel::create([
            'ccd_id' => $ccd1->id,
            'codigo' => '02',
            'nombre' => 'Fondo Asistencial',
            'descripcion' => 'Documentos relacionados con la atención médica y servicios asistenciales',
            'tipo_nivel' => 'fondo',
            'nivel' => 1,
            'orden' => 2,
            'activo' => true,
        ]);

        // Secciones del Fondo Administrativo
        $seccionGestionHumana = CCDNivel::create([
            'ccd_id' => $ccd1->id,
            'parent_id' => $fondoAdministrativo->id,
            'codigo' => '01.01',
            'nombre' => 'Gestión Humana',
            'descripcion' => 'Documentos de recursos humanos y gestión del talento',
            'tipo_nivel' => 'seccion',
            'nivel' => 2,
            'orden' => 1,
            'activo' => true,
        ]);

        $seccionContabilidad = CCDNivel::create([
            'ccd_id' => $ccd1->id,
            'parent_id' => $fondoAdministrativo->id,
            'codigo' => '01.02',
            'nombre' => 'Contabilidad y Finanzas',
            'descripcion' => 'Documentos financieros y contables',
            'tipo_nivel' => 'seccion',
            'nivel' => 2,
            'orden' => 2,
            'activo' => true,
        ]);

        // Series documentales
        CCDNivel::create([
            'ccd_id' => $ccd1->id,
            'parent_id' => $seccionGestionHumana->id,
            'codigo' => '01.01.01',
            'nombre' => 'Hojas de Vida',
            'descripcion' => 'Documentos que contienen información del personal',
            'tipo_nivel' => 'serie',
            'nivel' => 3,
            'orden' => 1,
            'activo' => true,
        ]);

        CCDNivel::create([
            'ccd_id' => $ccd1->id,
            'parent_id' => $seccionGestionHumana->id,
            'codigo' => '01.01.02',
            'nombre' => 'Contratos Laborales',
            'descripcion' => 'Contratos de trabajo y vinculación laboral',
            'tipo_nivel' => 'serie',
            'nivel' => 3,
            'orden' => 2,
            'activo' => true,
        ]);

        CCDNivel::create([
            'ccd_id' => $ccd1->id,
            'parent_id' => $seccionContabilidad->id,
            'codigo' => '01.02.01',
            'nombre' => 'Comprobantes de Egreso',
            'descripcion' => 'Documentos de salida de recursos financieros',
            'tipo_nivel' => 'serie',
            'nivel' => 3,
            'orden' => 1,
            'activo' => true,
        ]);

        // Secciones del Fondo Asistencial
        $seccionHistoriasClinicas = CCDNivel::create([
            'ccd_id' => $ccd1->id,
            'parent_id' => $fondoAsistencial->id,
            'codigo' => '02.01',
            'nombre' => 'Historias Clínicas',
            'descripcion' => 'Documentos de atención médica de pacientes',
            'tipo_nivel' => 'seccion',
            'nivel' => 2,
            'orden' => 1,
            'activo' => true,
        ]);

        CCDNivel::create([
            'ccd_id' => $ccd1->id,
            'parent_id' => $seccionHistoriasClinicas->id,
            'codigo' => '02.01.01',
            'nombre' => 'Historias Clínicas Hospitalización',
            'descripcion' => 'HC de pacientes hospitalizados',
            'tipo_nivel' => 'serie',
            'nivel' => 3,
            'orden' => 1,
            'activo' => true,
        ]);

        // Actualizar rutas de todos los niveles
        foreach ($ccd1->niveles as $nivel) {
            $nivel->actualizarRuta();
        }

        // Crear CCD de ejemplo 2: En borrador
        $ccd2 = CCD::create([
            'codigo' => 'CCD-HUV-2026',
            'nombre' => 'Cuadro de Clasificación Documental 2026 (Borrador)',
            'descripcion' => 'CCD en proceso de construcción para el año 2026',
            'version' => '0.1',
            'estado' => 'borrador',
            'created_by' => $adminUser->id,
        ]);

        CCDNivel::create([
            'ccd_id' => $ccd2->id,
            'codigo' => '01',
            'nombre' => 'Fondo General',
            'descripcion' => 'Fondo general de documentos',
            'tipo_nivel' => 'fondo',
            'nivel' => 1,
            'orden' => 1,
            'activo' => true,
        ]);

        // Crear CCD de ejemplo 3: Archivado
        CCD::create([
            'codigo' => 'CCD-HUV-2024',
            'nombre' => 'Cuadro de Clasificación Documental 2024 (Archivado)',
            'descripcion' => 'CCD utilizado durante el año 2024',
            'version' => '1.0',
            'estado' => 'archivado',
            'fecha_vigencia_inicio' => now()->subYear(),
            'fecha_vigencia_fin' => now()->subMonths(3),
            'created_by' => $adminUser->id,
        ]);

        $this->command->info('✅ Se crearon 3 CCDs de ejemplo con su estructura jerárquica');
        $this->command->info('   - CCD-HUV-2025: Activo con estructura completa');
        $this->command->info('   - CCD-HUV-2026: Borrador');
        $this->command->info('   - CCD-HUV-2024: Archivado');
    }
}
