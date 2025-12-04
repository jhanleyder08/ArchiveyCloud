<?php

namespace App\Services;

use App\Models\User;
use App\Models\TwoFactorAuthentication;
use App\Models\TwoFactorChallenge;
use App\Models\TwoFactorBackupCode;
use App\Notifications\TwoFactorEnabledNotification;
use App\Notifications\TwoFactorDisabledNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FAQRCode\Google2FA;

class TwoFactorAuthenticationService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Habilitar 2FA para un usuario
     */
    public function enable(User $user, string $method = 'totp'): TwoFactorAuthentication
    {
        $secret = $this->google2fa->generateSecretKey();

        $twoFactor = TwoFactorAuthentication::updateOrCreate(
            ['user_id' => $user->id],
            [
                'enabled' => false, // Se habilitará después de confirmar
                'secret' => encrypt($secret),
                'method' => $method,
                'recovery_codes' => $this->generateRecoveryCodes(),
            ]
        );

        return $twoFactor;
    }

    /**
     * Confirmar y activar 2FA
     */
    public function confirm(User $user, string $code): bool
    {
        $twoFactor = $user->twoFactorAuthentication;

        if (!$twoFactor) {
            return false;
        }

        $secret = decrypt($twoFactor->secret);

        if ($this->google2fa->verifyKey($secret, $code)) {
            $twoFactor->update([
                'enabled' => true,
                'confirmed_at' => now(),
            ]);
            
            // Enviar notificación de seguridad
            $user->notify(new TwoFactorEnabledNotification($twoFactor->method));
            
            return true;
        }

        return false;
    }

    /**
     * Deshabilitar 2FA
     */
    public function disable(User $user): bool
    {
        $twoFactor = $user->twoFactorAuthentication;

        if ($twoFactor) {
            $twoFactor->delete();
            
            // Enviar notificación de seguridad
            $user->notify(new TwoFactorDisabledNotification());
            
            return true;
        }

        return false;
    }

    /**
     * Verificar código 2FA
     */
    public function verify(User $user, string $code): bool
    {
        $twoFactor = $user->twoFactorAuthentication;

        if (!$twoFactor || !$twoFactor->enabled) {
            return false;
        }

        // Intentar con TOTP
        $secret = decrypt($twoFactor->secret);
        if ($this->google2fa->verifyKey($secret, $code, 2)) { // 2 ventanas de tiempo
            return true;
        }

        // Intentar con código de recuperación
        return $this->verifyRecoveryCode($user, $code);
    }

    /**
     * Verificar código de recuperación
     */
    protected function verifyRecoveryCode(User $user, string $code): bool
    {
        $twoFactor = $user->twoFactorAuthentication;
        $recoveryCodes = $twoFactor->recovery_codes ?? [];

        foreach ($recoveryCodes as $index => $hashedCode) {
            if (Hash::check($code, $hashedCode)) {
                // Marcar el código como usado (eliminarlo)
                unset($recoveryCodes[$index]);
                $twoFactor->update([
                    'recovery_codes' => array_values($recoveryCodes),
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Generar códigos de recuperación
     */
    public function generateRecoveryCodes(int $count = 10): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $code = strtoupper(Str::random(10));
            $codes[] = Hash::make($code);
        }

        return $codes;
    }

    /**
     * Regenerar códigos de recuperación
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $twoFactor = $user->twoFactorAuthentication;

        if (!$twoFactor) {
            throw new \Exception('2FA no está habilitado para este usuario');
        }

        $plainCodes = [];
        $hashedCodes = [];

        for ($i = 0; $i < 10; $i++) {
            $code = strtoupper(Str::random(10));
            $plainCodes[] = $code;
            $hashedCodes[] = Hash::make($code);
        }

        $twoFactor->update([
            'recovery_codes' => $hashedCodes,
        ]);

        return $plainCodes;
    }

    /**
     * Generar QR Code para configuración
     */
    public function getQRCodeUrl(User $user): string
    {
        $twoFactor = $user->twoFactorAuthentication;

        if (!$twoFactor) {
            throw new \Exception('2FA no está configurado para este usuario');
        }

        $secret = decrypt($twoFactor->secret);
        $appName = config('app.name', 'SGDEA');

        return $this->google2fa->getQRCodeUrl(
            $appName,
            $user->email,
            $secret
        );
    }

    /**
     * Obtener el secreto para configuración manual
     */
    public function getSecret(User $user): ?string
    {
        $twoFactor = $user->twoFactorAuthentication;

        if (!$twoFactor) {
            return null;
        }

        return decrypt($twoFactor->secret);
    }

    /**
     * Enviar código por SMS
     */
    public function sendCodeViaSMS(User $user): bool
    {
        $twoFactor = $user->twoFactorAuthentication;

        if (!$twoFactor || $twoFactor->method !== 'sms') {
            return false;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Guardar el código en la tabla de challenges
        TwoFactorChallenge::create([
            'user_id' => $user->id,
            'code' => Hash::make($code),
            'method' => 'sms',
            'expires_at' => now()->addMinutes(5),
        ]);

        // TODO: Integrar con servicio SMS (Twilio, etc.)
        // Por ahora solo guardamos el código

        return true;
    }

    /**
     * Enviar código por Email
     */
    public function sendCodeViaEmail(User $user): bool
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expirationMinutes = config('twofactor.code_expiration', 5);

        // Guardar el código en la tabla de challenges
        TwoFactorChallenge::create([
            'user_id' => $user->id,
            'code' => Hash::make($code),
            'method' => 'email',
            'expires_at' => now()->addMinutes($expirationMinutes),
        ]);

        // Enviar email con plantilla HTML
        try {
            \Illuminate\Support\Facades\Mail::send(
                'emails.two-factor-code',
                [
                    'code' => $code,
                    'user' => $user,
                    'expirationMinutes' => $expirationMinutes,
                ],
                function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject(config('twofactor.email.subject', 'Código de Verificación 2FA'));
                }
            );
            return true;
        } catch (\Exception $e) {
            // Log el error pero no fallar el proceso
            \Illuminate\Support\Facades\Log::error('Error enviando email 2FA: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar código de challenge (SMS/Email)
     */
    public function verifyChallenge(User $user, string $code): bool
    {
        $challenge = TwoFactorChallenge::where('user_id', $user->id)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$challenge) {
            return false;
        }

        if (Hash::check($code, $challenge->code)) {
            $challenge->update(['used' => true]);
            return true;
        }

        return false;
    }

    /**
     * Verificar si el usuario tiene 2FA habilitado
     */
    public function isEnabled(User $user): bool
    {
        $twoFactor = $user->twoFactorAuthentication;
        return $twoFactor && $twoFactor->enabled;
    }

    /**
     * Obtener método de 2FA del usuario
     */
    public function getMethod(User $user): ?string
    {
        $twoFactor = $user->twoFactorAuthentication;
        return $twoFactor?->method;
    }
}
