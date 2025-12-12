<?php

namespace App\Services;

use App\Models\TRD;
use App\Models\SerieDocumental;
use App\Models\SubserieDocumental;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Servicio para importar TRDs desde archivos Excel/CSV
 * Basado en el formato FOR-GDI-GDO-002 del Hospital Universitario del Valle
 */
class TRDImportService
{
    /**
     * Importar TRD desde archivo Excel
     * 
     * Formato esperado del Excel:
     * Fila 1-5: Encabezado (se ignora)
     * Fila 6+: Datos de series/subseries
     * 
     * Columnas:
     * A: CODIGO (ej: 111-2, 111-2.20)
     * B: SERIES, SUBSERIES Y TIPOS DOCUMENTALES
     * C: Soporte Documento Físico (X)
     * D: Soporte Documento Electrónico (X)
     * E: Retención Archivo Gestión (años)
     * F: Retención Archivo Central (años)
     * G: Disposición Final CT (X)
     * H: Disposición Final E (X)
     * I: Disposición Final D (X)
     * J: Disposición Final S (X)
     * K: PROCEDIMIENTO
     */
    public function importFromExcel(UploadedFile $file, TRD $trd): array
    {
        $results = [
            'series_creadas' => 0,
            'subseries_creadas' => 0,
            'errores' => [],
            'filas_procesadas' => 0,
        ];

        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Buscar la fila donde empiezan los datos (después de CODIGO | SERIES...)
            $dataStartRow = $this->findDataStartRow($rows);
            
            if ($dataStartRow === -1) {
                $results['errores'][] = 'No se encontró la estructura de datos esperada en el archivo';
                return $results;
            }

            DB::beginTransaction();

            $currentSerie = null;

            for ($i = $dataStartRow; $i < count($rows); $i++) {
                $row = $rows[$i];
                $results['filas_procesadas']++;

                // Saltar filas vacías
                if (empty(trim($row[0] ?? '')) && empty(trim($row[1] ?? ''))) {
                    continue;
                }

                $codigo = trim($row[0] ?? '');
                $nombre = trim($row[1] ?? '');

                // Si no hay código pero hay nombre, es una lista de tipos documentales
                if (empty($codigo) && !empty($nombre)) {
                    continue; // Los tipos documentales se manejan en la descripción
                }

                // Determinar si es serie o subserie por el formato del código
                // Serie: XXX-XX (ej: 111-2)
                // Subserie: XXX-XX.XX (ej: 111-2.20)
                $isSubserie = strpos($codigo, '.') !== false;

                try {
                    if ($isSubserie && $currentSerie) {
                        // Crear subserie
                        $subserie = $this->createSubserie($currentSerie, $row, $rows, $i);
                        if ($subserie) {
                            $results['subseries_creadas']++;
                        }
                    } else if (!$isSubserie && !empty($codigo)) {
                        // Crear serie
                        $currentSerie = $this->createSerie($trd, $row, $rows, $i);
                        if ($currentSerie) {
                            $results['series_creadas']++;
                        }
                    }
                } catch (\Exception $e) {
                    $results['errores'][] = "Fila " . ($i + 1) . ": " . $e->getMessage();
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error importando TRD desde Excel', ['error' => $e->getMessage()]);
            $results['errores'][] = 'Error general: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Encontrar la fila donde empiezan los datos
     */
    private function findDataStartRow(array $rows): int
    {
        for ($i = 0; $i < min(20, count($rows)); $i++) {
            $firstCell = strtoupper(trim($rows[$i][0] ?? ''));
            if ($firstCell === 'CODIGO' || $firstCell === 'CÓDIGO') {
                return $i + 1; // Los datos empiezan en la siguiente fila
            }
        }
        return -1;
    }

    /**
     * Crear una serie documental desde una fila del Excel
     */
    private function createSerie(TRD $trd, array $row, array $allRows, int $currentIndex): ?SerieDocumental
    {
        $codigo = trim($row[0] ?? '');
        $nombre = trim($row[1] ?? '');

        if (empty($codigo) || empty($nombre)) {
            return null;
        }

        // Recopilar tipos documentales de las filas siguientes (sin código)
        $tiposDocumentales = $this->collectTiposDocumentales($allRows, $currentIndex);

        $serie = SerieDocumental::create([
            'trd_id' => $trd->id,
            'codigo' => $codigo,
            'nombre' => $nombre,
            'descripcion' => null,
            'tipos_documentales_lista' => $tiposDocumentales,
            'soporte_fisico' => $this->hasX($row[2] ?? ''),
            'soporte_electronico' => $this->hasX($row[3] ?? ''),
            'retencion_gestion' => $this->parseNumber($row[4] ?? 0),
            'retencion_central' => $this->parseNumber($row[5] ?? 0),
            'disposicion_ct' => $this->hasX($row[6] ?? ''),
            'disposicion_e' => $this->hasX($row[7] ?? ''),
            'disposicion_d' => $this->hasX($row[8] ?? ''),
            'disposicion_s' => $this->hasX($row[9] ?? ''),
            'procedimiento' => trim($row[10] ?? ''),
            'activa' => true,
        ]);

        return $serie;
    }

    /**
     * Crear una subserie documental desde una fila del Excel
     */
    private function createSubserie(SerieDocumental $serie, array $row, array $allRows, int $currentIndex): ?SubserieDocumental
    {
        $codigo = trim($row[0] ?? '');
        $nombre = trim($row[1] ?? '');

        if (empty($codigo) || empty($nombre)) {
            return null;
        }

        // Recopilar tipos documentales de las filas siguientes
        $tiposDocumentales = $this->collectTiposDocumentales($allRows, $currentIndex);

        $subserie = SubserieDocumental::create([
            'serie_id' => $serie->id,
            'codigo' => $codigo,
            'nombre' => $nombre,
            'descripcion' => null,
            'tipos_documentales_lista' => $tiposDocumentales,
            'soporte_fisico' => $this->hasX($row[2] ?? ''),
            'soporte_electronico' => $this->hasX($row[3] ?? ''),
            'retencion_gestion' => $this->parseNumber($row[4] ?? 0),
            'retencion_central' => $this->parseNumber($row[5] ?? 0),
            'disposicion_ct' => $this->hasX($row[6] ?? ''),
            'disposicion_e' => $this->hasX($row[7] ?? ''),
            'disposicion_d' => $this->hasX($row[8] ?? ''),
            'disposicion_s' => $this->hasX($row[9] ?? ''),
            'procedimiento' => trim($row[10] ?? ''),
            'activa' => true,
        ]);

        return $subserie;
    }

    /**
     * Recopilar tipos documentales de las filas sin código
     */
    private function collectTiposDocumentales(array $allRows, int $startIndex): ?string
    {
        $tipos = [];
        
        // La primera fila puede tener tipos documentales en la columna B después del nombre
        $primeraFila = $allRows[$startIndex][1] ?? '';
        
        // Buscar en las filas siguientes hasta encontrar otra serie/subserie
        for ($i = $startIndex + 1; $i < count($allRows); $i++) {
            $codigo = trim($allRows[$i][0] ?? '');
            $nombre = trim($allRows[$i][1] ?? '');
            
            // Si tiene código, es otra serie/subserie, detenerse
            if (!empty($codigo)) {
                break;
            }
            
            // Si solo tiene nombre, es un tipo documental
            if (!empty($nombre) && strpos($nombre, '-') === 0) {
                $tipos[] = ltrim($nombre, '- ');
            }
        }

        return !empty($tipos) ? implode("\n", $tipos) : null;
    }

    /**
     * Verificar si una celda contiene X (marca de selección)
     */
    private function hasX(string $value): bool
    {
        $value = strtoupper(trim($value));
        return $value === 'X' || $value === '✓' || $value === '✔';
    }

    /**
     * Parsear número desde celda
     */
    private function parseNumber($value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }
        return 0;
    }

    /**
     * Importar desde CSV
     */
    public function importFromCSV(UploadedFile $file, TRD $trd): array
    {
        $results = [
            'series_creadas' => 0,
            'subseries_creadas' => 0,
            'errores' => [],
            'filas_procesadas' => 0,
        ];

        try {
            $handle = fopen($file->getPathname(), 'r');
            if ($handle === false) {
                $results['errores'][] = 'No se pudo abrir el archivo CSV';
                return $results;
            }

            $rows = [];
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $rows[] = $row;
            }
            fclose($handle);

            // Usar la misma lógica que Excel
            $dataStartRow = $this->findDataStartRow($rows);
            
            if ($dataStartRow === -1) {
                // Intentar con la primera fila como encabezado
                $dataStartRow = 1;
            }

            DB::beginTransaction();

            $currentSerie = null;

            for ($i = $dataStartRow; $i < count($rows); $i++) {
                $row = $rows[$i];
                $results['filas_procesadas']++;

                if (empty(trim($row[0] ?? '')) && empty(trim($row[1] ?? ''))) {
                    continue;
                }

                $codigo = trim($row[0] ?? '');
                $isSubserie = strpos($codigo, '.') !== false;

                try {
                    if ($isSubserie && $currentSerie) {
                        $subserie = $this->createSubserie($currentSerie, $row, $rows, $i);
                        if ($subserie) {
                            $results['subseries_creadas']++;
                        }
                    } else if (!$isSubserie && !empty($codigo)) {
                        $currentSerie = $this->createSerie($trd, $row, $rows, $i);
                        if ($currentSerie) {
                            $results['series_creadas']++;
                        }
                    }
                } catch (\Exception $e) {
                    $results['errores'][] = "Fila " . ($i + 1) . ": " . $e->getMessage();
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $results['errores'][] = 'Error: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Generar plantilla Excel para importación
     */
    public function generateTemplate(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Encabezados según formato oficial
        $headers = [
            'CODIGO',
            'SERIES, SUBSERIES Y TIPOS DOCUMENTALES',
            'Doc. Físico',
            'Doc. Electrónico',
            'Ret. Gestión',
            'Ret. Central',
            'CT',
            'E',
            'D',
            'S',
            'PROCEDIMIENTO'
        ];

        // Escribir encabezados
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }

        // Ejemplo de datos
        $ejemplos = [
            ['111-2', 'ACTAS', '', '', '', '', '', '', '', '', ''],
            ['111-2.20', 'Actas de Comité Hospitalario de Emergencias', 'X', 'X', '2', '8', 'X', '', 'X', '', 'Subserie que contiene las actas...'],
            ['', '-Citación comité', '', '', '', '', '', '', '', '', ''],
            ['', '-Acta de Reunión', '', '', '', '', '', '', '', '', ''],
            ['111-26', 'INFORMES', '', '', '', '', '', '', '', '', ''],
            ['111-26.10', 'Informes de Gestión', 'X', 'X', '2', '8', 'X', '', '', 'X', 'Subserie que contiene los Informes...'],
        ];

        foreach ($ejemplos as $rowIndex => $data) {
            foreach ($data as $col => $value) {
                $sheet->setCellValueByColumnAndRow($col + 1, $rowIndex + 2, $value);
            }
        }

        // Ajustar anchos de columna
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(45);
        $sheet->getColumnDimension('K')->setWidth(60);

        return $spreadsheet;
    }
}
