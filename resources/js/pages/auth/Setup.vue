<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3'
import AuthCard from '@/layouts/AuthCard.vue'
import { store } from '@/routes/setup'

// Saved on the account: the user's day starts in their own timezone.
const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone
</script>

<template>
    <AuthCard
        title="Set up your instance"
        description="First account on this Fleche: it will be the admin."
    >
        <Head title="Setup" />

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
                label="Create admin account"
            />
        </Form>
    </AuthCard>
</template>
