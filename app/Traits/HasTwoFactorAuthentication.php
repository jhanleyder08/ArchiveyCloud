<?php

namespace App\Traits;

use App\Models\TwoFactorAuthentication;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasTwoFactorAuthentication
{
    /**
     * Relación con autenticación de dos factores
     */
    public function twoFactorAuthentication(): HasOne
    {
        return $this->hasOne(TwoFactorAuthentication::class);
    }

    /**
     * Verificar si el usuario tiene 2FA habilitado
     */
    public function hasTwoFactorEnabled(): bool
    {
        $twoFactor = $this->twoFactorAuthentication;
        return $twoFactor && $twoFactor->enabled;
    }

    /**
     * Verificar si el usuario tiene 2FA confirmado
     */
    public function hasTwoFactorConfirmed(): bool
    {
        $twoFactor = $this->twoFactorAuthentication;
        return $twoFactor && $twoFactor->enabled && $twoFactor->isConfirmed();
    }

    /**
     * Obtener el método 2FA del usuario
     */
    public function getTwoFactorMethod(): ?string
    {
        return $this->twoFactorAuthentication?->method;
    }

    /**
     * Verificar si el usuario usa un método específico
     */
    public function usesTwoFactorMethod(string $method): bool
    {
        return $this->getTwoFactorMethod() === $method;
    }

    /**
     * Verificar si el usuario necesita completar el desafío 2FA
     */
    public function needsTwoFactorChallenge(): bool
    {
        if (!$this->hasTwoFactorEnabled()) {
            return false;
        }

        return !session()->has('2fa_verified_at');
    }

    /**
     * Marcar el desafío 2FA como completado para este usuario
     */
    public function markTwoFactorChallengeAsPassed(): void
    {
        session()->put('2fa_verified_at', now());
        session()->put('2fa_user_id', $this->id);
    }

    /**
     * Limpiar el estado del desafío 2FA
     */
    public function clearTwoFactorChallengeState(): void
    {
        session()->forget('2fa_verified_at');
        session()->forget('2fa_user_id');
    }

    /**
     * Verificar si la sesión 2FA ha expirado
     */
    public function hasTwoFactorSessionExpired(): bool
    {
        if (!session()->has('2fa_verified_at')) {
            return true;
        }

        $verifiedAt = session()->get('2fa_verified_at');
        $sessionLifetime = config('twofactor.session_lifetime', 30);

        return now()->diffInMinutes($verifiedAt) > $sessionLifetime;
    }

    /**
     * Obtener estadísticas de códigos de recuperación
     */
    public function getTwoFactorRecoveryCodesStats(): array
    {
        $twoFactor = $this->twoFactorAuthentication;
        
        if (!$twoFactor) {
            return [
                'total' => 0,
                'remaining' => 0,
            ];
        }

        $codes = $twoFactor->recovery_codes ?? [];

        return [
            'total' => count($codes),
            'remaining' => count($codes),
        ];
    }

    /**
     * Scope para usuarios con 2FA habilitado
     */
    public function scopeWithTwoFactorEnabled($query)
    {
        return $query->whereHas('twoFactorAuthentication', function ($q) {
            $q->where('enabled', true);
        });
    }

    /**
     * Scope para usuarios con 2FA deshabilitado
     */
    public function scopeWithoutTwoFactor($query)
    {
        return $query->whereDoesntHave('twoFactorAuthentication')
            ->orWhereHas('twoFactorAuthentication', function ($q) {
                $q->where('enabled', false);
            });
    }

    /**
     * Scope para usuarios con método específico
     */
    public function scopeWithTwoFactorMethod($query, string $method)
    {
        return $query->whereHas('twoFactorAuthentication', function ($q) use ($method) {
            $q->where('enabled', true)->where('method', $method);
        });
    }
}
