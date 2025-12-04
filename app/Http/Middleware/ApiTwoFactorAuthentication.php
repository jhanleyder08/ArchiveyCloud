<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiTwoFactorAuthentication
{
    /**
     * Handle an incoming request.
     * 
     * Este middleware verifica si el usuario API ha completado la verificación 2FA
     * cuando está habilitado para su cuenta.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'No autenticado',
            ], 401);
        }

        // Verificar si el usuario tiene 2FA habilitado
        if ($user->hasTwoFactorEnabled()) {
            // Para API, verificamos si se proporcionó un código 2FA válido en el header
            $twoFactorCode = $request->header('X-Two-Factor-Code');

            if (!$twoFactorCode) {
                return response()->json([
                    'error' => 'Two Factor Required',
                    'message' => 'Se requiere código de autenticación de dos factores',
                    'two_factor_required' => true,
                    'method' => $user->getTwoFactorMethod(),
                ], 403);
            }

            // Verificar el código 2FA
            $twoFactorService = app(\App\Services\TwoFactorAuthenticationService::class);
            
            $verified = false;
            $method = $user->getTwoFactorMethod();

            if ($method === 'totp') {
                $verified = $twoFactorService->verify($user, $twoFactorCode);
            } elseif (in_array($method, ['sms', 'email'])) {
                $verified = $twoFactorService->verifyChallenge($user, $twoFactorCode);
            }

            if (!$verified) {
                return response()->json([
                    'error' => 'Invalid Two Factor Code',
                    'message' => 'Código de autenticación de dos factores inválido',
                ], 403);
            }

            // Marcar como verificado en la petición actual
            $request->attributes->set('2fa_verified', true);
        }

        return $next($request);
    }
}
