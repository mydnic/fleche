<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import ImagePreview from '@/components/ImagePreview.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { describeRule } from '@/lib/rule'
import { hub } from '@/routes'
import { destroy, importMethod, moderate, publish } from '@/routes/hub'
import type { HubPack } from '@/types'

const props = defineProps<{
    packs: HubPack[]
    error: string | null
    q: string
    canPublish: boolean
    rules: { id: number, name: string }[]
    mine: { id: number, name: string, status: string, imports_count: number }[]
    pending: HubPack[]
}>()

const page = usePage()
const isAdmin = computed(() => page.props.edition === 'cloud' && page.props.auth.user?.is_admin === true)

function remove (pack: HubPack): void {
    if (confirm(`Remove "${pack.name}" from the hub? People who already imported it keep their rules.`)) {
        router.delete(destroy.url(pack.id), { preserveScroll: true })
    }
}

const search = ref(props.q)
const publishing = ref(false)
const importing = ref<number | null>(null)

const form = useForm({ name: '', description: '', rule_ids: [] as number[] })

function find (): void {
    router.get(hub.url(), search.value ? { q: search.value } : {}, { preserveState: true, replace: true })
}

function take (pack: HubPack): void {
    importing.value = pack.id
    router.post(importMethod.url(pack.id), {}, { onFinish: () => (importing.value = null) })
}

function send (): void {
    form.post(publish.url(), { onSuccess: () => { form.reset(); publishing.value = false } })
}

const STATUS_COLOR = { pending: 'warning', approved: 'success', rejected: 'error' } as const
</script>

<template>
    <AppLayout>
        <Head title="Community hub" />

        <section class="mb-6 flex items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold">
                    Community hub
                </h1>
                <p class="text-stone-500">
                    Rule packs shared by other archers. Import one, it's yours to tweak. Authors earn ★ for every import.
                </p>
            </div>
            <UButton
                v-if="canPublish"
                icon="i-lucide-upload"
                size="lg"
                label="Share"
                color="secondary"
                class="pop shrink-0"
                @click="publishing = !publishing"
            />
        </section>

        <form
            v-if="publishing"
            class="pop mb-6 space-y-3 rounded-3xl border-2 border-dashed border-sky-200 bg-white p-5"
            @submit.prevent="send"
        >
            <h2 class="text-lg font-bold">
                Share a pack
            </h2>
            <UFormField
                label="Pack name"
                :error="form.errors.name"
            >
                <UInput
                    v-model="form.name"
                    placeholder="Desk Survival Kit"
                    class="w-full"
                />
            </UFormField>
            <UFormField label="Description">
                <UTextarea
                    v-model="form.description"
                    :rows="2"
                    class="w-full"
                />
            </UFormField>
            <UFormField
                label="Rules in this pack"
                :error="form.errors.rule_ids"
            >
                <div class="flex flex-wrap gap-2">
                    <UCheckbox
                        v-for="rule in rules"
                        :key="rule.id"
                        :id="`hub-rule-${rule.id}`"
                        :model-value="form.rule_ids.includes(rule.id)"
                        :label="rule.name"
                        @update:model-value="form.rule_ids = form.rule_ids.includes(rule.id) ? form.rule_ids.filter(id => id !== rule.id) : [...form.rule_ids, rule.id]"
                    />
                </div>
            </UFormField>
            <p class="text-xs text-stone-500">
                Packs are reviewed by a human before they go public.
            </p>
            <UButton
                type="submit"
                color="secondary"
                :loading="form.processing"
                label="Submit for review"
            />
        </form>

        <section
            v-if="pending.length"
            class="mb-6 rounded-3xl border-2 border-amber-200 bg-amber-50 p-5"
        >
            <h2 class="mb-3 text-lg font-bold">
                Waiting for review ({{ pending.length }})
            </h2>
            <div
                v-for="pack in pending"
                :key="pack.id"
                class="mb-3 rounded-2xl bg-white p-4"
            >
                <p class="font-bold">
                    {{ pack.name }} <span class="text-sm font-normal text-stone-400">by {{ pack.author }}</span>
                </p>
                <p class="text-sm text-stone-500">
                    {{ pack.description }}
                </p>
                <ul class="my-2 text-sm">
                    <li
                        v-for="(rule, i) in pack.rules"
                        :key="i"
                        class="flex items-center gap-2 py-0.5"
                    >
                        <ImagePreview
                            v-if="rule.image_url"
                            :src="rule.image_url"
                            :title="rule.name"
                            thumb-class="size-8"
                        />
                        <span>• <b>{{ rule.name }}</b> — {{ describeRule(rule) }}</span>
                    </li>
                </ul>
                <div class="flex gap-2">
                    <UButton
                        size="sm"
                        color="success"
                        label="Approve"
                        @click="router.patch(moderate.url(pack.id), { status: 'approved' }, { preserveScroll: true })"
                    />
                    <UButton
                        size="sm"
                        color="error"
                        variant="soft"
                        label="Reject"
                        @click="router.patch(moderate.url(pack.id), { status: 'rejected' }, { preserveScroll: true })"
                    />
                </div>
            </div>
        </section>

        <form
            class="mb-5"
            @submit.prevent="find"
        >
            <UInput
                v-model="search"
                icon="i-lucide-search"
                placeholder="Search packs: workout, money, plants…"
                size="xl"
                class="w-full"
            />
        </form>

        <UAlert
            v-if="error"
            :title="error"
            color="warning"
            variant="soft"
            class="mb-4"
        />

        <div class="grid gap-4 sm:grid-cols-2">
            <article
                v-for="pack in packs"
                :key="pack.id"
                class="pop flex flex-col rounded-3xl border-2 border-sky-100 bg-white p-5"
            >
                <div class="flex items-start justify-between gap-2">
                    <h3 class="text-lg leading-tight font-semibold">
                        {{ pack.name }}
                    </h3>
                    <UBadge
                        v-if="pack.imports_count > 0"
                        color="secondary"
                        variant="soft"
                        icon="i-lucide-download"
                        :label="String(pack.imports_count)"
                    />
                </div>
                <p class="text-xs text-stone-400">
                    by {{ pack.author }}
                </p>
                <p
                    v-if="pack.description"
                    class="mt-2 text-sm text-stone-600"
                >
                    {{ pack.description }}
                </p>
                <ul class="my-3 space-y-1 text-sm">
                    <li
                        v-for="(rule, i) in pack.rules"
                        :key="i"
                        class="flex items-center gap-3 rounded-lg bg-sky-50 px-2 py-1.5"
                    >
                        <ImagePreview
                            v-if="rule.image_url"
                            :src="rule.image_url"
                            :title="rule.name"
                        />
                        <span class="min-w-0">
                            <b>{{ rule.name }}</b>
                            <span class="text-stone-500"> · {{ describeRule(rule) }}</span>
                        </span>
                    </li>
                </ul>
                <div class="mt-auto flex gap-2">
                    <UButton
                        class="flex-1"
                        block
                        icon="i-lucide-download"
                        :loading="importing === pack.id"
                        :label="`Import ${pack.rules.length} rule${pack.rules.length > 1 ? 's' : ''}`"
                        @click="take(pack)"
                    />
                    <UButton
                        v-if="isAdmin"
                        color="error"
                        variant="soft"
                        icon="i-lucide-trash-2"
                        aria-label="Remove this pack from the hub"
                        @click="remove(pack)"
                    />
                </div>
            </article>
        </div>

        <p
            v-if="!packs.length && !error"
            class="py-10 text-center text-stone-500"
        >
            Nothing here yet{{ q ? ` for “${q}”` : '' }}.
        </p>

        <section
            v-if="mine.length"
            class="mt-10"
        >
            <h2 class="mb-3 text-lg font-semibold text-stone-500">
                Your packs
            </h2>
            <ul class="space-y-2">
                <li
                    v-for="pack in mine"
                    :key="pack.id"
                    class="flex items-center justify-between rounded-xl bg-white px-4 py-2"
                >
                    <span class="font-semibold">{{ pack.name }}</span>
                    <span class="flex items-center gap-2 text-sm text-stone-500">
                        {{ pack.imports_count }} imports
                        <UBadge
                            :color="STATUS_COLOR[pack.status as keyof typeof STATUS_COLOR]"
                            variant="soft"
                            :label="pack.status"
                        />
                    </span>
                </li>
            </ul>
        </section>
    </AppLayout>
</template>
