<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        // Excluir rutas de 2FA del middleware para evitar loops
        $excludedRoutes = [
            'two-factor.challenge',
            'two-factor.verify',
            'two-factor.resend',
            'logout'
        ];

        if (in_array($request->route()->getName(), $excludedRoutes)) {
            return $next($request);
        }

        // Verificar si el usuario tiene 2FA habilitado
        if ($user->hasTwoFactorEnabled()) {
            // Verificar si ya pasó la validación 2FA en esta sesión
            if (!$request->session()->has('2fa_verified_at')) {
                // Redirigir a la página de verificación 2FA
                return redirect()->route('two-factor.challenge');
            }

            // Verificar que la validación no haya expirado (30 minutos)
            $verifiedAt = $request->session()->get('2fa_verified_at');
            if (now()->diffInMinutes($verifiedAt) > 30) {
                $request->session()->forget('2fa_verified_at');
                return redirect()->route('two-factor.challenge');
            }
        }

        return $next($request);
    }
}
