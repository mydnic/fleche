import ui from '@nuxt/ui/vue-plugin'
import { createApp } from 'vue'
import LandingDemo from '@/components/LandingDemo.vue'

// The marketing page is Blade; only the sample list is an island of Vue.
const el = document.getElementById('demo')

if (el) {
    createApp(LandingDemo).use(ui).mount(el)
}
