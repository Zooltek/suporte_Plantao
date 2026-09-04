import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { lanCorsOriginAllowlist } from './vite.cors.mjs';
import { resolveViteHmrHost } from './vite.hmr.mjs';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/login-cover.css',
                // Entradas Blade que precisam existir no manifest quando o HMR
                // é desativado para acessos vindos da rede.
                'resources/js/admin/category/category-manager.js',
                'resources/js/admin/categories/index.js',
                'resources/js/admin/categories/create.js',
                'resources/js/admin/categories/edit.js',
                'resources/js/admin/crm/feedback.js',
                'resources/js/admin/departments.js',
                'resources/js/agent/customer/schedule-manager.js',
                'resources/js/agent/monitor/monitor-manager.js',
                'resources/js/category/category-edit.js',
                'resources/js/category-edit.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        manifest: 'manifest.runtime.json',
        assetsDir: 'assets-runtime',
        emptyOutDir: false,
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor-chart': ['chart.js'],
                },
            },
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: false,
        // CORS restrito à LAN privada (RFC1918) + localhost.
        // Lista mantida em `vite.cors.mjs` para ser unitariamente testável.
        cors: {
            origin: lanCorsOriginAllowlist,
        },
        hmr: {
            host: resolveViteHmrHost(process.env),
        },
    },
});
