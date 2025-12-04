import { ReactNode } from 'react';
import { usePermissions } from '@/hooks/usePermissions';
import AccessDenied from './access-denied';

interface PermissionGateProps {
    children: ReactNode;
    permission: string | string[];
    fallback?: ReactNode;
    showAccessDenied?: boolean;
    accessDeniedMessage?: string;
}

/**
 * Componente que verifica permisos antes de mostrar contenido
 * Si el usuario no tiene el permiso, muestra el componente AccessDenied o un fallback
 */
export function PermissionGate({
    children,
    permission,
    fallback,
    showAccessDenied = true,
    accessDeniedMessage,
}: PermissionGateProps) {
    const { hasPermission, hasAnyPermission, hasRole } = usePermissions();

    // Verificar si tiene el permiso
    const hasAccess = Array.isArray(permission)
        ? hasAnyPermission(permission)
        : hasPermission(permission);

    if (hasAccess) {
        return <>{children}</>;
    }

    // Si es rol Consulta, mostrar mensaje específico
    if (hasRole('Consulta')) {
        if (showAccessDenied) {
            return (
                <AccessDenied
                    title="Acceso Denegado"
                    message={accessDeniedMessage || "Tu rol de Consulta solo permite ver información, no modificarla."}
                />
            );
        }
    }

    // Si hay un fallback personalizado, mostrarlo
    if (fallback) {
        return <>{fallback}</>;
    }

    // Por defecto, mostrar AccessDenied
    if (showAccessDenied) {
        return (
            <AccessDenied
                message={accessDeniedMessage || "No tienes permisos para realizar esta acción."}
            />
        );
    }

    // Si no hay fallback y no se debe mostrar AccessDenied, no mostrar nada
    return null;
}

/**
 * Componente para ocultar elementos si el usuario no tiene permiso
 * Útil para botones de editar, eliminar, etc.
 */
export function HideWithoutPermission({
    children,
    permission,
}: {
    children: ReactNode;
    permission: string | string[];
}) {
    const { hasPermission, hasAnyPermission } = usePermissions();

    const hasAccess = Array.isArray(permission)
        ? hasAnyPermission(permission)
        : hasPermission(permission);

    if (!hasAccess) {
        return null;
    }

    return <>{children}</>;
}

/**
 * Componente para deshabilitar elementos si el usuario no tiene permiso
 * Muestra el elemento pero deshabilitado con un tooltip
 */
export function DisableWithoutPermission({
    children,
    permission,
    message = "No tienes permisos para esta acción",
}: {
    children: ReactNode;
    permission: string | string[];
    message?: string;
}) {
    const { hasPermission, hasAnyPermission } = usePermissions();

    const hasAccess = Array.isArray(permission)
        ? hasAnyPermission(permission)
        : hasPermission(permission);

    if (!hasAccess) {
        return (
            <div className="relative group">
                <div className="opacity-50 pointer-events-none">
                    {children}
                </div>
                <div className="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-50">
                    {message}
                </div>
            </div>
        );
    }

    return <>{children}</>;
}
