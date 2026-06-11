import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import ProtectedRoute from '@/components/ProtectedRoute'
import RoleRoute from '@/components/RoleRoute'
import { AuthProvider, useAuth } from '@/contexts/AuthContext'
import { ThemeProvider } from '@/contexts/ThemeContext'
import Login from '@/pages/Login'
import Register from '@/pages/Register'
import NotFound from '@/pages/NotFound'
import EmployeeDashboard from '@/pages/employee/Dashboard'
import NewPaymentRequest from '@/pages/employee/NewPaymentRequest'
import PaymentRequestDetail from '@/pages/employee/PaymentRequestDetail'
import FinanceDashboard from '@/pages/finance/Dashboard'
import FinanceRequestDetail from '@/pages/finance/RequestDetail'

export default function App() {
    return (
        <AuthProvider>
            <ThemeProvider>
                <BrowserRouter>
                    <Routes>
                        <Route path="/" element={<RootRedirect />} />
                        <Route path="/login" element={<Login />} />
                        <Route path="/register" element={<Register />} />

                        <Route element={<ProtectedRoute />}>
                            <Route element={<RoleRoute role="employee" />}>
                                <Route path="/dashboard" element={<EmployeeDashboard />} />
                                <Route path="/payment-requests/new" element={<NewPaymentRequest />} />
                                <Route path="/payment-requests/:id" element={<PaymentRequestDetail />} />
                            </Route>

                            <Route element={<RoleRoute role="finance" />}>
                                <Route path="/finance/dashboard" element={<FinanceDashboard />} />
                                <Route path="/finance/requests/:id" element={<FinanceRequestDetail />} />
                            </Route>
                        </Route>

                        <Route path="*" element={<NotFound />} />
                    </Routes>
                </BrowserRouter>
            </ThemeProvider>
        </AuthProvider>
    )
}

function RootRedirect() {
    const { isAuthenticated, user, isLoading } = useAuth()

    if (isLoading) return <div className="min-h-screen bg-slate-50 dark:bg-slate-950" />
    if (!isAuthenticated || !user) return <Navigate to="/login" replace />
    return <Navigate to={user.role === 'finance' ? '/finance/dashboard' : '/dashboard'} replace />
}
