import * as React from 'react'
import { cn } from '@/lib/utils'

type ButtonVariant = 'default' | 'outline' | 'secondary' | 'destructive' | 'ghost'

const variants: Record<ButtonVariant, string> = {
    default: 'bg-slate-950 text-white shadow-sm hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200',
    outline: 'border border-slate-300 bg-white text-slate-900 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:hover:bg-slate-900',
    secondary: 'bg-slate-100 text-slate-900 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700',
    destructive: 'bg-red-600 text-white hover:bg-red-700',
    ghost: 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800',
}

export interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: ButtonVariant
    asChild?: boolean
}

export const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
    ({ className, variant = 'default', type = 'button', asChild = false, children, ...props }, ref) => {
        const classes = cn(
            'inline-flex h-10 items-center justify-center rounded-md px-4 text-sm font-medium transition-colors disabled:pointer-events-none disabled:opacity-50',
            variants[variant],
            className,
        )

        if (asChild && React.isValidElement<{ className?: string }>(children)) {
            return React.cloneElement(children, {
                className: cn(classes, children.props.className),
            })
        }

        return (
            <button ref={ref} type={type} className={classes} {...props}>
                {children}
            </button>
        )
    },
)

Button.displayName = 'Button'
