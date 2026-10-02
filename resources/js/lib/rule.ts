import type { Rule } from '@/types'

const DAY_SHORT: Record<string, string> = {
    monday: 'Mon', tuesday: 'Tue', wednesday: 'Wed', thursday: 'Thu', friday: 'Fri', saturday: 'Sat', sunday: 'Sun'
}

const MONTH_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']

/** Human sentence for a rule, e.g. "Sat or Sun · at most every 2 weeks · 1 in 2 chance". */
export function describeRule (rule: Partial<Rule>): string {
    const parts: string[] = []

    if (rule.days?.length) {
        parts.push(describeDays(rule.days, rule.random_day ?? false))
    }

    if (rule.day_of_month) {
        parts.push(rule.day_of_month === -1 ? 'last day of the month' : `on the ${rule.day_of_month}${ordinal(rule.day_of_month)}`)
    }

    if (rule.months?.length) {
        parts.push(describeMonths(rule.months))
    }

    if (rule.every_value && rule.every_unit) {
        parts.push(`at most every ${rule.every_value === 1 ? '' : rule.every_value + ' '}${rule.every_unit}${rule.every_value === 1 ? '' : 's'}`)
    }

    if (rule.start_after) {
        parts.push(`from ${rule.start_after}`)
    }

    if (rule.chance !== undefined && Number(rule.chance) < 1) {
        parts.push(`${describeChance(Number(rule.chance))} chance`)
    }

    return parts.length ? parts.join(' · ') : 'every day'
}

const WEEKDAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']
const WEEKEND = ['saturday', 'sunday']

/**
 * "1 in 3" when the odds are a clean fraction, "75%" otherwise. Mirrors
 * TodoSetting::describeChance().
 */
export function describeChance (chance: number): string {
    const oneIn = 1 / chance

    return Math.abs(oneIn - Math.round(oneIn)) / oneIn < 0.02 ? `1 in ${Math.round(oneIn)}` : `${Math.round(chance * 100)}%`
}

function describeDays (days: string[], randomDay: boolean): string {
    if (sameSet(days, WEEKDAYS)) {
        return randomDay ? 'one weekday' : 'weekdays'
    }

    if (sameSet(days, WEEKEND)) {
        return randomDay ? 'Sat or Sun' : 'weekends'
    }

    const names = days.map(day => DAY_SHORT[day]).join(randomDay ? ' or ' : ', ')

    return randomDay ? `one of ${names}` : names
}

function describeMonths (months: number[]): string {
    const sorted = [...months].sort((a, b) => a - b)

    if (sameSet(sorted, [1, 4, 7, 10])) {
        return 'every quarter'
    }

    const contiguous = sorted.length > 2 && sorted.every((month, i) => i === 0 || month === sorted[i - 1] + 1)

    return contiguous
        ? `${MONTH_SHORT[sorted[0] - 1]}–${MONTH_SHORT[sorted[sorted.length - 1] - 1]}`
        : `in ${sorted.map(month => MONTH_SHORT[month - 1]).join(', ')}`
}

function sameSet<T> (a: T[], b: T[]): boolean {
    return a.length === b.length && b.every(item => a.includes(item))
}

function ordinal (n: number): string {
    if (n % 100 >= 11 && n % 100 <= 13) {
        return 'th'
    }

    return { 1: 'st', 2: 'nd', 3: 'rd' }[n % 10] ?? 'th'
}
