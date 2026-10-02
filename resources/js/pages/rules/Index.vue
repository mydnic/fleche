<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { describeChance, describeRule } from '@/lib/rule'
import ruleRoutes from '@/routes/rules'
import type { Rule } from '@/types'

defineProps<{ rules: (Rule & { id: number })[] }>()

function toggle (rule: Rule & { id: number }, active: boolean): void {
    router.patch(ruleRoutes.update.url(rule.id), { active }, { preserveScroll: true })
}
</script>

<template>
    <AppLayout>
        <Head title="Rules" />

        <section class="mb-6 flex items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold">
                    Rules
                </h1>
                <p class="text-stone-500">
                    When your day starts, every rule rolls its dice and maybe drops a todo in your list.
                </p>
            </div>
            <UButton
                :to="ruleRoutes.create.url()"
                icon="i-lucide-plus"
                size="lg"
                label="New rule"
                class="pop shrink-0"
            />
        </section>

        <div
            v-if="!rules.length"
            class="rounded-3xl border-2 border-dashed border-orange-200 bg-white/70 p-10 text-center"
        >
            <p class="font-display text-xl font-semibold">
                No rules yet
            </p>
            <p class="mt-1 text-sm text-stone-500">
                Try "Bins out every Tuesday", or "Plank for a minute, 1 in 3 chance". Or grab a ready-made pack from the hub.
            </p>
        </div>

        <ul class="grid gap-3 sm:grid-cols-2">
            <li
                v-for="rule in rules"
                :key="rule.id"
                class="pop flex flex-col gap-2 rounded-2xl border-2 bg-white p-4 transition"
                :class="rule.active ? 'border-orange-100' : 'border-stone-100 opacity-60'"
            >
                <div class="flex items-start justify-between gap-2">
                    <Link
                        :href="ruleRoutes.edit.url(rule.id)"
                        class="font-display text-lg leading-tight font-semibold hover:text-orange-600"
                    >
                        {{ rule.name }}
                    </Link>
                    <USwitch
                        :model-value="rule.active"
                        :aria-label="`${rule.active ? 'Pause' : 'Resume'} ${rule.name}`"
                        @update:model-value="toggle(rule, $event)"
                    />
                </div>
                <p class="text-sm text-stone-500">
                    {{ describeRule(rule) }}
                </p>
                <div class="mt-auto flex flex-wrap gap-1.5">
                    <UBadge
                        v-if="rule.reward_cost"
                        color="secondary"
                        variant="soft"
                        icon="i-lucide-gift"
                        :label="`Reward · ${rule.reward_cost} ★`"
                    />
                    <UBadge
                        v-if="rule.points"
                        color="warning"
                        variant="soft"
                        :label="`+${rule.points} ★`"
                    />
                    <UBadge
                        v-if="Number(rule.chance) < 1"
                        color="primary"
                        variant="soft"
                        icon="i-lucide-dices"
                        :label="describeChance(Number(rule.chance))"
                    />
                    <UBadge
                        v-if="rule.allow_duplicates"
                        color="neutral"
                        variant="soft"
                        label="stacks"
                    />
                </div>
            </li>
        </ul>
    </AppLayout>
</template>
