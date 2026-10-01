<script setup lang="ts">
import Logo from '@/components/Logo.vue'
import AimingArcher from '@/components/AimingArcher.vue'

defineProps<{ title: string, description?: string }>()
</script>

<template>
    <div class="flex min-h-screen cursor-crosshair flex-col items-center justify-center gap-6 p-6">
        <Logo />
        <div class="pop relative w-full max-w-sm rounded-3xl border-2 border-orange-100 bg-white p-6">
            <AimingArcher class="absolute -top-[59px] right-2 h-[68px] w-[90px]" />
            <h1 class="text-2xl font-bold">
                {{ title }}
            </h1>
            <p
                v-if="description"
                class="mt-1 text-sm text-stone-500"
            >
                {{ description }}
            </p>
            <div class="mt-5">
                <slot />
            </div>
        </div>
    </div>
</template>
