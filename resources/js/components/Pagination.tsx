import { Button } from '@/components/ui/button'

interface PaginationProps {
    currentPage: number
    lastPage: number
    total: number
    perPage: number
    onPageChange: (page: number) => void
    disabled?: boolean
}

export default function Pagination({ currentPage, lastPage, total, perPage, onPageChange, disabled }: PaginationProps) {
    if (lastPage <= 1) return null

    const start = (currentPage - 1) * perPage + 1
    const end = Math.min(currentPage * perPage, total)

    return (
        <div className="flex flex-col gap-3 border-t border-slate-200 pt-4 text-sm text-slate-600 dark:border-slate-800 dark:text-slate-400 sm:flex-row sm:items-center sm:justify-between">
            <span>
                Showing {start}-{end} of {total}
            </span>
            <div className="flex items-center gap-2">
                <Button variant="outline" disabled={disabled || currentPage <= 1} onClick={() => onPageChange(currentPage - 1)}>
                    Previous
                </Button>
                <span className="min-w-24 text-center font-medium text-slate-700 dark:text-slate-300">
                    Page {currentPage} of {lastPage}
                </span>
                <Button variant="outline" disabled={disabled || currentPage >= lastPage} onClick={() => onPageChange(currentPage + 1)}>
                    Next
                </Button>
            </div>
        </div>
    )
}
