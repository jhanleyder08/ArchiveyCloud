<?php

namespace App\Helpers;

use App\Models\User;

class TwoFactorHelper
{
    /**
     * Verificar si un usuario tiene 2FA habilitado
     */
    public static function isEnabled(User $user): bool
    {
        return $user->hasTwoFactorEnabled();
    }

    /**
     * Obtener el método 2FA del usuario
     */
    public static function getMethod(User $user): ?string
    {
        $twoFactor = $user->twoFactorAuthentication;
        return $twoFactor?->method;
    }

    /**
     * Verificar si el usuario ha completado el desafío 2FA en esta sesión
     */
    public static function hasPassedChallenge(): bool
    {
        return session()->has('2fa_verified_at');
    }

    /**
     * Marcar el desafío 2FA como completado
     */
    public static function markChallengeAsPassed(): void
    {
        session()->put('2fa_verified_at', now());
    }

    /**
     * Limpiar el estado del desafío 2FA
     */
    public static function clearChallengeState(): void
    {
        session()->forget('2fa_verified_at');
    }

    /**
     * Obtener tiempo restante de la sesión 2FA (en minutos)
     */
    public static function getRemainingSessionTime(): ?int
    {
        if (!self::hasPassedChallenge()) {
            return null;
        }

        $verifiedAt = session()->get('2fa_verified_at');
        $sessionLifetime = config('twofactor.session_lifetime', 30);
        $elapsed = now()->diffInMinutes($verifiedAt);
        $remaining = $sessionLifetime - $elapsed;

        return max(0, $remaining);
    }

    /**
     * Verificar si la sesión 2FA ha expirado
     */
    public static function hasSessionExpired(): bool
    {
        if (!self::hasPassedChallenge()) {
            return false;
        }

        $verifiedAt = session()->get('2fa_verified_at');
        $sessionLifetime = config('twofactor.session_lifetime', 30);

        return now()->diffInMinutes($verifiedAt) > $sessionLifetime;
    }

    /**
     * Obtener estadísticas de códigos de recuperación
     */
    public static function getRecoveryCodesStats(User $user): array
    {
        $twoFactor = $user->twoFactorAuthentication;
        
        if (!$twoFactor) {
            return [
                'total' => 0,
                'remaining' => 0,
                'used' => 0,
            ];
        }

        $codes = $twoFactor->recovery_codes ?? [];
        $total = count($codes);

        return [
            'total' => $total,
            'remaining' => $total,
            'used' => 0, // Los códigos hasheados no pueden determinarse como usados sin verificación
        ];
    }

    /**
     * Formatear el nombre del método 2FA
     */
    public static function formatMethodName(string $method): string
    {
        return match($method) {
            'totp' => 'Aplicación de Autenticación (TOTP)',
            'sms' => 'SMS',
            'email' => 'Correo Electrónico',
            default => ucfirst($method),
        };
    }

    /**
     * Obtener icono del método 2FA
     */
    public static function getMethodIcon(string $method): string
    {
        return match($method) {
            'totp' => '📱',
            'sms' => '📲',
            'email' => '📧',
            default => '🔐',
        };
    }

    /**
     * Verificar si un método 2FA está habilitado en la configuración
     */
    public static function isMethodEnabled(string $method): bool
    {
        $enabledMethods = config('twofactor.enabled_methods', []);
        return $enabledMethods[$method] ?? false;
    }

    /**
     * Obtener todos los métodos 2FA disponibles
     */
    public static function getAvailableMethods(): array
    {
        $methods = [];
        $enabledMethods = config('twofactor.enabled_methods', []);

        foreach ($enabledMethods as $method => $enabled) {
            if ($enabled) {
                $methods[] = [
                    'id' => $method,
                    'name' => self::formatMethodName($method),
                    'icon' => self::getMethodIcon($method),
                ];
            }
        }

        return $methods;
    }
}
