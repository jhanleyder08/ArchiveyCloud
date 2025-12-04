<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Tiempo máximo de inactividad en minutos
     */
    const INACTIVITY_TIMEOUT = 10;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $now = now();
            
            // Obtener la última actividad desde cache
            $lastActivity = Cache::get("user_last_activity_{$user->id}");
            
            // Si hay actividad previa, verificar si ha pasado el tiempo límite
            if ($lastActivity) {
                $lastActivityTime = \Carbon\Carbon::parse($lastActivity);
                $minutesInactive = $now->diffInMinutes($lastActivityTime);
                
                // Si ha estado inactivo más de 10 minutos, cerrar sesión
                if ($minutesInactive >= self::INACTIVITY_TIMEOUT) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    
                    // Limpiar cache de actividad
                    Cache::forget("user_last_activity_{$user->id}");
                    
                    // Si es una petición AJAX/API, devolver respuesta JSON
                    if ($request->expectsJson()) {
                        return response()->json([
                            'message' => 'Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente.',
                            'session_expired' => true,
                            'redirect' => route('login')
                        ], 401);
                    }
                    
                    // Para peticiones normales, redirigir al login
                    return redirect()->route('login')
                        ->with('warning', 'Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente.');
                }
            }
            
            // Actualizar la última actividad en cache (expira en 15 minutos para limpiar automáticamente)
            Cache::put("user_last_activity_{$user->id}", $now->toDateTimeString(), 15);
            
            // Agregar información de sesión a la respuesta para JavaScript
            $response = $next($request);
            
            // Solo para respuestas Inertia/web
            if (!$request->expectsJson() && method_exists($response, 'getContent')) {
                // Agregar datos de sesión para el frontend
                $timeRemaining = self::INACTIVITY_TIMEOUT * 60; // En segundos
                if ($lastActivity) {
                    $elapsed = $now->diffInSeconds(\Carbon\Carbon::parse($lastActivity));
                    $timeRemaining = max(0, (self::INACTIVITY_TIMEOUT * 60) - $elapsed);
                }
                
                // Compartir datos con Inertia
                \Inertia\Inertia::share([
                    'session' => [
                        'timeout_minutes' => self::INACTIVITY_TIMEOUT,
                        'time_remaining' => $timeRemaining,
                        'last_activity' => $now->toDateTimeString(),
                    ]
                ]);
            }
            
            return $response;
        }

        return $next($request);
    }
}
