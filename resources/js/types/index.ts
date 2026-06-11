export type UserRole = 'employee' | 'finance'

export interface User {
    id: number
    name: string
    email: string
    role: UserRole
    country?: string
    local_currency?: string
    currency_code?: string
}

export type PaymentStatus = 'pending' | 'approved' | 'rejected' | 'expired'

export interface PaymentRequest {
    id: number
    user_id: number
    local_currency: string
    local_amount: string | number
    target_currency: string
    converted_amount: string | number
    exchange_rate: string | number
    exchange_rate_source?: string
    exchange_rate_fetched_at: string
    status: PaymentStatus
    description?: string | null
    approved_by?: number | null
    approved_at?: string | null
    rejected_by?: number | null
    rejected_at?: string | null
    rejection_reason?: string | null
    expires_at?: string
    created_at: string
    updated_at: string
    user?: {
        name: string
        email: string
        country?: string
    }
}

export interface AuthResponse {
    token: string
    user: User
    message?: string
}

export interface AuthContextType {
    user: User | null
    token: string | null
    login: (email: string, password: string) => Promise<User>
    logout: () => Promise<void>
    isAuthenticated: boolean
    isLoading: boolean
}

export interface ApiItemResponse<T> {
    data: T
    message?: string
}

export interface ApiListResponse<T> {
    data: T[]
    meta?: {
        total: number
        per_page: number
        current_page: number
        last_page: number
    }
}

export type Theme = 'light' | 'dark' | 'system'
