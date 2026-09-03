<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import gymVideo from '@/assets/From Klickpin.com- Romantic couple habits and clever inspiration for thoughtful sharing for romantic Pinterest boards-pin-id-229402174765702647.mp4'

const router = useRouter()

const email = ref('')
const password = ref('')
const rememberMe = ref(false)
const showPassword = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const handleLogin = () => {
  errorMessage.value = ''
  if (!email.value || !password.value) {
    errorMessage.value = 'Silakan isi email dan kata sandi Anda.'
    return
  }
  isLoading.value = true
  setTimeout(() => {
    isLoading.value = false
    // Arahkan langsung ke halaman home / workout tracker
    router.push('/home')
  }, 600)
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

    <!-- SISI KANAN: FORM LOGIN -->
    <div class="form-col">
      <div class="form-card">
        <div class="form-top" v-motion :initial="{ opacity: 0, y: 15 }" :enter="{ opacity: 1, y: 0 }">
          <RouterLink to="/" class="mobile-logo">
            <svg class="brand-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
              <path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/><path d="m3 10 7-7"/><path d="m14 21 7-7"/>
            </svg>
            <span>APEX<b>STRENGTH</b></span>
          </RouterLink>
          <span class="portal-pill">TRACKER PORTAL</span>
          <h2>Selamat Datang Kembali</h2>
          <p>Masukkan akun Anda untuk membuka sesi latihan dan logbook hari ini.</p>
        </div>

        <div v-if="errorMessage" class="error-msg">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span>{{ errorMessage }}</span>
        </div>

        <form @submit.prevent="handleLogin" class="form-body">
          <div class="input-grp" v-motion :initial="{ opacity: 0, y: 10 }" :enter="{ opacity: 1, y: 0, transition: { delay: 100 } }">
            <label for="email">Email / Username</label>
            <div class="input-box">
              <svg class="field-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
              <input id="email" v-model="email" type="email" placeholder="nama@email.com" required autocomplete="username" />
            </div>
          </div>

          <div class="input-grp" v-motion :initial="{ opacity: 0, y: 10 }" :enter="{ opacity: 1, y: 0, transition: { delay: 150 } }">
            <label for="password">Kata Sandi</label>
            <div class="input-box">
              <svg class="field-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <input id="password" v-model="password" :type="showPassword ? 'text' : 'password'" placeholder="••••••••" required autocomplete="current-password" />
              <button type="button" class="btn-toggle" @click="togglePassword" tabindex="-1">
                <svg v-if="!showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9.88 9.88 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
              </button>
            </div>
          </div>

          <div class="row-opts" v-motion :initial="{ opacity: 0 }" :enter="{ opacity: 1, transition: { delay: 200 } }">
            <label class="check-wrap">
              <input type="checkbox" v-model="rememberMe" />
              <span>Ingat saya di perangkat ini</span>
            </label>
          </div>

          <button type="submit" class="btn-submit" :disabled="isLoading" v-motion :initial="{ opacity: 0, y: 10 }" :enter="{ opacity: 1, y: 0, transition: { delay: 250 } }">
            <span v-if="!isLoading" class="btn-txt">
              MASUK KE TRACKER
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </span>
            <span v-else class="btn-txt"><span class="spin"></span> Memverifikasi...</span>
          </button>
        </form>
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
.brand-name { font-family: 'Syne', sans-serif; font-size: 1.4rem; font-weight: 800; letter-spacing: 1px; color: #fff; }
.brand-name span { color: #ccff00; }
.back-link {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 1.5px;
  color: rgba(255,255,255,0.7);
  background: rgba(255,255,255,0.08);
  padding: 0.35rem 0.8rem;
  border-radius: 999px;
  border: 1px solid rgba(255,255,255,0.12);
  backdrop-filter: blur(6px);
}
.back-link:hover { color: #ccff00; border-color: rgba(204,255,0,0.3); }

.vid-bottom { max-width: 520px; }
.quote-title { font-family: 'Syne', sans-serif; font-size: 2.5rem; font-weight: 800; line-height: 1.15; color: #fff; margin-bottom: 0.8rem; text-transform: uppercase; }
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

/* KANAN: FORM */
.form-col {
  flex: 0.95;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background: radial-gradient(circle at 90% 10%, rgba(204,255,0,0.04) 0%, #07090e 70%);
  overflow-y: auto;
}
.form-card { width: 100%; max-width: 400px; }
.mobile-logo { display: none; margin-bottom: 1.2rem; font-family: 'Syne', sans-serif; font-size: 1.25rem; font-weight: 800; color: #fff; }
.mobile-logo b { color: #ccff00; }
.portal-pill { font-size: 0.66rem; font-weight: 800; letter-spacing: 2px; color: #ccff00; }
.form-top h2 { font-size: 1.8rem; font-weight: 800; letter-spacing: -0.5px; color: #fff; margin: 0.35rem 0; }
.form-top p { font-size: 0.86rem; color: #94a3b8; line-height: 1.45; margin-bottom: 1.4rem; }

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

.form-body { display: flex; flex-direction: column; gap: 0.9rem; }
.input-grp { display: flex; flex-direction: column; gap: 0.35rem; }
.input-grp label { font-size: 0.78rem; font-weight: 600; color: #cbd5e1; }
.input-box { position: relative; display: flex; align-items: center; }
.field-ic { position: absolute; left: 0.85rem; width: 17px; height: 17px; color: #64748b; pointer-events: none; }
.input-box input {
  width: 100%;
  padding: 0.75rem 2.5rem 0.75rem 2.5rem;
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
  padding: 0.85rem;
  background: #ccff00;
  color: #07090e;
  border: none;
  border-radius: 10px;
  font-size: 0.88rem;
  font-weight: 800;
  cursor: pointer;
  box-shadow: 0 4px 16px rgba(204,255,0,0.22);
  transition: all 0.2s;
}
.btn-submit:hover:not(:disabled) { background: #d9ff33; transform: translateY(-1px); }
.btn-txt { display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
.btn-txt svg { width: 16px; height: 16px; }
.spin { width: 15px; height: 15px; border: 2px solid rgba(0,0,0,0.2); border-top-color: #000; border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 960px) {
  .login-page { flex-direction: column; }
  .video-col { flex: none; height: 260px; padding: 1.5rem; }
  .quote-title { font-size: 1.5rem; }
  .quote-desc, .metrics-row { display: none; }
  .form-col { padding: 2rem 1.5rem; }
  .mobile-logo { display: flex; }
}
</style>
