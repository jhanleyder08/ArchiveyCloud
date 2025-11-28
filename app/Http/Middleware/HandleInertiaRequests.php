<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user = $request->user();
        
        // Obtener permisos del usuario si está autenticado
        $permissions = [];
        if ($user) {
            try {
                // Cargar todas las relaciones de permisos y el rol
                $user->load(['role.permisos', 'permisos', 'roles.permisos']);
                
                // IMPORTANTE: Si el usuario es Super Administrador, no necesita cargar permisos
                // El frontend manejará esto automáticamente
                $isSuperAdmin = $user->role && $user->role->name === 'Super Administrador';
                
                if ($isSuperAdmin) {
                    // Para Super Admin, podemos retornar un array vacío o todos los permisos
                    // El hook usePermissions ya maneja esto, pero agreguemos todos los permisos
                    // para compatibilidad con otras partes del sistema
                    $allPermisos = \App\Models\Permiso::pluck('nombre')->toArray();
                    $permissions = $allPermisos;
                } else {
                    // Obtener todos los permisos del usuario (del rol principal + roles adicionales + permisos directos)
                    $permisos = collect();
                    
                    // 1. Permisos del rol principal
                    if ($user->role && $user->role->permisos) {
                        $permisos = $permisos->merge($user->role->permisos);
                    }
                    
                    // 2. Permisos de roles adicionales (relación many-to-many)
                    if ($user->roles) {
                        foreach ($user->roles as $role) {
                            if ($role->permisos) {
                                $permisos = $permisos->merge($role->permisos);
                            }
                        }
                    }
                    
                    // 3. Permisos directos del usuario (relación many-to-many)
                    if ($user->permisos) {
                        $permisos = $permisos->merge($user->permisos);
                    }
                    
                    // Crear array de nombres de permisos únicos
                    $permissions = $permisos->pluck('nombre')->unique()->values()->toArray();
                }
            } catch (\Exception $e) {
                // En caso de error, el usuario no tendrá permisos (más seguro)
                \Log::warning('Error cargando permisos del usuario: ' . $e->getMessage());
                $permissions = [];
            }
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $user,
                'permissions' => $permissions,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
