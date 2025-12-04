<?php

namespace App\Console\Commands;

use App\Models\CCD;
use App\Models\CCDNivel;
use Illuminate\Console\Command;

class AddCCDRootLevel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ccd:add-root-level {ccd_id : ID del CCD}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Agrega un nivel raíz (Fondo) por defecto a un CCD específico';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ccdId = $this->argument('ccd_id');

        // Buscar el CCD
        $ccd = CCD::find($ccdId);

        if (!$ccd) {
            $this->error("No se encontró el CCD con ID: {$ccdId}");
            return 1;
        }

        // Verificar si ya tiene niveles raíz
        $nivelesRaiz = $ccd->nivelesRaiz()->count();
        if ($nivelesRaiz > 0) {
            $this->warn("El CCD '{$ccd->nombre}' ya tiene {$nivelesRaiz} nivel(es) raíz configurado(s).");
            
            if (!$this->confirm('¿Desea agregar otro nivel raíz de todas formas?')) {
                $this->info('Operación cancelada.');
                return 0;
            }
        }

        // Solicitar datos del nivel raíz
        $codigo = $this->ask('Código del Fondo', 'FOND-01');
        $nombre = $this->ask('Nombre del Fondo', 'Fondo Documental Principal');
        $descripcion = $this->ask('Descripción (opcional)', 'Fondo raíz del cuadro de clasificación documental');

        // Crear el nivel raíz
        try {
            $nivel = new CCDNivel([
                'ccd_id' => $ccd->id,
                'parent_id' => null,
                'codigo' => $codigo,
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'nivel' => 1,
                'tipo_nivel' => 'fondo',
                'orden' => 0,
                'activo' => true,
            ]);

            $nivel->save();
            $nivel->actualizarRuta();

            $this->info('✓ Nivel raíz creado exitosamente');
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['ID', $nivel->id],
                    ['CCD', $ccd->nombre],
                    ['Código', $nivel->codigo],
                    ['Nombre', $nivel->nombre],
                    ['Tipo', $nivel->tipo_nivel],
                    ['Nivel', $nivel->nivel],
                ]
            );

            // Validar el CCD
            $errores = $ccd->validar();
            if (empty($errores)) {
                $this->info('✓ El CCD ahora tiene una estructura válida');
            } else {
                $this->warn('⚠ El CCD aún tiene errores de validación:');
                foreach ($errores as $error) {
                    $this->line("  - {$error}");
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Error al crear el nivel raíz: ' . $e->getMessage());
            return 1;
        }
    }
}
