import { createContext, useContext, useEffect, useMemo, useState } from 'react'
import type React from 'react'
import api from '@/lib/axios'
import type { ApiItemResponse, AuthContextType, AuthResponse, User } from '@/types'

const AuthContext = createContext<AuthContextType | undefined>(undefined)

function normalizeUser(user: User): User {
    return {
        ...user,
        role: user.role,
        local_currency: user.local_currency ?? user.currency_code,
        currency_code: user.currency_code ?? user.local_currency,
    }
}

function storedUser(): User | null {
    const value = localStorage.getItem('auth_user')
    if (!value) return null

    try {
        return normalizeUser(JSON.parse(value) as User)
    } catch {
        localStorage.removeItem('auth_user')
        return null
    }
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
    const [token, setToken] = useState<string | null>(() => localStorage.getItem('auth_token'))
    const [user, setUser] = useState<User | null>(() => storedUser())
    const [isLoading, setIsLoading] = useState(Boolean(localStorage.getItem('auth_token')))

    useEffect(() => {
        let ignore = false

        async function loadCurrentUser() {
            if (!token) {
                setIsLoading(false)
                return
            }

            try {
                const { data } = await api.get<ApiItemResponse<User> | { user: User }>('/auth/me')
                const currentUser = normalizeUser('user' in data ? data.user : data.data)
                if (ignore) return

                localStorage.setItem('auth_user', JSON.stringify(currentUser))
                setUser(currentUser)
            } catch {
                if (ignore) return

                localStorage.removeItem('auth_token')
                localStorage.removeItem('auth_user')
                setToken(null)
                setUser(null)
            } finally {
                if (!ignore) setIsLoading(false)
            }
        }

        loadCurrentUser()

        return () => {
            ignore = true
        }
    }, [token])

    async function login(email: string, password: string) {
        const { data } = await api.post<AuthResponse>('/auth/login', { email, password })
        const normalizedUser = normalizeUser(data.user)

        localStorage.setItem('auth_token', data.token)
        localStorage.setItem('auth_user', JSON.stringify(normalizedUser))
        setToken(data.token)
        setUser(normalizedUser)
        setIsLoading(false)

        return normalizedUser
    }

    async function logout() {
        try {
            if (token) await api.post('/auth/logout')
        } finally {
            localStorage.removeItem('auth_token')
            localStorage.removeItem('auth_user')
            setToken(null)
            setUser(null)
        }
    }

    const value = useMemo<AuthContextType>(
        () => ({
            user,
            token,
            login,
            logout,
            isAuthenticated: Boolean(token && user),
            isLoading,
        }),
        [token, user, isLoading],
    )

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
    const context = useContext(AuthContext)
    if (!context) throw new Error('useAuth must be used inside AuthProvider')
    return context
}
