<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { checkout } from '@/routes/settings'

const props = defineProps<{
    days: { date: string, hits: number }[]
    currentStreak: number
    bestStreak: number
    totalHits: number
    points: number
    hitRate: number | null
    topRules: { name: string, hits: number }[]
    /** Set for free cloud accounts: days of history they keep. */
    freeDays: number | null
}>()

/**
 * A clean top for the y-axis: an even whole number, so the middle tick is a
 * whole number of todos too (0 / 2 / 4, never 0 / 0.5 / 1).
 */
const maxHits = computed(() => {
    const max = Math.max(2, ...props.days.map(day => day.hits))
    const step = max <= 10 ? 2 : 10

    return Math.ceil(max / step) * step
})

const ticks = computed(() => [maxHits.value, maxHits.value / 2, 0])

/** Index of the first day a free account still has; everything before is gone. */
const lockedUntil = computed(() => props.freeDays === null ? 0 : props.days.length - props.freeDays)
const lockedWidth = computed(() => `${(lockedUntil.value / props.days.length) * 100}%`)

const hovered = ref<number | null>(null)

const monthLabels = computed(() => props.days
    .map((day, index) => ({ index, label: new Date(day.date + 'T12:00:00').toLocaleDateString(undefined, { month: 'short' }) }))
    .filter(({ index }) => index === 0 || props.days[index].date.endsWith('-01')))

function niceDate (date: string): string {
    return new Date(date + 'T12:00:00').toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short' })
}

const topMax = computed(() => Math.max(1, ...props.topRules.map(rule => rule.hits)))
</script>

<template>
    <AppLayout>
        <Head title="Stats" />

        <h1 class="mb-1 text-3xl font-bold">
            Stats
        </h1>
        <p class="mb-6 text-stone-500">
            Every target you hit, counted.
        </p>

        <section class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-5">
            <div class="pop col-span-2 rounded-2xl border-2 border-orange-100 bg-white p-4 sm:col-span-1">
                <p class="text-sm text-stone-500">
                    Current streak
                </p>
                <p class="text-3xl font-bold">
                    🔥 {{ currentStreak }}
                </p>
                <p class="text-xs text-stone-400">
                    {{ currentStreak === 1 ? 'day' : 'days' }} in a row
                </p>
            </div>
            <div
                v-for="tile in [
                    { label: 'Best streak', value: bestStreak, unit: bestStreak === 1 ? 'day' : 'days', limited: true },
                    { label: 'Total hits', value: totalHits, unit: 'todos done', limited: true },
                    { label: 'Points', value: points, unit: '★ to spend', limited: false },
                    { label: 'Hit rate', value: hitRate === null ? '—' : `${hitRate}%`, unit: 'last 30 days', limited: true },
                ]"
                :key="tile.label"
                class="pop rounded-2xl border-2 border-orange-100 bg-white p-4"
            >
                <p class="text-sm text-stone-500">
                    {{ tile.label }}
                </p>
                <p class="text-3xl font-bold">
                    {{ tile.value }}
                </p>
                <p class="text-xs text-stone-400">
                    <template v-if="freeDays && tile.limited">
                        last {{ freeDays }} days
                    </template>
                    <template v-else>
                        {{ tile.unit }}
                    </template>
                </p>
            </div>
        </section>

        <section class="pop mb-6 rounded-3xl border-2 border-orange-100 bg-white p-5">
            <h2 class="text-xl font-bold">
                Hits per day
            </h2>
            <p class="mb-4 text-sm text-stone-500">
                The last {{ days.length }} days
            </p>

            <div class="flex gap-2">
                <!-- Y axis: three clean ticks, recessive. -->
                <div class="flex h-44 flex-col justify-between pb-0 text-right text-xs text-stone-400 tabular-nums">
                    <span
                        v-for="tick in ticks"
                        :key="tick"
                        class="-translate-y-1/2 leading-none last:translate-y-1/2"
                    >{{ tick }}</span>
                </div>

                <div class="relative min-w-0 flex-1">
                    <div class="relative h-44">
                        <!-- Hairline grid. -->
                        <div class="absolute inset-0 flex flex-col justify-between">
                            <div
                                v-for="tick in ticks"
                                :key="tick"
                                class="h-px bg-stone-100"
                            />
                        </div>

                        <!-- Columns: thin, rounded at the top, square on the baseline, 2px apart. -->
                        <div class="absolute inset-0 flex items-end gap-[2px]">
                            <button
                                v-for="(day, index) in days"
                                :key="day.date"
                                type="button"
                                class="relative flex h-full flex-1 items-end justify-center focus:outline-none"
                                :aria-label="`${niceDate(day.date)}: ${day.hits} done`"
                                @pointerenter="hovered = index"
                                @pointerleave="hovered = null"
                                @focus="hovered = index"
                                @blur="hovered = null"
                            >
                                <span
                                    v-if="day.hits > 0"
                                    class="w-full max-w-6 rounded-t-[4px] transition-colors"
                                    :class="hovered === index ? 'bg-orange-600' : 'bg-orange-400'"
                                    :style="{ height: `${(day.hits / maxHits) * 100}%` }"
                                />
                            </button>
                        </div>

                        <!-- Free plan: the history that isn't kept. -->
                        <div
                            v-if="freeDays"
                            class="absolute inset-y-0 left-0 flex items-center justify-center rounded-lg bg-[repeating-linear-gradient(135deg,transparent_0_7px,#f5f5f4_7px_8px)]"
                            :style="{ width: lockedWidth }"
                        >
                            <p class="mx-4 max-w-xs text-center text-sm text-stone-400">
                                The free plan only keeps your last {{ freeDays }} days.
                                <button
                                    type="button"
                                    class="font-semibold text-orange-600 underline decoration-orange-200 underline-offset-2 hover:decoration-orange-500"
                                    @click="router.post(checkout.url())"
                                >
                                    Keep them all
                                </button>
                            </p>
                        </div>

                        <!-- Tooltip: value first, date after. -->
                        <div
                            v-if="hovered !== null"
                            class="pointer-events-none absolute -top-2 z-10 -translate-y-full rounded-xl bg-stone-800 px-3 py-1.5 text-xs whitespace-nowrap text-white shadow"
                            :class="hovered < days.length * 0.15 ? '-translate-x-2' : hovered > days.length * 0.85 ? '-translate-x-[calc(100%-0.5rem)]' : '-translate-x-1/2'"
                            :style="{ left: `${((hovered + 0.5) / days.length) * 100}%` }"
                        >
                            <b>{{ days[hovered].hits }} done</b> · {{ niceDate(days[hovered].date) }}
                        </div>
                    </div>

                    <!-- Month labels under the first day of each month. -->
                    <div class="relative mt-1 h-4 text-xs text-stone-400">
                        <span
                            v-for="month in monthLabels"
                            :key="month.index"
                            class="absolute"
                            :style="{ left: `${(month.index / days.length) * 100}%` }"
                        >{{ month.label }}</span>
                    </div>
                </div>
            </div>

            <details class="mt-4 text-sm">
                <summary class="cursor-pointer text-stone-500">
                    Show as a table
                </summary>
                <table class="mt-2 w-full max-w-xs">
                    <tbody>
                        <tr
                            v-for="day in [...days].reverse().filter(day => day.hits > 0)"
                            :key="day.date"
                            class="border-t border-orange-100"
                        >
                            <td class="py-1">
                                {{ niceDate(day.date) }}
                            </td>
                            <td class="py-1 text-right font-semibold tabular-nums">
                                {{ day.hits }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </details>
        </section>

        <section
            v-if="topRules.length"
            class="pop rounded-3xl border-2 border-orange-100 bg-white p-5"
        >
            <h2 class="mb-4 text-xl font-bold">
                Most hit
                <span
                    v-if="freeDays"
                    class="text-sm font-normal text-stone-400"
                >· last {{ freeDays }} days</span>
            </h2>
            <ul class="space-y-2">
                <li
                    v-for="rule in topRules"
                    :key="rule.name"
                    class="grid grid-cols-[minmax(0,10rem)_1fr] items-center gap-3 text-sm sm:grid-cols-[minmax(0,14rem)_1fr]"
                >
                    <span class="truncate font-semibold">{{ rule.name }}</span>
                    <span class="flex items-center gap-2">
                        <span
                            class="h-3 rounded-r-[4px] bg-orange-400"
                            :style="{ width: `${(rule.hits / topMax) * 100}%` }"
                        />
                        <span class="text-stone-500 tabular-nums">{{ rule.hits }}</span>
                    </span>
                </li>
            </ul>
        </section>
    </AppLayout>
</template>
