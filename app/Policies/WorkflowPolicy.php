<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Workflow;
use App\Models\User;

/**
 * Política de Autorización para Workflows
 */
class WorkflowPolicy
{
    /**
     * Verificar si el usuario es administrador
     */
    private function esAdmin(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    /**
     * Verificar antes de todos los checks
     */
    public function before(User $user, string $ability): ?bool
    {
        // Los super-admins pueden todo
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null; // Continuar con los checks normales
    }

    /**
     * Ver listado de workflows
     */
    public function viewAny(User $user): bool
    {
        // Todos los usuarios autenticados pueden ver workflows
        return true;
    }

    /**
     * Ver un workflow específico
     */
    public function view(User $user, Workflow $workflow): bool
    {
        // Ver workflows activos o propios
        return $workflow->activo || $workflow->usuario_creador_id === $user->id || $this->esAdmin($user);
    }

    /**
     * Crear workflows
     */
    public function create(User $user): bool
    {
        // Admins y usuarios con permiso
        return $this->esAdmin($user) || $user->can('crear_workflows');
    }

    /**
     * Actualizar workflow
     */
    public function update(User $user, Workflow $workflow): Response
    {
        // Solo el creador o administradores
        if ($workflow->usuario_creador_id === $user->id || $this->esAdmin($user)) {
            // No permitir editar si hay instancias en progreso
            if ($workflow->instancias()->whereIn('estado', ['pendiente', 'en_progreso'])->exists()) {
                return Response::deny('No se puede editar un workflow con instancias activas');
            }
            
            return Response::allow();
        }

        return Response::deny('No tienes permiso para editar este workflow');
    }

    /**
     * Eliminar workflow
     */
    public function delete(User $user, Workflow $workflow): Response
    {
        // Solo admins pueden eliminar
        if (!$this->esAdmin($user)) {
            return Response::deny('Solo administradores pueden eliminar workflows');
        }

        // No permitir eliminar si hay instancias
        if ($workflow->instancias()->exists()) {
            return Response::deny('No se puede eliminar un workflow con instancias existentes');
        }

        return Response::allow();
    }

    /**
     * Restaurar workflow eliminado
     */
    public function restore(User $user, Workflow $workflow): bool
    {
        return $this->esAdmin($user);
    }

    /**
     * Eliminar permanentemente
     */
    public function forceDelete(User $user, Workflow $workflow): bool
    {
        return $user->hasRole('super-admin');
    }

    /**
     * Iniciar una instancia del workflow
     */
    public function iniciar(User $user, Workflow $workflow): Response
    {
        if (!$workflow->activo) {
            return Response::deny('El workflow no está activo');
        }

        return Response::allow();
    }

    /**
     * Activar/Desactivar workflow
     */
    public function toggleActive(User $user, Workflow $workflow): bool
    {
        return $workflow->usuario_creador_id === $user->id || $this->esAdmin($user);
    }

    /**
     * Ver estadísticas del workflow
     */
    public function viewStatistics(User $user, Workflow $workflow): bool
    {
        return $workflow->usuario_creador_id === $user->id || $this->esAdmin($user);
    }
}
