export interface User {
    id: number
    name: string
    email: string
    points: number
    is_admin: boolean
    paid_at: string | null
    notify_mail: boolean
    notify_telegram: boolean
    day_start_hour: number
    timezone: string
    telegram_chat_id: string | null
}

export interface Todo {
    id: number
    todo_setting_id: number | null
    name: string
    description: string | null
    image_url: string | null
    date: string
    done_at: string | null
    points: number
}

export type Weekday = 'monday' | 'tuesday' | 'wednesday' | 'thursday' | 'friday' | 'saturday' | 'sunday'

export interface Rule {
    id?: number
    name: string
    group?: string | null
    description: string | null
    image_url?: string | null
    active: boolean
    days: Weekday[] | null
    random_day: boolean
    every_value: number | null
    every_unit: 'day' | 'week' | 'month' | 'year' | null
    day_of_month: number | null
    months: number[] | null
    start_after: string | null
    chance: number
    allow_duplicates: boolean
    points: number
    reward_cost: number | null
}

export interface HubPack {
    id: number
    name: string
    description: string | null
    author: string
    rules: Partial<Rule>[]
    imports_count: number
}
