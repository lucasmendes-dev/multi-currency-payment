import { Navigate, Outlet } from 'react-router-dom'
import Navbar from '@/components/Navbar'
import { useAuth } from '@/contexts/AuthContext'

export default function ProtectedRoute() {
    const { isAuthenticated, isLoading } = useAuth()

    if (isLoading) return <div className="min-h-screen bg-slate-50 dark:bg-slate-950" />
    if (!isAuthenticated) return <Navigate to="/login" replace />

    return (
        <div className="min-h-screen bg-slate-50 text-slate-950 dark:bg-slate-950 dark:text-slate-100">
            <Navbar />
            <main className="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
                <Outlet />
            </main>
        </div>
    )
}
