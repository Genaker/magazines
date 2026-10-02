import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/editor.js',
                'resources/js/gallery.js',
                'resources/js/gallery-composer.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
