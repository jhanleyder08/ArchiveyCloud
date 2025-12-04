<?php

namespace App\Services;

use App\Models\ImportacionDatos;
use App\Models\Expediente;
use App\Models\Documento;
use App\Models\SerieDocumental;
use App\Models\SubserieDocumental;
use App\Models\User;
use App\Models\CertificadoDigital;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use League\Csv\Reader;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class ImportacionDatosService
{
    /**
     * Procesar un archivo de importación
     */
    public function procesarImportacion(ImportacionDatos $importacion)
    {
        try {
            $importacion->iniciarProcesamiento();
            
            $resultado = $this->procesarArchivo($importacion);
            
            if ($resultado['exito']) {
                $importacion->completarProcesamiento();
            } else {
                $importacion->fallarProcesamiento($resultado['error']);
            }
            
            return $resultado;
            
        } catch (\Exception $e) {
            Log::error('Error procesando importación: ' . $e->getMessage(), [
                'importacion_id' => $importacion->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            $importacion->fallarProcesamiento($e->getMessage());
            
            return [
                'exito' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Crear una nueva importación desde un archivo subido
     */
    public function crearImportacion(
        string $nombre,
        string $tipo,
        UploadedFile $archivo,
        array $configuracion = [],
        string $descripcion = null,
        int $usuarioId = null
    ): ImportacionDatos {
        
        // Generar nombre único para el archivo
        $nombreArchivo = time() . '_' . Str::slug($nombre) . '.' . $archivo->getClientOriginalExtension();
        $rutaArchivo = $archivo->storeAs('importaciones', $nombreArchivo, 'local');
        
        // Detectar formato del archivo
        $formato = $this->detectarFormato($archivo);
        
        // Analizar archivo para obtener información inicial
        $analisis = $this->analizarArchivo($rutaArchivo, $formato);
        
        return ImportacionDatos::create([
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'tipo' => $tipo,
            'formato_origen' => $formato,
            'archivo_origen' => $rutaArchivo,
            'configuracion' => $configuracion,
            'total_registros' => $analisis['total_registros'],
            'metadatos' => $analisis['metadatos'],
            'usuario_id' => $usuarioId
        ]);
    }

    /**
     * Procesar el archivo según su tipo y formato
     */
    private function procesarArchivo(ImportacionDatos $importacion): array
    {
        $rutaArchivo = Storage::path($importacion->archivo_origen);
        
        if (!file_exists($rutaArchivo)) {
            return [
                'exito' => false,
                'error' => 'Archivo no encontrado: ' . $importacion->archivo_origen
            ];
        }

        switch ($importacion->formato_origen) {
            case ImportacionDatos::FORMATO_CSV:
                return $this->procesarCSV($importacion, $rutaArchivo);
            
            case ImportacionDatos::FORMATO_EXCEL:
                return $this->procesarExcel($importacion, $rutaArchivo);
            
            case ImportacionDatos::FORMATO_JSON:
                return $this->procesarJSON($importacion, $rutaArchivo);
            
            default:
                return [
                    'exito' => false,
                    'error' => 'Formato no soportado: ' . $importacion->formato_origen
                ];
        }
    }

    /**
     * Procesar archivo CSV
     */
    private function procesarCSV(ImportacionDatos $importacion, string $rutaArchivo): array
    {
        try {
            $csv = Reader::createFromPath($rutaArchivo, 'r');
            $csv->setHeaderOffset(0);
            
            $registros = iterator_to_array($csv->getRecords());
            $procesados = 0;
            $exitosos = 0;
            $fallidos = 0;
            $errores = [];

            foreach ($registros as $indice => $registro) {
                try {
                    $resultado = $this->procesarRegistro($importacion->tipo, $registro, $importacion->configuracion);
                    
                    if ($resultado['exito']) {
                        $exitosos++;
                    } else {
                        $fallidos++;
                        $errores[] = [
                            'fila' => $indice + 2,
                            'error' => $resultado['error'],
                            'datos' => $registro
                        ];
                    }
                } catch (\Exception $e) {
                    $fallidos++;
                    $errores[] = [
                        'fila' => $indice + 2,
                        'error' => $e->getMessage(),
                        'datos' => $registro
                    ];
                }
                
                $procesados++;
                
                if ($procesados % 100 === 0) {
                    $importacion->actualizarProgreso($procesados, $exitosos, $fallidos);
                }
            }

            if (!empty($errores)) {
                $this->guardarArchivoErrores($importacion, $errores);
            }

            $importacion->actualizarProgreso($procesados, $exitosos, $fallidos, 100);

            return [
                'exito' => true,
                'procesados' => $procesados,
                'exitosos' => $exitosos,
                'fallidos' => $fallidos,
                'errores' => count($errores)
            ];

        } catch (\Exception $e) {
            return [
                'exito' => false,
                'error' => 'Error procesando CSV: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Procesar un registro individual según el tipo
     */
    private function procesarRegistro(string $tipo, array $datos, array $configuracion): array
    {
        try {
            switch ($tipo) {
                case ImportacionDatos::TIPO_EXPEDIENTES:
                    return $this->procesarExpediente($datos, $configuracion);
                
                case ImportacionDatos::TIPO_DOCUMENTOS:
                    return $this->procesarDocumento($datos, $configuracion);
                
                case ImportacionDatos::TIPO_SERIES:
                    return $this->procesarSerie($datos, $configuracion);
                
                case ImportacionDatos::TIPO_USUARIOS:
                    return $this->procesarUsuario($datos, $configuracion);
                
                default:
                    return [
                        'exito' => false,
                        'error' => 'Tipo de importación no soportado: ' . $tipo
                    ];
            }
        } catch (\Exception $e) {
            return [
                'exito' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function procesarExpediente(array $datos, array $configuracion): array
    {
        try {
            $mapeo = $configuracion['mapeo'] ?? [];
            
            $expedienteData = [
                'codigo' => $this->obtenerValor($datos, $mapeo['codigo'] ?? 'codigo'),
                'nombre' => $this->obtenerValor($datos, $mapeo['nombre'] ?? 'nombre'),
                'descripcion' => $this->obtenerValor($datos, $mapeo['descripcion'] ?? 'descripcion'),
                'estado' => $this->obtenerValor($datos, $mapeo['estado'] ?? 'estado', 'abierto'),
                'nivel_acceso' => $this->obtenerValor($datos, $mapeo['nivel_acceso'] ?? 'nivel_acceso', 'publico'),
                'usuario_id' => 1, // Usuario por defecto
                'created_at' => now(),
                'updated_at' => now()
            ];

            if (empty($expedienteData['codigo']) || empty($expedienteData['nombre'])) {
                return [
                    'exito' => false,
                    'error' => 'Código y nombre son requeridos'
                ];
            }

            if (Expediente::where('codigo', $expedienteData['codigo'])->exists()) {
                return [
                    'exito' => false,
                    'error' => 'Expediente ya existe: ' . $expedienteData['codigo']
                ];
            }

            Expediente::create($expedienteData);
            return ['exito' => true];

        } catch (\Exception $e) {
            return [
                'exito' => false,
                'error' => 'Error procesando expediente: ' . $e->getMessage()
            ];
        }
    }

    private function procesarDocumento(array $datos, array $configuracion): array
    {
        try {
            $mapeo = $configuracion['mapeo'] ?? [];
            
            $documentoData = [
                'nombre' => $this->obtenerValor($datos, $mapeo['nombre'] ?? 'nombre'),
                'descripcion' => $this->obtenerValor($datos, $mapeo['descripcion'] ?? 'descripcion'),
                'ruta_archivo' => $this->obtenerValor($datos, $mapeo['ruta_archivo'] ?? 'ruta_archivo'),
                'usuario_id' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ];

            if (empty($documentoData['nombre'])) {
                return [
                    'exito' => false,
                    'error' => 'Nombre del documento es requerido'
                ];
            }

            Documento::create($documentoData);
            return ['exito' => true];

        } catch (\Exception $e) {
            return [
                'exito' => false,
                'error' => 'Error procesando documento: ' . $e->getMessage()
            ];
        }
    }

    private function procesarSerie(array $datos, array $configuracion): array
    {
        try {
            $mapeo = $configuracion['mapeo'] ?? [];
            
            $serieData = [
                'codigo' => $this->obtenerValor($datos, $mapeo['codigo'] ?? 'codigo'),
                'nombre' => $this->obtenerValor($datos, $mapeo['nombre'] ?? 'nombre'),
                'descripcion' => $this->obtenerValor($datos, $mapeo['descripcion'] ?? 'descripcion'),
                'created_at' => now(),
                'updated_at' => now()
            ];

            if (empty($serieData['codigo']) || empty($serieData['nombre'])) {
                return [
                    'exito' => false,
                    'error' => 'Código y nombre son requeridos'
                ];
            }

            if (SerieDocumental::where('codigo', $serieData['codigo'])->exists()) {
                return [
                    'exito' => false,
                    'error' => 'Serie ya existe: ' . $serieData['codigo']
                ];
            }

            SerieDocumental::create($serieData);
            return ['exito' => true];

        } catch (\Exception $e) {
            return [
                'exito' => false,
                'error' => 'Error procesando serie: ' . $e->getMessage()
            ];
        }
    }

    private function procesarUsuario(array $datos, array $configuracion): array
    {
        try {
            $mapeo = $configuracion['mapeo'] ?? [];
            
            $userData = [
                'name' => $this->obtenerValor($datos, $mapeo['name'] ?? 'name'),
                'email' => $this->obtenerValor($datos, $mapeo['email'] ?? 'email'),
                'password' => bcrypt('temporal123'),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ];

            if (empty($userData['name']) || empty($userData['email'])) {
                return [
                    'exito' => false,
                    'error' => 'Nombre y email son requeridos'
                ];
            }

            if (User::where('email', $userData['email'])->exists()) {
                return [
                    'exito' => false,
                    'error' => 'Usuario ya existe: ' . $userData['email']
                ];
            }

            User::create($userData);
            return ['exito' => true];

        } catch (\Exception $e) {
            return [
                'exito' => false,
                'error' => 'Error procesando usuario: ' . $e->getMessage()
            ];
        }
    }

    private function detectarFormato(UploadedFile $archivo): string
    {
        $extension = strtolower($archivo->getClientOriginalExtension());
        
        switch ($extension) {
            case 'csv':
                return ImportacionDatos::FORMATO_CSV;
            case 'xlsx':
            case 'xls':
                return ImportacionDatos::FORMATO_EXCEL;
            case 'json':
                return ImportacionDatos::FORMATO_JSON;
            default:
                return ImportacionDatos::FORMATO_CSV;
        }
    }

    private function analizarArchivo(string $rutaArchivo, string $formato): array
    {
        try {
            $rutaCompleta = Storage::path($rutaArchivo);
            
            if ($formato === ImportacionDatos::FORMATO_CSV) {
                $csv = Reader::createFromPath($rutaCompleta, 'r');
                $csv->setHeaderOffset(0);
                return [
                    'total_registros' => count(iterator_to_array($csv->getRecords())),
                    'metadatos' => [
                        'headers' => $csv->getHeader(),
                        'delimiter' => $csv->getDelimiter()
                    ]
                ];
            }
            
            return [
                'total_registros' => 0,
                'metadatos' => []
            ];
        } catch (\Exception $e) {
            return [
                'total_registros' => 0,
                'metadatos' => ['error' => $e->getMessage()]
            ];
        }
    }

    private function obtenerValor(array $datos, string $campo, $defecto = null)
    {
        return $datos[$campo] ?? $defecto;
    }

    private function guardarArchivoErrores(ImportacionDatos $importacion, array $errores)
    {
        $nombreArchivo = 'errores_' . $importacion->id . '_' . time() . '.json';
        $rutaArchivo = 'importaciones/errores/' . $nombreArchivo;
        
        Storage::put($rutaArchivo, json_encode($errores, JSON_PRETTY_PRINT));
        
        $importacion->update(['archivo_errores' => $rutaArchivo]);
    }
}
