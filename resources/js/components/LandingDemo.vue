<script setup lang="ts">
import { ref } from 'vue'
import AimingArcher from '@/components/AimingArcher.vue'
import TodoItem from '@/components/TodoItem.vue'
import type { Todo } from '@/types'

/**
 * The landing page's playable sample: same todo rows and archer as the app,
 * nothing saved. A refresh brings everything back unchecked.
 */
const today = new Date().toISOString().slice(0, 10)

const sample = (id: number, name: string, points: number, description: string | null = null): Todo => ({
    id, name, points, description, todo_setting_id: null, image_url: null, date: today, done_at: null
})

const todos = [
    sample(1, 'Take out the trash', 2, 'Every Wednesday'),
    sample(2, 'Water the plants 🌱', 2, 'At most every week · 1 in 2 chance'),
    sample(3, 'Tell them "I love you" 🎲', 3, '1 in 7 chance'),
    sample(4, 'Eat some cake 🎁', 0, 'Reward · showed up because you had 50 ★')
]

const points = ref(120)
const bump = ref(false)

/**
 * One archer on screen at a time: the big one ducks behind the card, the
 * row's archer pops up 250 ms later, shoots, ducks, and the big one comes back.
 * A counter, since several rows can be ticked in a row.
 */
const DUCK_MS = 250
const shooting = ref(0)

function start (): void {
    shooting.value++
}

function hit (todo: Todo): void {
    points.value += todo.points
    bump.value = true
    setTimeout(() => (bump.value = false), 300)
    setTimeout(() => shooting.value--, DUCK_MS)
}
</script>

<template>
    <div class="relative rounded-3xl border-2 border-orange-100 bg-white p-5 shadow-[0_6px_0_0_rgb(0_0_0/0.06)]">
        <AimingArcher
            :away="shooting > 0"
            class="absolute -top-[59px] right-4 h-[68px] w-[90px]"
        />
        <p class="text-xs font-extrabold tracking-wide text-orange-500 uppercase">
            Today · try it, tick one
        </p>
        <ul class="mt-3 space-y-2">
            <TodoItem
                v-for="todo in todos"
                :key="todo.id"
                :todo="todo"
                :today="today"
                demo
                :start-delay="DUCK_MS"
                @start="start"
                @done="hit"
            />
        </ul>
        <div class="mt-4 flex justify-end">
            <span
                class="rounded-full bg-amber-300 px-3 py-1 text-sm font-extrabold text-amber-900 transition-transform"
                :class="bump ? 'scale-125' : ''"
            >★ {{ points }}</span>
        </div>
    </div>
</template>
