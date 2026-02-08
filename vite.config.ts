import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { VitePWA } from 'vite-plugin-pwa';
import { defineConfig } from 'vite';

export default defineConfig({
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

        VitePWA({
            registerType: 'autoUpdate',
            strategies: 'generateSW',
            srcDir: 'resources/js',
            filename: 'sw-custom.ts',

            manifest: {
                name: 'QR Made - Sistema de Gift Cards',
                short_name: 'QR Made',
                description: 'Sistema de gestión de gift cards con QR y notificaciones en tiempo real',
                theme_color: '#191731',
                background_color: '#F8F6F1',
                display: 'standalone',
                orientation: 'portrait',
                scope: '/',
                start_url: '/dashboard',
                icons: [
                    {
                        src: '/icons/icon-192x192.png',
                        sizes: '192x192',
                        type: 'image/png',
                        purpose: 'any',
                    },
                    {
                        src: '/icons/icon-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any',
                    },
                    {
                        src: '/icons/icon-maskable.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'maskable',
                    },
                ],
                screenshots: [
                    {
                        src: '/screenshots/screenshot-mobile-1.png',
                        sizes: '540x720',
                        type: 'image/png',
                        form_factor: 'narrow',
                    },
                ],
            },

            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2,webp}'],
                navigateFallback: null,

                runtimeCaching: [
                    {
                        urlPattern: ({ request }) => request.headers.get('x-inertia') === 'true',
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'inertia-pages',
                            networkTimeoutSeconds: 10,
                            expiration: {
                                maxEntries: 20,
                                maxAgeSeconds: 7 * 24 * 60 * 60,
                            },
                        },
                    },

                    {
                        urlPattern: /\.(?:png|jpg|jpeg|svg|gif|webp)$/,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'images',
                            expiration: {
                                maxEntries: 50,
                                maxAgeSeconds: 30 * 24 * 60 * 60,
                            },
                        },
                    },

                    {
                        urlPattern: /^https:\/\/[^/]+\/api\//,
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'api-calls',
                            networkTimeoutSeconds: 5,
                            expiration: {
                                maxEntries: 20,
                                maxAgeSeconds: 60,
                            },
                        },
                    },
                ],
            },

            devOptions: {
                enabled: true,
                type: 'module',
                navigateFallbackAllowlist: [
                    /^(?!.*\.(?:js|css|webp|png|jpg|jpeg|svg|gif|woff2|woff|ttf|eot)(?:$|\?))/,
                ],
            },
        }),
    ],
    esbuild: {
        jsx: 'automatic',
    },
});
