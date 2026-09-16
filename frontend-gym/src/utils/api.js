import axios from 'axios'

/**
 * Axios instance terkonfigurasi sebagai jembatan HTTP ke backend API.
 * Base URL dapat diatur lewat environment variable VITE_API_BASE_URL,
 * atau default ke http://127.0.0.1:8000/api (standar Laravel / local server).
 */
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  timeout: 15000,
  headers: {
    'Accept': 'application/json',
  },
})

// Request Interceptor: Otomatis menyisipkan Authorization Bearer token jika tersedia di localStorage
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    // Set Content-Type JSON hanya jika request mengirimkan data (POST / PUT / PATCH)
    if (config.data && !config.headers['Content-Type']) {
      config.headers['Content-Type'] = 'application/json'
    }
    console.log(`📡 [API Request] ${config.method?.toUpperCase()} ${config.baseURL || ''}${config.url}`)
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response Interceptor: Menangani response global dan error autentikasi (401)
api.interceptors.response.use(
  (response) => {
    console.log(`✅ [API Response] ${response.config.method?.toUpperCase()} ${response.config.url} - Status: ${response.status}`)
    return response
  },
  (error) => {
    console.error(`❌ [API Error] ${error.config?.method?.toUpperCase()} ${error.config?.url}:`, error.message, error.response?.data || '')
    if (error.response && error.response.status === 401) {
      // Bersihkan session jika token expired atau invalid
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    }
    return Promise.reject(error)
  }
)

export default api
