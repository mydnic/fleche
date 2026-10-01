import type { User } from '@/types'

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            auth: { user: User | null }
            edition: 'self' | 'cloud'
            registerUrl: string | null
            status: string | null
            [key: string]: unknown
        }
    }
}
