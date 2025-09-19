<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;

class VerifyEmailController extends Controller
{
    /**
     * Mark the user's email address as verified.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        // Validar que la URL esté firmada correctamente
        if (! URL::hasValidSignature($request)) {
            return redirect()->route('login')->with('error', 'Link de verificación inválido o expirado.');
        }

        // Encontrar el usuario por ID
        $user = User::find($request->route('id'));
        
        if (!$user) {
            return redirect()->route('login')->with('error', 'Usuario no encontrado.');
        }

        // Verificar el hash del email
        $hash = sha1($user->getEmailForVerification());
        if (!hash_equals($hash, (string) $request->route('hash'))) {
            return redirect()->route('login')->with('error', 'Link de verificación inválido.');
        }

        // Si ya está verificado
        if ($user->hasVerifiedEmail()) {
            // Autenticar al usuario si no está logueado
            if (!Auth::check()) {
                Auth::login($user);
            }
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        // Marcar como verificado
        if ($user->markEmailAsVerified()) {
            event(new \Illuminate\Auth\Events\Verified($user));
        }

        // Autenticar al usuario después de la verificación
        if (!Auth::check()) {
            Auth::login($user);
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1')
            ->with('success', '¡Email verificado exitosamente!');
    }
}
