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
        host: '0.0.0.0', // Escuchar en todas las interfaces
        port: 5173,
        strictPort: false,
        hmr: {
            host: 'localhost', // Usar localhost para HMR
            port: 5173,
            protocol: 'ws',
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
