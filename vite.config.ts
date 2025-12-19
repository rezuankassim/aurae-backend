import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import path from 'node:path';
import { defineConfig } from 'vite';

export default defineConfig({
    optimizeDeps: {
        include: ['qs'],
        // If the error originates from a specific package using qs, be more specific:
        // include: ['@protonemedia/inertiajs-tables-laravel-query-builder > qs'],
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
        }),
        react(),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'), // 👈 '@' → resources/js
        },
    },
    esbuild: {
        jsx: 'automatic',
    },
});
