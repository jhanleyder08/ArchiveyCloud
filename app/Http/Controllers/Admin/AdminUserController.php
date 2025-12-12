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
     * LISTAR USUARIOS
     * Este método obtiene todos los usuarios de la base de datos
     * y los envía al frontend (React) usando Inertia.js
     */
    public function index()
    {
        // 1. CONSULTAR USUARIOS con su rol relacionado
        // User::with('role') = Obtener usuarios CON su rol (relación)
        $users = User::with('role')
            // Filtro de búsqueda: si hay parámetro 'search' en la URL
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
                } elseif ($status === 'without_role') {
                    $query->whereNull('role_id');
                }
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // 2. ESTADÍSTICAS para mostrar en las tarjetas del dashboard
        $stats = [
            'total' => User::count(),
            'active' => User::whereNotNull('email_verified_at')->where('active', true)->count(),
            'pending' => User::whereNull('email_verified_at')->count(),
            'without_role' => User::whereNull('role_id')->count(),
            'deleted' => User::onlyTrashed()->count(), // Usuarios eliminados
        ];

        // 3. OBTENER ROLES para el select del formulario
        // Solo roles activos, ordenados por jerarquía
        $roles = Role::where('activo', true)
            ->orderBy('nivel_jerarquico')
            ->get(['id', 'name', 'description']);

        // 4. ENVIAR DATOS AL FRONTEND con Inertia.js
        // 'admin/users' = ruta del componente React: resources/js/pages/admin/users.tsx
        // Los datos se convierten en PROPS del componente React
        return Inertia::render('admin/users', [
            'users' => $users,       // Lista de usuarios paginada
            'stats' => $stats,       // Estadísticas (total, activos, etc.)
            'roles' => $roles,       // Lista de roles para el select
            'filters' => request()->only(['search', 'status']), // Filtros actuales
        ]);
    }

    /**
     * MOSTRAR FORMULARIO DE CREAR
     * Muestra la página con el formulario para crear un nuevo usuario
     */
    public function create()
    {
        $roles = Role::where('activo', true)->orderBy('nivel_jerarquico')->get(['id', 'name', 'description']);
        
        return Inertia::render('admin/users/create', [
            'roles' => $roles,
        ]);
    }

    /**
     * GUARDAR NUEVO USUARIO
     * Recibe los datos del formulario desde React (via Inertia)
     * Valida, crea el usuario en la BD y redirecciona
     */
    public function store(Request $request)
    {
        // 1. VALIDAR DATOS del formulario
        // Si la validación falla, Laravel devuelve los errores automáticamente
        $validated = $request->validate([
            'name' => 'required|string|max:255',           // Nombre obligatorio
            'email' => 'required|string|email|max:255|unique:users,email,NULL,id,deleted_at,NULL', // Email único
            'password' => ['required', 'confirmed', Rules\Password::defaults()], // Contraseña confirmada
            'role_id' => 'required|exists:roles,id',       // Rol debe existir en la tabla roles
            'verify_email' => 'nullable|boolean',
            
            // Campos adicionales de información personal y laboral
            'documento_identidad' => 'nullable|string|unique:users,documento_identidad,NULL,id,deleted_at,NULL',
            'tipo_documento' => 'nullable|in:cedula_ciudadania,cedula_extranjeria,pasaporte,tarjeta_identidad',
            'telefono' => 'nullable|string|max:20',
            'cargo' => 'nullable|string|max:255',
            'dependencia' => 'nullable|string|max:255',
            'fecha_ingreso' => 'nullable|date',
            'fecha_vencimiento_cuenta' => 'nullable|date|after:today',
        ], [
            'name.required' => 'El nombre es obligatorio',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe ser válido',
            'email.unique' => 'Este email ya está registrado en un usuario activo',
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
        
        // 2. CREAR USUARIO en la base de datos
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']), // Encriptar contraseña
            'role_id' => $roleId,                             // Asignar rol
            'active' => true,
            'estado_cuenta' => User::ESTADO_ACTIVO,
            'email_verified_at' => $verifyEmail ? now() : null,
            
            // Información personal y laboral
            'documento_identidad' => $validated['documento_identidad'] ?? null,
            'tipo_documento' => $validated['tipo_documento'] ?? 'cedula_ciudadania',
            'telefono' => $validated['telefono'] ?? null,
            'cargo' => $validated['cargo'] ?? null,
            'dependencia' => $validated['dependencia'] ?? null,
            'fecha_ingreso' => $validated['fecha_ingreso'] ?? now(),
            'fecha_vencimiento_cuenta' => $validated['fecha_vencimiento_cuenta'] ?? null,
            
            // Campos de seguridad (valores por defecto)
            'intentos_fallidos' => 0,
            'cambio_password_requerido' => false,
            'fecha_ultimo_cambio_password' => now(),
        ]);

        // 3. SINCRONIZAR ROL en tabla user_roles (REQ-CS-004: Auditoría de roles)
        $user->roles()->attach($roleId, [
            'vigencia_desde' => now(),
            'vigencia_hasta' => null,  // Rol permanente
            'temporal' => false,
            'activo' => true,
            'asignado_por' => auth()->id(),
            'observaciones' => 'Rol inicial asignado al crear usuario',
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
     * VER DETALLE DE USUARIO
     * Muestra la información completa de un usuario específico
     */
    public function show(User $user)
    {
        $user->load('role');
        
        return Inertia::render('admin/users/show', [
            'user' => $user,
        ]);
    }

    /**
     * MOSTRAR FORMULARIO DE EDITAR
     * Carga los datos del usuario para editarlos
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
     * ACTUALIZAR USUARIO (CAMBIAR ROL)
     * Recibe los datos editados y actualiza en la BD
     * Aquí es donde se cambia el rol del usuario
     */
    public function update(Request $request, User $user)
    {
        // 1. VALIDAR datos de edición
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id . ',id,deleted_at,NULL',
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role_id' => 'required|exists:roles,id',
            'active' => 'boolean',
            
            // Campos adicionales de información personal y laboral
            'documento_identidad' => 'nullable|string|unique:users,documento_identidad,' . $user->id . ',id,deleted_at,NULL',
            'tipo_documento' => 'nullable|in:cedula_ciudadania,cedula_extranjeria,pasaporte,tarjeta_identidad',
            'telefono' => 'nullable|string|max:20',
            'cargo' => 'nullable|string|max:255',
            'dependencia' => 'nullable|string|max:255',
            'fecha_ingreso' => 'nullable|date',
            'fecha_vencimiento_cuenta' => 'nullable|date|after:today',
        ]);

        // 2. DETECTAR si se cambió el rol
        $roleChanged = $user->role_id != $request->role_id;
        $isCurrentUser = $user->id === auth()->id();

        // 3. PREPARAR datos para actualizar
        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,  // ← AQUÍ SE CAMBIA EL ROL
            'active' => $request->boolean('active', true),
            
            // Información personal y laboral
            'documento_identidad' => $request->documento_identidad,
            'tipo_documento' => $request->tipo_documento ?? 'cedula_ciudadania',
            'telefono' => $request->telefono,
            'cargo' => $request->cargo,
            'dependencia' => $request->dependencia,
            'fecha_ingreso' => $request->fecha_ingreso,
            'fecha_vencimiento_cuenta' => $request->fecha_vencimiento_cuenta,
        ];

        // Solo actualizar contraseña si se proporciona una nueva
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        // 4. GUARDAR cambios en la base de datos
        $user->update($updateData);

        $message = 'Usuario actualizado exitosamente.';
        
        // Si se cambió el rol, sincronizar también en tabla user_roles
        // REQ-CS-004: Gestión de roles con auditoría completa
        if ($roleChanged) {
            $newRole = \App\Models\Role::find($request->role_id);
            
            // Sincronizar con tabla user_roles (mantiene historial y auditoría)
            $user->roles()->sync([
                $request->role_id => [
                    'vigencia_desde' => now(),
                    'vigencia_hasta' => null,  // Rol permanente (sin fecha de expiración)
                    'temporal' => false,
                    'activo' => true,
                    'asignado_por' => auth()->id(),
                    'observaciones' => 'Rol asignado desde panel de administración',
                ]
            ]);
            
            $message .= " Nuevo rol: {$newRole->name}";
            
            // Si el usuario editó su propio rol, recargar completamente la página
            // para actualizar los permisos y el sidebar
            if ($isCurrentUser) {
                $message .= ' Los cambios se aplicarán inmediatamente.';
            }
        }

        // 5. REDIRECCIONAR con mensaje
        return redirect()->route('admin.users.index')
            ->with('success', $message);
    }

    /**
     * ELIMINAR USUARIO
     * Usa soft delete (borrado suave) para mantener historial
     * El usuario se marca como eliminado pero no se borra de la BD
     */
    public function destroy(User $user)
    {
        // Evitar que el usuario se elimine a sí mismo
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        // Soft delete: marca como eliminado pero mantiene el registro
        // Esto permite:
        // 1. Mantener historial de quién creó documentos, expedientes, etc.
        // 2. Reutilizar el email para crear un nuevo usuario
        // 3. Cumplir con restricciones de foreign keys
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario eliminado exitosamente. El correo electrónico puede ser reutilizado para crear un nuevo usuario.');
    }

    /**
     * ACTIVAR/DESACTIVAR USUARIO
     * Cambia el estado activo del usuario sin eliminarlo
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
