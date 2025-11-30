<?php

namespace Database\Seeders;

use App\Models\CCD;
use App\Models\CCDNivel;
use Illuminate\Database\Seeder;

class FixCCD8Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ccdId = 8;

        // Buscar el CCD
        $ccd = CCD::find($ccdId);

        if (!$ccd) {
            $this->command->error("No se encontró el CCD con ID: {$ccdId}");
            return;
        }

        $this->command->info("Procesando CCD: {$ccd->nombre}");

        // Verificar si ya tiene niveles raíz
        $nivelesRaiz = $ccd->nivelesRaiz()->count();
        if ($nivelesRaiz > 0) {
            $this->command->warn("El CCD ya tiene {$nivelesRaiz} nivel(es) raíz configurado(s).");
            return;
        }

        // Crear el nivel raíz por defecto
        $nivel = CCDNivel::create([
            'ccd_id' => $ccd->id,
            'parent_id' => null,
            'codigo' => 'FOND-HUV-01',
            'nombre' => 'Fondo Hospital Universitario del Valle',
            'descripcion' => 'Fondo raíz del cuadro de clasificación documental',
            'nivel' => 1,
            'tipo_nivel' => 'fondo',
            'orden' => 0,
            'activo' => true,
        ]);

        // Actualizar la ruta
        $nivel->actualizarRuta();

        $this->command->info("✓ Nivel raíz creado exitosamente: {$nivel->codigo} - {$nivel->nombre}");

        // Validar el CCD
        $errores = $ccd->validar();
        if (empty($errores)) {
            $this->command->info("✓ El CCD ahora tiene una estructura válida");
        } else {
            $this->command->warn("⚠ El CCD aún tiene errores:");
            foreach ($errores as $error) {
                $this->command->line("  - {$error}");
            }
        }
    }
}
