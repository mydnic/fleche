<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import PixelArcher from '@/components/PixelArcher.vue'
import TodoItem from '@/components/TodoItem.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { today as todayRoute } from '@/routes'
import { store } from '@/routes/todos'
import type { Todo } from '@/types'

const props = defineProps<{
    today: string
    open: Todo[]
    overdue: Todo[]
    upcoming: Todo[]
    doneOn: string
    done: Todo[]
    doneTodayCount: number
    hasDoneEver: boolean
}>()

const page = usePage()
const adding = ref(false)

const greeting = computed(() => {
    const hour = new Date().getHours()

    return hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening'
})

const tomorrow = computed(() => {
    const date = new Date(props.today + 'T12:00:00')
    date.setDate(date.getDate() + 1)

    return date.toISOString().slice(0, 10)
})

const progress = computed(() => {
    const total = props.open.length + props.doneTodayCount

    return total === 0 ? 0 : Math.round((props.doneTodayCount / total) * 100)
})

/** Earlier open todos, one group per day, newest day first (server order). */
const overdueByDay = computed(() => {
    const groups = new Map<string, Todo[]>()

    for (const todo of props.overdue) {
        groups.set(todo.date, [...(groups.get(todo.date) ?? []), todo])
    }

    return [...groups].map(([date, todos]) => ({ date, todos }))
})

function dayLabel (date: string): string {
    const days = Math.round((new Date(props.today + 'T12:00:00').getTime() - new Date(date + 'T12:00:00').getTime()) / 86_400_000)

    return days === 1 ? 'Yesterday' : `${niceDate(date)} · ${days} days ago`
}

/** Swaps only the "done" list, keeping the rest of the page and the scroll. */
function pickDoneDay (date: string): void {
    router.get(todayRoute.url(), date === props.today ? {} : { done_on: date }, {
        only: ['doneOn', 'done'],
        preserveState: true,
        preserveScroll: true,
        replace: true
    })
}

function niceDate (date: string): string {
    return new Date(date + 'T12:00:00').toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short' })
}
</script>

<template>
    <AppLayout>
        <Head title="Today" />

        <section class="mb-6 flex items-end justify-between gap-4">
            <div>
                <p class="text-sm font-bold tracking-wide text-orange-500 uppercase">
                    {{ niceDate(today) }}
                </p>
                <h1 class="text-3xl font-bold sm:text-4xl">
                    {{ greeting }}, {{ page.props.auth.user?.name }}!
                </h1>
                <p class="mt-1 text-stone-500">
                    <template v-if="open.length">
                        {{ open.length }} target{{ open.length > 1 ? 's' : '' }} in sight. Take aim.
                    </template>
                    <template v-else-if="doneTodayCount">
                        Bullseye. Everything's done for today 🎯
                    </template>
                    <template v-else>
                        Nothing today. The dice were kind.
                    </template>
                    <template v-if="overdue.length">
                        Plus {{ overdue.length }} still open from earlier.
                    </template>
                </p>
            </div>
            <UButton
                icon="i-lucide-plus"
                size="lg"
                label="Add"
                class="pop shrink-0"
                @click="adding = !adding"
            />
        </section>

        <div
            v-if="open.length + doneTodayCount > 0"
            class="mb-6 h-3 overflow-hidden rounded-full bg-orange-100"
        >
            <div
                class="h-full rounded-full bg-gradient-to-r from-orange-400 to-amber-400 transition-all duration-700"
                :style="{ width: progress + '%' }"
            />
        </div>

        <Form
            v-if="adding"
            v-slot="{ errors, processing }"
            v-bind="store.form()"
            reset-on-success
            class="pop mb-6 grid gap-3 rounded-2xl border-2 border-dashed border-orange-200 bg-white p-4 sm:grid-cols-[1fr_auto_auto]"
            @success="adding = false"
        >
            <UFormField
                name="name"
                :error="errors.name"
            >
                <UInput
                    name="name"
                    placeholder="Something to do…"
                    required
                    autofocus
                    size="lg"
                    class="w-full"
                />
            </UFormField>
            <UInput
                name="image"
                type="file"
                accept="image/*"
                size="lg"
                class="sm:col-span-3 sm:order-last"
                aria-label="Picture (optional)"
            />
            <UInput
                name="date"
                type="date"
                :default-value="tomorrow"
                size="lg"
            />
            <UButton
                type="submit"
                size="lg"
                :loading="processing"
                label="Add todo"
            />
        </Form>

        <ul
            v-if="open.length"
            class="space-y-3"
        >
            <TodoItem
                v-for="todo in open"
                :key="todo.id"
                :todo="todo"
                :today="today"
            />
        </ul>

        <div
            v-else
            class="flex flex-col items-center gap-3 rounded-3xl border-2 border-dashed border-orange-200 bg-white/70 py-12 text-center"
        >
            <PixelArcher class="h-20 w-18 animate-bounce [animation-duration:2s]" />
            <p class="font-display text-xl font-semibold">
                Quiver's full, no target left.
            </p>
            <p class="text-sm text-stone-500">
                New todos drop in when your day starts (see Settings).
            </p>
        </div>

        <section
            v-if="overdue.length"
            class="mt-8"
        >
            <h2 class="mb-3 text-lg font-semibold text-stone-500">
                Still open from earlier
            </h2>
            <div class="space-y-5">
                <div
                    v-for="group in overdueByDay"
                    :key="group.date"
                >
                    <p class="mb-2 text-xs font-extrabold tracking-wide text-amber-600 uppercase">
                        {{ dayLabel(group.date) }}
                    </p>
                    <ul class="space-y-2">
                        <TodoItem
                            v-for="todo in group.todos"
                            :key="todo.id"
                            :todo="todo"
                            :today="today"
                        />
                    </ul>
                </div>
            </div>
        </section>

        <section
            v-if="hasDoneEver"
            class="mt-8"
        >
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-stone-500">
                    {{ doneOn === today ? 'Hit today' : `Hit on ${niceDate(doneOn)}` }} 🎯
                </h2>
                <div class="flex items-center gap-1">
                    <UButton
                        v-if="doneOn !== today"
                        size="xs"
                        variant="ghost"
                        color="neutral"
                        label="Today"
                        @click="pickDoneDay(today)"
                    />
                    <UInput
                        type="date"
                        size="sm"
                        :model-value="doneOn"
                        :max="today"
                        aria-label="Show what was done on another day"
                        @update:model-value="pickDoneDay(String($event || today))"
                    />
                </div>
            </div>
            <p
                v-if="!done.length"
                class="rounded-xl bg-white/70 px-4 py-3 text-sm text-stone-400"
            >
                {{ doneOn === today ? 'Nothing hit yet today.' : 'Nothing hit that day.' }}
            </p>
            <ul class="space-y-2">
                <TodoItem
                    v-for="todo in done"
                    :key="todo.id"
                    :todo="todo"
                    :today="today"
                />
            </ul>
        </section>

        <section
            v-if="upcoming.length"
            class="mt-8"
        >
            <h2 class="mb-3 text-lg font-semibold text-stone-500">
                Coming up
            </h2>
            <ul class="space-y-2">
                <li
                    v-for="todo in upcoming"
                    :key="todo.id"
                    class="flex items-center justify-between rounded-xl bg-white/70 px-4 py-2 text-sm"
                >
                    <span class="font-semibold">{{ todo.name }}</span>
                    <span class="text-stone-400">{{ niceDate(todo.date) }}</span>
                </li>
            </ul>
        </section>

    </AppLayout>
</template>
