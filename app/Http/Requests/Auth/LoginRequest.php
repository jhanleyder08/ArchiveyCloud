<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Verificar estado del usuario y permisos de acceso
        $user = Auth::user();
        if ($user) {
            // Verificar si el email está verificado
            if (is_null($user->email_verified_at)) {
                Auth::logout();
                
                throw ValidationException::withMessages([
                    'email' => 'Tu cuenta aún no ha sido verificada. Por favor, revisa tu correo electrónico y haz clic en el enlace de verificación que te hemos enviado.',
                ]);
            }
            
            // Verificar si el usuario puede acceder al sistema
            if (!$user->puedeAcceder()) {
                Auth::logout();
                
                // Registrar intento de acceso de usuario desactivado
                $user->registrarIntentoFallido();
                
                $mensaje = 'Tu cuenta no está disponible para acceder al sistema.';
                
                // Personalizar mensaje según el estado
                switch ($user->estado_cuenta) {
                    case $user::ESTADO_INACTIVO:
                        $mensaje = 'Tu cuenta está desactivada. Contacta al administrador para más información.';
                        break;
                    case $user::ESTADO_BLOQUEADO:
                        $mensaje = 'Tu cuenta está bloqueada temporalmente. Intenta más tarde o contacta al administrador.';
                        break;
                    case $user::ESTADO_SUSPENDIDO:
                        $mensaje = 'Tu cuenta ha sido suspendida. Contacta al administrador para más información.';
                        break;
                    case $user::ESTADO_VENCIDO:
                        $mensaje = 'Tu cuenta ha vencido. Contacta al administrador para renovarla.';
                        break;
                }
                
                throw ValidationException::withMessages([
                    'email' => $mensaje,
                ]);
            }
            
            // Registrar acceso exitoso
            $user->registrarAccesoExitoso();
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return $this->string('email')
            ->lower()
            ->append('|'.$this->ip())
            ->transliterate()
            ->value();
    }
}
