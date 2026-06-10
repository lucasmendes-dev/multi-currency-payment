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
    const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content
    if (token) config.headers['X-CSRF-TOKEN'] = token
    return config
})

export default api
