<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorAuthenticationService;
use App\Events\TwoFactorAuthenticationEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TwoFactorChallengeController extends Controller
{
    protected TwoFactorAuthenticationService $twoFactorService;

    public function __construct(TwoFactorAuthenticationService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Mostrar formulario de verificación 2FA
     */
    public function show()
    {
        $user = Auth::user();
        $method = $this->twoFactorService->getMethod($user);

        // Enviar código automáticamente para SMS/Email
        if ($method === 'sms') {
            $this->twoFactorService->sendCodeViaSMS($user);
        } elseif ($method === 'email') {
            $this->twoFactorService->sendCodeViaEmail($user);
        }

        return Inertia::render('Auth/TwoFactorChallenge', [
            'method' => $method,
        ]);
    }

    /**
     * Verificar código 2FA
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = Auth::user();
        $code = $request->input('code');
        $method = $this->twoFactorService->getMethod($user);

        $verified = false;

        if ($method === 'totp') {
            $verified = $this->twoFactorService->verify($user, $code);
        } elseif (in_array($method, ['sms', 'email'])) {
            $verified = $this->twoFactorService->verifyChallenge($user, $code);
        }

        if ($verified) {
            // Marcar como verificado en la sesión
            $request->session()->put('2fa_verified_at', now());
            
            // Disparar evento de auditoría
            event(new TwoFactorAuthenticationEvent(
                $user,
                '2fa_verified',
                $method,
                true
            ));

            return response()->json([
                'success' => true,
                'redirect' => route('dashboard'),
            ]);
        }
        
        // Disparar evento de intento fallido
        event(new TwoFactorAuthenticationEvent(
            $user,
            '2fa_verified',
            $method,
            false
        ));

        return response()->json([
            'success' => false,
            'message' => 'Código inválido. Por favor intenta nuevamente.',
        ], 400);
    }

    /**
     * Reenviar código (para SMS/Email)
     */
    public function resend()
    {
        $user = Auth::user();
        $method = $this->twoFactorService->getMethod($user);

        if ($method === 'sms') {
            $this->twoFactorService->sendCodeViaSMS($user);
            return response()->json([
                'success' => true,
                'message' => 'Código enviado a tu teléfono',
            ]);
        } elseif ($method === 'email') {
            $this->twoFactorService->sendCodeViaEmail($user);
            return response()->json([
                'success' => true,
                'message' => 'Código enviado a tu correo',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Este método no requiere reenvío',
        ], 400);
    }
}
