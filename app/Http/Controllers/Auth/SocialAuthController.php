<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Exception;

/**
 * Controlador de Autenticación Social (SSO)
 * Soporta: Google, Microsoft, Azure AD
 */
class SocialAuthController extends Controller
{
    /**
     * Redireccionar a proveedor
     * 
     * GET /auth/{provider}
     */
    public function redirectToProvider(string $provider)
    {
        $this->validateProvider($provider);

        // Cuando Laravel Socialite esté instalado:
        // return Socialite::driver($provider)->redirect();
        
        return response()->json([
            'message' => 'Redirigir a ' . $provider,
            'redirect_url' => $this->getProviderAuthUrl($provider),
        ]);
    }

    /**
     * Callback del proveedor
     * 
     * GET /auth/{provider}/callback
     */
    public function handleProviderCallback(string $provider)
    {
        $this->validateProvider($provider);

        try {
            // Cuando Laravel Socialite esté instalado:
            // $socialUser = Socialite::driver($provider)->user();
            
            // Por ahora, estructura base
            $socialUser = $this->getMockSocialUser($provider);
            
            // Buscar o crear usuario
            $user = $this->findOrCreateUser($socialUser, $provider);
            
            // Login
            Auth::login($user, true);
            
            return redirect()->intended('/dashboard')
                ->with('success', 'Inicio de sesión exitoso con ' . ucfirst($provider));

        } catch (Exception $e) {
            return redirect('/login')
                ->with('error', 'Error al autenticar con ' . ucfirst($provider) . ': ' . $e->getMessage());
        }
    }

    /**
     * Encontrar o crear usuario desde datos sociales
     */
    private function findOrCreateUser($socialUser, string $provider): User
    {
        // Buscar por email
        $user = User::where('email', $socialUser->email)->first();

        if ($user) {
            // Actualizar info del proveedor
            $user->update([
                $provider . '_id' => $socialUser->id,
                $provider . '_token' => $socialUser->token ?? null,
                $provider . '_refresh_token' => $socialUser->refreshToken ?? null,
            ]);

            return $user;
        }

        // Crear nuevo usuario
        return User::create([
            'name' => $socialUser->name,
            'email' => $socialUser->email,
            'email_verified_at' => now(),
            'password' => Hash::make(Str::random(32)), // Password aleatorio
            $provider . '_id' => $socialUser->id,
            $provider . '_token' => $socialUser->token ?? null,
            $provider . '_refresh_token' => $socialUser->refreshToken ?? null,
            'avatar' => $socialUser->avatar ?? null,
        ]);
    }

    /**
     * Desvincular cuenta social
     * 
     * POST /auth/{provider}/disconnect
     */
    public function disconnectProvider(Request $request, string $provider)
    {
        $this->validateProvider($provider);

        $user = $request->user();

        $user->update([
            $provider . '_id' => null,
            $provider . '_token' => null,
            $provider . '_refresh_token' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta de ' . ucfirst($provider) . ' desvinculada exitosamente',
        ]);
    }

    /**
     * Obtener cuentas vinculadas
     * 
     * GET /auth/connected-accounts
     */
    public function connectedAccounts(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'accounts' => [
                'google' => [
                    'connected' => !empty($user->google_id),
                    'email' => $user->google_id ? $user->email : null,
                ],
                'microsoft' => [
                    'connected' => !empty($user->microsoft_id),
                    'email' => $user->microsoft_id ? $user->email : null,
                ],
                'azure' => [
                    'connected' => !empty($user->azure_id),
                    'email' => $user->azure_id ? $user->email : null,
                ],
            ],
        ]);
    }

    /**
     * Validar proveedor soportado
     */
    private function validateProvider(string $provider): void
    {
        $allowedProviders = ['google', 'microsoft', 'azure', 'github'];

        if (!in_array($provider, $allowedProviders)) {
            abort(404, 'Proveedor no soportado');
        }
    }

    /**
     * Obtener URL de autorización del proveedor
     */
    private function getProviderAuthUrl(string $provider): string
    {
        $clientId = config("services.{$provider}.client_id");
        $redirectUri = config("services.{$provider}.redirect");

        return match($provider) {
            'google' => "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => 'openid email profile',
            ]),
            'microsoft' => "https://login.microsoftonline.com/common/oauth2/v2.0/authorize?" . http_build_query([
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => 'openid email profile',
            ]),
            'azure' => "https://login.microsoftonline.com/" . config('services.azure.tenant_id') . "/oauth2/v2.0/authorize?" . http_build_query([
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => 'openid email profile',
            ]),
            default => '#',
        };
    }

    /**
     * Mock de usuario social (para testing)
     */
    private function getMockSocialUser(string $provider): object
    {
        return (object) [
            'id' => '123456789',
            'name' => 'Usuario de ' . ucfirst($provider),
            'email' => 'usuario@' . $provider . '.com',
            'avatar' => 'https://via.placeholder.com/150',
            'token' => Str::random(60),
            'refreshToken' => Str::random(60),
        ];
    }
}
