import { FormEvent, useState } from 'react'
import { Link, Navigate, useNavigate } from 'react-router-dom'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useAuth } from '@/contexts/AuthContext'
import { apiError } from '@/lib/format'

export default function Login() {
    const { login, isAuthenticated, user } = useAuth()
    const navigate = useNavigate()
    const [email, setEmail] = useState('')
    const [password, setPassword] = useState('')
    const [error, setError] = useState('')
    const [loading, setLoading] = useState(false)

    if (isAuthenticated && user) {
        return <Navigate to={user.role === 'finance' ? '/finance/dashboard' : '/dashboard'} replace />
    }

    async function handleSubmit(event: FormEvent) {
        event.preventDefault()
        setError('')
        setLoading(true)

        try {
            const loggedUser = await login(email, password)
            navigate(loggedUser.role === 'finance' ? '/finance/dashboard' : '/dashboard', { replace: true })
        } catch (exception) {
            setError(apiError(exception))
        } finally {
            setLoading(false)
        }
    }

    return (
        <div className="flex min-h-screen items-center justify-center bg-[radial-gradient(circle_at_top_left,#dbeafe,transparent_32%),#f8fafc] px-4 py-10 dark:bg-[radial-gradient(circle_at_top_left,#1e3a8a,transparent_30%),#020617]">
            <Card className="w-full max-w-md overflow-hidden">
                <CardHeader className="items-center border-b border-slate-100 bg-slate-50/70 text-center dark:border-slate-800 dark:bg-slate-900">
                    <img src="/logo.jpg" alt="Multi Currency Payment" className="h-16 w-16 rounded-xl object-cover shadow-md mx-auto" />
                    <CardTitle>Sign in</CardTitle>
                    <CardDescription>Access the multi-currency payment portal.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form className="space-y-4" onSubmit={handleSubmit}>
                        <div className="space-y-2">
                            <Label htmlFor="email">Email</Label>
                            <Input id="email" type="email" value={email} onChange={(event) => setEmail(event.target.value)} required />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="password">Password</Label>
                            <Input id="password" type="password" value={password} onChange={(event) => setPassword(event.target.value)} required />
                        </div>
                        {error && <p className="text-sm text-red-600">{error}</p>}
                        <Button type="submit" className="w-full" disabled={loading}>
                            {loading ? 'Signing in...' : 'Sign in'}
                        </Button>
                    </form>
                    <p className="mt-4 text-center text-sm text-slate-600">
                        Need an account? <Link className="font-medium text-slate-950 underline dark:text-white" to="/register">Register</Link>
                    </p>
                </CardContent>
            </Card>
        </div>
    )
}
