<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Auth\Events\Registered;

class AdminUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::with('role')
            ->when(request('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->when(request('status'), function ($query, $status) {
                if ($status === 'active') {
                    $query->whereNotNull('email_verified_at')->where('active', true);
                } elseif ($status === 'inactive') {
                    $query->where('active', false);
                } elseif ($status === 'pending') {
                    $query->whereNull('email_verified_at');
                }
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => User::count(),
            'active' => User::whereNotNull('email_verified_at')->where('active', true)->count(),
            'pending' => User::whereNull('email_verified_at')->count(),
        ];

        // Obtener todos los roles disponibles para los formularios
        $roles = Role::where('activo', true)
            ->orderBy('nivel_jerarquico')
            ->get(['id', 'name', 'description']);

        return Inertia::render('admin/users', [
            'users' => $users,
            'stats' => $stats,
            'roles' => $roles,
            'filters' => request()->only(['search', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::where('activo', true)->orderBy('nivel_jerarquico')->get(['id', 'name', 'description']);
        
        return Inertia::render('admin/users/create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role_id' => 'required|exists:roles,id',
            'verify_email' => 'nullable|boolean', // Opcional: verificar email automáticamente
        ], [
            'name.required' => 'El nombre es obligatorio',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe ser válido',
            'email.unique' => 'Este email ya está registrado',
            'password.required' => 'La contraseña es obligatoria',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'role_id.required' => 'Debe seleccionar un rol',
            'role_id.exists' => 'El rol seleccionado no existe',
        ]);

        // Convertir role_id a entero si viene como string
        $roleId = is_numeric($validated['role_id']) ? (int)$validated['role_id'] : $validated['role_id'];
        
        // Verificar si se debe verificar el email automáticamente
        $verifyEmail = $request->has('verify_email') && 
                      ($request->input('verify_email') === true || 
                       $request->input('verify_email') === 'true' || 
                       $request->input('verify_email') === '1' ||
                       $request->boolean('verify_email'));
        
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $roleId,
            'active' => true,
            'estado_cuenta' => User::ESTADO_ACTIVO,
            // Si el admin marca verificar email, se verifica automáticamente
            // Si no, se deja null para que el usuario verifique por correo
            'email_verified_at' => $verifyEmail ? now() : null,
        ]);

        // Disparar evento Registered para enviar correo de verificación
        // Solo si el email NO está verificado automáticamente
        if (!$verifyEmail) {
            event(new Registered($user));
        }

        $message = $verifyEmail
            ? 'Usuario creado exitosamente. El email fue verificado automáticamente.'
            : 'Usuario creado exitosamente. Se ha enviado un correo de verificación.';

        return redirect()->route('admin.users.index')
            ->with('success', $message);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $user->load('role');
        
        return Inertia::render('admin/users/show', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $roles = Role::where('activo', true)->orderBy('nivel_jerarquico')->get(['id', 'name', 'description']);
        
        return Inertia::render('admin/users/edit', [
            'user' => $user->load('role'),
            'roles' => $roles,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role_id' => 'required|exists:roles,id',
            'active' => 'boolean',
        ]);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'active' => $request->boolean('active', true),
        ];

        // Solo actualizar contraseña si se proporciona
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Evitar que el usuario se elimine a sí mismo
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario eliminado exitosamente.');
    }

    /**
     * Toggle user status (activate/deactivate)
     */
    public function toggleStatus(User $user)
    {
        // Evitar que el usuario se desactive a sí mismo
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $user->update(['active' => !$user->active]);
        
        $status = $user->active ? 'activado' : 'desactivado';
        
        return redirect()->route('admin.users.index')
            ->with('success', "Usuario {$status} exitosamente.");
    }
}
