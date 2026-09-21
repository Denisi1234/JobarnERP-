import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/css/uswds.scss',
                'resources/js/app.js',
                'resources/css/filament/app/theme.css'
            ],
            refresh: true,
        }),
    ],
});
