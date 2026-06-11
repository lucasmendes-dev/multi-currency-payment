import { createContext, useContext, useEffect, useMemo, useState } from 'react'
import type React from 'react'
import type { Theme } from '@/types'

interface ThemeContextType {
    theme: Theme
    setTheme: (theme: Theme) => void
}

const ThemeContext = createContext<ThemeContextType | undefined>(undefined)

function applyTheme(theme: Theme) {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
    const shouldUseDark = theme === 'dark' || (theme === 'system' && prefersDark)

    document.documentElement.classList.toggle('dark', shouldUseDark)
    document.documentElement.style.colorScheme = shouldUseDark ? 'dark' : 'light'
}

export function ThemeProvider({ children }: { children: React.ReactNode }) {
    const [theme, setThemeState] = useState<Theme>(() => (localStorage.getItem('theme') as Theme | null) ?? 'system')

    useEffect(() => {
        applyTheme(theme)

        const media = window.matchMedia('(prefers-color-scheme: dark)')
        const listener = () => {
            if (theme === 'system') applyTheme(theme)
        }

        media.addEventListener('change', listener)
        return () => media.removeEventListener('change', listener)
    }, [theme])

    function setTheme(nextTheme: Theme) {
        localStorage.setItem('theme', nextTheme)
        setThemeState(nextTheme)
    }

    const value = useMemo(() => ({ theme, setTheme }), [theme])

    return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>
}

export function useTheme() {
    const context = useContext(ThemeContext)
    if (!context) throw new Error('useTheme must be used inside ThemeProvider')
    return context
}
