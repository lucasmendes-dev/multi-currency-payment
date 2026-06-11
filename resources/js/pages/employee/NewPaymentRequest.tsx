import { FormEvent, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '@/lib/axios'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { useAuth } from '@/contexts/AuthContext'
import { apiError } from '@/lib/format'
import type { ApiItemResponse, PaymentRequest } from '@/types'

export default function NewPaymentRequest() {
    const { user } = useAuth()
    const navigate = useNavigate()
    const [title, setTitle] = useState('')
    const [description, setDescription] = useState('')
    const [amount, setAmount] = useState('')
    const [currency, setCurrency] = useState(user?.local_currency ?? user?.currency_code ?? '')
    const [error, setError] = useState('')
    const [loading, setLoading] = useState(false)

    async function handleSubmit(event: FormEvent) {
        event.preventDefault()
        setError('')
        setLoading(true)

        const combinedDescription = [title.trim(), description.trim()].filter(Boolean).join(' - ')

        try {
            const { data } = await api.post<ApiItemResponse<PaymentRequest>>('/payments', {
                local_amount: amount,
                local_currency: currency.toUpperCase(),
                description: combinedDescription || null,
            })
            navigate(`/payment-requests/${data.data.id}`)
        } catch (exception) {
            setError(apiError(exception))
        } finally {
            setLoading(false)
        }
    }

    return (
        <div className="mx-auto max-w-2xl">
            <Card>
                <CardHeader>
                    <CardTitle>New payment request</CardTitle>
                    <CardDescription>Exchange rate will be fetched automatically at submission.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form className="space-y-4" onSubmit={handleSubmit}>
                        <div className="space-y-2">
                            <Label htmlFor="title">Title</Label>
                            <Input id="title" maxLength={120} value={title} onChange={(event) => setTitle(event.target.value)} required />
                        </div>
                        <div className="grid gap-4 sm:grid-cols-[1fr_140px]">
                            <div className="space-y-2">
                                <Label htmlFor="amount">Amount</Label>
                                <Input id="amount" type="number" min="1" step="0.01" value={amount} onChange={(event) => setAmount(event.target.value)} required />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="currency">Currency</Label>
                                <Input id="currency" maxLength={3} value={currency} onChange={(event) => setCurrency(event.target.value)} required />
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="description">Description</Label>
                            <Textarea id="description" maxLength={120} value={description} onChange={(event) => setDescription(event.target.value)} />
                        </div>
                        {error && <p className="text-sm text-red-600">{error}</p>}
                        <div className="flex justify-end gap-2">
                            <Button variant="outline" onClick={() => navigate('/dashboard')}>Cancel</Button>
                            <Button type="submit" disabled={loading}>{loading ? 'Submitting...' : 'Submit request'}</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    )
}
