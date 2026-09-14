<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import gymVideo from '@/assets/From Klickpin.com- Romantic couple habits and clever inspiration for thoughtful sharing for romantic Pinterest boards-pin-id-229402174765702647.mp4'
import api from '@/utils/api'

const router = useRouter()

// Mode Toggle & Arah Animasi Flip
const isRegister = ref(false)
const flipDirection = ref('forward') // 'forward' = flip ke kanan, 'backward' = flip ke kiri

const toggleMode = (toRegister) => {
  errorMessage.value = ''
  successMessage.value = ''
  regErrorMessage.value = ''
  regSuccessMessage.value = ''
  flipDirection.value = toRegister ? 'forward' : 'backward'
  isRegister.value = toRegister
}

// State Form Login
const email = ref('')
const password = ref('')
const rememberMe = ref(false)
const showPassword = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

// State Form Register
const regName = ref('')
const regEmail = ref('')
const regPassword = ref('')
const regPasswordConfirm = ref('')
const showRegPassword = ref(false)
const regLoading = ref(false)
const regErrorMessage = ref('')
const regSuccessMessage = ref('')

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const toggleRegPassword = () => {
  showRegPassword.value = !showRegPassword.value
}

// 1. Eksekusi Login ke Backend API
const handleLogin = async () => {
  errorMessage.value = ''
  successMessage.value = ''

  if (!email.value || !password.value) {
    errorMessage.value = 'Silakan isi email dan kata sandi Anda.'
    return
  }

  isLoading.value = true

  try {
    const response = await api.post('/login', {
      email: email.value.trim(),
      password: password.value,
    })

    // Tangani token dari response backend (format Sanctum standar)
    const token = response.data.token || response.data.access_token || response.data?.data?.token
    const user = response.data.user || response.data?.data?.user || response.data?.data

    if (token) {
      localStorage.setItem('token', token)
    }
    if (user) {
      localStorage.setItem('user', JSON.stringify(user))
    }

    // Berhasil login, arahkan ke Home / Workout Tracker
    router.push('/home')
  } catch (error) {
    console.error('Login Error:', error)
    if (error.response?.data?.message) {
      errorMessage.value = error.response.data.message
    } else if (error.response?.status === 401) {
      errorMessage.value = 'Email atau kata sandi tidak cocok.'
    } else {
      errorMessage.value = 'Gagal terhubung ke backend. Pastikan server Laravel aktif.'
    }
  } finally {
    isLoading.value = false
  }
}

// 2. Eksekusi Register ke Backend API (tanpa merusak struktur database backend)
const handleRegister = async () => {
  regErrorMessage.value = ''
  regSuccessMessage.value = ''

  if (!regName.value || !regEmail.value || !regPassword.value) {
    regErrorMessage.value = 'Semua kolom wajib diisi.'
    return
  }

  if (regPassword.value.length < 6) {
    regErrorMessage.value = 'Kata sandi minimal 6 karakter.'
    return
  }

  if (regPassword.value !== regPasswordConfirm.value) {
    regErrorMessage.value = 'Konfirmasi kata sandi tidak cocok.'
    return
  }

  regLoading.value = true

  try {
    // Struktur data standar Laravel (name, email, password, password_confirmation)
    const payload = {
      name: regName.value.trim(),
      email: regEmail.value.trim(),
      password: regPassword.value,
      password_confirmation: regPasswordConfirm.value,
    }

    const response = await api.post('/register', payload)

    const token = response.data.token || response.data.access_token || response.data?.data?.token
    const user = response.data.user || response.data?.data?.user || response.data?.data

    // Jika backend langsung menyertakan token saat registrasi
    if (token) {
      localStorage.setItem('token', token)
      if (user) {
        localStorage.setItem('user', JSON.stringify(user))
      }
      router.push('/home')
    } else {
      // Jika backend memerlukan login ulang setelah daftar
      email.value = regEmail.value
      successMessage.value = 'Akun berhasil dibuat! Silakan masuk dengan akun baru Anda.'
      toggleMode(false)
    }
  } catch (error) {
    console.error('Register Error:', error)
    if (error.response?.data?.errors) {
      // Tampilkan error validasi Laravel pertama (misal: "The email has already been taken.")
      const firstKey = Object.keys(error.response.data.errors)[0]
      regErrorMessage.value = error.response.data.errors[firstKey][0]
    } else if (error.response?.data?.message) {
      regErrorMessage.value = error.response.data.message
    } else {
      regErrorMessage.value = 'Registrasi gagal. Periksa koneksi jaringan ke server.'
    }
  } finally {
    regLoading.value = false
  }
}
</script>

<template>
  <div class="login-page">
    <!-- SISI KIRI: VIDEO SHOWCASE -->
    <div class="video-col">
      <video class="bg-vid" :src="gymVideo" autoplay loop muted playsinline></video>
      <div class="vid-overlay"></div>

      <div class="vid-top" v-motion :initial="{ opacity: 0, y: -15 }" :enter="{ opacity: 1, y: 0 }">
        <div class="brand">
          <svg class="brand-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/><path d="m3 10 7-7"/><path d="m14 21 7-7"/>
          </svg>
          <span class="brand-name">APEX<span>STRENGTH</span></span>
        </div>
      </div>

      <div class="vid-bottom" v-motion :initial="{ opacity: 0, y: 25 }" :enter="{ opacity: 1, y: 0, transition: { delay: 100 } }">
        <h1 class="quote-title">Disiplin Mengalahkan Motivasi.</h1>
        <p class="quote-desc">
          Catat setiap set dan repetisi latihan Anda. Pantau kenaikan beban (progressive overload) dan capai PR baru secara terukur.
        </p>
        <div class="metrics-row">
          <div class="m-item"><strong>100%</strong><span>Log Akurat</span></div>
          <div class="m-sep"></div>
          <div class="m-item"><strong>Real-Time</strong><span>Rest Timer</span></div>
          <div class="m-sep"></div>
          <div class="m-item"><strong>Auto 1RM</strong><span>Kalkulasi PR</span></div>
        </div>
      </div>
    </div>

    <!-- SISI KANAN: FORM DENGAN 3D FLIP ANIMATION -->
    <div class="form-col">
      <div class="form-wrapper">
        <!-- TAB TOGGLE MASUK / DAFTAR -->
        <div class="tab-switch" v-motion :initial="{ opacity: 0, y: -10 }" :enter="{ opacity: 1, y: 0 }">
          <button
            type="button"
            class="tab-btn"
            :class="{ active: !isRegister }"
            @click="toggleMode(false)"
          >
            MASUK
          </button>
          <button
            type="button"
            class="tab-btn"
            :class="{ active: isRegister }"
            @click="toggleMode(true)"
          >
            DAFTAR BARU
          </button>
        </div>

        <!-- TRANSISI FLIP VUE DENGAN 3D TRANSFORM & MOTION -->
        <Transition :name="flipDirection === 'forward' ? 'flip-forward' : 'flip-backward'" mode="out-in">
          
          <!-- ==================== 1. FORM LOGIN ==================== -->
          <div v-if="!isRegister" key="login" class="form-card">
            <div class="form-top" v-motion :initial="{ opacity: 0, y: 15 }" :enter="{ opacity: 1, y: 0 }">
              <RouterLink to="/" class="mobile-logo">
                <svg class="brand-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                  <path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/><path d="m3 10 7-7"/><path d="m14 21 7-7"/>
                </svg>
                <span>APEX<b>STRENGTH</b></span>
              </RouterLink>
              <span class="portal-pill">MEMBER PORTAL</span>
              <h2>Selamat Datang Kembali</h2>
              <p>Masukkan akun Anda untuk membuka sesi latihan dan logbook hari ini.</p>
            </div>

            <!-- Pesan Sukses -->
            <div v-if="successMessage" class="success-msg">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
              <span>{{ successMessage }}</span>
            </div>

            <!-- Pesan Error -->
            <div v-if="errorMessage" class="error-msg">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              <span>{{ errorMessage }}</span>
            </div>

            <form @submit.prevent="handleLogin" class="form-body">
              <div class="input-grp" v-motion :initial="{ opacity: 0, y: 10 }" :enter="{ opacity: 1, y: 0, transition: { delay: 60 } }">
                <label for="login-email">Email</label>
                <div class="input-box">
                  <svg class="field-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                  <input id="login-email" v-model="email" type="email" placeholder="nama@email.com" required autocomplete="username" />
                </div>
              </div>

              <div class="input-grp" v-motion :initial="{ opacity: 0, y: 10 }" :enter="{ opacity: 1, y: 0, transition: { delay: 120 } }">
                <label for="login-password">Kata Sandi</label>
                <div class="input-box">
                  <svg class="field-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                  <input id="login-password" v-model="password" :type="showPassword ? 'text' : 'password'" placeholder="••••••••" required autocomplete="current-password" />
                  <button type="button" class="btn-toggle" @click="togglePassword" tabindex="-1">
                    <svg v-if="!showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9.88 9.88 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                  </button>
                </div>
              </div>

              <div class="row-opts" v-motion :initial="{ opacity: 0 }" :enter="{ opacity: 1, transition: { delay: 180 } }">
                <label class="check-wrap">
                  <input type="checkbox" v-model="rememberMe" />
                  <span>Ingat saya di perangkat ini</span>
                </label>
              </div>

              <button type="submit" class="btn-submit" :disabled="isLoading" v-motion :initial="{ opacity: 0, y: 10 }" :enter="{ opacity: 1, y: 0, transition: { delay: 220 } }">
                <span v-if="!isLoading" class="btn-txt">
                  MASUK KE TRACKER
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </span>
                <span v-else class="btn-txt"><span class="spin"></span> Memverifikasi akun...</span>
              </button>
            </form>

            <div class="switch-bottom">
              Belum punya akun tracker?
              <button type="button" class="link-switch" @click="toggleMode(true)">
                Daftar Member Baru
              </button>
            </div>
          </div>

          <!-- ==================== 2. FORM REGISTER ==================== -->
          <div v-else key="register" class="form-card">
            <div class="form-top" v-motion :initial="{ opacity: 0, y: 15 }" :enter="{ opacity: 1, y: 0 }">
              <RouterLink to="/" class="mobile-logo">
                <svg class="brand-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                  <path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/><path d="m3 10 7-7"/><path d="m14 21 7-7"/>
                </svg>
                <span>APEX<b>STRENGTH</b></span>
              </RouterLink>
              <span class="portal-pill new-pill">REGISTRASI MEMBER</span>
              <h2>Mulai Catat Kemajuan</h2>
              <p>Buat akun baru untuk mulai mencatat repetisi, beban angkatan, dan PR Anda.</p>
            </div>

            <!-- Pesan Error Register -->
            <div v-if="regErrorMessage" class="error-msg">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              <span>{{ regErrorMessage }}</span>
            </div>

            <form @submit.prevent="handleRegister" class="form-body">
              <div class="input-grp" v-motion :initial="{ opacity: 0, y: 10 }" :enter="{ opacity: 1, y: 0, transition: { delay: 60 } }">
                <label for="reg-name">Nama Lengkap</label>
                <div class="input-box">
                  <svg class="field-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  <input id="reg-name" v-model="regName" type="text" placeholder="Contoh: Dera Azhar" required autocomplete="name" />
                </div>
              </div>

              <div class="input-grp" v-motion :initial="{ opacity: 0, y: 10 }" :enter="{ opacity: 1, y: 0, transition: { delay: 110 } }">
                <label for="reg-email">Alamat Email</label>
                <div class="input-box">
                  <svg class="field-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                  <input id="reg-email" v-model="regEmail" type="email" placeholder="nama@email.com" required autocomplete="email" />
                </div>
              </div>

              <div class="input-grp" v-motion :initial="{ opacity: 0, y: 10 }" :enter="{ opacity: 1, y: 0, transition: { delay: 160 } }">
                <label for="reg-pass">Kata Sandi (Min. 6 Karakter)</label>
                <div class="input-box">
                  <svg class="field-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                  <input id="reg-pass" v-model="regPassword" :type="showRegPassword ? 'text' : 'password'" placeholder="Minimal 6 karakter" required autocomplete="new-password" />
                  <button type="button" class="btn-toggle" @click="toggleRegPassword" tabindex="-1">
                    <svg v-if="!showRegPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9.88 9.88 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                  </button>
                </div>
              </div>

              <div class="input-grp" v-motion :initial="{ opacity: 0, y: 10 }" :enter="{ opacity: 1, y: 0, transition: { delay: 210 } }">
                <label for="reg-pass-confirm">Ulangi Kata Sandi</label>
                <div class="input-box">
                  <svg class="field-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                  <input id="reg-pass-confirm" v-model="regPasswordConfirm" :type="showRegPassword ? 'text' : 'password'" placeholder="Konfirmasi kata sandi" required autocomplete="new-password" />
                </div>
              </div>

              <button type="submit" class="btn-submit btn-reg" :disabled="regLoading" v-motion :initial="{ opacity: 0, y: 10 }" :enter="{ opacity: 1, y: 0, transition: { delay: 260 } }">
                <span v-if="!regLoading" class="btn-txt">
                  BUAT AKUN & GABUNG
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </span>
                <span v-else class="btn-txt"><span class="spin"></span> Mendaftarkan akun...</span>
              </button>
            </form>

            <div class="switch-bottom">
              Sudah terdaftar sebagai member?
              <button type="button" class="link-switch" @click="toggleMode(false)">
                Masuk ke Akun Anda
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </div>
  </div>
</template>

<style scoped>
.login-page {
  display: flex;
  min-height: 100vh;
  width: 100vw;
  background: #07090e;
  color: #f1f5f9;
  font-family: 'Plus Jakarta Sans', sans-serif;
  overflow-x: hidden;
}

/* KIRI: VIDEO */
.video-col {
  position: relative;
  flex: 1.15;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 3rem;
  overflow: hidden;
  background: #000;
}
.bg-vid {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  z-index: 1;
  filter: brightness(0.9) contrast(1.05);
}
.vid-overlay {
  position: absolute;
  inset: 0;
  z-index: 2;
  background: linear-gradient(180deg, rgba(7,9,14,0.7) 0%, rgba(7,9,14,0.3) 45%, rgba(7,9,14,0.95) 95%);
}
.vid-top, .vid-bottom { position: relative; z-index: 3; }
.vid-top { display: flex; align-items: center; justify-content: space-between; }
.brand, .mobile-logo { display: flex; align-items: center; gap: 0.6rem; }
.brand-ic { width: 26px; height: 26px; color: #ccff00; filter: drop-shadow(0 0 6px rgba(204,255,0,0.5)); }
.brand-name { font-size: 1.3rem; font-weight: 800; letter-spacing: -0.02em; color: #fff; }
.brand-name span { color: #ccff00; }

.vid-bottom { max-width: 520px; }
.quote-title { font-size: 2.3rem; font-weight: 800; line-height: 1.15; color: #fff; margin-bottom: 0.8rem; letter-spacing: -0.02em; }
.quote-desc { font-size: 0.92rem; line-height: 1.6; color: #cbd5e1; margin-bottom: 1.6rem; }
.metrics-row {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  background: rgba(15,23,42,0.6);
  border: 1px solid rgba(255,255,255,0.1);
  padding: 0.8rem 1.3rem;
  border-radius: 12px;
  backdrop-filter: blur(8px);
}
.m-item strong { font-size: 1.1rem; font-weight: 800; color: #fff; display: block; }
.m-item span { font-size: 0.7rem; color: #94a3b8; }
.m-sep { width: 1px; height: 24px; background: rgba(255,255,255,0.15); }

/* KANAN: FORM PERSPECTIVE WRAPPER */
.form-col {
  flex: 0.95;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2.5rem 2rem;
  background: radial-gradient(circle at 90% 10%, rgba(204,255,0,0.04) 0%, #07090e 70%);
  overflow-y: auto;
  perspective: 1200px;
}
.form-wrapper {
  width: 100%;
  max-width: 420px;
}

/* TAB TOGGLE */
.tab-switch {
  display: flex;
  background: #111622;
  padding: 4px;
  border-radius: 10px;
  border: 1px solid rgba(255,255,255,0.08);
  margin-bottom: 1.5rem;
}
.tab-btn {
  flex: 1;
  padding: 0.6rem 0;
  border: none;
  background: transparent;
  color: #94a3b8;
  font-size: 0.8rem;
  font-weight: 700;
  border-radius: 7px;
  cursor: pointer;
  transition: all 0.25s ease;
}
.tab-btn.active {
  background: #1b2333;
  color: #ccff00;
  box-shadow: 0 2px 8px rgba(0,0,0,0.3);
}
.tab-btn:hover:not(.active) {
  color: #fff;
}

/* 3D FLIP ANIMATIONS */
.flip-forward-enter-active,
.flip-forward-leave-active,
.flip-backward-enter-active,
.flip-backward-leave-active {
  transition: transform 0.45s cubic-bezier(0.34, 1.35, 0.64, 1), opacity 0.3s ease;
  transform-style: preserve-3d;
  backface-visibility: hidden;
}
.flip-forward-enter-from {
  opacity: 0;
  transform: rotateY(90deg) scale(0.92);
}
.flip-forward-leave-to {
  opacity: 0;
  transform: rotateY(-90deg) scale(0.92);
}
.flip-backward-enter-from {
  opacity: 0;
  transform: rotateY(-90deg) scale(0.92);
}
.flip-backward-leave-to {
  opacity: 0;
  transform: rotateY(90deg) scale(0.92);
}

.form-card {
  width: 100%;
}
.mobile-logo { display: none; margin-bottom: 1.2rem; font-size: 1.25rem; font-weight: 800; color: #fff; }
.mobile-logo b { color: #ccff00; }
.portal-pill { font-size: 0.66rem; font-weight: 800; letter-spacing: 1.5px; color: #ccff00; }
.portal-pill.new-pill { color: #38bdf8; }
.form-top h2 { font-size: 1.6rem; font-weight: 700; letter-spacing: -0.02em; color: #fff; margin: 0.35rem 0; }
.form-top p { font-size: 0.85rem; color: #94a3b8; line-height: 1.45; margin-bottom: 1.2rem; }

/* NOTIFIKASI */
.error-msg {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  background: rgba(239,68,68,0.12);
  border: 1px solid rgba(239,68,68,0.35);
  color: #f87171;
  padding: 0.65rem 0.85rem;
  border-radius: 8px;
  font-size: 0.82rem;
  margin-bottom: 1rem;
}
.error-msg svg { width: 16px; height: 16px; flex-shrink: 0; }

.success-msg {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  background: rgba(34,197,94,0.12);
  border: 1px solid rgba(34,197,94,0.35);
  color: #4ade80;
  padding: 0.65rem 0.85rem;
  border-radius: 8px;
  font-size: 0.82rem;
  margin-bottom: 1rem;
}
.success-msg svg { width: 16px; height: 16px; flex-shrink: 0; }

.form-body { display: flex; flex-direction: column; gap: 0.85rem; }
.input-grp { display: flex; flex-direction: column; gap: 0.3rem; }
.input-grp label { font-size: 0.76rem; font-weight: 600; color: #cbd5e1; }
.input-box { position: relative; display: flex; align-items: center; }
.field-ic { position: absolute; left: 0.85rem; width: 17px; height: 17px; color: #64748b; pointer-events: none; }
.input-box input {
  width: 100%;
  padding: 0.72rem 2.4rem 0.72rem 2.4rem;
  background: #121722;
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 10px;
  color: #fff;
  font-size: 0.88rem;
  outline: none;
  transition: all 0.2s;
}
.input-box input:focus { border-color: #ccff00; background: #151b27; box-shadow: 0 0 0 3px rgba(204,255,0,0.15); }
.btn-toggle { position: absolute; right: 0.75rem; background: none; border: none; color: #64748b; cursor: pointer; display: flex; padding: 2px; }
.btn-toggle:hover { color: #fff; }
.btn-toggle svg { width: 17px; height: 17px; }

.row-opts { display: flex; align-items: center; font-size: 0.8rem; }
.check-wrap { display: flex; align-items: center; gap: 0.5rem; color: #94a3b8; cursor: pointer; }
.check-wrap input { accent-color: #ccff00; width: 15px; height: 15px; cursor: pointer; }

.btn-submit {
  width: 100%;
  padding: 0.82rem;
  background: #ccff00;
  color: #07090e;
  border: none;
  border-radius: 10px;
  font-size: 0.86rem;
  font-weight: 800;
  letter-spacing: 0.5px;
  cursor: pointer;
  box-shadow: 0 4px 16px rgba(204,255,0,0.22);
  transition: all 0.2s;
  margin-top: 0.3rem;
}
.btn-submit:hover:not(:disabled) { background: #d9ff33; transform: translateY(-1px); }
.btn-reg { background: #38bdf8; box-shadow: 0 4px 16px rgba(56,189,248,0.22); }
.btn-reg:hover:not(:disabled) { background: #7dd3fc; }

.btn-txt { display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-weight: 700; font-size: 0.86rem; letter-spacing: 0.02em; }
.btn-txt svg { width: 16px; height: 16px; }
.spin { width: 15px; height: 15px; border: 2px solid rgba(0,0,0,0.2); border-top-color: #000; border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* LINK SWITCH DI BAWAH */
.switch-bottom {
  margin-top: 1.4rem;
  text-align: center;
  font-size: 0.82rem;
  color: #94a3b8;
}
.link-switch {
  background: none;
  border: none;
  color: #ccff00;
  font-weight: 700;
  cursor: pointer;
  padding: 0 0.25rem;
  text-decoration: underline;
  font-family: inherit;
  font-size: inherit;
}
.link-switch:hover { color: #fff; }

@media (max-width: 960px) {
  .login-page { flex-direction: column; }
  .video-col { flex: none; height: 260px; padding: 1.5rem; }
  .quote-title { font-size: 1.5rem; }
  .quote-desc, .metrics-row { display: none; }
  .form-col { padding: 2rem 1.5rem; }
  .mobile-logo { display: flex; }
}
</style>
