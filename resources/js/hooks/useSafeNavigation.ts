import { router } from '@inertiajs/react';

/**
 * Hook para navegación segura que evita errores cuando no hay historial
 */
export function useSafeNavigation() {
    /**
     * Navega hacia atrás de forma segura.
     * Si no hay historial, redirige a la URL de fallback (por defecto /dashboard)
     */
    const goBack = (fallbackUrl: string = '/dashboard') => {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            router.visit(fallbackUrl);
        }
    };

    /**
     * Navega hacia atrás o a una URL específica
     */
    const goBackOrTo = (url: string) => {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            router.visit(url);
        }
    };

    return {
        goBack,
        goBackOrTo,
    };
}

export default useSafeNavigation;
