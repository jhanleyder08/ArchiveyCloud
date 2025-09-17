import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
            detectTls: false,
            valetTls: false,
        }),
        react(),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
        extensions: ['.js', '.ts', '.jsx', '.tsx', '.json'],
    },
    server: {
        host: '192.168.2.202', // IP específica de la máquina
        port: 5173,
        strictPort: true,
        hmr: {
            port: 5173,
            // Eliminar host fijo para permitir HMR desde red externa
        },
        cors: {
            origin: '*', // Permite cualquier origen
            methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            allowedHeaders: ['Content-Type', 'Authorization', 'X-Requested-With'],
            credentials: true,
        },
    },
    esbuild: {
        jsx: 'automatic',
    },
});
