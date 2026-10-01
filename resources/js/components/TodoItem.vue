<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import PixelArcher from '@/components/PixelArcher.vue'
import { done } from '@/routes/todos'
import type { Todo } from '@/types'

/**
 * `demo`: the landing page's sample list, checked client-side only.
 * `startDelay`: ms to wait before this row's archer pops up, leaving another
 * archer on the page time to duck out first (one archer on screen at a time).
 */
const props = withDefaults(defineProps<{ todo: Todo, today: string, demo?: boolean, startDelay?: number }>(), { startDelay: 0 })
const emit = defineEmits<{ start: [], done: [todo: Todo] }>()

/**
 * idle → aiming (archer pops in, draws) → flying (arrow crosses the row,
 * its trail striking through the name) → hit (checkbox thunks) → request.
 * The request waits for the animation: a reload mid-flight would remove the
 * row before the arrow lands.
 */
const stage = ref<'idle' | 'aiming' | 'flying' | 'hit'>(props.todo.done_at ? 'hit' : 'idle')
const reduceMotion = typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches

const late = computed(() => !props.todo.done_at && props.todo.date < props.today)
const isDone = computed(() => props.todo.done_at !== null || stage.value === 'hit')
const waiting = ref(false)

function check (): void {
    if (stage.value !== 'idle' || props.todo.done_at) {
        return
    }

    const save = (): void => props.demo
        ? emit('done', props.todo)
        : router.post(done.url(props.todo.id), {}, { preserveScroll: true })

    emit('start')

    if (reduceMotion) {
        stage.value = 'hit'
        save()

        return
    }

    const at = (ms: number, step: () => void): number => setTimeout(step, props.startDelay + ms)

    stage.value = 'aiming'
    // Holds the row in "aiming" without the sprite until the delay is over.
    waiting.value = props.startDelay > 0
    at(0, () => (waiting.value = false))
    at(350, () => (stage.value = 'flying'))
    at(750, () => (stage.value = 'hit'))
    at(props.demo ? 750 : 1300, save)
}

</script>

<template>
    <li
        class="pop relative overflow-hidden rounded-2xl border-2 bg-white px-4 py-3 transition"
        :class="isDone ? 'border-green-200 bg-green-50/60' : late ? 'border-amber-200' : 'border-orange-100'"
    >
        <!-- Checkbox and title share one line: the arrow flies along it, and its
             trail strikes the title through. Details sit below, out of the way. -->
        <div class="relative flex items-center gap-3">
            <button
                type="button"
                class="relative grid size-8 shrink-0 place-items-center rounded-xl border-[3px] transition"
                :class="[
                    isDone ? 'border-green-500 bg-green-500 text-white' : 'border-orange-300 bg-orange-50 hover:border-orange-500 hover:bg-orange-100',
                    stage === 'hit' && !todo.done_at ? 'animate-thunk' : ''
                ]"
                :aria-label="`Mark “${todo.name}” as done`"
                :disabled="isDone"
                @click="check"
            >
                <UIcon
                    v-if="isDone"
                    name="i-lucide-check"
                    class="size-5"
                />
            </button>

            <p
                class="min-w-0 flex-1 truncate font-semibold"
                :class="isDone ? 'text-stone-400' : 'text-stone-800'"
            >
                {{ todo.name }}
            </p>

            <UBadge
                v-if="todo.points > 0 && (stage === 'idle' || isDone)"
                color="secondary"
                variant="soft"
                size="sm"
                :label="`+${todo.points} ★`"
            />

            <!-- The arrow's trail doubles as the strike-through. -->
            <span
                v-if="stage === 'flying' || (stage === 'hit' && !todo.done_at)"
                class="trail pointer-events-none absolute top-1/2 right-0 h-[3px] -translate-y-1/2 rounded-full bg-orange-400"
            />

            <!-- Pops up from the row's bottom edge, ducks back down after the shot. -->
            <Transition name="duck">
                <PixelArcher
                    v-if="!waiting && (stage === 'aiming' || stage === 'flying')"
                    :drawn="stage === 'aiming'"
                    class="archer absolute top-[calc(50%-1.5rem)] right-0 h-12 w-11"
                />
            </Transition>
            <template v-if="stage === 'flying'">
                <svg
                    class="arrow absolute top-1/2 h-3 w-10 -translate-y-1/2"
                    viewBox="0 0 16 5"
                    shape-rendering="crispEdges"
                    aria-hidden="true"
                >
                    <rect x="0" y="2" width="2" height="1" fill="#57534e" />
                    <rect x="1" y="1" width="1" height="3" fill="#57534e" />
                    <rect x="2" y="2" width="11" height="1" fill="#a16207" />
                    <rect x="12" y="0" width="1" height="2" fill="#f97316" />
                    <rect x="13" y="1" width="2" height="1" fill="#f97316" />
                    <rect x="12" y="3" width="1" height="2" fill="#f97316" />
                    <rect x="13" y="3" width="2" height="1" fill="#f97316" />
                </svg>
            </template>
        </div>

        <!-- Pulled up under the title: the checkbox makes the first line tall. -->
        <div
            v-if="todo.description || todo.image_url"
            class="-mt-1.5 pl-11"
        >
            <p
                v-if="todo.description"
                class="line-clamp-2 text-xs leading-snug whitespace-pre-line text-stone-400"
            >
                {{ todo.description }}
            </p>
            <a
                v-if="todo.image_url"
                :href="todo.image_url"
                target="_blank"
                rel="noopener"
                class="mt-2 block w-fit"
            >
                <img
                    :src="todo.image_url"
                    alt=""
                    loading="lazy"
                    class="max-h-40 rounded-xl border-2 border-orange-100 object-contain"
                    :class="isDone ? 'opacity-50 grayscale' : ''"
                >
            </a>
        </div>
    </li>
</template>

<style scoped>
.archer {
    animation: pop-in 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.duck-leave-active {
    transition: transform 0.25s ease-in, opacity 0.25s ease-in;
}

.duck-leave-to {
    transform: translateY(120%);
    opacity: 0;
}

.arrow {
    animation: fly 0.4s cubic-bezier(0.5, 0, 0.9, 0.6) forwards;
}

.trail {
    animation: trail 0.4s cubic-bezier(0.5, 0, 0.9, 0.6) forwards;
}

.animate-thunk {
    animation: thunk 0.35s ease-out;
}

@keyframes pop-in {
    from { transform: translateY(100%) scale(0.6); opacity: 0; }
    to { transform: none; opacity: 1; }
}

/* From the archer to the checkbox, the full width of the row. */
/* From the archer's bow to the checkbox, along the title line. */
@keyframes fly {
    from { right: 2.6rem; }
    to { right: calc(100% - 3.5rem); }
}

@keyframes trail {
    from { width: 0; }
    to { width: calc(100% - 2.75rem); }
}

@keyframes thunk {
    0% { transform: scale(1); }
    30% { transform: scale(0.8) rotate(-8deg); }
    60% { transform: scale(1.15) rotate(4deg); }
    100% { transform: scale(1); }
}
</style>
