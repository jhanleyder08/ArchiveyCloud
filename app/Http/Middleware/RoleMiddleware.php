<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Debe iniciar sesión para acceder.');
        }

        $user = auth()->user();
        
        // Super Administrador tiene acceso automático a TODO
        if ($user->hasRole('Super Administrador')) {
            return $next($request);
        }
        
        // Verificar si el usuario tiene alguno de los roles requeridos
        $hasRole = false;
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                $hasRole = true;
                break;
            }
        }

        if (!$hasRole) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No tiene permisos para acceder a este recurso.',
                    'required_roles' => $roles,
                    'user_role' => $user->role->name ?? 'Sin rol'
                ], 403);
            }

            return redirect()->route('dashboard')->with('error', 
                'No tiene permisos para acceder a esta sección. Se requiere rol: ' . implode(' o ', $roles)
            );
        }

        return $next($request);
    }
}
