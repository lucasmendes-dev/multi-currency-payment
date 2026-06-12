import { FormEvent, useEffect, useState } from 'react'
import type React from 'react'
import { Link, useParams } from 'react-router-dom'
import api from '@/lib/axios'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
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

export default function FinanceRequestDetail() {
    const { id } = useParams()
    const [request, setRequest] = useState<PaymentRequest | null>(null)
    const [loading, setLoading] = useState(true)
    const [actionLoading, setActionLoading] = useState(false)
    const [rejectOpen, setRejectOpen] = useState(false)
    const [rejectionReason, setRejectionReason] = useState('')
    const [error, setError] = useState('')

    async function loadRequest(ignore = false) {
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

    useEffect(() => {
        let ignore = false
        loadRequest(ignore)
        return () => {
            ignore = true
        }
    }, [id])

    async function approve() {
        setActionLoading(true)
        setError('')

        try {
            const { data } = await api.patch<ApiItemResponse<PaymentRequest>>(`/payments/${id}/approve`)
            setRequest(data.data)
        } catch (exception) {
            setError(apiError(exception))
        } finally {
            setActionLoading(false)
        }
    }

    async function reject(event: FormEvent) {
        event.preventDefault()
        setActionLoading(true)
        setError('')

        try {
            const { data } = await api.patch<ApiItemResponse<PaymentRequest>>(`/payments/${id}/reject`, {
                rejection_reason: rejectionReason,
            })
            setRequest(data.data)
            setRejectOpen(false)
            setRejectionReason('')
        } catch (exception) {
            setError(apiError(exception))
        } finally {
            setActionLoading(false)
        }
    }

    if (loading) return <p className="text-sm text-slate-500">Loading request...</p>
    if (error && !request) return <p className="text-sm text-red-600">{error}</p>
    if (!request) return <p className="text-sm text-slate-500">Payment request not found.</p>

    const canAct = request.status === 'pending'

    return (
        <div className="space-y-4">
            <Button variant="outline" asChild><Link to="/finance/dashboard">Back</Link></Button>
            <Card>
                <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <CardTitle>Payment request #{request.id}</CardTitle>
                        <p className="mt-1 text-sm text-slate-500">{request.user?.name ?? `User #${request.user_id}`}</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <StatusBadge status={request.status} />
                        {canAct && (
                            <>
                                <Button onClick={approve} disabled={actionLoading}>{actionLoading ? 'Working...' : 'Approve'}</Button>
                                <Button variant="destructive" onClick={() => setRejectOpen(true)} disabled={actionLoading}>Reject</Button>
                            </>
                        )}
                    </div>
                </CardHeader>
                <CardContent>
                    {error && <p className="mb-4 text-sm text-red-600">{error}</p>}
                    <dl>
                        <DetailRow label="Employee email" value={request.user?.email ?? '-'} />
                        <DetailRow label="Employee country" value={request.user?.country ?? '-'} />
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
                        <DetailRow label="Approved by" value={request.approved_by ?? '-'} />
                        <DetailRow label="Rejected at" value={dateTime(request.rejected_at)} />
                        <DetailRow label="Rejected by" value={request.rejected_by ?? '-'} />
                        <DetailRow label="Rejection reason" value={request.rejection_reason ?? '-'} />
                    </dl>
                </CardContent>
            </Card>

            <Dialog open={rejectOpen} onOpenChange={setRejectOpen}>
                <DialogContent>
                    <form onSubmit={reject}>
                        <DialogHeader>
                            <DialogTitle>Reject Request</DialogTitle>
                            <DialogDescription>Add an optional reason for the employee.</DialogDescription>
                        </DialogHeader>
                        <div className="mt-4 space-y-2">
                            <Label htmlFor="rejection_reason">Reason (Optional)</Label>
                            <Textarea id="rejection_reason" value={rejectionReason} onChange={(event) => setRejectionReason(event.target.value)} />
                        </div>
                        {error && <p className="mt-3 text-sm text-red-600">{error}</p>}
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setRejectOpen(false)} disabled={actionLoading}>Cancel</Button>
                            <Button variant="destructive" type="submit" disabled={actionLoading}>
                                {actionLoading ? 'Rejecting...' : 'Reject request'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    )
}
