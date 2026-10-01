import type { Rule } from '@/types'

const DAY_SHORT: Record<string, string> = {
    monday: 'Mon', tuesday: 'Tue', wednesday: 'Wed', thursday: 'Thu', friday: 'Fri', saturday: 'Sat', sunday: 'Sun'
}

const MONTH_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']

/** Human sentence for a rule, e.g. "Sat or Sun · at most every 2 weeks · 1 in 2 chance". */
export function describeRule (rule: Partial<Rule>): string {
    const parts: string[] = []

    if (rule.days?.length) {
        const days = rule.days.map(day => DAY_SHORT[day]).join(rule.random_day ? ' or ' : ', ')
        parts.push(rule.random_day ? `one of ${days}` : days)
    }

    if (rule.day_of_month) {
        parts.push(rule.day_of_month === -1 ? 'last day of the month' : `on the ${rule.day_of_month}${ordinal(rule.day_of_month)}`)
    }

    if (rule.months?.length) {
        parts.push(`in ${rule.months.map(month => MONTH_SHORT[month - 1]).join(', ')}`)
    }

    if (rule.every_value && rule.every_unit) {
        parts.push(`at most every ${rule.every_value === 1 ? '' : rule.every_value + ' '}${rule.every_unit}${rule.every_value === 1 ? '' : 's'}`)
    }

    if (rule.start_after) {
        parts.push(`from ${rule.start_after}`)
    }

    if (rule.chance !== undefined && Number(rule.chance) < 1) {
        parts.push(`1 in ${Math.round(1 / Number(rule.chance))} chance`)
    }

    return parts.length ? parts.join(' · ') : 'every day'
}

function ordinal (n: number): string {
    if (n % 100 >= 11 && n % 100 <= 13) {
        return 'th'
    }

    return { 1: 'st', 2: 'nd', 3: 'rd' }[n % 10] ?? 'th'
}
