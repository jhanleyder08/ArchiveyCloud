<?php

namespace App\Imports;

use App\Models\CCD;
use App\Models\CCDNivel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CCDImport
{
    protected $ccdId;
    protected $stats = [
        'series_creadas' => 0,
        'subseries_creadas' => 0,
        'errores' => [],
        'filas_procesadas' => 0,
    ];

    public function __construct(int $ccdId)
    {
        $this->ccdId = $ccdId;
    }

    /**
     * Procesar archivo Excel
     */
    public function import(string $filePath)
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // Saltar las primeras 3 filas (título, instrucciones y encabezados)
            $dataRows = array_slice($rows, 3);

            DB::beginTransaction();

            $ccd = CCD::findOrFail($this->ccdId);
            $cache = []; // Cache para todos los niveles

            foreach ($dataRows as $index => $row) {
                // Validar que la fila tenga datos
                if (empty($row[1]) && empty($row[5])) {
                    continue; // Saltar filas vacías
                }

                $this->stats['filas_procesadas']++;

                try {
                    // Mapear columnas del Excel
                    // No. | SECCIÓN | CÓDIGO | SUBSECCIÓN | CÓDIGO | SERIE | CÓDIGO | SUBSERIE | CÓDIGO
                    $data = [
                        'numero' => $row[0] ?? null,
                        'seccion' => trim($row[1] ?? ''),
                        'codigo_seccion' => trim($row[2] ?? ''),
                        'subseccion' => trim($row[3] ?? ''),
                        'codigo_subseccion' => trim($row[4] ?? ''),
                        'serie' => trim($row[5] ?? ''),
                        'codigo_serie' => trim($row[6] ?? ''),
                        'subserie' => trim($row[7] ?? ''),
                        'codigo_subserie' => trim($row[8] ?? ''),
                    ];

                    $parentId = null;
                    $nivel = 1;

                    // 1. Procesar SECCIÓN (Nivel 1)
                    if (!empty($data['seccion']) && !empty($data['codigo_seccion'])) {
                        $keySeccion = 'seccion_' . $data['codigo_seccion'];
                        
                        if (!isset($cache[$keySeccion])) {
                            $seccion = CCDNivel::firstOrCreate(
                                [
                                    'ccd_id' => $this->ccdId,
                                    'codigo' => $data['codigo_seccion'],
                                    'tipo_nivel' => 'seccion',
                                ],
                                [
                                    'nombre' => $data['seccion'],
                                    'nivel' => 1,
                                    'parent_id' => null,
                                    'activo' => true,
                                    'orden' => count(array_filter(array_keys($cache), fn($k) => str_starts_with($k, 'seccion_'))) + 1,
                                ]
                            );

                            $cache[$keySeccion] = $seccion;
                        }

                        $parentId = $cache[$keySeccion]->id;
                        $nivel = 2;
                    }

                    // 2. Procesar SUBSECCIÓN (Nivel 2) - OPCIONAL
                    if (!empty($data['subseccion']) && !empty($data['codigo_subseccion'])) {
                        $keySubseccion = 'subseccion_' . $data['codigo_subseccion'];
                        
                        if (!isset($cache[$keySubseccion])) {
                            $subseccion = CCDNivel::firstOrCreate(
                                [
                                    'ccd_id' => $this->ccdId,
                                    'codigo' => $data['codigo_subseccion'],
                                    'tipo_nivel' => 'subseccion',
                                ],
                                [
                                    'nombre' => $data['subseccion'],
                                    'nivel' => 2,
                                    'parent_id' => $parentId,
                                    'activo' => true,
                                    'orden' => count(array_filter(array_keys($cache), fn($k) => str_starts_with($k, 'subseccion_'))) + 1,
                                ]
                            );

                            $cache[$keySubseccion] = $subseccion;
                        }

                        $parentId = $cache[$keySubseccion]->id;
                        $nivel = 3;
                    }

                    // 3. Procesar SERIE (Nivel 3 o 2 si no hay subsección)
                    if (!empty($data['serie']) && !empty($data['codigo_serie'])) {
                        // Crear clave única combinando con el parent para evitar conflictos
                        $keySerie = 'serie_' . $parentId . '_' . $data['codigo_serie'];
                        
                        if (!isset($cache[$keySerie])) {
                            $serie = CCDNivel::firstOrCreate(
                                [
                                    'ccd_id' => $this->ccdId,
                                    'codigo' => $data['codigo_serie'],
                                    'parent_id' => $parentId,
                                    'tipo_nivel' => CCDNivel::TIPO_SERIE,
                                ],
                                [
                                    'nombre' => $data['serie'],
                                    'nivel' => $nivel,
                                    'activo' => true,
                                    'orden' => count(array_filter(array_keys($cache), fn($k) => str_starts_with($k, 'serie_'))) + 1,
                                ]
                            );

                            if ($serie->wasRecentlyCreated) {
                                $this->stats['series_creadas']++;
                            }

                            $cache[$keySerie] = $serie;
                        }

                        $parentId = $cache[$keySerie]->id;
                        $nivel++;
                    }

                    // 4. Procesar SUBSERIE (Nivel 4, 3 o 2 dependiendo de la jerarquía)
                    if (!empty($data['subserie']) && !empty($data['codigo_subserie']) && $parentId) {
                        $subserie = CCDNivel::create([
                            'ccd_id' => $this->ccdId,
                            'parent_id' => $parentId,
                            'codigo' => $data['codigo_subserie'],
                            'nombre' => $data['subserie'],
                            'tipo_nivel' => CCDNivel::TIPO_SUBSERIE,
                            'nivel' => $nivel,
                            'activo' => true,
                            'orden' => $this->stats['subseries_creadas'] + 1,
                        ]);

                        $this->stats['subseries_creadas']++;
                    }

                } catch (\Exception $e) {
                    $this->stats['errores'][] = [
                        'fila' => $index + 4, // +4 porque saltamos 3 filas + índice 0
                        'mensaje' => $e->getMessage(),
                    ];
                    Log::error("Error procesando fila " . ($index + 4) . ": " . $e->getMessage());
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error en importación CCD: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener estadísticas de la importación
     */
    public function getStats(): array
    {
        return $this->stats;
    }
}
