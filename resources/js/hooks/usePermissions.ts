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
     * Verifica si el usuario tiene un permiso específico
     */
    const hasPermission = (permission: string): boolean => {
        return permissions.includes(permission);
    };

    /**
     * Verifica si el usuario tiene alguno de los permisos especificados
     */
    const hasAnyPermission = (requiredPermissions: string[]): boolean => {
        return requiredPermissions.some(permission => permissions.includes(permission));
    };

    /**
     * Verifica si el usuario tiene todos los permisos especificados
     */
    const hasAllPermissions = (requiredPermissions: string[]): boolean => {
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
    };
}
