import { usePage } from '@inertiajs/react';

interface AuthData {
    user: any;
    permissions: string[];
}

interface PageProps {
    auth: AuthData;
}

/**
 * Hook para verificar permisos del usuario autenticado
 */
export function usePermissions() {
    const { auth } = usePage<PageProps>().props;
    const permissions = auth?.permissions || [];

    /**
     * Verifica si el usuario es Super Administrador
     */
    const isSuperAdmin = (): boolean => {
        return auth?.user?.role?.name === 'Super Administrador';
    };

    /**
     * Verifica si el usuario tiene un permiso específico
     * Super Administrador tiene todos los permisos automáticamente
     */
    const hasPermission = (permission: string): boolean => {
        if (isSuperAdmin()) return true;
        return permissions.includes(permission);
    };

    /**
     * Verifica si el usuario tiene alguno de los permisos especificados
     * Super Administrador tiene todos los permisos automáticamente
     */
    const hasAnyPermission = (requiredPermissions: string[]): boolean => {
        if (isSuperAdmin()) return true;
        return requiredPermissions.some(permission => permissions.includes(permission));
    };

    /**
     * Verifica si el usuario tiene todos los permisos especificados
     * Super Administrador tiene todos los permisos automáticamente
     */
    const hasAllPermissions = (requiredPermissions: string[]): boolean => {
        if (isSuperAdmin()) return true;
        return requiredPermissions.every(permission => permissions.includes(permission));
    };

    /**
     * Verifica si el usuario tiene un rol específico
     */
    const hasRole = (roleName: string): boolean => {
        return auth?.user?.role?.name === roleName;
    };

    return {
        permissions,
        hasPermission,
        hasAnyPermission,
        hasAllPermissions,
        hasRole,
        isSuperAdmin,
    };
}
