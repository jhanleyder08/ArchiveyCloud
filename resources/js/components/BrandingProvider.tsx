import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';

interface BrandingConfig {
    app_name: string;
    app_description: string;
    color_primario: string;
    color_secundario: string;
    tema_predeterminado: string;
    logo_principal: string | null;
    logo_secundario: string | null;
    favicon: string | null;
}

interface PageProps {
    branding?: BrandingConfig;
    [key: string]: unknown;
}

/**
 * Convierte un color hexadecimal a valores RGB
 */
function hexToRgb(hex: string): { r: number; g: number; b: number } | null {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result
        ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16),
        }
        : null;
}

/**
 * Componente que aplica los colores de branding como variables CSS globales
 */
export default function BrandingProvider({ children }: { children?: React.ReactNode }) {
    const pageProps = usePage<PageProps>().props;
    const branding = pageProps?.branding;

    useEffect(() => {
        if (!branding) return;

        const root = document.documentElement;

        // Aplicar color primario
        if (branding.color_primario) {
            root.style.setProperty('--brand-primary', branding.color_primario);
            const primaryRgb = hexToRgb(branding.color_primario);
            if (primaryRgb) {
                root.style.setProperty('--brand-primary-rgb', `${primaryRgb.r}, ${primaryRgb.g}, ${primaryRgb.b}`);
            }
        }

        // Aplicar color secundario
        if (branding.color_secundario) {
            root.style.setProperty('--brand-secondary', branding.color_secundario);
            const secondaryRgb = hexToRgb(branding.color_secundario);
            if (secondaryRgb) {
                root.style.setProperty('--brand-secondary-rgb', `${secondaryRgb.r}, ${secondaryRgb.g}, ${secondaryRgb.b}`);
            }
        }

        // Actualizar favicon si está configurado
        if (branding.favicon) {
            const existingFavicon = document.querySelector('link[rel="icon"]');
            if (existingFavicon) {
                existingFavicon.setAttribute('href', branding.favicon);
            } else {
                const favicon = document.createElement('link');
                favicon.rel = 'icon';
                favicon.href = branding.favicon;
                document.head.appendChild(favicon);
            }
        }

        // Actualizar título de la página si es necesario
        if (branding.app_name) {
            // Solo actualizar el nombre base, no el título completo
            document.documentElement.setAttribute('data-app-name', branding.app_name);
        }

    }, [branding]);

    return <>{children}</>;
}

/**
 * Hook para acceder a la configuración de branding
 */
export function useBranding(): BrandingConfig {
    const { branding } = usePage<PageProps>().props;
    
    return branding || {
        app_name: 'ArchiveyCloud',
        app_description: '',
        color_primario: '#2a3d83',
        color_secundario: '#6b7280',
        tema_predeterminado: 'light',
        logo_principal: null,
        logo_secundario: null,
        favicon: null,
    };
}
