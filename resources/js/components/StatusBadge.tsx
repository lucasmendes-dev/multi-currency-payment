import { Badge } from '@/components/ui/badge'
import type { PaymentStatus } from '@/types'

const classes: Record<PaymentStatus, string> = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-500/15 dark:text-yellow-300',
    approved: 'bg-green-100 text-green-800 dark:bg-green-500/15 dark:text-green-300',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-300',
    expired: 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
}

export default function StatusBadge({ status }: { status: PaymentStatus }) {
    return <Badge className={classes[status]}>{status}</Badge>
}
