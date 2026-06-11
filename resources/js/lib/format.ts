import type { PaymentStatus } from '@/types'

export const statusOptions: Array<'all' | PaymentStatus> = ['all', 'pending', 'approved', 'rejected', 'expired']

export function money(value: string | number, currency: string) {
    const amount = Number(value)
    if (Number.isNaN(amount)) return `${value} ${currency}`

    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
    }).format(amount)
}

export function dateTime(value?: string | null) {
    if (!value) return '-'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return value
    return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(date)
}

export function apiError(error: unknown) {
    if (typeof error === 'object' && error && 'response' in error) {
        const response = (error as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }).response
        const errors = response?.data?.errors
        if (errors) return Object.values(errors).flat().join(' ')
        if (response?.data?.message) return response.data.message
    }

    return 'Something went wrong. Please try again.'
}
