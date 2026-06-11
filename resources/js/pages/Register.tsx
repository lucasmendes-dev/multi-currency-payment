import { FormEvent, useState } from 'react'
import { Link, Navigate, useNavigate } from 'react-router-dom'
import api from '@/lib/axios'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { apiError } from '@/lib/format'
import { useAuth } from '@/contexts/AuthContext'

export default function Register() {
    const { isAuthenticated, user } = useAuth()
    const navigate = useNavigate()
    const [form, setForm] = useState({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        country: '',
        local_currency: '',
    })
    const [error, setError] = useState('')
    const [loading, setLoading] = useState(false)

    if (isAuthenticated && user) {
        return <Navigate to={user.role === 'finance' ? '/finance/dashboard' : '/dashboard'} replace />
    }

    function update(field: keyof typeof form, value: string) {
        setForm((current) => ({ ...current, [field]: value }))
    }

    async function handleSubmit(event: FormEvent) {
        event.preventDefault()
        setError('')
        setLoading(true)

        try {
            await api.post('/auth/register', { ...form, local_currency: form.local_currency.toUpperCase() })
            navigate('/login', { replace: true })
        } catch (exception) {
            setError(apiError(exception))
        } finally {
            setLoading(false)
        }
    }

    return (
        <div className="flex min-h-screen items-center justify-center bg-[radial-gradient(circle_at_top_left,#dbeafe,transparent_32%),#f8fafc] px-4 py-10 dark:bg-[radial-gradient(circle_at_top_left,#1e3a8a,transparent_30%),#020617]">
            <Card className="w-full max-w-xl overflow-hidden">
                <CardHeader className="items-center border-b border-slate-100 bg-slate-50/70 text-center dark:border-slate-800 dark:bg-slate-900">
                    <img src="/logo.jpg" alt="Multi Currency Payment" className="h-16 w-16 rounded-xl object-cover shadow-md" />
                    <CardTitle>Create account</CardTitle>
                    <CardDescription>Register as an employee and submit payment requests.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form className="grid gap-4 sm:grid-cols-2" onSubmit={handleSubmit}>
                        <div className="space-y-2 sm:col-span-2">
                            <Label htmlFor="name">Name</Label>
                            <Input id="name" value={form.name} onChange={(event) => update('name', event.target.value)} required />
                        </div>
                        <div className="space-y-2 sm:col-span-2">
                            <Label htmlFor="email">Email</Label>
                            <Input id="email" type="email" value={form.email} onChange={(event) => update('email', event.target.value)} required />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="password">Password</Label>
                            <Input id="password" type="password" value={form.password} onChange={(event) => update('password', event.target.value)} required />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="password_confirmation">Confirm password</Label>
                            <Input id="password_confirmation" type="password" value={form.password_confirmation} onChange={(event) => update('password_confirmation', event.target.value)} required />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="country">Country</Label>
                            <Input id="country" value={form.country} onChange={(event) => update('country', event.target.value)} required />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="local_currency">Currency code</Label>
                            <Input id="local_currency" maxLength={3} value={form.local_currency} onChange={(event) => update('local_currency', event.target.value)} required />
                        </div>
                        {error && <p className="text-sm text-red-600 sm:col-span-2">{error}</p>}
                        <Button type="submit" className="sm:col-span-2" disabled={loading}>
                            {loading ? 'Creating account...' : 'Create account'}
                        </Button>
                    </form>
                    <p className="mt-4 text-center text-sm text-slate-600">
                        Already registered? <Link className="font-medium text-slate-950 underline dark:text-white" to="/login">Sign in</Link>
                    </p>
                </CardContent>
            </Card>
        </div>
    )
}
