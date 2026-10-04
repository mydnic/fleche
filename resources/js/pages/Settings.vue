<script setup lang="ts">
import { Form, Head, router, useForm, usePage, usePoll } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { checkout, notifications } from '@/routes/settings'
import { destroy as destroyAccount } from '@/routes/settings/account'
import { disconnect } from '@/routes/settings/telegram'
import tokens from '@/routes/settings/tokens'
import userPassword from '@/routes/user-password'
import userProfileInformation from '@/routes/user-profile-information'

const props = defineProps<{
    telegramLink: string | null
    tokens: { id: number, name: string, last_used_at: string | null, created_at: string }[]
    newToken: string | null
    apiBase: string
}>()

const page = usePage()
const user = computed(() => page.props.auth.user!)
const cloud = computed(() => page.props.edition === 'cloud')

const notify = useForm({
    notify_mail: user.value.notify_mail,
    notify_telegram: user.value.notify_telegram,
    day_start_hour: user.value.day_start_hour,
    timezone: user.value.timezone
})

const TIMEZONES = Intl.supportedValuesOf('timeZone')

/**
 * Telegram only tells our server about the "Start" press when asked, so the
 * page asks every few seconds, and only after the bot link was opened.
 */
const waitingForTelegram = ref(false)
const telegramPoll = usePoll(2500, { only: ['auth'], data: { telegram: 1 } }, { autoStart: false })

function openTelegram (): void {
    waitingForTelegram.value = true
    telegramPoll.start()
}

watch(() => user.value.telegram_chat_id, (chatId) => {
    if (chatId) {
        waitingForTelegram.value = false
        telegramPoll.stop()
    }
})

const deleting = ref(false)
const deletion = useForm({ password: '' })

function deleteAccount (): void {
    deletion.delete(destroyAccount.url(), { preserveScroll: true, onError: () => deletion.reset('password') })
}

const HOURS = Array.from({ length: 24 }, (_, h) => ({ label: `${String(h).padStart(2, '0')}:00`, value: h }))

const curl = computed(() => `curl -H "Authorization: Bearer ${props.newToken ?? 'YOUR_TOKEN'}" -H "Accept: application/json" ${props.apiBase}/todos?active=1`)
</script>

<template>
    <AppLayout>
        <Head title="Settings" />

        <h1 class="mb-6 text-3xl font-bold">
            Settings
        </h1>

        <div class="space-y-5">
            <section
                v-if="cloud"
                class="pop rounded-3xl border-2 p-5"
                :class="user.paid_at ? 'border-green-200 bg-green-50' : 'border-amber-300 bg-amber-50'"
            >
                <template v-if="user.paid_at">
                    <h2 class="text-xl font-bold">
                        🏆 Lifetime member
                    </h2>
                    <p class="text-sm text-stone-600">
                        Your whole history is kept forever. Thanks for supporting Fleche!
                    </p>
                </template>
                <template v-else>
                    <h2 class="text-xl font-bold">
                        Keep your history forever
                    </h2>
                    <p class="mb-3 text-sm text-stone-600">
                        On the free plan, past todos are cleared after 7 days. One payment of $35, no subscription, unlimited history forever.
                    </p>
                    <UButton
                        size="lg"
                        icon="i-lucide-sparkles"
                        label="Unlock for life · $35"
                        @click="router.post(checkout.url())"
                    />
                </template>
            </section>

            <section class="pop space-y-4 rounded-3xl border-2 border-orange-100 bg-white p-5">
                <h2 class="text-xl font-bold">
                    🌅 My day
                </h2>
                <form
                    class="space-y-4"
                    @submit.prevent="notify.put(notifications.url(), { preserveScroll: true })"
                >
                    <div class="flex flex-wrap gap-4">
                        <UFormField
                            label="My day starts at"
                            help="New todos appear and your list is sent."
                        >
                            <USelect
                                v-model="notify.day_start_hour"
                                :items="HOURS"
                                class="w-32"
                            />
                        </UFormField>
                        <UFormField
                            label="Your timezone"
                            help="Your day starts in this timezone."
                            :error="notify.errors.timezone"
                        >
                            <USelectMenu
                                v-model="notify.timezone"
                                :items="TIMEZONES"
                                class="w-64"
                            />
                        </UFormField>
                    </div>
                    <USwitch
                        v-model="notify.notify_mail"
                        :label="`Email (${user.email})`"
                    />
                    <USwitch
                        v-model="notify.notify_telegram"
                        label="Telegram"
                        :disabled="!user.telegram_chat_id"
                    />
                    <UButton
                        type="submit"
                        :loading="notify.processing"
                        label="Save"
                    />
                </form>

                <div class="rounded-2xl bg-sky-50 p-4">
                    <template v-if="user.telegram_chat_id">
                        <p class="text-sm font-semibold">
                            ✅ Telegram connected
                        </p>
                        <UButton
                            size="sm"
                            variant="link"
                            color="neutral"
                            label="Disconnect"
                            class="px-0"
                            @click="router.delete(disconnect.url(), { preserveScroll: true })"
                        />
                    </template>
                    <template v-else-if="telegramLink">
                        <p class="mb-2 text-sm">
                            Open the bot, press <b>Start</b>, and you're linked.
                        </p>
                        <div class="flex flex-wrap items-center gap-3">
                            <UButton
                                :to="telegramLink"
                                target="_blank"
                                color="secondary"
                                icon="i-lucide-send"
                                label="Connect Telegram"
                                @click="openTelegram"
                            />
                            <span
                                v-if="waitingForTelegram"
                                class="flex items-center gap-2 text-sm text-stone-500"
                            >
                                <UIcon
                                    name="i-lucide-loader-circle"
                                    class="animate-spin"
                                />
                                Waiting for you to press Start…
                            </span>
                        </div>
                    </template>
                    <p
                        v-else
                        class="text-sm text-stone-500"
                    >
                        Telegram isn't configured on this instance (TELEGRAM_BOT_TOKEN / TELEGRAM_BOT_USERNAME).
                    </p>
                </div>
            </section>

            <section class="pop space-y-4 rounded-3xl border-2 border-orange-100 bg-white p-5">
                <h2 class="text-xl font-bold">
                    👤 Profile
                </h2>
                <Form
                    v-slot="{ errors, processing, recentlySuccessful }"
                    v-bind="userProfileInformation.update.form()"
                    :options="{ preserveScroll: true }"
                    class="grid gap-3 sm:grid-cols-2"
                >
                    <UFormField
                        label="Name"
                        :error="errors.name"
                    >
                        <UInput
                            name="name"
                            :default-value="user.name"
                            class="w-full"
                        />
                    </UFormField>
                    <UFormField
                        label="Email"
                        :error="errors.email"
                    >
                        <UInput
                            name="email"
                            type="email"
                            :default-value="user.email"
                            class="w-full"
                        />
                    </UFormField>
                    <div>
                        <UButton
                            type="submit"
                            :loading="processing"
                            :label="recentlySuccessful ? 'Saved ✓' : 'Save profile'"
                        />
                    </div>
                </Form>

                <Form
                    v-slot="{ errors, processing, recentlySuccessful }"
                    v-bind="userPassword.update.form()"
                    :options="{ preserveScroll: true }"
                    error-bag="updatePassword"
                    reset-on-success
                    class="grid gap-3 border-t border-orange-100 pt-4 sm:grid-cols-3"
                >
                    <UFormField
                        label="Current password"
                        :error="errors.current_password"
                    >
                        <UInput
                            name="current_password"
                            type="password"
                            autocomplete="current-password"
                            class="w-full"
                        />
                    </UFormField>
                    <UFormField
                        label="New password"
                        :error="errors.password"
                    >
                        <UInput
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            class="w-full"
                        />
                    </UFormField>
                    <UFormField label="Confirm">
                        <UInput
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="w-full"
                        />
                    </UFormField>
                    <div>
                        <UButton
                            type="submit"
                            variant="soft"
                            :loading="processing"
                            :label="recentlySuccessful ? 'Changed ✓' : 'Change password'"
                        />
                    </div>
                </Form>
            </section>

            <section class="pop space-y-4 rounded-3xl border-2 border-orange-100 bg-white p-5">
                <h2 class="text-xl font-bold">
                    🔑 API
                </h2>

                <UAlert
                    v-if="newToken"
                    color="success"
                    variant="soft"
                    title="Copy your key now, it won't be shown again"
                >
                    <template #description>
                        <code class="font-mono break-all select-all">{{ newToken }}</code>
                    </template>
                </UAlert>

                <Form
                    v-slot="{ errors, processing }"
                    v-bind="tokens.store.form()"
                    :options="{ preserveScroll: true }"
                    reset-on-success
                    class="flex gap-2"
                >
                    <UFormField
                        :error="errors.name"
                        class="flex-1"
                    >
                        <UInput
                            name="name"
                            placeholder="Key name (e.g. Home Assistant)"
                            class="w-full"
                        />
                    </UFormField>
                    <UButton
                        type="submit"
                        :loading="processing"
                        label="Generate key"
                    />
                </Form>

                <ul
                    v-if="props.tokens.length"
                    class="divide-y divide-orange-100"
                >
                    <li
                        v-for="token in props.tokens"
                        :key="token.id"
                        class="flex items-center justify-between py-2 text-sm"
                    >
                        <span>
                            <b>{{ token.name }}</b>
                            <span class="text-stone-400"> · {{ token.last_used_at ? 'used ' + new Date(token.last_used_at).toLocaleDateString() : 'never used' }}</span>
                        </span>
                        <UButton
                            size="xs"
                            color="error"
                            variant="ghost"
                            icon="i-lucide-trash-2"
                            aria-label="Revoke"
                            @click="router.delete(tokens.destroy.url(token.id), { preserveScroll: true })"
                        />
                    </li>
                </ul>

                <details class="rounded-2xl bg-stone-50 p-4 text-sm">
                    <summary class="cursor-pointer font-bold">
                        API documentation
                    </summary>
                    <div class="mt-3 space-y-3">
                        <p>Base URL <code class="font-mono">{{ apiBase }}</code>. Send <code class="font-mono">Authorization: Bearer &lt;key&gt;</code> and <code class="font-mono">Accept: application/json</code>.</p>
                        <table class="w-full text-left">
                            <tbody class="[&_td]:py-1 [&_td]:pr-3 [&_td:first-child]:font-mono [&_td:first-child]:whitespace-nowrap">
                                <tr><td>GET /todos</td><td>List todos. Filters: <code>from</code>, <code>to</code> (YYYY-MM-DD), <code>active</code> (1 = open, 0 = done).</td></tr>
                                <tr><td>POST /todos</td><td>Create a one-shot todo: <code>name</code>, <code>description?</code>, <code>date?</code> (default tomorrow, your timezone), <code>points?</code>, <code>image?</code> (file, multipart). Responses carry <code>image_url</code>.</td></tr>
                                <tr><td>GET /todos/{id}</td><td>One todo.</td></tr>
                                <tr><td>POST /todos/{id}/done</td><td>Mark as done. <b>Final</b>: there is no undo, anywhere.</td></tr>
                                <tr><td>GET /todo-settings</td><td>List rules.</td></tr>
                                <tr><td>POST /todo-settings</td><td>Create a rule (same fields as the form: <code>name, description, image (file, multipart; POST with _method=PATCH to update), remove_image, active, days[], random_day, every_value, every_unit (day|week|month|year), day_of_month (1-31, -1 = last), months[], start_after, chance (0-1], allow_duplicates, points, reward_cost</code>).</td></tr>
                                <tr><td>GET /todo-settings/{id}</td><td>One rule.</td></tr>
                                <tr><td>PATCH /todo-settings/{id}</td><td>Update some fields, e.g. <code>{"active": false}</code>.</td></tr>
                                <tr><td>DELETE /todo-settings/{id}</td><td>Delete a rule (its todos stay).</td></tr>
                            </tbody>
                        </table>
                        <pre class="overflow-x-auto rounded-xl bg-stone-800 p-3 text-xs text-orange-100">{{ curl }}</pre>
                    </div>
                </details>
            </section>

            <section class="rounded-3xl border-2 border-dashed border-red-200 bg-white/60 p-5">
                <h2 class="text-xl font-bold">
                    🗑️ Delete my account
                </h2>
                <p class="mt-1 text-sm text-stone-500">
                    Removes your account, todos, rules, pictures and API keys for good. Packs you shared on the hub stay, without your name.
                </p>
                <UButton
                    v-if="!deleting"
                    class="mt-3"
                    color="error"
                    variant="soft"
                    label="Delete my account"
                    @click="deleting = true"
                />
                <form
                    v-else
                    class="mt-3 flex flex-wrap items-start gap-2"
                    @submit.prevent="deleteAccount"
                >
                    <UFormField
                        :error="deletion.errors.password"
                        class="min-w-56 flex-1"
                    >
                        <UInput
                            v-model="deletion.password"
                            type="password"
                            autocomplete="current-password"
                            placeholder="Your password, to confirm"
                            class="w-full"
                            autofocus
                        />
                    </UFormField>
                    <UButton
                        type="submit"
                        color="error"
                        :loading="deletion.processing"
                        label="Delete everything"
                    />
                    <UButton
                        color="neutral"
                        variant="ghost"
                        label="Cancel"
                        @click="deleting = false"
                    />
                </form>
            </section>

            <p
                v-if="cloud"
                class="pb-4 text-center text-xs text-stone-400"
            >
                <a
                    href="/privacy"
                    class="underline"
                >Privacy</a> · <a
                    href="/terms"
                    class="underline"
                >Terms</a> · <a
                    href="https://github.com/mydnic/fleche"
                    target="_blank"
                    class="underline"
                >Open Source</a>
            </p>
        </div>
    </AppLayout>
</template>
