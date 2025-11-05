<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Obtener rol "Sin Acceso" para usuarios nuevos
        // Este rol solo permite editar perfil hasta que un admin asigne un rol con permisos
        $rolSinAcceso = Role::where('name', 'Sin Acceso')->first();
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $rolSinAcceso ? $rolSinAcceso->id : null,
            'active' => true,
            'estado_cuenta' => User::ESTADO_ACTIVO,
        ]);

        event(new Registered($user));

        // Hacer login pero redirigir a verificación de email (no al dashboard)
        Auth::login($user);

        // Redirigir a la pantalla de verificación de email en lugar del dashboard
        // El middleware 'verified' bloqueará el acceso al dashboard hasta que se verifique
        return redirect()->route('verification.notice');
    }
}
