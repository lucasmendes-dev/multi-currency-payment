import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import api from '@/lib/axios'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Select } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import StatusBadge from '@/components/StatusBadge'
import Pagination from '@/components/Pagination'
import { apiError, dateTime, money, statusOptions } from '@/lib/format'
import type { ApiListResponse, PaymentRequest, PaymentStatus } from '@/types'

export default function EmployeeDashboard() {
    const [requests, setRequests] = useState<PaymentRequest[]>([])
    const [status, setStatus] = useState<'all' | PaymentStatus>('all')
    const [page, setPage] = useState(1)
    const [meta, setMeta] = useState<ApiListResponse<PaymentRequest>['meta']>()
    const [loading, setLoading] = useState(true)
    const [error, setError] = useState('')

    function handleStatusChange(nextStatus: typeof status) {
        setStatus(nextStatus)
        setPage(1)
    }

    useEffect(() => {
        let ignore = false

        async function loadRequests() {
            setLoading(true)
            setError('')

            try {
                const params = status === 'all' ? { page } : { status, page }
                const { data } = await api.get<ApiListResponse<PaymentRequest>>('/payments', { params })
                if (!ignore) {
                    setRequests(data.data)
                    setMeta(data.meta)
                }
            } catch (exception) {
                if (!ignore) setError(apiError(exception))
            } finally {
                if (!ignore) setLoading(false)
            }
        }

        loadRequests()
        return () => {
            ignore = true
        }
    }, [status, page])

    return (
        <div className="space-y-6">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-3xl font-semibold text-slate-950 dark:text-white">Payment requests</h1>
                    <p className="text-sm text-slate-500 dark:text-slate-400">Track your submitted currency conversion requests.</p>
                </div>
                <Button asChild>
                    <Link to="/payment-requests/new">New request</Link>
                </Button>
            </div>

            <Card>
                <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <CardTitle>Your requests</CardTitle>
                        <CardDescription>Filter by status to narrow the list.</CardDescription>
                    </div>
                    <Select value={status} onChange={(event) => handleStatusChange(event.target.value as typeof status)} className="sm:w-48">
                        {statusOptions.map((option) => (
                            <option key={option} value={option}>
                                {option === 'all' ? 'All statuses' : option}
                            </option>
                        ))}
                    </Select>
                </CardHeader>
                <CardContent>
                    {error && <p className="mb-4 text-sm text-red-600">{error}</p>}
                    {loading ? (
                        <p className="text-sm text-slate-500">Loading requests...</p>
                    ) : requests.length === 0 ? (
                        <p className="text-sm text-slate-500">No payment requests found.</p>
                    ) : (
                        <div className="space-y-4">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>ID</TableHead>
                                        <TableHead>Amount</TableHead>
                                        <TableHead>EUR amount</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Created</TableHead>
                                        <TableHead />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {requests.map((request) => (
                                        <TableRow key={request.id}>
                                            <TableCell className="font-medium">#{request.id}</TableCell>
                                            <TableCell>{money(request.local_amount, request.local_currency)}</TableCell>
                                            <TableCell>{money(request.converted_amount, request.target_currency)}</TableCell>
                                            <TableCell><StatusBadge status={request.status} /></TableCell>
                                            <TableCell>{dateTime(request.created_at)}</TableCell>
                                            <TableCell className="text-right">
                                                <Link className="font-medium text-slate-950 underline dark:text-white" to={`/payment-requests/${request.id}`}>
                                                    View
                                                </Link>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                            {meta && (
                                <Pagination
                                    currentPage={meta.current_page}
                                    lastPage={meta.last_page}
                                    total={meta.total}
                                    perPage={meta.per_page}
                                    onPageChange={setPage}
                                    disabled={loading}
                                />
                            )}
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    )
}
