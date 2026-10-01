<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3'
import AuthCard from '@/layouts/AuthCard.vue'
import { update } from '@/routes/password'

defineProps<{ email: string, token: string }>()
</script>

<template>
    <AuthCard title="New password">
        <Head title="Reset password" />

        <Form
            v-slot="{ errors, processing }"
            v-bind="update.form()"
            :transform="data => ({ ...data, token, email })"
            class="space-y-4"
        >
            <UFormField
                label="Email"
                :error="errors.email"
            >
                <UInput
                    :model-value="email"
                    disabled
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
                    autofocus
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
            <UButton
                type="submit"
                :loading="processing"
                block
                size="lg"
                label="Reset password"
            />
        </Form>
    </AuthCard>
</template>
