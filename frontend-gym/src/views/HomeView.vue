<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import api from '@/utils/api'

// Asset Gambar Latihan yang Tepat
import imgPushUp from '@/assets/Liegestütze gehen immer!.jpg'
import imgDumbbellBench from '@/assets/How to Dumbbell Bench Press_ Form, Benefits, and Variations.jpg'
import imgBarbellBench from '@/assets/download (5).jpg'
import imgLatPulldown from '@/assets/download (6).jpg'
import imgShoulderPress from '@/assets/Overhead Shoulder Press Benefits & Muscles Worked.jpg'
import imgHammerCurl from '@/assets/Do Dumbbell Hammer Curls Like This For Next-Level Biceps.jpg'
import imgSquat from '@/assets/5 stretches to achieve a bigger squat, according to a mobility expert.jpg'

const router = useRouter()
const currentUser = ref(null)

// 1. Pengecekan Admin
const isAdmin = computed(() => {
  if (!currentUser.value) return false
  const u = currentUser.value
  return (
    (u.role && u.role.toLowerCase() === 'admin') ||
    u.is_admin === 1 || u.is_admin === true || u.is_admin === '1' ||
    (u.email && (u.email.toLowerCase().includes('admin') || u.email.toLowerCase() === 'admin@apex.com'))
  )
})

onMounted(() => {
  const token = localStorage.getItem('token')
  if (!token) {
    router.push('/login')
    return
  }
  try {
    const storedUser = localStorage.getItem('user')
    if (storedUser) currentUser.value = JSON.parse(storedUser)
  } catch (e) {}

  fetchExercises()
  fetchTodayLogs()
})

const handleLogout = async () => {
  try { await api.post('/logout') } catch (e) {}
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  router.push('/login')
}

// 2. Mapping Gambar yang Benar & Akurat
const getExerciseImage = (item) => {
  if (!item) return imgBarbellBench
  const name = (item.name || '').toLowerCase()
  const muscle = (item.muscle || item.target_muscle || '').toLowerCase()
  const equip = (item.equipment || '').toLowerCase()

  // 1. Lat Pulldown (Back / Cable) -> download (6).jpg
  if (name.includes('lat') || name.includes('pulldown') || name.includes('pull-down') || muscle.includes('back')) {
    return imgLatPulldown
  }

  // 2. Incline Dumbbell Press / Dumbbell Bench -> How to Dumbbell Bench Press...jpg
  if (name.includes('dumbbell') || equip.includes('dumbbell')) {
    if (name.includes('curl') || muscle.includes('arm')) return imgHammerCurl
    if (name.includes('shoulder') || muscle.includes('shoulder')) return imgShoulderPress
    return imgDumbbellBench
  }

  // 3. Barbell Bench Press (Chest) -> download (5).jpg
  if (name.includes('bench') || muscle.includes('chest')) {
    return imgBarbellBench
  }

  // 4. Push Up (Chest / Bodyweight) -> Liegestütze gehen immer!.jpg
  if (name.includes('push') || name.includes('push-up') || name.includes('pushup')) {
    return imgPushUp
  }

  // 5. Overhead Shoulder Press (Shoulders) -> Overhead Shoulder Press...jpg
  if (name.includes('shoulder') || name.includes('overhead') || muscle.includes('shoulder')) {
    return imgShoulderPress
  }

  // 6. Bicep Curl / Hammer Curl (Arms) -> Do Dumbbell Hammer Curls...jpg
  if (name.includes('curl') || name.includes('bicep') || muscle.includes('arm')) {
    return imgHammerCurl
  }

  // 7. Squat (Legs) -> 5 stretches to achieve a bigger squat...jpg
  if (name.includes('squat') || muscle.includes('leg')) {
    return imgSquat
  }

  return imgBarbellBench
}

// 3. Toast Notifikasi
const toastMsg = ref('')
const toastType = ref('success')
let toastTimer = null
const notify = (msg, type = 'success') => {
  toastMsg.value = msg
  toastType.value = type
  if (toastTimer) clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toastMsg.value = '' }, 3000)
}

// 4. Data Master & Log Harian
const exercisesList = ref([])
const todayLogs = ref([])
const loadingLogs = ref(false)
const selectedDate = ref(new Date().toISOString().split('T')[0])

const fetchExercises = async () => {
  try {
    let res
    try { res = await api.get('/exercises') } catch (e) { res = await api.get('/admin/exercises') }
    const raw = res.data?.data || (Array.isArray(res.data) ? res.data : [])
    exercisesList.value = raw.map(i => ({
      id: i.id,
      name: i.name,
      muscle: i.target_muscle || i.muscle_group || 'General',
      equipment: i.equipment || 'Barbell'
    }))
  } catch (err) {}
}

const fetchTodayLogs = async () => {
  loadingLogs.value = true
  try {
    const res = await api.get(`/workout-logs?date=${selectedDate.value}`)
    todayLogs.value = res.data?.data || (Array.isArray(res.data) ? res.data : [])
  } catch (err) {
  } finally {
    loadingLogs.value = false
  }
}

const groupedLogs = computed(() => {
  const groups = {}
  todayLogs.value.forEach(log => {
    const exId = log.exercise_id || log.exercise?.id || 'other'
    if (!groups[exId]) {
      groups[exId] = {
        id: exId,
        name: log.exercise?.name || 'Latihan',
        muscle: log.exercise?.target_muscle || log.exercise?.muscle_group || 'General',
        sets: []
      }
    }
    groups[exId].sets.push(log)
  })
  return Object.values(groups)
})

const totalVolume = computed(() => {
  return todayLogs.value.reduce((acc, cur) => acc + ((parseFloat(cur.weight) || 0) * (parseInt(cur.reps) || 0)), 0)
})
const totalSets = computed(() => todayLogs.value.length)

const removeLog = async (id) => {
  if (!confirm('Hapus catatan set ini?')) return
  try {
    await api.delete(`/workout-logs/${id}`)
    notify('Catatan set dihapus.')
    fetchTodayLogs()
  } catch (err) {
    notify('Gagal menghapus.', 'error')
  }
}

// 5. Fitur Sesi Latihan Interaktif (Pilih Gerakan -> Atur Set & Reps -> Jalankan Set per Set)
const isWorkoutModalOpen = ref(false)
const modalStep = ref('select') // 'select' | 'target' | 'live' | 'summary'
const selectedExercise = ref(null)

// Pengaturan Target Latihan
const totalSetsTarget = ref(3)  // Berapa set (misal 3 set)
const targetReps = ref(10)       // Berapa repetisi per set (misal 10 reps)
const targetWeight = ref(60)     // Beban kg (misal 60 kg)
const restDuration = ref(60)     // Durasi waktu istirahat antar set (detik)

// State Saat Sesi Berjalan
const currentSet = ref(1)        // Set ke berapa yang sedang berlangsung (1, 2, 3...)
const isResting = ref(false)     // Apakah sedang fase istirahat
const restCountdown = ref(60)    // Hitungan mundur istirahat
const isSaving = ref(false)
let restTimerInterval = null

const openWorkoutModal = () => {
  selectedExercise.value = exercisesList.value[0] || null
  totalSetsTarget.value = 3
  targetReps.value = 10
  targetWeight.value = 60
  restDuration.value = 60
  modalStep.value = 'select'
  isWorkoutModalOpen.value = true
}

const chooseExercise = (ex) => {
  selectedExercise.value = ex
  modalStep.value = 'target'
}

// Mulai sesi latihan dari Set 1
const startLiveWorkout = () => {
  currentSet.value = 1
  isResting.value = false
  modalStep.value = 'live'
}

// Selesaikan set saat ini (tanpa tap repetisi)
const completeCurrentSet = () => {
  if (currentSet.value < totalSetsTarget.value) {
    // Masuk fase istirahat sebelum lanjut ke set berikutnya
    isResting.value = true
    restCountdown.value = restDuration.value

    if (restTimerInterval) clearInterval(restTimerInterval)
    restTimerInterval = setInterval(() => {
      if (restCountdown.value > 0) {
        restCountdown.value--
      } else {
        // Waktu istirahat habis, lanjut ke set berikutnya
        nextSet()
      }
    }, 1000)
  } else {
    // Semua set telah selesai diselesaikan!
    if (restTimerInterval) clearInterval(restTimerInterval)
    isResting.value = false
    modalStep.value = 'summary'
  }
}

// Lanjut ke set berikutnya
const nextSet = () => {
  if (restTimerInterval) clearInterval(restTimerInterval)
  isResting.value = false
  currentSet.value++
}

// Simpan seluruh set latihan ke backend
const saveWorkoutResult = async () => {
  if (!selectedExercise.value) return
  isSaving.value = true
  try {
    const payload = {
      exercise_id: selectedExercise.value.id,
      workout_date: selectedDate.value,
      sets: Array.from({ length: totalSetsTarget.value }, (_, i) => ({
        set_number: i + 1,
        reps: parseInt(targetReps.value) || 10,
        weight: parseFloat(targetWeight.value) || 0,
        duration_seconds: parseInt(restDuration.value) || 60
      }))
    }

    try {
      // Coba kirim batch seluruh set
      await api.post('/workout-logs', payload)
    } catch (e) {
      // Fallback kirim satu per satu
      for (const s of payload.sets) {
        await api.post('/workout-logs', {
          exercise_id: payload.exercise_id,
          workout_date: payload.workout_date,
          set_number: s.set_number,
          reps: s.reps,
          weight: s.weight,
          duration_seconds: s.duration_seconds
        })
      }
    }

    notify(`Selesai! ${totalSetsTarget.value} Set ${selectedExercise.value.name} tersimpan! 🚀`)
    isWorkoutModalOpen.value = false
    fetchTodayLogs()
  } catch (err) {
    notify('Gagal menyimpan hasil latihan.', 'error')
  } finally {
    isSaving.value = false
  }
}

// 6. Rest Timer Cepat
const isRestOpen = ref(false)
const restSec = ref(90)
const restActive = ref(false)
let restTimer = null

const startRest = (s = 90) => {
  restSec.value = s
  restActive.value = true
  isRestOpen.value = true
  if (restTimer) clearInterval(restTimer)
  restTimer = setInterval(() => {
    if (restSec.value > 0) {
      restSec.value--
    } else {
      clearInterval(restTimer)
      restActive.value = false
      notify('Waktu istirahat selesai! Mulai set berikutnya.', 'success')
    }
  }, 1000)
}

onUnmounted(() => {
  if (timer) clearInterval(timer)
  if (restTimer) clearInterval(restTimer)
  if (toastTimer) clearTimeout(toastTimer)
})

const features = [
  { tag: 'INTERACTIVE', title: 'Live Timer & Reps', desc: 'Lakukan latihan dengan panduan gambar, countdown timer, dan tap hitungan repetisi.', stat: '⚡ Active Mode' },
  { tag: 'ANALYTICS', title: 'Progressive Overload', desc: 'Pantau akumulasi volume angkatan harian secara instan dan akurat.', stat: '📈 Auto Volume' },
  { tag: 'TIMER', title: 'Smart Rest Interval', desc: 'Countdown otomatis antar set agar intensitas dan detak jantung terjaga.', stat: '⏱️ Rest Timer' }
]
</script>

<template>
  <div class="home">
    <!-- Toast -->
    <div v-if="toastMsg" class="toast" :class="toastType">
      {{ toastMsg }}
    </div>

    <!-- Nav -->
    <nav class="nav">
      <div class="brand">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="ic"><path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/><path d="m3 10 7-7"/><path d="m14 21 7-7"/></svg>
        <span>APEX<b>STRENGTH</b></span>
      </div>
      <div class="nav-actions">
        <RouterLink v-if="isAdmin" to="/admin" class="btn-admin-link">Console Admin ⚙</RouterLink>
        <span v-if="currentUser?.name" class="user-tag">Lifter: <b>{{ currentUser.name }}</b></span>
        <button @click="handleLogout" class="btn-nav">Logout ⎋</button>
      </div>
    </nav>

    <!-- Hero -->
    <header class="hero">
      <h1 class="hero-title">
        <span>CATAT SESI LATIHAN</span>
        <span class="neon">PANTAU REPS & WAKTU</span>
      </h1>
      <p class="sub">Pilih gerakan latihan bergambar, atur target repetisi dan waktu, lalu catat progres gym Anda secara akurat.</p>
      
      <div class="cta-wrap">
        <button @click="openWorkoutModal" class="btn-primary">⚡ Mulai Sesi Latihan</button>
        <button @click="startRest(90)" class="btn-secondary">⏱️ Rest Timer (90s)</button>
      </div>

      <!-- Stats Banner -->
      <div class="stats-banner">
        <div class="stat-item">
          <small>VOLUME HARI INI</small>
          <strong>{{ totalVolume.toLocaleString('id-ID') }} <span>kg</span></strong>
        </div>
        <div class="stat-sep"></div>
        <div class="stat-item">
          <small>TOTAL SET</small>
          <strong>{{ totalSets }} <span>sets</span></strong>
        </div>
        <div class="stat-sep"></div>
        <div class="stat-item">
          <small>TANGGAL</small>
          <input type="date" v-model="selectedDate" @change="fetchTodayLogs" class="date-input" />
        </div>
      </div>

      <!-- Logbook Card -->
      <div class="log-card">
        <div class="card-head">
          <h4>Riwayat Latihan Hari Ini</h4>
          <button @click="openWorkoutModal" class="btn-mini">+ Tambah</button>
        </div>

        <div v-if="loadingLogs" class="state-box">Memuat catatan...</div>
        <div v-else-if="groupedLogs.length === 0" class="state-box">
          Belum ada catatan latihan untuk tanggal ini. Klik <b>Mulai Sesi Latihan</b> di atas.
        </div>
        <div v-else class="log-list">
          <div v-for="g in groupedLogs" :key="g.id" class="log-group">
            <div class="group-title">
              <img :src="getExerciseImage(g)" class="group-thumb" />
              <div>
                <strong>{{ g.name }}</strong>
                <span class="pill">{{ g.muscle }}</span>
              </div>
            </div>
            <div class="set-list">
              <div v-for="s in g.sets" :key="s.id" class="set-row">
                <span class="set-num">SET {{ s.set_number }}</span>
                <b>{{ s.weight }} kg × {{ s.reps }} reps</b>
                <span v-if="s.duration_seconds > 0" class="set-time">⏱️ {{ s.duration_seconds }}s</span>
                <button @click="removeLog(s.id)" class="btn-del" title="Hapus">✕</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Fitur Utama -->
    <section class="feats-section">
      <div class="feats-header">
        <small>FITUR UTAMA</small>
        <h2>Fokus Pada Progres Fisik Anda</h2>
      </div>
      <div class="feats-grid">
        <div v-for="(f, i) in features" :key="i" class="feat-card">
          <small class="feat-tag">{{ f.tag }}</small>
          <h3>{{ f.title }}</h3>
          <p>{{ f.desc }}</p>
          <span class="feat-badge">{{ f.stat }}</span>
        </div>
      </div>
    </section>

    <!-- Modal Latihan Interaktif -->
    <div v-if="isWorkoutModalOpen" class="modal-overlay" @click.self="isWorkoutModalOpen = false">
      <div class="modal-window">
        <div class="modal-top">
          <h3>{{ modalStep === 'select' ? 'Pilih Gerakan Latihan' : modalStep === 'target' ? 'Atur Target Latihan' : modalStep === 'live' ? 'Latihan Aktif' : 'Selesai!' }}</h3>
          <button @click="isWorkoutModalOpen = false" class="btn-close">✕</button>
        </div>

        <!-- Step 1: Pilih Gambar -->
        <div v-if="modalStep === 'select'" class="gallery-grid">
          <div v-for="ex in exercisesList" :key="ex.id" class="gallery-card" @click="chooseExercise(ex)">
            <img :src="getExerciseImage(ex)" class="gallery-img" />
            <div class="gallery-info">
              <strong>{{ ex.name }}</strong>
              <small>{{ ex.muscle }} • {{ ex.equipment }}</small>
            </div>
          </div>
        </div>

        <!-- Step 2: Atur Jumlah Set, Repetisi & Istirahat -->
        <div v-else-if="modalStep === 'target'" class="target-pane">
          <div class="selected-header">
            <img :src="getExerciseImage(selectedExercise)" class="selected-thumb" />
            <div>
              <strong>{{ selectedExercise?.name }}</strong>
              <small>{{ selectedExercise?.muscle }} • {{ selectedExercise?.equipment }}</small>
            </div>
            <button @click="modalStep = 'select'" class="btn-link">Ganti</button>
          </div>

          <!-- 1. Mau berapa SET -->
          <div class="form-row">
            <label>Jumlah Set Latihan:</label>
            <div class="counter-ctrl">
              <button @click="totalSetsTarget > 1 ? totalSetsTarget-- : null">-</button>
              <span class="val">{{ totalSetsTarget }} set</span>
              <button @click="totalSetsTarget++">+</button>
            </div>
          </div>

          <!-- 2. Repetisi per SET -->
          <div class="form-row">
            <label>Target Repetisi per Set:</label>
            <div class="counter-ctrl">
              <button @click="targetReps > 1 ? targetReps-- : null">-</button>
              <span class="val">{{ targetReps }} reps</span>
              <button @click="targetReps++">+</button>
            </div>
          </div>

          <!-- 3. Beban kg -->
          <div class="form-row">
            <label>Beban Angkatan (kg):</label>
            <input type="number" step="0.5" min="0" v-model="targetWeight" placeholder="0 jika bodyweight" class="num-box" />
          </div>

          <!-- 4. Waktu Istirahat antar Set -->
          <div class="form-row">
            <label>Waktu Istirahat Antar Set:</label>
            <div class="counter-ctrl">
              <button @click="restDuration > 15 ? restDuration -= 15 : null">-15s</button>
              <span class="val">{{ restDuration }} detik</span>
              <button @click="restDuration += 15">+15s</button>
            </div>
          </div>

          <button @click="startLiveWorkout" class="btn-primary full">🔥 MULAI SESI LATIHAN (SET 1)</button>
        </div>

        <!-- Step 3: Layar Latihan Aktif (Maju Set demi Set) -->
        <div v-else-if="modalStep === 'live'" class="live-pane">
          <!-- FASE 1: SEDANG LATIHAN (AKTIF ANGKAT BEBAN) -->
          <div v-if="!isResting" class="active-set-card">
            <div class="set-indicator">
              SET {{ currentSet }} DARI {{ totalSetsTarget }}
            </div>

            <div class="exercise-preview-row">
              <img :src="getExerciseImage(selectedExercise)" class="preview-mini-thumb" />
              <div>
                <h3>{{ selectedExercise?.name }}</h3>
                <span class="sub-target">{{ targetReps }} Reps • {{ targetWeight }} kg</span>
              </div>
            </div>

            <p class="set-instruction">Lakukan <b>{{ targetReps }} repetisi</b> dengan fokus gerakan yang benar. Setelah selesai angkat beban, klik tombol di bawah:</p>

            <button @click="completeCurrentSet" class="btn-complete-set">
              ✓ Selesaikan Set {{ currentSet }}
            </button>
          </div>

          <!-- FASE 2: FASE ISTIRAHAT ANTAR SET -->
          <div v-else class="resting-card">
            <div class="rest-badge">⏱️ FASE ISTIRAHAT ANTAR SET</div>
            <div class="rest-timer-large">{{ restCountdown }}s</div>
            <p class="rest-hint">Tarik nafas dan minum. Bersiap untuk <b>Set {{ currentSet + 1 }} dari {{ totalSetsTarget }}</b>.</p>

            <button @click="nextSet" class="btn-primary">
              Lewati Istirahat & Lanjut Set {{ currentSet + 1 }} ➔
            </button>
          </div>
        </div>

        <!-- Step 4: Ringkasan Selesai Seluruh Set -->
        <div v-else-if="modalStep === 'summary'" class="summary-pane">
          <div class="trophy">🏆</div>
          <h4>Semua Set Selesai! Luar Biasa!</h4>
          <p>Anda berhasil menyelesaikan <b>{{ totalSetsTarget }} Set</b> {{ selectedExercise?.name }} ({{ targetReps }} reps × {{ targetWeight }} kg).</p>
          <div class="summary-btns">
            <button @click="modalStep = 'target'" class="btn-secondary">Ulangi</button>
            <button @click="saveWorkoutResult" :disabled="isSaving" class="btn-primary">
              <span v-if="!isSaving">Simpan {{ totalSetsTarget }} Set ke Logbook 🚀</span>
              <span v-else>Menyimpan...</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Rest Timer Sederhana -->
    <div v-if="isRestOpen" class="modal-overlay" @click.self="isRestOpen = false">
      <div class="modal-window mini">
        <div class="modal-top">
          <h3>Rest Timer ⏱️</h3>
          <button @click="isRestOpen = false" class="btn-close">✕</button>
        </div>
        <div class="timer-number">{{ restSec }}s</div>
        <div class="timer-btns">
          <button @click="startRest(60)" class="btn-secondary">60s</button>
          <button @click="startRest(90)" class="btn-secondary">90s</button>
          <button @click="startRest(120)" class="btn-secondary">120s</button>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
      <span>APEX<b>STRENGTH</b></span> &copy; 2026 Minimalist Gym Workout Tracker.
    </footer>
  </div>
</template>

<style scoped>
.home { background: #07090e; color: #f1f5f9; min-height: 100vh; font-family: 'Plus Jakarta Sans', sans-serif; }
.nav { max-width: 960px; margin: 0 auto; padding: 1rem 1.2rem; display: flex; justify-content: space-between; align-items: center; }
.brand { display: flex; align-items: center; gap: 0.5rem; font-size: 1.15rem; font-weight: 800; color: #fff; }
.brand b, .neon, .feat-tag { color: #ccff00; }
.ic { width: 22px; height: 22px; color: #ccff00; }
.nav-actions { display: flex; align-items: center; gap: 0.6rem; }
.user-tag { font-size: 0.8rem; color: #94a3b8; background: #121722; padding: 0.3rem 0.7rem; border-radius: 999px; border: 1px solid rgba(255,255,255,0.08); }
.user-tag b { color: #ccff00; }
.btn-nav { background: transparent; border: 1px solid rgba(255,255,255,0.15); color: #cbd5e1; padding: 0.35rem 0.8rem; border-radius: 999px; font-size: 0.78rem; font-weight: 700; cursor: pointer; }
.btn-admin-link { background: rgba(56,189,248,0.12); border: 1px solid rgba(56,189,248,0.3); color: #38bdf8; padding: 0.35rem 0.8rem; border-radius: 999px; font-size: 0.78rem; font-weight: 700; text-decoration: none; }

/* Hero */
.hero { max-width: 720px; margin: 0 auto; padding: 2.5rem 1.2rem 2rem; text-align: center; }
.hero-title { font-size: clamp(1.6rem, 3.5vw, 2.3rem); font-weight: 800; line-height: 1.2; display: flex; flex-direction: column; gap: 0.3rem; margin-bottom: 0.8rem; }
.sub { color: #94a3b8; font-size: 0.92rem; line-height: 1.5; margin-bottom: 1.5rem; }
.cta-wrap { display: flex; justify-content: center; gap: 0.8rem; margin-bottom: 2rem; }
.btn-primary { background: #ccff00; color: #07090e; font-weight: 800; border: none; padding: 0.7rem 1.4rem; border-radius: 10px; cursor: pointer; transition: .2s; }
.btn-primary:hover { background: #d9ff33; }
.btn-primary.full { width: 100%; margin-top: 1rem; padding: 0.85rem; font-size: 0.95rem; }
.btn-secondary { background: #131926; border: 1px solid rgba(255,255,255,0.12); color: #cbd5e1; font-weight: 700; padding: 0.7rem 1.2rem; border-radius: 10px; cursor: pointer; }
.btn-secondary:hover { background: #1c2538; color: #fff; }

/* Stats Banner */
.stats-banner { display: flex; justify-content: space-around; align-items: center; background: #0d121c; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 0.85rem 1rem; margin-bottom: 2rem; }
.stat-item small { font-size: 0.65rem; color: #94a3b8; font-weight: 800; letter-spacing: 0.5px; display: block; }
.stat-item strong { font-size: 1.25rem; font-weight: 800; color: #fff; }
.stat-item strong span { font-size: 0.75rem; color: #ccff00; }
.stat-sep { width: 1px; height: 28px; background: rgba(255,255,255,0.08); }
.date-input { background: #141b28; border: 1px solid rgba(204,255,0,0.3); color: #ccff00; border-radius: 6px; padding: 0.2rem 0.4rem; font-size: 0.75rem; font-weight: 700; }

/* Log Card */
.log-card { background: #0e1420; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 1.2rem; text-align: left; }
.card-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.card-head h4 { margin: 0; font-size: 1.05rem; font-weight: 800; color: #fff; }
.btn-mini { background: rgba(204,255,0,0.12); border: 1px solid rgba(204,255,0,0.3); color: #ccff00; padding: 0.25rem 0.6rem; border-radius: 6px; font-size: 0.74rem; font-weight: 800; cursor: pointer; }
.state-box { text-align: center; padding: 1.8rem; color: #94a3b8; font-size: 0.85rem; }
.log-list { display: flex; flex-direction: column; gap: 0.85rem; }
.log-group { background: #131926; border: 1px solid rgba(255,255,255,0.06); border-radius: 8px; padding: 0.75rem; }
.group-title { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.5rem; }
.group-thumb { width: 36px; height: 36px; border-radius: 6px; object-fit: cover; }
.group-title strong { font-size: 0.88rem; color: #fff; }
.pill { font-size: 0.65rem; font-weight: 800; color: #38bdf8; background: rgba(56,189,248,0.1); padding: 0.15rem 0.4rem; border-radius: 4px; margin-left: 0.4rem; }
.set-list { display: flex; flex-direction: column; gap: 0.35rem; }
.set-row { display: flex; justify-content: space-between; align-items: center; background: #182133; padding: 0.45rem 0.65rem; border-radius: 6px; font-size: 0.8rem; }
.set-num { color: #64748b; font-weight: 800; font-size: 0.7rem; }
.set-time { font-size: 0.72rem; color: #38bdf8; background: rgba(56,189,248,0.1); padding: 0.1rem 0.35rem; border-radius: 4px; }
.btn-del { background: transparent; border: none; color: #ef4444; font-weight: 800; cursor: pointer; padding: 0.1rem; }

/* Fitur Utama - Bersih & Rapi */
.feats-section { max-width: 960px; margin: 0 auto; padding: 3rem 1.2rem; }
.feats-header { text-align: center; margin-bottom: 1.8rem; }
.feats-header small { font-size: 0.7rem; font-weight: 800; letter-spacing: 1.5px; color: #ccff00; }
.feats-header h2 { font-size: 1.5rem; font-weight: 800; color: #fff; margin-top: 0.2rem; }
.feats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
.feat-card { background: #0e1420; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 1.3rem; display: flex; flex-direction: column; }
.feat-card h3 { font-size: 1.05rem; font-weight: 800; color: #fff; margin: 0.4rem 0 0.3rem; }
.feat-card p { font-size: 0.82rem; color: #94a3b8; line-height: 1.5; margin-bottom: 0.9rem; flex-grow: 1; }
.feat-badge { font-size: 0.72rem; font-weight: 700; color: #cbd5e1; background: rgba(255,255,255,0.06); padding: 0.25rem 0.5rem; border-radius: 5px; align-self: flex-start; }

/* Modal */
.modal-overlay { position: fixed; inset: 0; background: rgba(4,7,12,0.85); backdrop-filter: blur(6px); display: flex; justify-content: center; align-items: center; z-index: 1000; padding: 1rem; }
.modal-window { background: #0f1523; border: 1px solid rgba(255,255,255,0.12); border-radius: 14px; width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto; padding: 1.4rem; }
.modal-window.mini { max-width: 320px; text-align: center; }
.modal-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem; }
.modal-top h3 { margin: 0; font-size: 1.1rem; font-weight: 800; color: #fff; }
.btn-close { background: transparent; border: none; color: #94a3b8; font-size: 1.1rem; cursor: pointer; }

/* Gallery */
.gallery-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.8rem; }
.gallery-card { background: #141b29; border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; overflow: hidden; cursor: pointer; transition: .2s; }
.gallery-card:hover { border-color: #ccff00; transform: translateY(-2px); }
.gallery-img { width: 100%; height: 105px; object-fit: cover; }
.gallery-info { padding: 0.6rem; }
.gallery-info strong { display: block; font-size: 0.84rem; color: #fff; }
.gallery-info small { font-size: 0.7rem; color: #94a3b8; }

/* Target Pane */
.selected-header { display: flex; align-items: center; gap: 0.75rem; background: #141b29; padding: 0.75rem; border-radius: 10px; margin-bottom: 1.2rem; }
.selected-thumb { width: 46px; height: 46px; border-radius: 6px; object-fit: cover; border: 1px solid #ccff00; }
.btn-link { margin-left: auto; background: transparent; border: 1px solid rgba(255,255,255,0.15); color: #cbd5e1; font-size: 0.72rem; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; }
.form-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.9rem; }
.form-row label { font-size: 0.82rem; font-weight: 700; color: #cbd5e1; }
.counter-ctrl { display: flex; align-items: center; gap: 0.5rem; }
.counter-ctrl button { background: #192133; border: 1px solid rgba(255,255,255,0.1); color: #fff; font-weight: 800; width: 34px; height: 34px; border-radius: 6px; cursor: pointer; }
.counter-ctrl .val { font-size: 0.88rem; font-weight: 800; color: #ccff00; min-width: 65px; text-align: center; }
.num-box { background: #192133; border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 0.45rem 0.6rem; border-radius: 6px; font-weight: 700; width: 140px; text-align: right; }

/* Live Pane (Set Progression) */
.live-pane { text-align: center; padding: 0.5rem 0; }
.active-set-card { background: #141b29; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 1.5rem 1.2rem; }
.set-indicator { display: inline-block; font-size: 0.82rem; font-weight: 800; color: #07090e; background: #ccff00; padding: 0.3rem 0.8rem; border-radius: 999px; margin-bottom: 1.2rem; letter-spacing: 0.5px; }
.exercise-preview-row { display: flex; align-items: center; justify-content: center; gap: 0.8rem; margin-bottom: 1rem; }
.preview-mini-thumb { width: 48px; height: 48px; border-radius: 8px; object-fit: cover; border: 1px solid #ccff00; }
.exercise-preview-row h3 { margin: 0; font-size: 1.2rem; font-weight: 800; color: #fff; }
.sub-target { font-size: 0.88rem; color: #38bdf8; font-weight: 700; display: block; margin-top: 0.2rem; }
.set-instruction { font-size: 0.84rem; color: #94a3b8; line-height: 1.5; margin: 1rem auto 1.5rem; max-width: 380px; }
.btn-complete-set { width: 100%; max-width: 320px; background: #ccff00; color: #07090e; font-size: 1.05rem; font-weight: 900; padding: 1rem; border-radius: 12px; border: none; cursor: pointer; transition: .2s; box-shadow: 0 4px 20px rgba(204,255,0,0.3); }
.btn-complete-set:hover { background: #d9ff33; transform: scale(1.02); }

/* Resting Card */
.resting-card { background: #141b29; border: 1px solid rgba(56,189,248,0.25); border-radius: 12px; padding: 2rem 1.2rem; text-align: center; }
.rest-badge { font-size: 0.75rem; font-weight: 800; color: #38bdf8; letter-spacing: 1px; margin-bottom: 0.5rem; }
.rest-timer-large { font-size: 4rem; font-weight: 900; color: #38bdf8; line-height: 1; margin-bottom: 0.8rem; }
.rest-hint { font-size: 0.88rem; color: #94a3b8; margin-bottom: 1.5rem; }

/* Summary */
.summary-pane { text-align: center; padding: 1rem 0; }
.trophy { font-size: 3rem; margin-bottom: 0.4rem; }
.summary-pane h4 { font-size: 1.3rem; margin: 0 0 0.3rem; color: #fff; }
.summary-pane p { font-size: 0.88rem; color: #94a3b8; margin-bottom: 1.5rem; }
.summary-btns { display: flex; justify-content: center; gap: 0.7rem; }

/* Mini Rest Timer */
.timer-number { font-size: 2.8rem; font-weight: 900; color: #38bdf8; margin: 0.8rem 0; }
.timer-btns { display: flex; justify-content: center; gap: 0.4rem; }

/* Toast */
.toast { position: fixed; bottom: 1.5rem; right: 1.5rem; background: #ccff00; color: #07090e; font-weight: 800; font-size: 0.82rem; padding: 0.65rem 1.2rem; border-radius: 8px; z-index: 2000; box-shadow: 0 8px 20px rgba(0,0,0,0.5); }
.toast.error { background: #ef4444; color: #fff; }
.footer { text-align: center; padding: 2rem 1rem; font-size: 0.78rem; color: #64748b; border-top: 1px solid rgba(255,255,255,0.06); }

@media (max-width: 768px) {
  .feats-grid { grid-template-columns: 1fr; }
  .stats-banner { flex-direction: column; gap: 0.6rem; }
  .stat-sep { display: none; }
  .gallery-grid { grid-template-columns: 1fr; }
}
</style>
