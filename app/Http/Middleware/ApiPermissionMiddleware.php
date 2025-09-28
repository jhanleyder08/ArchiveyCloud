<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $token = $request->attributes->get('api_token');
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TOKEN_MISSING',
                    'message' => 'Token API requerido'
                ]
            ], 401);
        }
        
        // Verificar si tiene permiso específico
        if (!$this->hasPermission($token, $permission)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INSUFFICIENT_PERMISSIONS',
                    'message' => "Permiso requerido: {$permission}",
                    'details' => [
                        'required_permission' => $permission,
                        'token_permissions' => $token->permisos
                    ]
                ]
            ], 403);
        }
        
        return $next($request);
    }
    
    /**
     * Verificar si el token tiene el permiso requerido
     */
    private function hasPermission($token, string $requiredPermission): bool
    {
        $permissions = $token->permisos ?? [];
        
        // Si tiene permiso admin, puede hacer todo
        if (in_array('admin', $permissions)) {
            return true;
        }
        
        // Verificar permiso exacto
        if (in_array($requiredPermission, $permissions)) {
            return true;
        }
        
        // Verificar wildcards (ej: documentos:* incluye documentos:read, documentos:write, etc.)
        $parts = explode(':', $requiredPermission);
        if (count($parts) === 2) {
            $resource = $parts[0];
            $wildcardPermission = $resource . ':*';
            
            if (in_array($wildcardPermission, $permissions)) {
                return true;
            }
        }
        
        return false;
    }
}
