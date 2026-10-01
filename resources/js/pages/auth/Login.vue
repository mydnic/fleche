<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3'
import AuthCard from '@/layouts/AuthCard.vue'
import { store } from '@/routes/login'
import { request } from '@/routes/password'

defineProps<{ status?: string }>()

const page = usePage()
</script>

<template>
    <AuthCard
        title="Welcome back!"
        description="Your todos are waiting. Some of them rolled the dice today."
    >
        <Head title="Log in" />

        <UAlert
            v-if="status"
            :description="status"
            color="success"
            variant="soft"
            class="mb-4"
        />

        <Form
            v-slot="{ errors, processing }"
            v-bind="store.form()"
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
                    autocomplete="username"
                    required
                    autofocus
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
                    autocomplete="current-password"
                    required
                    size="lg"
                    class="w-full"
                />
            </UFormField>
            <UCheckbox
                name="remember"
                value="1"
                label="Remember me"
            />
            <UButton
                type="submit"
                :loading="processing"
                block
                size="lg"
                label="Let's go 🏹"
            />
            <div class="flex justify-between text-sm text-stone-500">
                <Link
                    :href="request.url()"
                    class="underline"
                >
                    Forgot password?
                </Link>
                <Link
                    v-if="page.props.registerUrl"
                    :href="page.props.registerUrl"
                    class="underline"
                >
                    Create an account
                </Link>
            </div>
        </Form>
    </AuthCard>
</template>
