import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/vue.js',
                'resources/css/filament/admin/theme.css',
                // Campo `YoutubeVideoField` del panel. Entrada propia y no
                // parte de `app.js` porque sólo se carga en el formulario que
                // lo usa; el CSS se importa desde el propio módulo, así que
                // `@vite()` inyecta las dos etiquetas con una sola entrada.
                'resources/js/youtube-video-search.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
});
