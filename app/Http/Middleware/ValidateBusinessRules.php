<?php

namespace App\Http\Middleware;

use App\Services\BusinessRulesService;
use App\Models\Documento;
use App\Models\Expediente;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Middleware para aplicar validaciones de reglas de negocio automáticamente
 */
class ValidateBusinessRules
{
    protected BusinessRulesService $businessRules;

    public function __construct(BusinessRulesService $businessRules)
    {
        $this->businessRules = $businessRules;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $validationType
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?string $validationType = null)
    {
        // Solo aplicar en operaciones de escritura
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            return $next($request);
        }

        try {
            // Aplicar validaciones según el tipo especificado
            switch ($validationType) {
                case 'documento':
                    $this->validarDocumento($request);
                    break;
                    
                case 'expediente':
                    $this->validarExpediente($request);
                    break;
                    
                case 'estructura-trd':
                    $this->validarEstructuraTRD($request);
                    break;
                    
                default:
                    // Validación automática basada en la ruta
                    $this->aplicarValidacionAutomatica($request);
            }

            return $next($request);

        } catch (ValidationException $e) {
            // Re-lanzar excepciones de validación de Laravel
            throw $e;
            
        } catch (\Exception $e) {
            Log::error('Error en middleware de validación de reglas de negocio', [
                'url' => $request->url(),
                'method' => $request->method(),
                'validation_type' => $validationType,
                'error' => $e->getMessage()
            ]);

            // En producción, continuar sin bloquear
            if (app()->environment('production')) {
                return $next($request);
            }

            throw $e;
        }
    }

    /**
     * Validar datos de documento
     */
    private function validarDocumento(Request $request): void
    {
        // Validar estructura TRD si se proporcionan datos relacionados
        if ($request->has(['serie_id', 'tipologia_id'])) {
            $datosEstructura = $request->only(['serie_id', 'subserie_id', 'tipologia_id']);
            $resultado = $this->businessRules->validarEstructuraTRD($datosEstructura);
            
            if (!$resultado['valido']) {
                throw ValidationException::withMessages([
                    'estructura_trd' => $resultado['errores']
                ]);
            }
        }

        // Si es actualización de documento existente, validar integridad
        if ($request->route('documento')) {
            $documento = $request->route('documento');
            if ($documento instanceof Documento) {
                $resultado = $this->businessRules->validarIntegridadReferencial($documento, 'update');
                
                if (!$resultado['valido']) {
                    throw ValidationException::withMessages([
                        'integridad' => $resultado['errores']
                    ]);
                }
            }
        }
    }

    /**
     * Validar datos de expediente
     */
    private function validarExpediente(Request $request): void
    {
        $expediente = $request->route('expediente');
        
        if ($expediente instanceof Expediente) {
            // Obtener cambios propuestos
            $cambios = $request->only(['estado', 'fecha_cierre', 'serie_id', 'subserie_id']);
            
            if (!empty($cambios)) {
                $resultado = $this->businessRules->validarReglasExpediente($expediente, $cambios);
                
                if (!$resultado['valido']) {
                    throw ValidationException::withMessages([
                        'reglas_negocio' => $resultado['errores']
                    ]);
                }
            }

            // Validar integridad referencial
            $resultado = $this->businessRules->validarIntegridadReferencial($expediente, 'update');
            
            if (!$resultado['valido']) {
                throw ValidationException::withMessages([
                    'integridad' => $resultado['errores']
                ]);
            }
        }
    }

    /**
     * Validar estructura TRD/CCD
     */
    private function validarEstructuraTRD(Request $request): void
    {
        $datosEstructura = $request->only(['serie_id', 'subserie_id', 'tipologia_id', 'clasificacion_ccd']);
        
        if (!empty(array_filter($datosEstructura))) {
            $resultado = $this->businessRules->validarEstructuraTRD($datosEstructura);
            
            if (!$resultado['valido']) {
                throw ValidationException::withMessages([
                    'estructura_trd' => $resultado['errores']
                ]);
            }
        }
    }

    /**
     * Aplicar validación automática basada en la ruta
     */
    private function aplicarValidacionAutomatica(Request $request): void
    {
        $route = $request->route();
        if (!$route) return;

        $routeName = $route->getName();
        
        // Validaciones automáticas por nombre de ruta
        if (str_contains($routeName, 'documento')) {
            $this->validarDocumento($request);
        } elseif (str_contains($routeName, 'expediente')) {
            $this->validarExpediente($request);
        }
    }
}
