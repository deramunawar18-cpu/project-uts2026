<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import api from '@/utils/api'

// Asset Gambar Latihan
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

// 2. Mapping Gambar Visual Latihan
const getExerciseImage = (item) => {
  if (!item) return imgBarbellBench
  const name = (item.name || '').toLowerCase()
  const muscle = (item.muscle || item.target_muscle || '').toLowerCase()
  const equip = (item.equipment || '').toLowerCase()

  if (name.includes('lat') || name.includes('pulldown') || name.includes('pull-down') || muscle.includes('back')) {
    return imgLatPulldown
  }
  if (name.includes('dumbbell') || equip.includes('dumbbell')) {
    if (name.includes('curl') || muscle.includes('arm')) return imgHammerCurl
    if (name.includes('shoulder') || muscle.includes('shoulder')) return imgShoulderPress
    return imgDumbbellBench
  }
  if (name.includes('bench') || muscle.includes('chest')) {
    return imgBarbellBench
  }
  if (name.includes('push') || name.includes('push-up') || name.includes('pushup')) {
    return imgPushUp
  }
  if (name.includes('shoulder') || name.includes('overhead') || muscle.includes('shoulder')) {
    return imgShoulderPress
  }
  if (name.includes('curl') || name.includes('bicep') || muscle.includes('arm')) {
    return imgHammerCurl
  }
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

// 4. Data Master & Logbook Harian
const exercisesList = ref([])
const todayLogs = ref([])
const loadingLogs = ref(false)
const selectedDate = ref(new Date().toISOString().split('T')[0])

const formattedDate = computed(() => {
  if (!selectedDate.value) return ''
  try {
    const [year, month, day] = selectedDate.value.split('-')
    const d = new Date(year, month - 1, day)
    return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
  } catch (e) {
    return selectedDate.value
  }
})

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

// 5. Fitur Sesi Latihan Interaktif
const isWorkoutModalOpen = ref(false)
const modalStep = ref('select')
const selectedExercise = ref(null)

// Target Latihan
const totalSetsTarget = ref(3)
const targetReps = ref(10)
const targetWeight = ref(60)
const restDuration = ref(60)

// State Sesi Berjalan
const currentSet = ref(1)
const isResting = ref(false)
const restCountdown = ref(60)
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
  isWorkoutModalOpen.value = true
}

const startLiveWorkout = () => {
  currentSet.value = 1
  isResting.value = false
  modalStep.value = 'live'
}

const startRestInterval = () => {
  if (restTimerInterval) clearInterval(restTimerInterval)
  restTimerInterval = setInterval(() => {
    if (restCountdown.value > 0) {
      restCountdown.value--
    } else {
      clearInterval(restTimerInterval)
      notify(`Waktu istirahat selesai! Bersiap untuk Set ${currentSet.value + 1} 🔥`, 'success')
    }
  }, 1000)
}

const adjustRestTime = (delta) => {
  const newVal = Math.max(5, restCountdown.value + delta)
  restCountdown.value = newVal
  restDuration.value = newVal
  startRestInterval()
}

const setRestTime = (sec) => {
  restCountdown.value = sec
  restDuration.value = sec
  startRestInterval()
}

const completeCurrentSet = () => {
  if (currentSet.value < totalSetsTarget.value) {
    isResting.value = true
    restCountdown.value = restDuration.value || 60
    startRestInterval()
  } else {
    if (restTimerInterval) clearInterval(restTimerInterval)
    isResting.value = false
    modalStep.value = 'summary'
  }
}

const nextSet = () => {
  if (restTimerInterval) clearInterval(restTimerInterval)
  isResting.value = false
  currentSet.value++
}

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
      await api.post('/workout-logs', payload)
    } catch (e) {
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
const restSec = ref(90)
const restActive = ref(false)
let restTimer = null

const startRest = (s = 90) => {
  restSec.value = s
  restActive.value = true
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
  if (restTimerInterval) clearInterval(restTimerInterval)
  if (restTimer) clearInterval(restTimer)
  if (toastTimer) clearTimeout(toastTimer)
})
</script>

<template>
  <div class="home-layout">
    <!-- Toast Feedback -->
    <div v-if="toastMsg" class="toast-bubble" :class="toastType">
      {{ toastMsg }}
    </div>

    <!-- Clean Modern Navbar -->
    <header class="app-nav">
      <div class="nav-container">
        <div class="brand">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="brand-icon">
            <path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/><path d="m3 10 7-7"/><path d="m14 21 7-7"/>
          </svg>
          <span class="brand-title">APEX<b>STRENGTH</b></span>
        </div>

        <div class="nav-actions">
          <RouterLink v-if="isAdmin" to="/admin" class="nav-badge-admin">
            Console Admin ⚙
          </RouterLink>
          <div class="user-pill">
            <span class="dot"></span>
            <span>{{ currentUser?.name || 'Lifter' }}</span>
          </div>
          <button @click="handleLogout" class="btn-logout" title="Keluar">
            Keluar ⎋
          </button>
        </div>
      </div>
    </header>

    <!-- Main Dashboard Body -->
    <main class="dashboard-body">
      <!-- Top Overview Bar -->
      <section class="overview-header">
        <div class="overview-info">
          <span class="sub-date">{{ formattedDate }}</span>
          <h2>Jurnal Latihan Harian</h2>
        </div>
        <div class="overview-actions">
          <button @click="openWorkoutModal" class="btn-action-primary">
            + Sesi Latihan Baru
          </button>
          <button @click="startRest(90)" class="btn-action-secondary">
            ⏱️ Rest Timer (90s)
          </button>
        </div>
      </section>

      <!-- Stat Metrics (3 Clean Minimalist Cards) -->
      <section class="metrics-grid">
        <div class="metric-card">
          <div class="metric-head">
            <span class="metric-label">VOLUME BEBAN HARI INI</span>
            <span class="metric-tag">BEBAN</span>
          </div>
          <div class="metric-val">
            {{ totalVolume.toLocaleString('id-ID') }} <span class="unit">kg</span>
          </div>
          <p class="metric-sub">Akumulasi progres beban terangkat</p>
        </div>

        <div class="metric-card">
          <div class="metric-head">
            <span class="metric-label">TOTAL SET DISELESAIKAN</span>
            <span class="metric-tag">SETS</span>
          </div>
          <div class="metric-val">
            {{ totalSets }} <span class="unit">sets</span>
          </div>
          <p class="metric-sub">{{ groupedLogs.length }} variasi latihan tercatat</p>
        </div>

        <div class="metric-card">
          <div class="metric-head">
            <span class="metric-label">TANGGAL LATIHAN</span>
            <span class="metric-tag">ARSIP</span>
          </div>
          <div class="date-picker-wrap">
            <input type="date" v-model="selectedDate" @change="fetchTodayLogs" class="input-date-native" />
          </div>
          <p class="metric-sub">Pilih tanggal untuk melihat riwayat</p>
        </div>
      </section>

      <!-- Two Column Workspace Layout -->
      <div class="workspace-grid">
        <!-- Kolom Kiri: Riwayat Logbook Sesi -->
        <section class="workspace-main">
          <div class="section-card">
            <div class="section-card-head">
              <div class="head-title">
                <h3>Riwayat Latihan</h3>
                <span class="counter-badge">{{ groupedLogs.length }} Gerakan</span>
              </div>
              <button @click="openWorkoutModal" class="btn-inline-add">
                + Tambah Set
              </button>
            </div>

            <!-- Loading State -->
            <div v-if="loadingLogs" class="empty-state">
              <span>Memuat catatan latihan...</span>
            </div>

            <!-- Empty State -->
            <div v-else-if="groupedLogs.length === 0" class="empty-state">
              <div class="empty-icon">🏋️‍♂️</div>
              <h4>Belum Ada Catatan Latihan</h4>
              <p>Mulai sesi latihan hari ini dan pantau beban angkatan secara terukur.</p>
              <button @click="openWorkoutModal" class="btn-action-primary mini">
                Mulai Sesi Latihan
              </button>
            </div>

            <!-- Log List -->
            <div v-else class="exercise-logs">
              <div v-for="g in groupedLogs" :key="g.id" class="exercise-log-item">
                <div class="item-header">
                  <img :src="getExerciseImage(g)" class="item-thumb" :alt="g.name" />
                  <div class="item-meta">
                    <h4>{{ g.name }}</h4>
                    <span class="badge-muscle">{{ g.muscle }}</span>
                  </div>
                  <div class="item-summary-pill">
                    {{ g.sets.length }} Set
                  </div>
                </div>

                <div class="sets-table">
                  <div v-for="s in g.sets" :key="s.id" class="set-row">
                    <span class="set-badge">SET {{ s.set_number }}</span>
                    <span class="set-data"><b>{{ s.weight }} kg</b> × <b>{{ s.reps }} reps</b></span>
                    <span v-if="s.duration_seconds > 0" class="set-timer">⏱️ {{ s.duration_seconds }}s</span>
                    <button @click="removeLog(s.id)" class="btn-remove" title="Hapus set ini">✕</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Kolom Kanan: Pilihan Cepat & Rest Timer Widget -->
        <aside class="workspace-side">
          <!-- Pilihan Latihan Cepat -->
          <div class="side-card">
            <div class="side-head">
              <h4>Pilihan Latihan Cepat</h4>
              <span class="sub-hint">Klik untuk mulai</span>
            </div>
            <div class="quick-ex-list">
              <div 
                v-for="ex in exercisesList.slice(0, 5)" 
                :key="ex.id" 
                class="quick-ex-item"
                @click="chooseExercise(ex)"
              >
                <img :src="getExerciseImage(ex)" class="quick-thumb" :alt="ex.name" />
                <div class="quick-meta">
                  <strong>{{ ex.name }}</strong>
                  <small>{{ ex.muscle }} • {{ ex.equipment }}</small>
                </div>
                <span class="quick-arrow">➔</span>
              </div>
            </div>
          </div>

          <!-- Rest Timer Widget -->
          <div class="side-card rest-widget">
            <div class="side-head">
              <h4>Rest Timer Cepat</h4>
              <span class="timer-tag" :class="{ running: restActive }">
                {{ restActive ? 'BERJALAN' : 'STANDBY' }}
              </span>
            </div>
            <div class="widget-display">
              {{ restSec }}<span>s</span>
            </div>
            <div class="widget-presets">
              <button @click="startRest(30)" class="btn-timer-chip">30s</button>
              <button @click="startRest(60)" class="btn-timer-chip">60s</button>
              <button @click="startRest(90)" class="btn-timer-chip">90s</button>
              <button @click="startRest(120)" class="btn-timer-chip">120s</button>
            </div>
          </div>
        </aside>
      </div>
    </main>

    <!-- Modal Latihan Interaktif -->
    <div v-if="isWorkoutModalOpen" class="modal-overlay" @click.self="isWorkoutModalOpen = false">
      <div class="modal-window">
        <div class="modal-top">
          <h3>{{ modalStep === 'select' ? 'Pilih Gerakan Latihan' : modalStep === 'target' ? 'Atur Target Latihan' : modalStep === 'live' ? 'Latihan Aktif' : 'Selesai!' }}</h3>
          <button @click="isWorkoutModalOpen = false" class="btn-close">✕</button>
        </div>

        <!-- Step 1: Pilih Gerakan -->
        <div v-if="modalStep === 'select'" class="gallery-grid">
          <div v-for="ex in exercisesList" :key="ex.id" class="gallery-card" @click="chooseExercise(ex)">
            <img :src="getExerciseImage(ex)" class="gallery-img" />
            <div class="gallery-info">
              <strong>{{ ex.name }}</strong>
              <small>{{ ex.muscle }} • {{ ex.equipment }}</small>
            </div>
          </div>
        </div>

        <!-- Step 2: Atur Jumlah Set, Repetisi, dan Beban -->
        <div v-else-if="modalStep === 'target'" class="target-pane">
          <div class="selected-header">
            <img :src="getExerciseImage(selectedExercise)" class="selected-thumb" />
            <div>
              <strong>{{ selectedExercise?.name }}</strong>
              <small>{{ selectedExercise?.muscle }} • {{ selectedExercise?.equipment }}</small>
            </div>
            <button @click="modalStep = 'select'" class="btn-link">Ganti</button>
          </div>

          <div class="form-row">
            <label>Jumlah Set Latihan:</label>
            <div class="counter-ctrl">
              <button @click="totalSetsTarget > 1 ? totalSetsTarget-- : null">-</button>
              <span class="val">{{ totalSetsTarget }} set</span>
              <button @click="totalSetsTarget++">+</button>
            </div>
          </div>

          <div class="form-row">
            <label>Target Repetisi per Set:</label>
            <div class="counter-ctrl">
              <button @click="targetReps > 1 ? targetReps-- : null">-</button>
              <span class="val">{{ targetReps }} reps</span>
              <button @click="targetReps++">+</button>
            </div>
          </div>

          <div class="form-row">
            <label>Beban Angkatan (kg):</label>
            <input type="number" step="0.5" min="0" v-model="targetWeight" placeholder="0 jika bodyweight" class="num-box" />
          </div>

          <button @click="startLiveWorkout" class="btn-action-primary full">🔥 MULAI SESI LATIHAN (SET 1)</button>
        </div>

        <!-- Step 3: Layar Latihan Aktif -->
        <div v-else-if="modalStep === 'live'" class="live-pane">
          <!-- FASE 1: SEDANG LATIHAN -->
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

          <!-- FASE 2: JEDA ISTIRAHAT -->
          <div v-else class="resting-card">
            <div class="rest-badge">⏱️ JEDA ISTIRAHAT • SET {{ currentSet }} SELESAI</div>
            <div class="rest-timer-large" :class="{ finished: restCountdown <= 0 }">
              {{ restCountdown }}s
            </div>

            <p class="rest-hint">
              {{ restCountdown > 0 ? 'Tarik napas & minum. Anda dapat mengatur durasi jeda istirahat di bawah:' : 'Waktu istirahat selesai! Bersiap untuk set berikutnya:' }}
            </p>

            <div class="rest-ctrl-panel">
              <div class="rest-stepper">
                <button type="button" @click="adjustRestTime(-15)" class="btn-adjust" title="Kurangi 15 detik">-15s</button>
                <span class="adjust-val">{{ restCountdown }} detik</span>
                <button type="button" @click="adjustRestTime(15)" class="btn-adjust" title="Tambah 15 detik">+15s</button>
              </div>

              <div class="rest-presets">
                <button type="button" v-for="sec in [30, 60, 90, 120]" :key="sec" 
                        @click="setRestTime(sec)" 
                        :class="['btn-preset', { active: restCountdown === sec }]">
                  {{ sec }}s
                </button>
              </div>
            </div>

            <button @click="nextSet" class="btn-next-set">
              {{ restCountdown > 0 ? `Lewati Istirahat & Lanjut Set ${currentSet + 1} ➔` : `🔥 Mulai Set ${currentSet + 1} Sekarang ➔` }}
            </button>
          </div>
        </div>

        <!-- Step 4: Ringkasan Selesai -->
        <div v-else-if="modalStep === 'summary'" class="summary-pane">
          <div class="trophy">🏆</div>
          <h4>Semua Set Selesai! Luar Biasa!</h4>
          <p>Anda berhasil menyelesaikan <b>{{ totalSetsTarget }} Set</b> {{ selectedExercise?.name }} ({{ targetReps }} reps × {{ targetWeight }} kg).</p>
          <div class="summary-btns">
            <button @click="modalStep = 'target'" class="btn-action-secondary">Ulangi</button>
            <button @click="saveWorkoutResult" :disabled="isSaving" class="btn-action-primary">
              <span v-if="!isSaving">Simpan {{ totalSetsTarget }} Set ke Logbook 🚀</span>
              <span v-else>Menyimpan...</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Clean Minimalist Footer -->
    <footer class="app-footer">
      <div class="footer-inner">
        <span>APEX<b>STRENGTH</b></span>
        <span class="sep">•</span>
        <span class="copy">Minimalist Workout & Progressive Overload Tracker</span>
      </div>
    </footer>
  </div>
</template>

<style scoped>
/* BASE STYLING */
.home-layout { background: #090d16; color: #f1f5f9; min-height: 100vh; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; display: flex; flex-direction: column; }

/* NAVBAR */
.app-nav { background: #0d131f; border-bottom: 1px solid rgba(255,255,255,0.08); position: sticky; top: 0; z-index: 50; }
.nav-container { max-width: 1120px; margin: 0 auto; padding: 0.85rem 1.5rem; display: flex; justify-content: space-between; align-items: center; }
.brand { display: flex; align-items: center; gap: 0.6rem; }
.brand-icon { width: 22px; height: 22px; color: #ccff00; }
.brand-title { font-size: 1.15rem; font-weight: 800; letter-spacing: -0.02em; color: #fff; }
.brand-title b { color: #ccff00; }
.nav-actions { display: flex; align-items: center; gap: 0.75rem; }
.nav-badge-admin { background: rgba(56,189,248,0.12); border: 1px solid rgba(56,189,248,0.25); color: #38bdf8; padding: 0.35rem 0.75rem; border-radius: 6px; font-size: 0.78rem; font-weight: 700; text-decoration: none; transition: .2s; }
.nav-badge-admin:hover { background: rgba(56,189,248,0.2); }
.user-pill { display: flex; align-items: center; gap: 0.5rem; background: #141b2b; border: 1px solid rgba(255,255,255,0.08); padding: 0.35rem 0.75rem; border-radius: 6px; font-size: 0.8rem; font-weight: 600; color: #cbd5e1; }
.user-pill .dot { width: 7px; height: 7px; border-radius: 999px; background: #ccff00; }
.btn-logout { background: transparent; border: 1px solid rgba(255,255,255,0.12); color: #94a3b8; padding: 0.35rem 0.75rem; border-radius: 6px; font-size: 0.78rem; font-weight: 700; cursor: pointer; transition: .2s; }
.btn-logout:hover { color: #f87171; border-color: rgba(248,113,113,0.3); }

/* DASHBOARD & OVERVIEW */
.dashboard-body { max-width: 1120px; margin: 0 auto; padding: 2rem 1.5rem; width: 100%; flex: 1; }
.overview-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.6rem; }
.sub-date { font-size: 0.8rem; font-weight: 700; color: #ccff00; letter-spacing: 0.5px; text-transform: uppercase; display: block; margin-bottom: 0.3rem; }
.overview-info h2 { font-size: 1.6rem; font-weight: 800; letter-spacing: -0.02em; color: #fff; margin: 0; }
.overview-actions { display: flex; gap: 0.6rem; }

/* ACTION BUTTONS */
.btn-action-primary { background: #ccff00; color: #090d16; font-size: 0.84rem; font-weight: 800; padding: 0.6rem 1.1rem; border-radius: 8px; border: none; cursor: pointer; transition: .2s; }
.btn-action-primary:hover { background: #d9ff33; }
.btn-action-primary.mini { padding: 0.5rem 1rem; font-size: 0.8rem; margin-top: 1rem; }
.btn-action-primary.full { width: 100%; padding: 0.85rem; font-size: 0.95rem; margin-top: 1rem; }
.btn-action-secondary { background: #131a29; border: 1px solid rgba(255,255,255,0.1); color: #cbd5e1; font-size: 0.82rem; font-weight: 700; padding: 0.6rem 1rem; border-radius: 8px; cursor: pointer; transition: .2s; }
.btn-action-secondary:hover { background: #1a2336; color: #fff; }

/* METRICS GRID */
.metrics-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.8rem; }
.metric-card { background: #0f1523; border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 1.2rem; display: flex; flex-direction: column; }
.metric-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
.metric-label { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.04em; color: #94a3b8; }
.metric-tag { font-size: 0.65rem; font-weight: 800; color: #38bdf8; background: rgba(56,189,248,0.1); padding: 0.15rem 0.4rem; border-radius: 4px; }
.metric-val { font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em; color: #fff; line-height: 1.2; }
.metric-val .unit { font-size: 0.9rem; color: #ccff00; font-weight: 700; }
.metric-sub { font-size: 0.75rem; color: #64748b; margin: 0.4rem 0 0; }
.date-picker-wrap { margin: 0.2rem 0; }
.input-date-native { background: #141b2a; border: 1px solid rgba(204,255,0,0.25); color: #ccff00; padding: 0.4rem 0.6rem; border-radius: 6px; font-weight: 700; font-size: 0.85rem; width: 100%; }

/* WORKSPACE GRID */
.workspace-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 1.2rem; align-items: start; }
.section-card { background: #0f1523; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 1.4rem; }
.section-card-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem; }
.head-title { display: flex; align-items: center; gap: 0.6rem; }
.head-title h3 { font-size: 1.1rem; font-weight: 800; color: #fff; margin: 0; }
.counter-badge { font-size: 0.7rem; font-weight: 700; color: #94a3b8; background: #172033; padding: 0.2rem 0.5rem; border-radius: 4px; }
.btn-inline-add { background: rgba(204,255,0,0.1); border: 1px solid rgba(204,255,0,0.25); color: #ccff00; font-size: 0.76rem; font-weight: 800; padding: 0.35rem 0.75rem; border-radius: 6px; cursor: pointer; transition: .2s; }
.btn-inline-add:hover { background: rgba(204,255,0,0.2); }

/* EMPTY & LOGS */
.empty-state { text-align: center; padding: 3rem 1.5rem; color: #94a3b8; }
.empty-icon { font-size: 2.5rem; margin-bottom: 0.6rem; }
.empty-state h4 { font-size: 1rem; font-weight: 800; color: #f1f5f9; margin: 0 0 0.3rem; }
.empty-state p { font-size: 0.82rem; color: #64748b; margin: 0; }

.exercise-logs { display: flex; flex-direction: column; gap: 1rem; }
.exercise-log-item { background: #131a29; border: 1px solid rgba(255,255,255,0.06); border-radius: 10px; padding: 1rem; }
.item-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.8rem; }
.item-thumb { width: 44px; height: 44px; border-radius: 8px; object-fit: cover; border: 1px solid rgba(255,255,255,0.1); }
.item-meta { flex: 1; }
.item-meta h4 { font-size: 0.95rem; font-weight: 800; color: #fff; margin: 0 0 0.2rem; }
.badge-muscle { font-size: 0.68rem; font-weight: 700; color: #38bdf8; background: rgba(56,189,248,0.1); padding: 0.15rem 0.45rem; border-radius: 4px; }
.item-summary-pill { font-size: 0.72rem; font-weight: 700; color: #94a3b8; }

.sets-table { display: flex; flex-direction: column; gap: 0.4rem; }
.set-row { display: flex; justify-content: space-between; align-items: center; background: #182236; padding: 0.5rem 0.8rem; border-radius: 6px; font-size: 0.82rem; }
.set-badge { color: #94a3b8; font-size: 0.72rem; font-weight: 800; }
.set-data { color: #e2e8f0; }
.set-data b { color: #fff; }
.set-timer { font-size: 0.72rem; color: #38bdf8; background: rgba(56,189,248,0.1); padding: 0.15rem 0.4rem; border-radius: 4px; }
.btn-remove { background: transparent; border: none; color: #f87171; font-weight: 800; font-size: 0.85rem; cursor: pointer; padding: 0.2rem; }
.btn-remove:hover { color: #ef4444; }

/* SIDEBAR */
.workspace-side { display: flex; flex-direction: column; gap: 1.2rem; }
.side-card { background: #0f1523; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 1.2rem; }
.side-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.9rem; }
.side-head h4 { font-size: 0.92rem; font-weight: 800; color: #fff; margin: 0; }
.sub-hint { font-size: 0.7rem; color: #64748b; }
.quick-ex-list { display: flex; flex-direction: column; gap: 0.5rem; }
.quick-ex-item { display: flex; align-items: center; gap: 0.65rem; background: #141b2b; border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; padding: 0.5rem 0.65rem; cursor: pointer; transition: .2s; }
.quick-ex-item:hover { background: #1a2336; border-color: #ccff00; }
.quick-thumb { width: 38px; height: 38px; border-radius: 6px; object-fit: cover; }
.quick-meta { flex: 1; }
.quick-meta strong { display: block; font-size: 0.82rem; color: #fff; }
.quick-meta small { font-size: 0.7rem; color: #94a3b8; }
.quick-arrow { color: #64748b; font-size: 0.75rem; }
.quick-ex-item:hover .quick-arrow { color: #ccff00; }

/* MINI REST WIDGET */
.rest-widget { text-align: center; }
.timer-tag { font-size: 0.65rem; font-weight: 800; color: #64748b; background: #172033; padding: 0.15rem 0.45rem; border-radius: 4px; }
.timer-tag.running { color: #ccff00; background: rgba(204,255,0,0.12); }
.widget-display { font-size: 3rem; font-weight: 900; color: #38bdf8; line-height: 1; margin: 1rem 0 1.2rem; font-variant-numeric: tabular-nums; }
.widget-display span { font-size: 1.2rem; color: #64748b; }
.widget-presets { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.4rem; }
.btn-timer-chip { background: #141b2a; border: 1px solid rgba(255,255,255,0.08); color: #cbd5e1; font-size: 0.76rem; font-weight: 700; padding: 0.45rem 0; border-radius: 6px; cursor: pointer; transition: .2s; }
.btn-timer-chip:hover { background: rgba(56,189,248,0.15); border-color: #38bdf8; color: #38bdf8; }

/* MODAL */
.modal-overlay { position: fixed; inset: 0; background: rgba(4,7,14,0.85); backdrop-filter: blur(6px); display: flex; justify-content: center; align-items: center; z-index: 1000; padding: 1rem; }
.modal-window { background: #0f1523; border: 1px solid rgba(255,255,255,0.12); border-radius: 14px; width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto; padding: 1.5rem; }
.modal-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem; }
.modal-top h3 { margin: 0; font-size: 1.15rem; font-weight: 800; color: #fff; }
.btn-close { background: transparent; border: none; color: #94a3b8; font-size: 1.1rem; cursor: pointer; }

/* GALLERY & TARGET */
.gallery-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.8rem; }
.gallery-card { background: #141b29; border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; overflow: hidden; cursor: pointer; transition: .2s; }
.gallery-card:hover { border-color: #ccff00; transform: translateY(-2px); }
.gallery-img { width: 100%; height: 105px; object-fit: cover; }
.gallery-info { padding: 0.6rem; }
.gallery-info strong { display: block; font-size: 0.84rem; color: #fff; }
.gallery-info small { font-size: 0.7rem; color: #94a3b8; }

.selected-header { display: flex; align-items: center; gap: 0.75rem; background: #141b29; padding: 0.75rem; border-radius: 10px; margin-bottom: 1.2rem; }
.selected-thumb { width: 46px; height: 46px; border-radius: 6px; object-fit: cover; border: 1px solid #ccff00; }
.btn-link { margin-left: auto; background: transparent; border: 1px solid rgba(255,255,255,0.15); color: #cbd5e1; font-size: 0.72rem; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; }
.form-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.9rem; }
.form-row label { font-size: 0.82rem; font-weight: 700; color: #cbd5e1; }
.counter-ctrl { display: flex; align-items: center; gap: 0.5rem; }
.counter-ctrl button { background: #192133; border: 1px solid rgba(255,255,255,0.1); color: #fff; font-weight: 800; width: 34px; height: 34px; border-radius: 6px; cursor: pointer; }
.counter-ctrl .val { font-size: 0.88rem; font-weight: 800; color: #ccff00; min-width: 65px; text-align: center; }
.num-box { background: #192133; border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 0.45rem 0.6rem; border-radius: 6px; font-weight: 700; width: 140px; text-align: right; }

/* LIVE & REST */
.live-pane { text-align: center; padding: 0.5rem 0; }
.active-set-card { background: #141b29; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 1.5rem 1.2rem; }
.set-indicator { display: inline-block; font-size: 0.8rem; font-weight: 800; color: #090d16; background: #ccff00; padding: 0.3rem 0.8rem; border-radius: 999px; margin-bottom: 1.2rem; letter-spacing: 0.5px; }
.exercise-preview-row { display: flex; align-items: center; justify-content: center; gap: 0.8rem; margin-bottom: 1rem; }
.preview-mini-thumb { width: 48px; height: 48px; border-radius: 8px; object-fit: cover; border: 1px solid #ccff00; }
.exercise-preview-row h3 { margin: 0; font-size: 1.2rem; font-weight: 800; color: #fff; }
.sub-target { font-size: 0.88rem; color: #38bdf8; font-weight: 700; display: block; margin-top: 0.2rem; }
.set-instruction { font-size: 0.84rem; color: #94a3b8; line-height: 1.5; margin: 1rem auto 1.5rem; max-width: 380px; }
.btn-complete-set { width: 100%; max-width: 320px; background: #ccff00; color: #090d16; font-size: 1.05rem; font-weight: 900; padding: 1rem; border-radius: 12px; border: none; cursor: pointer; transition: .2s; box-shadow: 0 4px 20px rgba(204,255,0,0.25); }
.btn-complete-set:hover { background: #d9ff33; transform: scale(1.02); }

.resting-card { background: #141b29; border: 1px solid rgba(56,189,248,0.25); border-radius: 12px; padding: 1.8rem 1.2rem; text-align: center; }
.rest-badge { font-size: 0.75rem; font-weight: 800; color: #38bdf8; letter-spacing: 1px; margin-bottom: 0.4rem; }
.rest-timer-large { font-size: 3.8rem; font-weight: 900; color: #38bdf8; line-height: 1; margin-bottom: 0.6rem; font-variant-numeric: tabular-nums; }
.rest-timer-large.finished { color: #ccff00; }
.rest-hint { font-size: 0.84rem; color: #94a3b8; margin-bottom: 1.2rem; }

.rest-ctrl-panel { display: flex; flex-direction: column; align-items: center; gap: 0.8rem; margin-bottom: 1.5rem; }
.rest-stepper { display: flex; align-items: center; gap: 0.6rem; background: #0f1523; padding: 0.35rem 0.6rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08); }
.btn-adjust { background: #1a2233; border: 1px solid rgba(255,255,255,0.12); color: #fff; font-size: 0.78rem; font-weight: 800; padding: 0.35rem 0.7rem; border-radius: 6px; cursor: pointer; transition: .2s; }
.btn-adjust:hover { background: #26334d; color: #ccff00; }
.adjust-val { font-size: 0.9rem; font-weight: 800; color: #ccff00; min-width: 75px; }

.rest-presets { display: flex; gap: 0.4rem; flex-wrap: wrap; justify-content: center; }
.btn-preset { background: #1a2233; border: 1px solid rgba(255,255,255,0.1); color: #cbd5e1; font-size: 0.75rem; font-weight: 700; padding: 0.3rem 0.7rem; border-radius: 6px; cursor: pointer; transition: .2s; }
.btn-preset:hover, .btn-preset.active { background: rgba(56,189,248,0.2); border-color: #38bdf8; color: #38bdf8; font-weight: 800; }

.btn-next-set { width: 100%; max-width: 320px; background: #ccff00; color: #090d16; font-size: 0.95rem; font-weight: 800; padding: 0.85rem; border-radius: 10px; border: none; cursor: pointer; transition: .2s; }
.btn-next-set:hover { background: #d9ff33; transform: scale(1.02); }

/* SUMMARY, TOAST & FOOTER */
.summary-pane { text-align: center; padding: 1rem 0; }
.trophy { font-size: 3rem; margin-bottom: 0.4rem; }
.summary-pane h4 { font-size: 1.3rem; margin: 0 0 0.3rem; color: #fff; }
.summary-pane p { font-size: 0.88rem; color: #94a3b8; margin-bottom: 1.5rem; }
.summary-btns { display: flex; justify-content: center; gap: 0.7rem; }
.toast-bubble { position: fixed; bottom: 1.5rem; right: 1.5rem; background: #ccff00; color: #090d16; font-weight: 800; font-size: 0.82rem; padding: 0.65rem 1.2rem; border-radius: 8px; z-index: 2000; box-shadow: 0 8px 20px rgba(0,0,0,0.5); }
.toast-bubble.error { background: #ef4444; color: #fff; }

.app-footer { text-align: center; padding: 1.8rem 1rem; font-size: 0.78rem; color: #64748b; border-top: 1px solid rgba(255,255,255,0.06); margin-top: auto; }
.footer-inner { display: flex; justify-content: center; align-items: center; gap: 0.5rem; }
.footer-inner b { color: #ccff00; }
.footer-inner .sep { color: #334155; }

/* RESPONSIVE */
@media (max-width: 900px) {
  .workspace-grid { grid-template-columns: 1fr; }
  .metrics-grid { grid-template-columns: 1fr; }
  .overview-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
}
</style>
