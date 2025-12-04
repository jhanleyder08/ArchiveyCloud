<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class IndicesElectronicosExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithCustomStartCell
{
    protected $indices;
    protected $filtros;

    public function __construct($indices, $filtros = [])
    {
        $this->indices = $indices;
        $this->filtros = $filtros;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->indices->map(function ($indice) {
            return [
                'id' => $indice->id,
                'tipo' => ucfirst($indice->tipo_entidad),
                'codigo' => $indice->getCodigoCompleto(),
                'titulo' => $indice->titulo,
                'serie' => $indice->serie_documental,
                'subserie' => $indice->subserie_documental,
                'fecha_inicio' => $indice->fecha_inicio?->format('d/m/Y'),
                'fecha_fin' => $indice->fecha_fin?->format('d/m/Y'),
                'responsable' => $indice->responsable,
                'nivel_acceso' => $indice->getEtiquetaNivelAcceso(),
                'estado_conservacion' => $indice->getEtiquetaEstadoConservacion(),
                'folios' => $indice->cantidad_folios,
                'tamaño' => $indice->getTamaño(),
                'es_vital' => $indice->es_vital ? 'Sí' : 'No',
                'es_historico' => $indice->es_historico ? 'Sí' : 'No',
                'fecha_indexacion' => $indice->fecha_indexacion?->format('d/m/Y H:i'),
                'ubicacion_fisica' => $indice->ubicacion_fisica,
                'palabras_clave' => implode(', ', $indice->palabras_clave ?? []),
                'observaciones' => $indice->observaciones,
            ];
        });
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Tipo',
            'Código',
            'Título',
            'Serie Documental',
            'Subserie Documental',
            'Fecha Inicio',
            'Fecha Fin',
            'Responsable',
            'Nivel de Acceso',
            'Estado Conservación',
            'Folios',
            'Tamaño',
            'Es Vital',
            'Es Histórico',
            'Fecha Indexación',
            'Ubicación Física',
            'Palabras Clave',
            'Observaciones',
        ];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 12,  // Tipo
            'C' => 15,  // Código
            'D' => 40,  // Título
            'E' => 25,  // Serie
            'F' => 25,  // Subserie
            'G' => 12,  // Fecha Inicio
            'H' => 12,  // Fecha Fin
            'I' => 20,  // Responsable
            'J' => 15,  // Nivel Acceso
            'K' => 18,  // Estado Conservación
            'L' => 8,   // Folios
            'M' => 12,  // Tamaño
            'N' => 8,   // Es Vital
            'O' => 12,  // Es Histórico
            'P' => 16,  // Fecha Indexación
            'Q' => 20,  // Ubicación Física
            'R' => 30,  // Palabras Clave
            'S' => 40,  // Observaciones
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Índices Electrónicos';
    }

    /**
     * @return string
     */
    public function startCell(): string
    {
        return 'A4';
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        // Título principal
        $sheet->setCellValue('A1', 'ÍNDICES ELECTRÓNICOS - SISTEMA ARCHIVEYCLOUD');
        $sheet->mergeCells('A1:S1');
        
        // Información de exportación
        $sheet->setCellValue('A2', 'Fecha de exportación: ' . now()->format('d/m/Y H:i:s'));
        $sheet->mergeCells('A2:S2');
        
        // Filtros aplicados
        if (!empty($this->filtros)) {
            $filtrosTexto = 'Filtros aplicados: ';
            foreach ($this->filtros as $key => $value) {
                if (!empty($value) && $value !== 'all') {
                    $filtrosTexto .= ucfirst($key) . ': ' . $value . ' | ';
                }
            }
            $sheet->setCellValue('A3', rtrim($filtrosTexto, ' | '));
            $sheet->mergeCells('A3:S3');
        }

        return [
            // Título principal
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 16,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            
            // Información de exportación
            2 => [
                'font' => ['italic' => true, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ],
            
            // Filtros
            3 => [
                'font' => ['italic' => true, 'size' => 9],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ],
            
            // Encabezados
            4 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1F2937'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '374151'],
                    ],
                ],
            ],
            
            // Datos - bordes para todas las celdas con datos
            'A4:S' . (4 + $this->collection()->count()) => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D1D5DB'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_TOP,
                ],
            ],
        ];
    }
}
