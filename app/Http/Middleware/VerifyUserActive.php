<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerifyUserActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si el usuario está autenticado
        if (Auth::check()) {
            $user = Auth::user();
            
            // Verificar si el usuario puede acceder al sistema
            if (!$user->puedeAcceder()) {
                // Cerrar sesión automáticamente
                Auth::logout();
                
                // Invalidar la sesión
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                // Redirigir al login con mensaje
                return redirect()->route('login')->with('error', 'Tu cuenta ha sido desactivada o suspendida. Contacta al administrador.');
            }
        }
        
        return $next($request);
    }
}
