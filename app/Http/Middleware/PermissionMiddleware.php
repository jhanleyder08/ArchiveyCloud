<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$permissions
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Debe iniciar sesión para acceder.');
        }

        $user = auth()->user();
        
        // Super Administrador tiene acceso automático a TODO
        if ($user->hasRole('Super Administrador')) {
            return $next($request);
        }
        
        // Verificar si el usuario tiene alguno de los permisos requeridos
        $hasPermission = false;
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No tiene permisos para acceder a este recurso.',
                    'required_permissions' => $permissions,
                    'user_role' => $user->role->name ?? 'Sin rol'
                ], 403);
            }

            return redirect()->route('dashboard')->with('error', 
                'No tiene permisos para acceder a esta sección. Permiso requerido: ' . implode(' o ', $permissions)
            );
        }

        return $next($request);
    }
}
