<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3'
import { computed, watch } from 'vue'
import Logo from '@/components/Logo.vue'
import { hub, logout, settings, stats, today } from '@/routes'
import rules from '@/routes/rules'

const page = usePage()
const toast = useToast()
const user = computed(() => page.props.auth.user!)

const nav = computed(() => [
    { label: 'Today', icon: 'i-lucide-target', href: today.url(), active: page.url === today.url() || page.url.startsWith(today.url() + '?') },
    { label: 'Rules', icon: 'i-lucide-dices', href: rules.index.url(), active: page.url.startsWith(rules.index.url()) },
    { label: 'Stats', icon: 'i-lucide-chart-column', href: stats.url(), active: page.url.startsWith(stats.url()) },
    { label: 'Hub', icon: 'i-lucide-store', href: hub.url(), active: page.url.startsWith(hub.url()) },
    { label: 'Settings', icon: 'i-lucide-settings', href: settings.url(), active: page.url.startsWith(settings.url()) }
])

watch(() => page.props.status, (status) => {
    if (status) {
        toast.add({ title: status, color: 'success', icon: 'i-lucide-party-popper' })
    }
}, { immediate: true })
</script>

<template>
    <div class="min-h-screen pb-24 sm:pb-10">
        <header class="mx-auto flex max-w-3xl items-center justify-between gap-4 px-4 pt-5 pb-4">
            <Link :href="today.url()">
                <Logo />
            </Link>

            <nav class="hidden items-center gap-1 rounded-2xl bg-white p-1 shadow-sm ring-1 ring-orange-100 sm:flex">
                <Link
                    v-for="item in nav"
                    :key="item.label"
                    :href="item.href"
                    class="flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-sm font-bold transition"
                    :class="item.active ? 'bg-orange-500 text-white' : 'text-stone-600 hover:bg-orange-50'"
                >
                    <UIcon
                        :name="item.icon"
                        class="size-4"
                    />
                    {{ item.label }}
                </Link>
            </nav>

            <div class="flex items-center gap-2">
                <span
                    class="pop flex items-center gap-1 rounded-full bg-amber-300 px-3 py-1 text-sm font-extrabold text-amber-900"
                    title="Your points"
                >
                    ★ {{ user.points }}
                </span>
                <UButton
                    icon="i-lucide-log-out"
                    color="neutral"
                    variant="ghost"
                    aria-label="Log out"
                    @click="router.post(logout.url())"
                />
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4">
            <slot />
        </main>

        <!-- Phone: app-like bottom tabs (installable PWA). -->
        <nav class="fixed inset-x-3 bottom-3 z-10 grid grid-cols-5 rounded-2xl bg-white p-1.5 shadow-lg ring-1 ring-orange-100 sm:hidden">
            <Link
                v-for="item in nav"
                :key="item.label"
                :href="item.href"
                class="flex flex-col items-center gap-0.5 rounded-xl py-1.5 text-xs font-bold"
                :class="item.active ? 'bg-orange-500 text-white' : 'text-stone-500'"
            >
                <UIcon
                    :name="item.icon"
                    class="size-5"
                />
                {{ item.label }}
            </Link>
        </nav>
    </div>
</template>
