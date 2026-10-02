<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { describeRule } from '@/lib/rule'
import rules from '@/routes/rules'
import type { Rule, Weekday } from '@/types'

const props = defineProps<{ rule: (Rule & { id: number }) | null }>()

const WEEKDAYS: Weekday[] = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']
const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
const UNITS = [{ label: 'days', value: 'day' }, { label: 'weeks', value: 'week' }, { label: 'months', value: 'month' }, { label: 'years', value: 'year' }]
const DAYS_OF_MONTH = [{ label: 'Any day', value: null }, ...Array.from({ length: 31 }, (_, i) => ({ label: String(i + 1), value: i + 1 })), { label: 'Last day', value: -1 }]
/** One tap for the usual odds; anything else goes in the % field. */
const PRESETS = [
    { label: 'Always', chance: 1 },
    { label: '3 in 4', chance: 3 / 4 },
    { label: '1 in 2', chance: 1 / 2 },
    { label: '1 in 3', chance: 1 / 3 },
    { label: '1 in 4', chance: 1 / 4 },
    { label: '1 in 7', chance: 1 / 7 },
    { label: '1 in 10', chance: 1 / 10 },
    { label: '1 in 30', chance: 1 / 30 }
]

const form = useForm<Rule & { image: File | null, remove_image: boolean }>({
    image: null,
    remove_image: false,
    name: props.rule?.name ?? '',
    description: props.rule?.description ?? null,
    active: props.rule?.active ?? true,
    days: props.rule?.days ?? [],
    random_day: props.rule?.random_day ?? false,
    every_value: props.rule?.every_value ?? null,
    every_unit: props.rule?.every_unit ?? null,
    day_of_month: props.rule?.day_of_month ?? null,
    months: props.rule?.months ?? [],
    start_after: props.rule?.start_after ?? null,
    chance: Number(props.rule?.chance ?? 1),
    allow_duplicates: props.rule?.allow_duplicates ?? false,
    points: props.rule?.points ?? 0,
    reward_cost: props.rule?.reward_cost ?? null,
})

/** Edited as a percentage, stored as a probability (0..1]. */
const percent = computed({
    get: () => Math.round(form.chance * 1000) / 10,
    set: (value: number) => (form.chance = Math.min(100, Math.max(0.1, Number(value) || 100)) / 100)
})

const isPreset = (chance: number): boolean => Math.abs(form.chance - chance) < 0.0005

const isReward = computed({
    get: () => form.reward_cost !== null,
    set: (on: boolean) => (form.reward_cost = on ? (form.reward_cost ?? 50) : null)
})

const hasInterval = computed({
    get: () => form.every_unit !== null,
    set: (on: boolean) => {
        form.every_value = on ? (form.every_value ?? 1) : null
        form.every_unit = on ? (form.every_unit ?? 'week') : null
    }
})

/** What the picture slot shows: the new file, else the saved one unless removed. */
const preview = ref<string | null>(props.rule?.image_url ?? null)

function pickImage (event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null
    form.image = file
    form.remove_image = false
    preview.value = file ? URL.createObjectURL(file) : (props.rule?.image_url ?? null)
}

function removeImage (): void {
    form.image = null
    form.remove_image = true
    preview.value = null
}

onBeforeUnmount(() => preview.value?.startsWith('blob:') && URL.revokeObjectURL(preview.value))

function toggleIn<T> (list: T[] | null, value: T): T[] {
    const current = list ?? []

    return current.includes(value) ? current.filter(item => item !== value) : [...current, value]
}


function submit (): void {
    // Multipart for the picture: PUT is spoofed over POST so PHP parses the files.
    const transformed = form.transform(data => ({
        ...data,
        ...(props.rule ? { _method: 'put' } : {}),
        days: data.days?.length ? data.days : null,
        months: data.months?.length ? data.months : null,
        start_after: data.start_after || null
    }))

    transformed.post(props.rule ? rules.update.url(props.rule.id) : rules.store.url(), { forceFormData: true })
}

function destroy (): void {
    if (props.rule && confirm('Delete this rule? Todos it already created stay.')) {
        router.delete(rules.destroy.url(props.rule.id))
    }
}
</script>

<template>
    <AppLayout>
        <Head :title="rule ? rule.name : 'New rule'" />

        <Link
            :href="rules.index.url()"
            class="mb-3 inline-flex items-center gap-1 text-sm font-bold text-stone-500 hover:text-orange-600"
        >
            <UIcon name="i-lucide-arrow-left" /> Rules
        </Link>

        <form
            class="space-y-5"
            @submit.prevent="submit"
        >
            <section class="pop space-y-4 rounded-3xl border-2 border-orange-100 bg-white p-5">
                <UFormField
                    label="What should appear?"
                    :error="form.errors.name"
                    required
                >
                    <UInput
                        v-model="form.name"
                        placeholder="Water the plants"
                        size="xl"
                        class="w-full"
                        autofocus
                    />
                </UFormField>
                <UFormField
                    label="Details"
                    :error="form.errors.description"
                >
                    <UTextarea
                        :model-value="form.description ?? undefined"
                        :rows="2"
                        autoresize
                        class="w-full"
                        @update:model-value="form.description = String($event ?? '') || null"
                    />
                </UFormField>
                <UFormField
                    label="Picture"
                    help="Shown on the todo: an exercise, the right filter reference…"
                    :error="form.errors.image"
                >
                    <div class="flex items-center gap-3">
                        <img
                            v-if="preview"
                            :src="preview"
                            alt=""
                            class="h-20 rounded-xl border-2 border-orange-100 object-contain"
                        >
                        <UInput
                            type="file"
                            accept="image/*"
                            @change="pickImage"
                        />
                        <UButton
                            v-if="preview"
                            color="neutral"
                            variant="ghost"
                            icon="i-lucide-x"
                            label="Remove"
                            @click="removeImage"
                        />
                    </div>
                </UFormField>
                <div class="flex items-center gap-2 rounded-2xl bg-orange-50 px-4 py-3 text-sm font-semibold text-orange-800">
                    <UIcon
                        name="i-lucide-sparkles"
                        class="shrink-0"
                    />
                    {{ describeRule(form) }}
                </div>
            </section>

            <section class="pop space-y-5 rounded-3xl border-2 border-orange-100 bg-white p-5">
                <h2 class="text-xl font-bold">
                    📅 When
                </h2>

                <UFormField
                    label="On these days"
                    help="None picked = any day."
                    :error="form.errors.days"
                >
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="day in WEEKDAYS"
                            :key="day"
                            type="button"
                            class="rounded-xl border-2 px-3 py-1.5 text-sm font-bold capitalize transition"
                            :class="form.days?.includes(day) ? 'border-orange-500 bg-orange-500 text-white' : 'border-stone-200 hover:border-orange-300'"
                            @click="form.days = toggleIn(form.days, day)"
                        >
                            {{ day.slice(0, 3) }}
                        </button>
                    </div>
                </UFormField>
                <UCheckbox
                    v-if="(form.days?.length ?? 0) > 1"
                    v-model="form.random_day"
                    label="Only one of them, drawn at random"
                />

                <div class="flex flex-wrap items-center gap-3">
                    <USwitch
                        v-model="hasInterval"
                        label="At most every"
                    />
                    <template v-if="hasInterval">
                        <UInputNumber
                            v-model="form.every_value"
                            :min="1"
                            class="w-28"
                        />
                        <USelect
                            :model-value="form.every_unit ?? undefined"
                            :items="UNITS"
                            @update:model-value="form.every_unit = $event as Rule['every_unit']"
                            class="w-32"
                        />
                    </template>
                </div>
                <p
                    v-if="hasInterval"
                    class="-mt-3 text-xs text-stone-500"
                >
                    Counted from the last time you did it.
                </p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <UFormField
                        label="Day of the month"
                        :error="form.errors.day_of_month"
                    >
                        <USelect
                            v-model="form.day_of_month"
                            :items="DAYS_OF_MONTH"
                            class="w-full"
                        />
                    </UFormField>
                    <UFormField
                        label="Not before"
                        :error="form.errors.start_after"
                    >
                        <UInput
                            :model-value="form.start_after ?? undefined"
                            type="date"
                            @update:model-value="form.start_after = String($event ?? '') || null"
                            class="w-full"
                        />
                    </UFormField>
                </div>

                <UFormField
                    label="In these months"
                    help="None picked = all year."
                >
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="(month, index) in MONTHS"
                            :key="month"
                            type="button"
                            class="rounded-lg border-2 px-2 py-1 text-xs font-bold transition"
                            :class="form.months?.includes(index + 1) ? 'border-sky-500 bg-sky-500 text-white' : 'border-stone-200 hover:border-sky-300'"
                            @click="form.months = toggleIn(form.months, index + 1)"
                        >
                            {{ month }}
                        </button>
                    </div>
                </UFormField>
            </section>

            <section class="pop space-y-4 rounded-3xl border-2 border-orange-100 bg-white p-5">
                <h2 class="text-xl font-bold">
                    🎲 Chance
                </h2>
                <p class="text-sm text-stone-500">
                    On a matching day, roll the dice. Great for surprises.
                </p>
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="preset in PRESETS"
                        :key="preset.label"
                        type="button"
                        class="rounded-xl border-2 px-3 py-1.5 text-sm font-bold transition"
                        :class="isPreset(preset.chance) ? 'border-orange-500 bg-orange-500 text-white' : 'border-stone-200 hover:border-orange-300'"
                        @click="form.chance = preset.chance"
                    >
                        {{ preset.label }}
                    </button>
                </div>
                <div class="flex items-center gap-2 text-sm text-stone-500">
                    or exactly
                    <UInputNumber
                        v-model="percent"
                        :min="0.1"
                        :max="100"
                        :step="1"
                        :format-options="{ maximumFractionDigits: 1 }"
                        class="w-32"
                    />
                </div>
                <p
                    v-if="form.errors.chance"
                    class="text-sm text-red-500"
                >
                    {{ form.errors.chance }}
                </p>
            </section>

            <section class="pop space-y-4 rounded-3xl border-2 border-orange-100 bg-white p-5">
                <h2 class="text-xl font-bold">
                    ⭐ Points & rewards
                </h2>
                <UFormField
                    label="Points earned when done"
                    :error="form.errors.points"
                >
                    <UInputNumber
                        v-model="form.points"
                        :min="0"
                        class="w-36"
                    />
                </UFormField>
                <div class="rounded-2xl border-2 border-dashed border-sky-200 p-4">
                    <USwitch
                        v-model="isReward"
                        label="This is a reward 🎁"
                        description="Only appears if you can afford it, and costs the points when it does."
                    />
                    <UFormField
                        v-if="isReward"
                        label="Cost"
                        class="mt-3"
                        :error="form.errors.reward_cost"
                    >
                        <UInputNumber
                            v-model="form.reward_cost"
                            :min="1"
                            class="w-36"
                        />
                    </UFormField>
                </div>
            </section>

            <section class="pop space-y-4 rounded-3xl border-2 border-orange-100 bg-white p-5">
                <h2 class="text-xl font-bold">
                    ⚙️ Behaviour
                </h2>
                <USwitch
                    v-model="form.allow_duplicates"
                    label="Let it stack up"
                    description="A new one appears on schedule even if the last one isn't done yet."
                />
                <USwitch
                    v-model="form.active"
                    label="Active"
                />
            </section>

            <div class="flex items-center justify-between gap-3">
                <UButton
                    v-if="rule"
                    color="error"
                    variant="ghost"
                    icon="i-lucide-trash-2"
                    label="Delete"
                    @click="destroy"
                />
                <span v-else />
                <UButton
                    type="submit"
                    size="xl"
                    :loading="form.processing"
                    :label="rule ? 'Save rule' : 'Create rule'"
                    class="pop"
                />
            </div>
        </form>
    </AppLayout>
</template>
