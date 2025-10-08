<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorAuthenticationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TwoFactorAuthenticationController extends Controller
{
    protected TwoFactorAuthenticationService $twoFactorService;

    public function __construct(TwoFactorAuthenticationService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Mostrar configuración de 2FA
     */
    public function index()
    {
        $user = Auth::user();
        $twoFactor = $user->twoFactorAuthentication;

        return Inertia::render('Profile/TwoFactorAuthentication', [
            'enabled' => $twoFactor && $twoFactor->enabled,
            'confirmed' => $twoFactor && $twoFactor->isConfirmed(),
            'method' => $twoFactor?->method ?? 'totp',
        ]);
    }

    /**
     * Habilitar 2FA
     */
    public function enable(Request $request)
    {
        $request->validate([
            'method' => 'required|in:totp,sms,email',
            'phone_number' => 'required_if:method,sms|nullable|string',
        ]);

        $user = Auth::user();
        $method = $request->input('method');

        $twoFactor = $this->twoFactorService->enable($user, $method);

        if ($method === 'totp') {
            $qrCode = $this->twoFactorService->getQRCodeUrl($user);
            $secret = $this->twoFactorService->getSecret($user);

            return response()->json([
                'success' => true,
                'qr_code' => $qrCode,
                'secret' => $secret,
                'message' => 'Escanea el código QR con tu aplicación de autenticación',
            ]);
        } elseif ($method === 'sms') {
            $this->twoFactorService->sendCodeViaSMS($user);
            return response()->json([
                'success' => true,
                'message' => 'Se ha enviado un código de verificación a tu teléfono',
            ]);
        } elseif ($method === 'email') {
            $this->twoFactorService->sendCodeViaEmail($user);
            return response()->json([
                'success' => true,
                'message' => 'Se ha enviado un código de verificación a tu correo',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Método no soportado',
        ], 400);
    }

    /**
     * Confirmar y activar 2FA
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'code' => 'required|string|min:6|max:6',
        ]);

        $user = Auth::user();
        $code = $request->input('code');

        if ($this->twoFactorService->confirm($user, $code)) {
            // Obtener los códigos de recuperación (sin hashear para mostrarlos al usuario)
            $twoFactor = $user->fresh()->twoFactorAuthentication;
            $recoveryCodes = [];
            
            // Generar códigos legibles para mostrar al usuario
            // Nota: estos códigos ya están hasheados en la BD, por lo que necesitamos regenerarlos
            $plainCodes = $this->twoFactorService->regenerateRecoveryCodes($user);
            
            return response()->json([
                'success' => true,
                'recovery_codes' => $plainCodes,
                'message' => 'Autenticación de dos factores activada correctamente',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Código inválido. Por favor intenta nuevamente.',
        ], 400);
    }

    /**
     * Deshabilitar 2FA
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = Auth::user();

        if ($this->twoFactorService->disable($user)) {
            return response()->json([
                'success' => true,
                'message' => 'Autenticación de dos factores deshabilitada',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se pudo deshabilitar la autenticación de dos factores',
        ], 400);
    }

    /**
     * Regenerar códigos de recuperación
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = Auth::user();

        try {
            $codes = $this->twoFactorService->regenerateRecoveryCodes($user);

            return response()->json([
                'success' => true,
                'codes' => $codes,
                'message' => 'Códigos de recuperación regenerados. Guárdalos en un lugar seguro.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mostrar códigos de recuperación
     */
    public function showRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = Auth::user();
        $twoFactor = $user->twoFactorAuthentication;

        if (!$twoFactor) {
            return response()->json([
                'success' => false,
                'message' => '2FA no está habilitado',
            ], 400);
        }

        // Los códigos están hasheados, no podemos mostrarlos
        // Solo indicamos cuántos códigos quedan
        $remainingCodes = count($twoFactor->recovery_codes ?? []);

        return response()->json([
            'success' => true,
            'remaining_codes' => $remainingCodes,
            'message' => 'Los códigos de recuperación solo se muestran una vez al generarlos',
        ]);
    }
}
