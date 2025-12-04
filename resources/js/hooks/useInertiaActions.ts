import { router } from '@inertiajs/react';
import { toast } from 'sonner';

interface UseInertiaActionsOptions {
    preserveState?: boolean;
    preserveScroll?: boolean;
    only?: string[];
}

interface ActionOptions extends UseInertiaActionsOptions {
    onSuccess?: (data?: any) => void;
    onError?: (errors?: any) => void;
    onForbidden?: () => void;
    successMessage?: string;
    errorMessage?: string;
    confirmMessage?: string;
}

// Función global para mostrar modal de acceso denegado
let showAccessDeniedModal: ((message?: string) => void) | null = null;

export function setAccessDeniedHandler(handler: (message?: string) => void) {
    showAccessDeniedModal = handler;
}

/**
 * Hook personalizado para manejar acciones de Inertia sin recargar la página completa
 * Proporciona métodos optimizados para create, update, delete con feedback visual
 */
export function useInertiaActions(defaultOptions: UseInertiaActionsOptions = {}) {
    const defaults = {
        preserveState: true,
        preserveScroll: true,
        ...defaultOptions,
    };

    /**
     * Manejar error 403 (Forbidden)
     */
    const handleForbiddenError = (message?: string) => {
        if (showAccessDeniedModal) {
            showAccessDeniedModal(message);
        } else {
            // Fallback: mostrar toast y redirigir
            toast.error(message || 'No tienes permisos para realizar esta acción');
            router.visit('/dashboard');
        }
    };

    /**
     * Crear un nuevo recurso
     */
    const create = (url: string, data: any, options: ActionOptions = {}) => {
        const opts = { ...defaults, ...options };

        const requestOptions: Record<string, any> = {
            preserveState: opts.preserveState,
            preserveScroll: opts.preserveScroll,
            onSuccess: (page: any) => {
                if (opts.successMessage) {
                    toast.success(opts.successMessage);
                }
                opts.onSuccess?.(page);
            },
            onError: (errors: any) => {
                // Verificar si es error 403
                if (errors?.message?.includes('permisos') || errors?.error === 'FORBIDDEN') {
                    handleForbiddenError(errors.message);
                    opts.onForbidden?.();
                    return;
                }
                
                if (opts.errorMessage) {
                    toast.error(opts.errorMessage);
                } else {
                    // Mostrar el primer error
                    const firstError = Object.values(errors)[0];
                    if (typeof firstError === 'string') {
                        toast.error(firstError);
                    }
                }
                opts.onError?.(errors);
            },
        };

        // Solo agregar 'only' si está definido
        if (opts.only && opts.only.length > 0) {
            requestOptions.only = opts.only;
        }

        router.post(url, data, requestOptions);
    };

    /**
     * Actualizar un recurso existente
     */
    const update = (url: string, data: any, options: ActionOptions = {}) => {
        const opts = { ...defaults, ...options };

        const requestOptions: Record<string, any> = {
            preserveState: opts.preserveState,
            preserveScroll: opts.preserveScroll,
            onSuccess: (page: any) => {
                if (opts.successMessage) {
                    toast.success(opts.successMessage);
                }
                opts.onSuccess?.(page);
            },
            onError: (errors: any) => {
                // Verificar si es error 403
                if (errors?.message?.includes('permisos') || errors?.error === 'FORBIDDEN') {
                    handleForbiddenError(errors.message);
                    opts.onForbidden?.();
                    return;
                }
                
                if (opts.errorMessage) {
                    toast.error(opts.errorMessage);
                } else {
                    const firstError = Object.values(errors)[0];
                    if (typeof firstError === 'string') {
                        toast.error(firstError);
                    }
                }
                opts.onError?.(errors);
            },
        };

        if (opts.only && opts.only.length > 0) {
            requestOptions.only = opts.only;
        }

        router.put(url, data, requestOptions);
    };

    /**
     * Actualización parcial de un recurso
     */
    const patch = (url: string, data: any, options: ActionOptions = {}) => {
        const opts = { ...defaults, ...options };

        const requestOptions: Record<string, any> = {
            preserveState: opts.preserveState,
            preserveScroll: opts.preserveScroll,
            onSuccess: (page: any) => {
                if (opts.successMessage) {
                    toast.success(opts.successMessage);
                }
                opts.onSuccess?.(page);
            },
            onError: (errors: any) => {
                // Verificar si es error 403
                if (errors?.message?.includes('permisos') || errors?.error === 'FORBIDDEN') {
                    handleForbiddenError(errors.message);
                    opts.onForbidden?.();
                    return;
                }
                
                if (opts.errorMessage) {
                    toast.error(opts.errorMessage);
                } else {
                    const firstError = Object.values(errors)[0];
                    if (typeof firstError === 'string') {
                        toast.error(firstError);
                    }
                }
                opts.onError?.(errors);
            },
        };

        if (opts.only && opts.only.length > 0) {
            requestOptions.only = opts.only;
        }

        router.patch(url, data, requestOptions);
    };

    /**
     * Eliminar un recurso
     */
    const destroy = (url: string, options: ActionOptions = {}) => {
        const opts = { ...defaults, ...options };

        const performDelete = () => {
            const requestOptions: Record<string, any> = {
                preserveState: opts.preserveState,
                preserveScroll: opts.preserveScroll,
                onSuccess: (page: any) => {
                    if (opts.successMessage) {
                        toast.success(opts.successMessage);
                    }
                    opts.onSuccess?.(page);
                },
                onError: (errors: any) => {
                    // Verificar si es error 403
                    if (errors?.message?.includes('permisos') || errors?.error === 'FORBIDDEN') {
                        handleForbiddenError(errors.message);
                        opts.onForbidden?.();
                        return;
                    }
                    
                    if (opts.errorMessage) {
                        toast.error(opts.errorMessage);
                    } else {
                        const firstError = Object.values(errors)[0];
                        if (typeof firstError === 'string') {
                            toast.error(firstError);
                        }
                    }
                    opts.onError?.(errors);
                },
            };

            if (opts.only && opts.only.length > 0) {
                requestOptions.only = opts.only;
            }

            router.delete(url, requestOptions);
        };

        // Si hay mensaje de confirmación, mostrar diálogo
        if (opts.confirmMessage) {
            if (confirm(opts.confirmMessage)) {
                performDelete();
            }
        } else {
            performDelete();
        }
    };

    /**
     * Navegar a una ruta manteniendo estado
     */
    const visit = (url: string, options: UseInertiaActionsOptions = {}) => {
        const opts = { ...defaults, ...options };

        const visitOptions: Record<string, any> = {
            preserveState: opts.preserveState,
            preserveScroll: opts.preserveScroll,
        };

        // Solo agregar 'only' si está definido
        if (opts.only && opts.only.length > 0) {
            visitOptions.only = opts.only;
        }

        router.visit(url, visitOptions);
    };

    /**
     * Recargar solo datos específicos sin navegar
     */
    const reload = (options: UseInertiaActionsOptions = {}) => {
        const opts = { ...defaults, ...options };

        const reloadOptions: Record<string, any> = {};

        // Solo agregar 'only' si está definido
        if (opts.only && opts.only.length > 0) {
            reloadOptions.only = opts.only;
        }

        router.reload(reloadOptions);
    };

    return {
        create,
        update,
        patch,
        destroy,
        visit,
        reload,
    };
}
