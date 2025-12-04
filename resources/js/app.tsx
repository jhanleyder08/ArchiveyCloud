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

// Configurar interceptor de Axios para errores 403
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 403) {
            const message = error.response?.data?.message || 'No tienes permisos para realizar esta acción';
            
            // Intentar mostrar el modal usando el handler registrado
            if (window.__accessDeniedHandler) {
                window.__accessDeniedHandler(message);
            } else {
                // Fallback: mostrar alerta
                alert(message);
            }
        }
        return Promise.reject(error);
    }
);

// Registrar el handler global para useInertiaActions
setAccessDeniedHandler((message) => {
    if (window.__accessDeniedHandler) {
        window.__accessDeniedHandler(message);
    }
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
