import { Navigate, Outlet } from 'react-router-dom'
import { useAuth } from '@/contexts/AuthContext'
import type { UserRole } from '@/types'

export default function RoleRoute({ role }: { role: UserRole }) {
    const { user, isLoading } = useAuth()

    if (isLoading) return null
    if (!user) return <Navigate to="/login" replace />
    if (user.role !== role) {
        return <Navigate to={user.role === 'finance' ? '/finance/dashboard' : '/dashboard'} replace />
    }

    return <Outlet />
}
