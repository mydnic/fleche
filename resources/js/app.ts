import { createInertiaApp } from '@inertiajs/vue3'
import UApp from '@nuxt/ui/components/App.vue'
import ui from '@nuxt/ui/vue-plugin'
import { createApp, h } from 'vue'

createInertiaApp({
    title: title => (title ? `${title} · Fleche` : 'Fleche'),
    progress: { color: '#f97316' },
    setup ({ el, App, props, plugin }) {
        createApp({ render: () => h(UApp, null, () => h(App, props)) })
            .use(plugin)
            .use(ui)
            .mount(el!)
    }
})

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js')
}
