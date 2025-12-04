import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
// Import wayfinder to ensure global functions are available
import './wayfinder';
// Import Ziggy to make route() function available globally
import { Ziggy } from './ziggy';
import { route } from 'ziggy-js';
import axios from 'axios';
import { setAccessDeniedHandler } from './hooks/useInertiaActions';

// Make route function available globally
window.route = route;
window.Ziggy = Ziggy;

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Función para mostrar el modal de acceso denegado
const showAccessDenied = (message?: string) => {
    const defaultMessage = 'No tienes permisos para realizar esta acción. Tu rol actual solo permite consultar información.';
    if (window.__accessDeniedHandler) {
        window.__accessDeniedHandler(message || defaultMessage);
    } else {
        alert(message || defaultMessage);
    }
};

// Configurar interceptor de Axios para errores 403
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 403) {
            // Solo mostrar modal si es una respuesta JSON de la API (no archivos estáticos)
            const contentType = error.response?.headers?.['content-type'] || '';
            const isJsonResponse = contentType.includes('application/json');
            const url = error.config?.url || '';
            const isStaticFile = /\.(pdf|jpg|jpeg|png|gif|doc|docx|xls|xlsx|zip|rar)$/i.test(url);
            
            if (isJsonResponse && !isStaticFile) {
                const message = error.response?.data?.message;
                showAccessDenied(message);
            }
        }
        return Promise.reject(error);
    }
);

// Interceptar errores de Inertia antes de que se procesen
router.on('error', (event) => {
    console.log('Inertia error event:', event);
});

// Configurar listener para respuestas inválidas de Inertia (incluyendo 403)
router.on('invalid', (event) => {
    const response = (event.detail as any)?.response;
    console.log('Inertia invalid response:', response?.status);
    
    if (response?.status === 403) {
        event.preventDefault();
        
        // Mostrar modal inmediatamente con mensaje por defecto
        const defaultMessage = 'No tienes permisos para realizar esta acción. Tu rol actual solo permite consultar información.';
        
        // Intentar obtener mensaje específico si es posible
        if (typeof response.json === 'function') {
            response.json().then((data: any) => {
                showAccessDenied(data?.message || defaultMessage);
            }).catch(() => {
                showAccessDenied(defaultMessage);
            });
        } else {
            showAccessDenied(defaultMessage);
        }
    }
});

// Interceptar navegación de Inertia para capturar errores 403
router.on('navigate', (event) => {
    // Este evento se dispara después de cada navegación
});

// Escuchar errores globales de fetch/XHR para Inertia
const originalFetch = window.fetch;
window.fetch = async (...args) => {
    const response = await originalFetch(...args);
    if (response.status === 403) {
        const url = typeof args[0] === 'string' ? args[0] : (args[0] as Request).url;
        const isStaticFile = /\.(pdf|jpg|jpeg|png|gif|doc|docx|xls|xlsx|zip|rar)$/i.test(url);
        if (!isStaticFile) {
            try {
                const clonedResponse = response.clone();
                const data = await clonedResponse.json();
                showAccessDenied(data?.message);
            } catch {
                showAccessDenied();
            }
        }
    }
    return response;
};

// Registrar el handler global para useInertiaActions
setAccessDeniedHandler((message) => {
    showAccessDenied(message);
});

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
