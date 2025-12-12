<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
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

        // Excluir rutas de 2FA y configuración del middleware para evitar loops
        $excludedRoutes = [
            'two-factor.challenge',
            'two-factor.verify',
            'two-factor.resend',
            'two-factor.settings',
            'two-factor.enable',
            'two-factor.confirm',
            'two-factor.disable',
            'two-factor.recovery-codes.regenerate',
            'two-factor.recovery-codes.show',
            'logout',
            'login',
            'password.request',
            'password.reset',
        ];

        $currentRoute = $request->route()?->getName();
        if ($currentRoute && in_array($currentRoute, $excludedRoutes)) {
            return $next($request);
        }

        // Verificar si 2FA está habilitado globalmente en el sistema
        $twoFactorGlobalEnabled = $this->isTwoFactorGloballyEnabled();

        if (!$twoFactorGlobalEnabled) {
            return $next($request);
        }

        // Si 2FA global está habilitado, verificar el estado del usuario
        if ($user->hasTwoFactorEnabled()) {
            // El usuario tiene 2FA configurado - verificar si ya pasó la validación
            if (!$request->session()->has('2fa_verified_at')) {
                return redirect()->route('two-factor.challenge');
            }

            // Verificar que la validación no haya expirado (30 minutos)
            $verifiedAt = $request->session()->get('2fa_verified_at');
            if (now()->diffInMinutes($verifiedAt) > 30) {
                $request->session()->forget('2fa_verified_at');
                return redirect()->route('two-factor.challenge');
            }
        } else {
            // El usuario NO tiene 2FA configurado pero es obligatorio
            // Redirigir a configuración de 2FA para que lo active
            if (!$this->isSettingUpTwoFactor($request)) {
                return redirect()->route('two-factor.settings')
                    ->with('warning', 'La autenticación de dos factores es obligatoria. Por favor configúrala para continuar.');
            }
        }

        return $next($request);
    }

    /**
     * Verificar si 2FA está habilitado globalmente en el sistema
     */
    private function isTwoFactorGloballyEnabled(): bool
    {
        return Cache::remember('2fa_global_enabled', 60, function () {
            $config = DB::table('configuraciones_servicios')
                ->where('clave', '2fa_habilitado')
                ->where('activo', true)
                ->first();

            return $config && ($config->valor === 'true' || $config->valor === '1' || $config->valor === true);
        });
    }

    /**
     * Verificar si el usuario está en proceso de configurar 2FA
     */
    private function isSettingUpTwoFactor(Request $request): bool
    {
        $currentRoute = $request->route()?->getName();
        $setupRoutes = [
            'two-factor.settings',
            'two-factor.enable',
            'two-factor.confirm',
        ];

        return $currentRoute && in_array($currentRoute, $setupRoutes);
    }
}