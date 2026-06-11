import axios, { InternalAxiosRequestConfig } from 'axios'

const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
})

api.interceptors.request.use((config: InternalAxiosRequestConfig) => {
    const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content
    const authToken = localStorage.getItem('auth_token')

    if (csrfToken) config.headers['X-CSRF-TOKEN'] = csrfToken
    if (authToken) config.headers.Authorization = `Bearer ${authToken}`

    return config
})

export default api
