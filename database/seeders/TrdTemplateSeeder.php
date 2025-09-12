<?php

namespace Database\Seeders;

use App\Models\TrdTemplate;
use App\Models\TrdImportConfiguration;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TrdTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Plantillas TRD predefinidas
        $templates = [
            [
                'name' => 'TRD Entidad Gubernamental',
                'description' => 'Plantilla estándar para entidades del sector gubernamental',
                'category' => 'gobierno',
                'template_structure' => [
                    'default_sections' => [
                        [
                            'section_code' => '100',
                            'section_name' => 'Oficina de Planeación',
                            'series' => [
                                [
                                    'series_code' => '100.01',
                                    'series_name' => 'Planeación Institucional',
                                    'subseries' => [
                                        [
                                            'subseries_code' => '100.01.01',
                                            'subseries_name' => 'Planes de Desarrollo',
                                            'document_type' => 'Plan',
                                            'retention_archive_management' => 5,
                                            'retention_central_archive' => 15,
                                            'final_disposition' => 'conservation_total',
                                            'procedure' => 'Elaboración y seguimiento de planes estratégicos'
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [
                            'section_code' => '200',
                            'section_name' => 'Gestión Administrativa',
                            'series' => [
                                [
                                    'series_code' => '200.01',
                                    'series_name' => 'Recursos Humanos',
                                    'subseries' => [
                                        [
                                            'subseries_code' => '200.01.01',
                                            'subseries_name' => 'Hojas de Vida',
                                            'document_type' => 'Expediente',
                                            'retention_archive_management' => 3,
                                            'retention_central_archive' => 7,
                                            'final_disposition' => 'selection',
                                            'procedure' => 'Gestión de personal'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'is_active' => true
            ],
            [
                'name' => 'TRD Institución Educativa',
                'description' => 'Plantilla para instituciones educativas públicas y privadas',
                'category' => 'educacion',
                'template_structure' => [
                    'default_sections' => [
                        [
                            'section_code' => '300',
                            'section_name' => 'Secretaría Académica',
                            'series' => [
                                [
                                    'series_code' => '300.01',
                                    'series_name' => 'Registros Académicos',
                                    'subseries' => [
                                        [
                                            'subseries_code' => '300.01.01',
                                            'subseries_name' => 'Expedientes Estudiantiles',
                                            'document_type' => 'Expediente',
                                            'retention_archive_management' => 5,
                                            'retention_central_archive' => 25,
                                            'final_disposition' => 'conservation_total',
                                            'procedure' => 'Gestión académica estudiantil'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'is_active' => true
            ],
            [
                'name' => 'TRD Entidad de Salud',
                'description' => 'Plantilla para hospitales, clínicas y centros de salud',
                'category' => 'salud',
                'template_structure' => [
                    'default_sections' => [
                        [
                            'section_code' => '400',
                            'section_name' => 'Atención al Paciente',
                            'series' => [
                                [
                                    'series_code' => '400.01',
                                    'series_name' => 'Historias Clínicas',
                                    'subseries' => [
                                        [
                                            'subseries_code' => '400.01.01',
                                            'subseries_name' => 'Historias Clínicas Pacientes',
                                            'document_type' => 'Historia Clínica',
                                            'retention_archive_management' => 5,
                                            'retention_central_archive' => 15,
                                            'final_disposition' => 'conservation_total',
                                            'access_restrictions' => 'Confidencial - Reserva médica',
                                            'procedure' => 'Atención médica integral'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'is_active' => true
            ],
            [
                'name' => 'TRD Empresa Privada',
                'description' => 'Plantilla básica para empresas del sector privado',
                'category' => 'empresarial',
                'template_structure' => [
                    'default_sections' => [
                        [
                            'section_code' => '500',
                            'section_name' => 'Administración',
                            'series' => [
                                [
                                    'series_code' => '500.01',
                                    'series_name' => 'Gestión Financiera',
                                    'subseries' => [
                                        [
                                            'subseries_code' => '500.01.01',
                                            'subseries_name' => 'Estados Financieros',
                                            'document_type' => 'Estado Financiero',
                                            'retention_archive_management' => 3,
                                            'retention_central_archive' => 7,
                                            'final_disposition' => 'selection',
                                            'procedure' => 'Gestión contable y financiera'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'is_active' => true
            ]
        ];

        foreach ($templates as $template) {
            TrdTemplate::create($template);
        }

        // Configuraciones de importación predefinidas
        $importConfigurations = [
            [
                'name' => 'Importación CSV Estándar',
                'import_type' => 'csv',
                'field_mapping' => [
                    'section_code' => 0,
                    'section_name' => 1,
                    'series_code' => 2,
                    'series_name' => 3,
                    'subseries_code' => 4,
                    'subseries_name' => 5,
                    'document_type' => 6,
                    'retention_archive_management' => 7,
                    'retention_central_archive' => 8,
                    'final_disposition' => 9,
                    'procedure' => 10
                ],
                'validation_rules' => [
                    'section_code' => ['required'],
                    'section_name' => ['required'],
                    'series_code' => ['required'],
                    'series_name' => ['required'],
                    'subseries_code' => ['required'],
                    'subseries_name' => ['required'],
                    'retention_archive_management' => ['numeric'],
                    'retention_central_archive' => ['numeric']
                ],
                'is_active' => true
            ],
            [
                'name' => 'Importación Excel AGN',
                'import_type' => 'xlsx',
                'field_mapping' => [
                    'section_code' => 'A',
                    'section_name' => 'B',
                    'series_code' => 'C',
                    'series_name' => 'D',
                    'subseries_code' => 'E',
                    'subseries_name' => 'F',
                    'document_type' => 'G',
                    'retention_archive_management' => 'H',
                    'retention_central_archive' => 'I',
                    'final_disposition' => 'J',
                    'access_restrictions' => 'K',
                    'procedure' => 'L'
                ],
                'validation_rules' => [
                    'section_code' => ['required'],
                    'section_name' => ['required'],
                    'series_code' => ['required'],
                    'series_name' => ['required'],
                    'subseries_code' => ['required'],
                    'subseries_name' => ['required'],
                    'retention_archive_management' => ['numeric'],
                    'retention_central_archive' => ['numeric'],
                    'final_disposition' => ['required']
                ],
                'is_active' => true
            ]
        ];

        foreach ($importConfigurations as $config) {
            TrdImportConfiguration::create($config);
        }
    }
}
