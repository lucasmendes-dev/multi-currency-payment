import { Link } from 'react-router-dom'
import { Button } from '@/components/ui/button'

export default function NotFound() {
    return (
        <div className="flex min-h-screen items-center justify-center bg-slate-50 px-4">
            <div className="max-w-md text-center">
                <p className="text-sm font-medium text-slate-500">404</p>
                <h1 className="mt-2 text-3xl font-semibold text-slate-950">Page not found</h1>
                <p className="mt-3 text-slate-600">The page you are looking for does not exist.</p>
                <Button className="mt-6" asChild><Link to="/login">Go to login</Link></Button>
            </div>
        </div>
    )
}
