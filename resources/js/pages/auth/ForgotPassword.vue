<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3'
import AuthCard from '@/layouts/AuthCard.vue'
import { login } from '@/routes'
import { email } from '@/routes/password'

defineProps<{ status?: string }>()
</script>

<template>
    <AuthCard
        title="Forgot password?"
        description="We'll send a reset link to your inbox."
    >
        <Head title="Forgot password" />

        <UAlert
            v-if="status"
            :description="status"
            color="success"
            variant="soft"
            class="mb-4"
        />

        <Form
            v-slot="{ errors, processing }"
            v-bind="email.form()"
            class="space-y-4"
        >
            <UFormField
                label="Email"
                name="email"
                :error="errors.email"
            >
                <UInput
                    name="email"
                    type="email"
                    required
                    autofocus
                    size="lg"
                    class="w-full"
                />
            </UFormField>
            <UButton
                type="submit"
                :loading="processing"
                block
                size="lg"
                label="Send reset link"
            />
            <Link
                :href="login.url()"
                class="block text-center text-sm text-stone-500 underline"
            >
                Back to login
            </Link>
        </Form>
    </AuthCard>
</template>
