<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permiso;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoleController extends Controller
{
    /**
     * Display a listing of roles with their permissions.
     */
    public function index()
    {
        // Verificar que sea Super Administrador
        $this->authorize('manage-roles');

        $roles = Role::with(['permisos' => function($query) {
            $query->orderBy('categoria')->orderBy('nombre');
        }])
        ->orderBy('nivel_jerarquico')
        ->get()
        ->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'nivel_jerarquico' => $role->nivel_jerarquico,
                'activo' => $role->activo,
                'sistema' => $role->sistema,
                'permisos_count' => $role->permisos->count(),
                'permisos' => $role->permisos->pluck('id')->toArray(),
            ];
        });

        // Obtener todos los permisos agrupados por categoría
        $permisos = Permiso::orderBy('categoria')
            ->orderBy('subcategoria')
            ->orderBy('nombre')
            ->get()
            ->groupBy('categoria')
            ->map(function ($permisos) {
                return $permisos->map(function ($permiso) {
                    return [
                        'id' => $permiso->id,
                        'nombre' => $permiso->nombre,
                        'descripcion' => $permiso->descripcion,
                        'categoria' => $permiso->categoria,
                        'subcategoria' => $permiso->subcategoria,
                    ];
                });
            });

        return Inertia::render('admin/roles', [
            'roles' => $roles,
            'permisos' => $permisos,
        ]);
    }

    /**
     * Update the permissions for a specific role.
     */
    public function updatePermissions(Request $request, Role $role)
    {
        // Verificar que sea Super Administrador
        $this->authorize('manage-roles');

        // No permitir modificar el rol Super Administrador
        if ($role->name === 'Super Administrador') {
            return redirect()->back()
                ->with('error', 'No se puede modificar los permisos del Super Administrador.');
        }

        $request->validate([
            'permisos' => 'required|array',
            'permisos.*' => 'exists:permisos,id',
        ]);

        // Sincronizar permisos
        $role->permisos()->sync($request->permisos);

        return redirect()->back()
            ->with('success', "Permisos actualizados para el rol '{$role->name}' exitosamente.");
    }

    /**
     * Activate or deactivate a role.
     */
    public function toggleStatus(Role $role)
    {
        // Verificar que sea Super Administrador
        $this->authorize('manage-roles');

        // No permitir desactivar roles del sistema
        if ($role->sistema) {
            return redirect()->back()
                ->with('error', 'No se puede desactivar un rol del sistema.');
        }

        $role->update(['activo' => !$role->activo]);
        
        $status = $role->activo ? 'activado' : 'desactivado';
        
        return redirect()->back()
            ->with('success', "Rol '{$role->name}' {$status} exitosamente.");
    }
}
