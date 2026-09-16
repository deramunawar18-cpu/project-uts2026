import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import LoginView from '../views/LoginView.vue'
import AdminView from '../views/AdminView.vue'

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView
  },
  {
    path: '/home',
    name: 'home',
    component: HomeView
  },
  {
    path: '/admin',
    name: 'admin',
    component: AdminView
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

// Navigation Guard: Melindungi rute dari akses yang tidak sah
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  let user = null
  try {
    const raw = localStorage.getItem('user')
    user = raw ? JSON.parse(raw) : null
  } catch (e) {
    user = null
  }

  const isAdmin = user && (
    (user.role && user.role.toLowerCase() === 'admin') ||
    user.is_admin === 1 ||
    user.is_admin === true ||
    user.is_admin === '1' ||
    (user.email && (user.email.toLowerCase().includes('admin') || user.email.toLowerCase() === 'admin@apex.com'))
  )

  // 1. Jika belum login dan mengakses halaman yang butuh login
  if ((to.path === '/home' || to.path === '/admin') && !token) {
    return next('/login')
  }

  // 2. KHUSUS /admin: Hanya akun Admin yang boleh masuk
  if (to.path === '/admin') {
    if (!isAdmin) {
      alert('Akses Ditolak: Halaman Console Admin hanya dapat diakses oleh Admin.')
      return next('/home')
    }
  }

  // 3. Jika sudah login dan mencoba ke halaman login lagi
  if (to.path === '/login' && token) {
    return next('/home')
  }

  next()
})

export default router
