import { Link, useNavigate } from 'react-router-dom'
import { Button } from '@/components/ui/button'
import ThemeToggle from '@/components/ThemeToggle'
import { useAuth } from '@/contexts/AuthContext'

export default function Navbar() {
    const { user, logout } = useAuth()
    const navigate = useNavigate()

    async function handleLogout() {
        await logout()
        navigate('/login', { replace: true })
    }

    if (!user) return null

    const dashboard = user.role === 'finance' ? '/finance/dashboard' : '/dashboard'

    return (
        <header className="sticky top-0 z-40 border-b border-slate-200 bg-white/90 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-950/85">
            <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link to={dashboard} className="flex items-center gap-3 text-base font-semibold text-slate-950 dark:text-white">
                    <img src="/logo.jpg" alt="Multi Currency Payment" className="h-9 w-9 rounded-md object-cover shadow-sm" />
                    <span className="hidden sm:inline">Multi Currency Payment</span>
                </Link>
                <div className="flex items-center gap-3">
                    <ThemeToggle />
                    <span className="hidden text-sm text-slate-600 dark:text-slate-300 sm:inline">{user.name}</span>
                    <Button variant="outline" onClick={handleLogout}>
                        Logout
                    </Button>
                </div>
            </div>
        </header>
    )
}
