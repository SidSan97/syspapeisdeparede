import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    vue: ['vue', 'vue-router', 'pinia'],
                    lodash: ['lodash'],
                    moment: ['moment'],
                    chart: ['chart.js', 'chartjs-adapter-date-fns', 'chartjs-plugin-datalabels', 'vue-chartjs'],
                    dateFns: ['date-fns'],
                    sweetalert: ['sweetalert2'],
                    vcalendar: ['v-calendar'],
                    cropper: ['cropperjs'],
                    money: ['v-money3'],
                    vform: ['vform'],
                    pagination: ['laravel-vue-pagination'],
                    misc: ['@vuepic/vue-datepicker', '@aacassandra/vue3-progressbar'],
                },
            },
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                silenceDeprecations: [
                    'import',
                    'color-functions',
                    'global-builtin',
                ],
            },
        },
    },
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
            '@': path.resolve(__dirname, './resources/js'),
            '@assets': path.resolve(__dirname, './resources/assets'),
        },
    },
});
