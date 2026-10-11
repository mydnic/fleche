<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import type { DropdownMenuItem } from '@nuxt/ui'
import { computed, ref, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { describeChance, describeRule } from '@/lib/rule'
import ruleRoutes from '@/routes/rules'
import type { Rule } from '@/types'

type SavedRule = Rule & { id: number }

const props = defineProps<{ rules: SavedRule[] }>()

// Groups only live on rules: a fresh one stays client-side until a rule lands in it.
const draftGroups = ref<string[]>([])
const groups = computed(() => [...new Set([...props.rules.map(r => r.group).filter((g): g is string => !!g), ...draftGroups.value])]
    .sort((a, b) => a.localeCompare(b)))
const sections = computed(() => [...groups.value, null])
// The real contents of a group, filter or no filter: what the server acts on.
const inGroup = (group: string | null) => props.rules.filter(r => (r.group ?? null) === group)

// Everything the page needs is already in props, so the search never leaves the browser.
const query = ref('')
// Case and accents folded away, so "maison" finds "Maison" and "Café" finds "cafe".
const fold = (text: string) => text.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase()
const terms = computed(() => fold(query.value).split(/\s+/).filter(Boolean))
const searching = computed(() => !!terms.value.length)
const visibleRules = computed(() => {
    if (!terms.value.length) {
        return props.rules
    }

    return props.rules.filter(rule => {
        // describeRule() is the sentence already shown under the rule, so "every Tuesday" is searchable.
        const haystack = fold([rule.name, rule.group, rule.description, describeRule(rule)].filter(Boolean).join(' '))

        return terms.value.every(term => haystack.includes(term))
    })
})
// What a group shows on screen, which is all of it until something is typed.
const visibleInGroup = (group: string | null) => visibleRules.value.filter(r => (r.group ?? null) === group)

const dragged = ref<SavedRule | null>(null)
const over = ref<string | null | undefined>(undefined)
const editing = ref<string | null>(null)
// Kept after closing so the modal text doesn't blank out mid fade-out.
const confirming = ref('')
const confirmOpen = ref(false)

// Per-browser convenience; '' stands for "No group".
const collapsed = ref<string[]>([])
try {
    collapsed.value = JSON.parse(localStorage.getItem('rules.collapsed') ?? '[]')
} catch {}
watch(collapsed, value => {
    try {
        localStorage.setItem('rules.collapsed', JSON.stringify(value))
    } catch {}
})
// A collapsed group would hide a hit and make the search look broken, so a live
// query forces everything open. The saved state is untouched and comes back on clear.
const isCollapsed = (group: string | null) => !searching.value && collapsed.value.includes(group ?? '')
const allCollapsed = computed(() => sections.value.filter(g => g !== null || inGroup(null).length).every(isCollapsed))

function toggleCollapse (group: string | null): void {
    const key = group ?? ''
    collapsed.value = isCollapsed(group) ? collapsed.value.filter(g => g !== key) : [...collapsed.value, key]
}

function toggleAll (): void {
    collapsed.value = allCollapsed.value ? [] : sections.value.map(g => g ?? '')
}

function toggle (rule: SavedRule, active: boolean): void {
    router.patch(ruleRoutes.update.url(rule.id), { active }, { preserveScroll: true })
}

function move (rule: SavedRule, group: string | null): void {
    if ((rule.group ?? null) !== group) {
        // Keep the emptied group on screen, so a misdrop is one drag away from undone.
        if (rule.group && !draftGroups.value.includes(rule.group)) {
            draftGroups.value.push(rule.group)
        }
        router.patch(ruleRoutes.update.url(rule.id), { group }, { preserveScroll: true })
    }
}

// The rule title is an <a href>, which HTML makes draggable on its own, and draggable
// isn't inherited: without cancelling here, grabbing the title would still start a drag
// that this handler turns into a move, under an active filter.
function dragStart (event: DragEvent, rule: SavedRule): void {
    if (searching.value) {
        event.preventDefault()
        return
    }
    dragged.value = rule
    event.dataTransfer!.effectAllowed = 'move'
}

function drop (group: string | null): void {
    if (dragged.value) {
        move(dragged.value, group)
    }
    dragged.value = null
    over.value = undefined
}

function newGroup (rule?: SavedRule): void {
    let name = 'New group'
    for (let i = 2; groups.value.includes(name); i++) {
        name = `New group ${i}`
    }
    draftGroups.value.push(name)
    editing.value = name
    if (rule) {
        move(rule, name)
    }
}

function rename (from: string, to: string): void {
    if (editing.value !== from) {
        return
    }
    editing.value = null
    to = to.trim()
    if (!to || to === from) {
        return
    }
    draftGroups.value = draftGroups.value.map(g => g === from ? to : g)
    if (inGroup(from).length) {
        router.put(ruleRoutes.groups.url(), { from, to }, { preserveScroll: true })
    }
}

function dissolve (group: string): void {
    draftGroups.value = draftGroups.value.filter(g => g !== group)
    if (inGroup(group).length) {
        router.put(ruleRoutes.groups.url(), { from: group, to: null }, { preserveScroll: true })
    }
}

// No preserveScroll: the copy's edit form is where this lands.
function duplicate (rule: SavedRule): void {
    router.post(ruleRoutes.duplicate.url(rule.id))
}

function actionItems (rule: SavedRule): DropdownMenuItem[][] {
    return [
        [{ label: 'Duplicate', icon: 'i-lucide-copy', onSelect: () => duplicate(rule) }],
        ...moveItems(rule),
    ]
}

function moveItems (rule: SavedRule): DropdownMenuItem[][] {
    return [
        [
            ...groups.value.filter(g => g !== rule.group).map(g => ({ label: g, icon: 'i-lucide-folder', onSelect: () => move(rule, g) })),
            ...(rule.group ? [{ label: 'No group', icon: 'i-lucide-folder-x', onSelect: () => move(rule, null) }] : []),
        ],
        [{ label: 'New group', icon: 'i-lucide-folder-plus', onSelect: () => newGroup(rule) }],
    ]
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

        <UInput
            v-if="rules.length"
            v-model="query"
            icon="i-lucide-search"
            placeholder="Search rules: bins, Tuesday, 1 in 3…"
            size="xl"
            class="mb-6 w-full"
            aria-label="Search rules"
            @keydown.esc="query = ''"
        >
            <template
                v-if="query"
                #trailing
            >
                <UButton
                    icon="i-lucide-x"
                    color="neutral"
                    variant="link"
                    size="sm"
                    aria-label="Clear search"
                    @click="query = ''"
                />
            </template>
        </UInput>

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

        <div
            v-if="rules.length && !visibleRules.length"
            class="rounded-3xl border-2 border-dashed border-orange-200 bg-white/70 p-10 text-center"
        >
            <p class="font-display text-xl font-semibold">
                No rule matches "{{ query.trim() }}"
            </p>
            <p class="mt-1 text-sm text-stone-500">
                Names, groups, descriptions and schedules are all searched. Try a shorter word, or clear the search.
            </p>
        </div>

        <div
            v-if="visibleRules.length"
            class="flex flex-col gap-6"
        >
            <UButton
                v-if="groups.length && !searching"
                :icon="allCollapsed ? 'i-lucide-chevrons-up-down' : 'i-lucide-chevrons-down-up'"
                :label="allCollapsed ? 'Expand all' : 'Collapse all'"
                color="neutral"
                variant="ghost"
                size="sm"
                class="-mb-4 self-end"
                @click="toggleAll()"
            />
            <template
                v-for="group in sections"
                :key="group ?? ''"
            >
                <section
                    v-if="searching ? visibleInGroup(group).length : (group !== null || inGroup(null).length || (dragged && groups.length))"
                    class="rounded-3xl border-2 border-dashed p-3 transition"
                    :class="over === group && dragged ? 'border-orange-300 bg-orange-50/60' : 'border-transparent'"
                    @dragover.prevent="dragged && (over = group)"
                    @dragleave="!($event.currentTarget as Node).contains($event.relatedTarget as Node) && (over = undefined)"
                    @drop.prevent="drop(group)"
                >
                    <header
                        v-if="groups.length"
                        class="flex min-h-8 items-center gap-2 px-1"
                        :class="!isCollapsed(group) && 'mb-2'"
                    >
                        <button
                            type="button"
                            class="flex shrink-0 items-center gap-1 text-orange-400 hover:text-orange-600 disabled:cursor-default disabled:hover:text-orange-400"
                            :disabled="searching"
                            :aria-expanded="!isCollapsed(group)"
                            :aria-label="`${isCollapsed(group) ? 'Expand' : 'Collapse'} ${group ?? 'No group'}`"
                            :title="searching ? 'Groups stay open while you search' : undefined"
                            @click="toggleCollapse(group)"
                        >
                            <UIcon
                                name="i-lucide-chevron-right"
                                class="size-4 transition-transform"
                                :class="!isCollapsed(group) && 'rotate-90'"
                            />
                            <UIcon
                                :name="group === null ? 'i-lucide-inbox' : isCollapsed(group) ? 'i-lucide-folder' : 'i-lucide-folder-open'"
                                class="size-5"
                            />
                        </button>
                        <UInput
                            v-if="group !== null && editing === group"
                            :default-value="group"
                            autofocus
                            size="sm"
                            class="max-w-xs"
                            @focus="($event.target as HTMLInputElement).select()"
                            @keydown.enter="($event.target as HTMLInputElement).blur()"
                            @keydown.esc="editing = null"
                            @blur="rename(group, ($event.target as HTMLInputElement).value)"
                        />
                        <button
                            v-else-if="group !== null"
                            type="button"
                            class="font-display truncate text-lg font-semibold hover:text-orange-600"
                            title="Rename"
                            @click="editing = group"
                        >
                            {{ group }}
                        </button>
                        <span
                            v-else
                            class="font-display text-lg font-semibold text-stone-500"
                        >No group</span>
                        <span class="text-sm text-stone-400">{{ visibleInGroup(group).length }}</span>
                        <UButton
                            v-if="group !== null"
                            icon="i-lucide-x"
                            color="neutral"
                            variant="ghost"
                            size="xs"
                            class="ml-auto"
                            :aria-label="`Remove the ${group} group (its rules stay)`"
                            title="Remove group (its rules stay)"
                            @click="inGroup(group).length ? (confirming = group, confirmOpen = true) : dissolve(group)"
                        />
                    </header>
                    <template v-if="groups.length && isCollapsed(group)" />
                    <p
                        v-else-if="!visibleInGroup(group).length"
                        class="rounded-2xl border-2 border-dashed border-stone-200 p-6 text-center text-sm text-stone-400"
                    >
                        Drag rules here
                    </p>
                    <ul
                        v-else
                        class="grid gap-3 sm:grid-cols-2"
                    >
                        <li
                            v-for="rule in visibleInGroup(group)"
                            :key="rule.id"
                            :draggable="!searching"
                            class="pop flex flex-col gap-2 rounded-2xl border-2 bg-white p-4 transition"
                            :class="[
                                rule.active ? 'border-orange-100' : 'border-stone-100 opacity-60',
                                dragged?.id === rule.id && 'scale-95 opacity-40',
                                !searching && 'cursor-grab active:cursor-grabbing',
                            ]"
                            @dragstart="dragStart($event, rule)"
                            @dragend="dragged = null; over = undefined"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <Link
                                    :href="ruleRoutes.edit.url(rule.id)"
                                    class="font-display text-lg leading-tight font-semibold hover:text-orange-600"
                                >
                                    {{ rule.name }}
                                </Link>
                                <div class="flex shrink-0 items-center gap-1">
                                    <UDropdownMenu :items="actionItems(rule)">
                                        <UButton
                                            icon="i-lucide-ellipsis"
                                            color="neutral"
                                            variant="ghost"
                                            size="xs"
                                            :aria-label="`Actions for ${rule.name}`"
                                        />
                                    </UDropdownMenu>
                                    <USwitch
                                        :model-value="rule.active"
                                        :aria-label="`${rule.active ? 'Pause' : 'Resume'} ${rule.name}`"
                                        @update:model-value="toggle(rule, $event)"
                                    />
                                </div>
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
                </section>
            </template>
            <UButton
                v-if="!searching"
                icon="i-lucide-folder-plus"
                color="neutral"
                variant="soft"
                label="New group"
                class="self-start"
                @click="newGroup()"
            />
        </div>

        <UModal
            v-model:open="confirmOpen"
            :ui="{ content: 'max-w-sm' }"
        >
            <template #content>
                <div class="flex flex-col items-center gap-3 p-6 text-center">
                    <div class="grid size-14 place-items-center rounded-full bg-red-50 ring-8 ring-red-50/50">
                        <UIcon
                            name="i-lucide-folder-x"
                            class="size-7 text-red-500"
                        />
                    </div>
                    <h2 class="font-display text-xl font-semibold">
                        Remove "{{ confirming }}"?
                    </h2>
                    <p class="text-sm text-stone-500">
                        Its {{ inGroup(confirming).length }} rule(s) stay, they just go back to "No group".
                    </p>
                    <div class="mt-2 flex w-full gap-2">
                        <UButton
                            label="Cancel"
                            color="neutral"
                            variant="soft"
                            block
                            @click="confirmOpen = false"
                        />
                        <UButton
                            label="Remove group"
                            color="error"
                            icon="i-lucide-trash-2"
                            block
                            @click="dissolve(confirming); confirmOpen = false"
                        />
                    </div>
                </div>
            </template>
        </UModal>
    </AppLayout>
</template>
