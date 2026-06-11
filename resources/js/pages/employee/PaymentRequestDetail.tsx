import { useEffect, useState } from 'react'
import type React from 'react'
import { Link, useParams } from 'react-router-dom'
import api from '@/lib/axios'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import StatusBadge from '@/components/StatusBadge'
import { apiError, dateTime, money } from '@/lib/format'
import type { ApiItemResponse, PaymentRequest } from '@/types'

function DetailRow({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="grid gap-1 border-b border-slate-100 py-3 sm:grid-cols-3 dark:border-slate-800">
            <dt className="text-sm font-medium text-slate-500 dark:text-slate-400">{label}</dt>
            <dd className="text-sm text-slate-900 sm:col-span-2 dark:text-slate-100">{value}</dd>
        </div>
    )
}

export default function PaymentRequestDetail() {
    const { id } = useParams()
    const [request, setRequest] = useState<PaymentRequest | null>(null)
    const [loading, setLoading] = useState(true)
    const [error, setError] = useState('')

    useEffect(() => {
        let ignore = false

        async function loadRequest() {
            setLoading(true)
            setError('')

            try {
                const { data } = await api.get<ApiItemResponse<PaymentRequest>>(`/payments/${id}`)
                if (!ignore) setRequest(data.data)
            } catch (exception) {
                if (!ignore) setError(apiError(exception))
            } finally {
                if (!ignore) setLoading(false)
            }
        }

        loadRequest()
        return () => {
            ignore = true
        }
    }, [id])

    if (loading) return <p className="text-sm text-slate-500">Loading request...</p>
    if (error) return <p className="text-sm text-red-600">{error}</p>
    if (!request) return <p className="text-sm text-slate-500">Payment request not found.</p>

    return (
        <div className="space-y-4">
            <Button variant="outline" asChild><Link to="/dashboard">Back</Link></Button>
            <Card>
                <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <CardTitle>Payment request #{request.id}</CardTitle>
                    <StatusBadge status={request.status} />
                </CardHeader>
                <CardContent>
                    <dl>
                        <DetailRow label="Local amount" value={money(request.local_amount, request.local_currency)} />
                        <DetailRow label="EUR amount" value={money(request.converted_amount, request.target_currency)} />
                        <DetailRow label="Exchange rate" value={request.exchange_rate} />
                        <DetailRow label="Rate source" value={request.exchange_rate_source ?? '-'} />
                        <DetailRow label="Rate fetched at" value={dateTime(request.exchange_rate_fetched_at)} />
                        <DetailRow label="Description" value={request.description ?? '-'} />
                        <DetailRow label="Created" value={dateTime(request.created_at)} />
                        <DetailRow label="Updated" value={dateTime(request.updated_at)} />
                        <DetailRow label="Expires" value={dateTime(request.expires_at)} />
                        <DetailRow label="Approved at" value={dateTime(request.approved_at)} />
                        <DetailRow label="Rejected at" value={dateTime(request.rejected_at)} />
                        <DetailRow label="Rejection reason" value={request.rejection_reason ?? '-'} />
                    </dl>
                </CardContent>
            </Card>
        </div>
    )
}
