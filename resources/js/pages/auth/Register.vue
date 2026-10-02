<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3'
import AuthCard from '@/layouts/AuthCard.vue'
import { login } from '@/routes'
import { store } from '@/routes/register'

// Saved on the account: the user's day starts in their own timezone.
const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone

const page = usePage()
</script>

<template>
    <AuthCard
        title="Join Fleche"
        description="Todos that show up on their own. Sometimes by surprise."
    >
        <Head title="Register" />

        <Form
            v-slot="{ errors, processing }"
            v-bind="store.form()"
            :reset-on-error="['password', 'password_confirmation']"
            class="space-y-4"
        >
            <UFormField
                label="Name"
                name="name"
                :error="errors.name"
            >
                <UInput
                    name="name"
                    required
                    autofocus
                    size="lg"
                    class="w-full"
                />
            </UFormField>
            <UFormField
                label="Email"
                name="email"
                :error="errors.email"
            >
                <UInput
                    name="email"
                    type="email"
                    autocomplete="username"
                    required
                    size="lg"
                    class="w-full"
                />
            </UFormField>
            <UFormField
                label="Password"
                name="password"
                :error="errors.password"
            >
                <UInput
                    name="password"
                    type="password"
                    autocomplete="new-password"
                    required
                    size="lg"
                    class="w-full"
                />
            </UFormField>
            <UFormField
                label="Confirm password"
                name="password_confirmation"
            >
                <UInput
                    name="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    required
                    size="lg"
                    class="w-full"
                />
            </UFormField>
            <input
                type="hidden"
                name="timezone"
                :value="timezone"
            >
            <UButton
                type="submit"
                :loading="processing"
                block
                size="lg"
                label="Create my account"
            />
            <p
                v-if="page.props.edition === 'cloud'"
                class="text-center text-xs text-stone-500"
            >
                By creating an account you accept the
                <a
                    href="/terms"
                    target="_blank"
                    class="underline"
                >terms</a>
                and the
                <a
                    href="/privacy"
                    target="_blank"
                    class="underline"
                >privacy policy</a>.
            </p>
            <Link
                :href="login.url()"
                class="block text-center text-sm text-stone-500 underline"
            >
                Already have an account?
            </Link>
        </Form>
    </AuthCard>
</template>
