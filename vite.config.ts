import inertia from '@inertiajs/vite'
import { wayfinder } from '@laravel/vite-plugin-wayfinder'
import ui from '@nuxt/ui/vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import { bunny } from 'laravel-vite-plugin/fonts'
import { defineConfig } from 'vite'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts', 'resources/js/landing.ts'],
            refresh: true,
            fonts: [
                bunny('Fredoka', { weights: [400, 500, 600, 700] }),
                bunny('Nunito', { weights: [400, 600, 700, 800] }),
                bunny('Press Start 2P', { weights: [400] })
            ]
        }),
        inertia(),
        // Nuxt UI registers @tailwindcss/vite itself. Light only: the art
        // direction is playful and bright, no dark mode.
        ui({
            router: 'inertia',
            colorMode: false,
            // Icons ship in the bundle (from @iconify-json/lucide) instead of
            // being fetched from api.iconify.design: no visitor IP sent to a
            // third party, nothing to declare in the privacy policy.
            icon: { clientBundle: { scan: true } },
            ui: {
                colors: {
                    primary: 'orange',
                    secondary: 'sky',
                    neutral: 'stone'
                }
            }
        }),
        vue({
            template: {
                transformAssetUrls: { base: null, includeAbsolute: false }
            }
        }),
        wayfinder({ formVariants: true })
    ]
})
