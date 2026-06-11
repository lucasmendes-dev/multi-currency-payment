import * as React from 'react'
import { cn } from '@/lib/utils'

interface DialogProps {
    open: boolean
    onOpenChange: (open: boolean) => void
    children: React.ReactNode
}

export function Dialog({ open, onOpenChange, children }: DialogProps) {
    if (!open) return null

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" onMouseDown={() => onOpenChange(false)}>
            <div onMouseDown={(event) => event.stopPropagation()}>{children}</div>
        </div>
    )
}

export function DialogContent({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
    return <div className={cn('w-full max-w-lg rounded-lg bg-white p-6 shadow-xl dark:bg-slate-900', className)} {...props} />
}

export function DialogHeader({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
    return <div className={cn('space-y-2', className)} {...props} />
}

export function DialogTitle({ className, ...props }: React.HTMLAttributes<HTMLHeadingElement>) {
    return <h2 className={cn('text-lg font-semibold text-slate-950 dark:text-white', className)} {...props} />
}

export function DialogDescription({ className, ...props }: React.HTMLAttributes<HTMLParagraphElement>) {
    return <p className={cn('text-sm text-slate-500 dark:text-slate-400', className)} {...props} />
}

export function DialogFooter({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
    return <div className={cn('mt-6 flex justify-end gap-2', className)} {...props} />
}
