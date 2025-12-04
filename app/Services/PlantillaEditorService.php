<?php

namespace App\Services;

use App\Models\PlantillaDocumental;
use App\Models\Documento;
use App\Models\PistaAuditoria;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use DOMDocument;
use DOMXPath;

class PlantillaEditorService
{
    /**
     * Crear plantilla desde documento existente
     */
    public function crearPlantillaDesdeDocumento(Documento $documento, array $configuracion): PlantillaDocumental
    {
        try {
            // Extraer contenido HTML del documento si es posible
            $contenidoHtml = $this->extraerContenidoHTML($documento);
            
            // Generar código único de plantilla
            $codigo = $this->generarCodigoPlantilla($configuracion['categoria']);
            
            // Detectar campos variables automáticamente
            $camposVariables = $this->detectarCamposVariables($contenidoHtml);
            
            $plantilla = PlantillaDocumental::create([
                'codigo' => $codigo,
                'nombre' => $configuracion['nombre'],
                'descripcion' => $configuracion['descripcion'] ?? '',
                'categoria' => $configuracion['categoria'],
                'tipo_documento' => $documento->tipo_documento ?? 'documento',
                'serie_documental_id' => $documento->expediente->serie_id ?? null,
                'subserie_documental_id' => $documento->expediente->subserie_id ?? null,
                'contenido_html' => $contenidoHtml,
                'contenido_json' => $this->convertirHtmlAJson($contenidoHtml),
                'campos_variables' => $camposVariables,
                'metadatos_predefinidos' => $this->extraerMetadatos($documento),
                'configuracion_formato' => $this->generarConfiguracionFormato($contenidoHtml),
                'usuario_creador_id' => Auth::id(),
                'estado' => PlantillaDocumental::ESTADO_BORRADOR,
                'es_publica' => false,
                'version' => 1.0,
                'tags' => $configuracion['tags'] ?? []
            ]);

            // Registrar en auditoría
            PistaAuditoria::create([
                'usuario_id' => Auth::id(),
                'accion' => 'crear_plantilla_desde_documento',
                'modelo' => 'PlantillaDocumental',
                'modelo_id' => $plantilla->id,
                'detalles' => [
                    'plantilla' => $plantilla->nombre,
                    'documento_origen' => $documento->nombre,
                    'documento_id' => $documento->id
                ]
            ]);

            return $plantilla;

        } catch (\Exception $e) {
            throw new \Exception('Error al crear plantilla desde documento: ' . $e->getMessage());
        }
    }

    /**
     * Aplicar plantilla a nuevo documento
     */
    public function aplicarPlantilla(PlantillaDocumental $plantilla, array $datos): string
    {
        try {
            $contenido = $plantilla->contenido_html;
            
            // Reemplazar campos variables
            foreach ($plantilla->campos_variables as $campo) {
                $valor = $datos[$campo['nombre']] ?? $campo['valor_default'] ?? '';
                $marcador = $campo['marcador'] ?? "{{$campo['nombre']}}";
                $contenido = str_replace($marcador, $valor, $contenido);
            }
            
            // Aplicar metadatos predefinidos
            if ($plantilla->metadatos_predefinidos) {
                $contenido = $this->aplicarMetadatos($contenido, $plantilla->metadatos_predefinidos, $datos);
            }
            
            // Aplicar formateo
            if ($plantilla->configuracion_formato) {
                $contenido = $this->aplicarFormateo($contenido, $plantilla->configuracion_formato);
            }
            
            return $contenido;

        } catch (\Exception $e) {
            throw new \Exception('Error al aplicar plantilla: ' . $e->getMessage());
        }
    }

    /**
     * Crear nueva versión de plantilla
     */
    public function crearNuevaVersion(PlantillaDocumental $plantilla, array $cambios): PlantillaDocumental
    {
        try {
            $nuevaVersion = $plantilla->version + 0.1;
            
            $nuevaPlantilla = PlantillaDocumental::create([
                'codigo' => $plantilla->codigo,
                'nombre' => $cambios['nombre'] ?? $plantilla->nombre,
                'descripcion' => $cambios['descripcion'] ?? $plantilla->descripcion,
                'categoria' => $plantilla->categoria,
                'tipo_documento' => $plantilla->tipo_documento,
                'serie_documental_id' => $plantilla->serie_documental_id,
                'subserie_documental_id' => $plantilla->subserie_documental_id,
                'contenido_html' => $cambios['contenido_html'] ?? $plantilla->contenido_html,
                'contenido_json' => $cambios['contenido_json'] ?? $plantilla->contenido_json,
                'campos_variables' => $cambios['campos_variables'] ?? $plantilla->campos_variables,
                'metadatos_predefinidos' => $cambios['metadatos_predefinidos'] ?? $plantilla->metadatos_predefinidos,
                'configuracion_formato' => $cambios['configuracion_formato'] ?? $plantilla->configuracion_formato,
                'usuario_creador_id' => Auth::id(),
                'estado' => PlantillaDocumental::ESTADO_BORRADOR,
                'es_publica' => false,
                'version' => $nuevaVersion,
                'plantilla_padre_id' => $plantilla->id,
                'tags' => $cambios['tags'] ?? $plantilla->tags
            ]);

            // Registrar en auditoría
            PistaAuditoria::create([
                'usuario_id' => Auth::id(),
                'accion' => 'crear_version_plantilla',
                'modelo' => 'PlantillaDocumental',
                'modelo_id' => $nuevaPlantilla->id,
                'detalles' => [
                    'plantilla_nueva' => $nuevaPlantilla->nombre,
                    'plantilla_padre' => $plantilla->id,
                    'version_anterior' => $plantilla->version,
                    'version_nueva' => $nuevaVersion
                ]
            ]);

            return $nuevaPlantilla;

        } catch (\Exception $e) {
            throw new \Exception('Error al crear nueva versión: ' . $e->getMessage());
        }
    }

    /**
     * Validar estructura de plantilla
     */
    public function validarEstructura(string $contenidoHtml): array
    {
        $errores = [];
        $advertencias = [];
        
        try {
            $dom = new DOMDocument();
            @$dom->loadHTML($contenidoHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            
            // Validar estructura HTML básica
            if (!$dom->documentElement) {
                $errores[] = 'Estructura HTML inválida';
                return ['valida' => false, 'errores' => $errores, 'advertencias' => $advertencias];
            }
            
            $xpath = new DOMXPath($dom);
            
            // Verificar campos variables bien formados
            $marcadores = $xpath->query('//text()[contains(., "{")]');
            foreach ($marcadores as $marcador) {
                $texto = $marcador->textContent;
                if (preg_match_all('/\{([^}]+)\}/', $texto, $matches)) {
                    foreach ($matches[1] as $campo) {
                        if (empty(trim($campo))) {
                            $errores[] = "Campo variable vacío encontrado: {{$campo}}";
                        }
                    }
                }
            }
            
            // Verificar elementos de formato críticos
            $elementos = ['title', 'h1', 'h2', 'p'];
            foreach ($elementos as $elemento) {
                $nodes = $xpath->query("//{$elemento}");
                if ($nodes->length === 0 && $elemento === 'title') {
                    $advertencias[] = "Se recomienda incluir un elemento <{$elemento}>";
                }
            }
            
            // Verificar tamaño del contenido
            $tamaño = strlen($contenidoHtml);
            if ($tamaño > 1000000) { // 1MB
                $advertencias[] = 'El contenido es muy grande, puede afectar el rendimiento';
            }
            
            return [
                'valida' => empty($errores),
                'errores' => $errores,
                'advertencias' => $advertencias,
                'estadisticas' => [
                    'tamaño_bytes' => $tamaño,
                    'campos_variables' => count($matches[1] ?? []),
                    'elementos_html' => $dom->getElementsByTagName('*')->length
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'valida' => false,
                'errores' => ['Error al validar: ' . $e->getMessage()],
                'advertencias' => []
            ];
        }
    }

    /**
     * Exportar plantilla en diferentes formatos
     */
    public function exportarPlantilla(PlantillaDocumental $plantilla, string $formato = 'json'): string
    {
        switch ($formato) {
            case 'json':
                return $this->exportarComoJson($plantilla);
            case 'html':
                return $this->exportarComoHtml($plantilla);
            case 'xml':
                return $this->exportarComoXml($plantilla);
            case 'docx':
                return $this->exportarComoDocx($plantilla);
            default:
                throw new \Exception("Formato de exportación no soportado: {$formato}");
        }
    }

    /**
     * Importar plantilla desde archivo
     */
    public function importarPlantilla(string $rutaArchivo, string $formato, array $metadatos): PlantillaDocumental
    {
        try {
            $contenido = Storage::get($rutaArchivo);
            
            switch ($formato) {
                case 'json':
                    $datos = json_decode($contenido, true);
                    break;
                case 'html':
                    $datos = $this->parsearHtmlADatos($contenido);
                    break;
                case 'xml':
                    $datos = $this->parsearXmlADatos($contenido);
                    break;
                default:
                    throw new \Exception("Formato de importación no soportado: {$formato}");
            }
            
            $plantilla = PlantillaDocumental::create(array_merge($datos, $metadatos, [
                'codigo' => $this->generarCodigoPlantilla($metadatos['categoria']),
                'usuario_creador_id' => Auth::id(),
                'estado' => PlantillaDocumental::ESTADO_BORRADOR,
                'version' => 1.0
            ]));
            
            return $plantilla;
            
        } catch (\Exception $e) {
            throw new \Exception('Error al importar plantilla: ' . $e->getMessage());
        }
    }

    /**
     * Obtener estadísticas de uso de plantilla
     */
    public function obtenerEstadisticasUso(PlantillaDocumental $plantilla): array
    {
        return [
            'documentos_generados' => Documento::where('plantilla_id', $plantilla->id)->count(),
            'uso_ultimo_mes' => Documento::where('plantilla_id', $plantilla->id)
                ->where('created_at', '>=', now()->subMonth())
                ->count(),
            'usuarios_distintos' => Documento::where('plantilla_id', $plantilla->id)
                ->distinct('usuario_id')
                ->count('usuario_id'),
            'fecha_ultimo_uso' => Documento::where('plantilla_id', $plantilla->id)
                ->latest()
                ->value('created_at'),
            'promedio_uso_mensual' => $this->calcularPromedioUsoMensual($plantilla)
        ];
    }

    /**
     * Generar código único de plantilla
     */
    private function generarCodigoPlantilla(string $categoria): string
    {
        $prefijo = strtoupper(substr($categoria, 0, 3));
        $numero = PlantillaDocumental::where('categoria', $categoria)->count() + 1;
        return $prefijo . '-' . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Extraer contenido HTML del documento
     */
    private function extraerContenidoHTML(Documento $documento): string
    {
        // Implementación básica - en producción se integraría con convertidores
        // de documentos como Apache Tika o conversores específicos
        
        if ($documento->tipo_mime === 'text/html') {
            return Storage::get($documento->ruta_archivo);
        }
        
        // Para otros tipos, generar HTML básico
        return '<html><head><title>' . $documento->nombre . '</title></head><body>' .
               '<h1>' . $documento->nombre . '</h1>' .
               '<p>Contenido extraído de: ' . $documento->tipo_mime . '</p>' .
               '</body></html>';
    }

    /**
     * Detectar campos variables en contenido HTML
     */
    private function detectarCamposVariables(string $contenidoHtml): array
    {
        $campos = [];
        
        // Buscar patrones como {{campo}}, {campo}, [campo], etc.
        $patrones = [
            '/\{\{([^}]+)\}\}/',  // {{campo}}
            '/\{([^}]+)\}/',      // {campo}
            '/\[([^\]]+)\]/',     // [campo]
            '/%([^%]+)%/'         // %campo%
        ];
        
        foreach ($patrones as $patron) {
            if (preg_match_all($patron, $contenidoHtml, $matches)) {
                foreach ($matches[1] as $campo) {
                    $nombreCampo = trim($campo);
                    if (!empty($nombreCampo)) {
                        $campos[] = [
                            'nombre' => $nombreCampo,
                            'marcador' => $matches[0][array_search($campo, $matches[1])],
                            'tipo' => $this->detectarTipoCampo($nombreCampo),
                            'requerido' => true,
                            'valor_default' => ''
                        ];
                    }
                }
            }
        }
        
        return array_unique($campos, SORT_REGULAR);
    }

    /**
     * Detectar tipo de campo basado en el nombre
     */
    private function detectarTipoCampo(string $nombreCampo): string
    {
        $nombreLower = strtolower($nombreCampo);
        
        if (strpos($nombreLower, 'fecha') !== false || strpos($nombreLower, 'date') !== false) {
            return 'date';
        }
        if (strpos($nombreLower, 'numero') !== false || strpos($nombreLower, 'cantidad') !== false) {
            return 'number';
        }
        if (strpos($nombreLower, 'email') !== false || strpos($nombreLower, 'correo') !== false) {
            return 'email';
        }
        if (strpos($nombreLower, 'telefono') !== false || strpos($nombreLower, 'phone') !== false) {
            return 'tel';
        }
        
        return 'text';
    }

    /**
     * Convertir HTML a estructura JSON
     */
    private function convertirHtmlAJson(string $html): array
    {
        try {
            $dom = new DOMDocument();
            @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            
            return $this->domNodoAArray($dom->documentElement);
            
        } catch (\Exception $e) {
            return ['error' => 'No se pudo convertir HTML a JSON: ' . $e->getMessage()];
        }
    }

    /**
     * Convertir nodo DOM a array
     */
    private function domNodoAArray($node): array
    {
        $result = [];
        
        if ($node->nodeType === XML_TEXT_NODE) {
            return trim($node->textContent);
        }
        
        $result['tag'] = $node->nodeName;
        
        if ($node->hasAttributes()) {
            $result['attributes'] = [];
            foreach ($node->attributes as $attr) {
                $result['attributes'][$attr->name] = $attr->value;
            }
        }
        
        if ($node->hasChildNodes()) {
            $result['children'] = [];
            foreach ($node->childNodes as $child) {
                $childArray = $this->domNodoAArray($child);
                if (!empty($childArray)) {
                    $result['children'][] = $childArray;
                }
            }
        }
        
        return $result;
    }

    /**
     * Extraer metadatos del documento
     */
    private function extraerMetadatos(Documento $documento): array
    {
        return [
            'documento_origen' => $documento->nombre,
            'tipo_documento' => $documento->tipo_documento,
            'expediente' => $documento->expediente->codigo ?? null,
            'serie' => $documento->expediente->serie->nombre ?? null,
            'subserie' => $documento->expediente->subserie->nombre ?? null,
            'usuario_creador' => $documento->usuario->name ?? null,
            'fecha_creacion' => $documento->created_at->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Generar configuración de formato
     */
    private function generarConfiguracionFormato(string $html): array
    {
        return [
            'margen_superior' => '2.5cm',
            'margen_inferior' => '2.5cm',
            'margen_izquierdo' => '3cm',
            'margen_derecho' => '2cm',
            'fuente_principal' => 'Arial, sans-serif',
            'tamaño_fuente' => '12pt',
            'interlineado' => '1.5',
            'orientacion' => 'portrait',
            'tamaño_papel' => 'A4'
        ];
    }

    /**
     * Aplicar metadatos al contenido
     */
    private function aplicarMetadatos(string $contenido, array $metadatos, array $datos): string
    {
        foreach ($metadatos as $clave => $valor) {
            $marcador = "{{{$clave}}}";
            $valorFinal = $datos[$clave] ?? $valor;
            $contenido = str_replace($marcador, $valorFinal, $contenido);
        }
        
        return $contenido;
    }

    /**
     * Aplicar formateo al contenido
     */
    private function aplicarFormateo(string $contenido, array $configuracion): string
    {
        // Aplicar estilos CSS básicos
        $estilos = "<style>
            body { 
                font-family: {$configuracion['fuente_principal']}; 
                font-size: {$configuracion['tamaño_fuente']};
                line-height: {$configuracion['interlineado']};
                margin: {$configuracion['margen_superior']} {$configuracion['margen_derecho']} 
                        {$configuracion['margen_inferior']} {$configuracion['margen_izquierdo']};
            }
            @page { 
                size: {$configuracion['tamaño_papel']} {$configuracion['orientacion']}; 
            }
        </style>";
        
        // Insertar estilos en el head
        if (strpos($contenido, '</head>') !== false) {
            $contenido = str_replace('</head>', $estilos . '</head>', $contenido);
        } else {
            $contenido = $estilos . $contenido;
        }
        
        return $contenido;
    }

    /**
     * Exportar como JSON
     */
    private function exportarComoJson(PlantillaDocumental $plantilla): string
    {
        return json_encode([
            'plantilla' => $plantilla->toArray(),
            'metadatos' => [
                'exportado_en' => now()->toISOString(),
                'exportado_por' => Auth::user()->name,
                'version_exportacion' => '1.0'
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Exportar como HTML
     */
    private function exportarComoHtml(PlantillaDocumental $plantilla): string
    {
        return $plantilla->contenido_html;
    }

    /**
     * Exportar como XML
     */
    private function exportarComoXml(PlantillaDocumental $plantilla): string
    {
        $xml = new \SimpleXMLElement('<plantilla></plantilla>');
        $xml->addChild('codigo', htmlspecialchars($plantilla->codigo));
        $xml->addChild('nombre', htmlspecialchars($plantilla->nombre));
        $xml->addChild('descripcion', htmlspecialchars($plantilla->descripcion));
        $xml->addChild('categoria', htmlspecialchars($plantilla->categoria));
        $xml->addChild('contenido', htmlspecialchars($plantilla->contenido_html));
        
        return $xml->asXML();
    }

    /**
     * Exportar como DOCX (placeholder)
     */
    private function exportarComoDocx(PlantillaDocumental $plantilla): string
    {
        // En una implementación real se usaría PHPWord o similar
        throw new \Exception('Exportación a DOCX no implementada aún');
    }

    /**
     * Calcular promedio de uso mensual
     */
    private function calcularPromedioUsoMensual(PlantillaDocumental $plantilla): float
    {
        $mesesExistencia = $plantilla->created_at->diffInMonths(now()) ?: 1;
        $usoTotal = Documento::where('plantilla_id', $plantilla->id)->count();
        
        return round($usoTotal / $mesesExistencia, 2);
    }

    /**
     * Parsear HTML a datos de plantilla
     */
    private function parsearHtmlADatos(string $html): array
    {
        return [
            'contenido_html' => $html,
            'contenido_json' => $this->convertirHtmlAJson($html),
            'campos_variables' => $this->detectarCamposVariables($html)
        ];
    }

    /**
     * Parsear XML a datos de plantilla
     */
    private function parsearXmlADatos(string $xml): array
    {
        $data = simplexml_load_string($xml);
        
        return [
            'codigo' => (string)$data->codigo,
            'nombre' => (string)$data->nombre,
            'descripcion' => (string)$data->descripcion,
            'categoria' => (string)$data->categoria,
            'contenido_html' => (string)$data->contenido
        ];
    }
}
