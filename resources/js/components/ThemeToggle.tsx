import { Monitor, Moon, Sun } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useTheme } from '@/contexts/ThemeContext'
import type { Theme } from '@/types'

const options: Array<{ value: Theme; label: string; icon: typeof Sun }> = [
    { value: 'light', label: 'Light theme', icon: Sun },
    { value: 'dark', label: 'Dark theme', icon: Moon },
    { value: 'system', label: 'System theme', icon: Monitor },
]

export default function ThemeToggle() {
    const { theme, setTheme } = useTheme()

    return (
        <div className="flex rounded-md border border-slate-200 bg-white p-1 shadow-sm dark:border-slate-800 dark:bg-slate-950">
            {options.map(({ value, label, icon: Icon }) => (
                <Button
                    key={value}
                    variant={theme === value ? 'secondary' : 'ghost'}
                    className="h-8 w-8 px-0"
                    onClick={() => setTheme(value)}
                    title={label}
                    aria-label={label}
                >
                    <Icon
                        size={16}
                        className="h-4 w-4 shrink-0"
                    />
                </Button>
            ))}
        </div>
    )
}
