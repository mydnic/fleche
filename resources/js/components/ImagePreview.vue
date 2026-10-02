<script setup lang="ts">
/**
 * A picture shown as a thumbnail; a click opens it full size in a modal.
 * `thumbClass` sizes the thumbnail (it stays inside it, centered, never
 * overflowing).
 */
withDefaults(defineProps<{ src: string, title?: string, thumbClass?: string, fit?: 'cover' | 'contain' }>(), {
    title: 'Picture',
    thumbClass: 'size-10',
    fit: 'cover'
})
</script>

<template>
    <UModal
        :title="title"
        :ui="{ content: 'max-w-3xl', body: 'flex justify-center bg-stone-50' }"
    >
        <button
            type="button"
            class="group relative grid shrink-0 cursor-zoom-in place-items-center overflow-hidden rounded-xl border-2 border-white bg-white shadow-sm ring-1 ring-stone-200 transition hover:ring-2 hover:ring-orange-300"
            :class="thumbClass"
            :aria-label="`Open picture: ${title}`"
        >
            <img
                :src="src"
                alt=""
                loading="lazy"
                class="size-full transition group-hover:scale-105"
                :class="fit === 'cover' ? 'object-cover' : 'object-contain'"
            >
        </button>

        <template #body>
            <img
                :src="src"
                :alt="title"
                class="max-h-[75vh] w-full rounded-xl object-contain"
            >
        </template>
    </UModal>
</template>
