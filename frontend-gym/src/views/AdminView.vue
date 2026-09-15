<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import api from '@/utils/api'

const router = useRouter()
const activeTab = ref('dashboard')

// State Dashboard
const loadingStats = ref(true)
const stats = ref({ totalUsers: 0, totalWorkouts: 0, totalVolumeKg: 0, popularExercises: [] })

// State Master Latihan
const loadingExercises = ref(true)
const exercises = ref([])
const searchQuery = ref('')
const selectedMuscle = ref('ALL')
const muscles = ['ALL', 'Chest', 'Back', 'Legs', 'Shoulders', 'Arms', 'Core']
const equipments = ['Barbell', 'Dumbbell', 'Machine', 'Cable', 'Bodyweight']

// Modal Form
const isModalOpen = ref(false)
const isEditing = ref(false)
const modalLoading = ref(false)
const modalError = ref('')
const form = ref({ id: null, name: '', muscle_group: 'Chest', equipment: 'Barbell', instructions: '' })

// Toast Alert
const toast = ref({ show: false, message: '', type: 'success' })
const notify = (msg, type = 'success') => {
  toast.value = { show: true, message: msg, type }
  setTimeout(() => { toast.value.show = false }, 3000)
}

// 1. Fetch Dashboard Stats
const fetchStats = async () => {
  loadingStats.value = true
  try {
    const res = await api.get('/admin/dashboard')
    stats.value = res.data?.data || res.data || stats.value
  } catch (err) {
    console.error('Stats error:', err)
  } finally {
    loadingStats.value = false
  }
}

// 2. Fetch Exercises (dengan mapping target_muscle & description dari backend)
const fetchExercises = async () => {
  loadingExercises.value = true
  try {
    let res
    try {
      res = await api.get('/admin/exercises')
    } catch (e) {
      // Fallback ke /exercises jika backend mendaftarkannya di rute publik
      res = await api.get('/exercises')
    }
    const rawList = res.data?.data || (Array.isArray(res.data) ? res.data : [])
    exercises.value = rawList.map(item => ({
      id: item.id,
      name: item.name,
      muscle_group: item.target_muscle || item.muscle_group || 'General',
      equipment: item.equipment || 'Barbell',
      instructions: item.description || item.instructions || ''
    }))
  } catch (err) {
    console.error('Exercises error:', err)
    notify('Gagal memuat data dari backend.', 'error')
  } finally {
    loadingExercises.value = false
  }
}

// Filtered List
const filteredList = computed(() => {
  return exercises.value.filter(item => {
    const matchName = item.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchMuscle = selectedMuscle.value === 'ALL' || (item.muscle_group && item.muscle_group.toLowerCase() === selectedMuscle.value.toLowerCase())
    return matchName && matchMuscle
  })
})

// Modal Actions
const openModal = (item = null) => {
  modalError.value = ''
  if (item) {
    isEditing.value = true
    form.value = { ...item, instructions: item.instructions || '' }
  } else {
    isEditing.value = false
    form.value = { id: null, name: '', muscle_group: 'Chest', equipment: 'Barbell', instructions: '' }
  }
  isModalOpen.value = true
}

const closeModal = () => { isModalOpen.value = false }

// Submit Form (Tambah / Update - mengirim field target_muscle & description sesuai backend)
const saveExercise = async () => {
  if (!form.value.name.trim()) {
    modalError.value = 'Nama latihan wajib diisi.'
    return
  }
  modalLoading.value = true
  modalError.value = ''
  try {
    const payload = {
      name: form.value.name.trim(),
      target_muscle: form.value.muscle_group, // Sesuai kolom database backend teman
      muscle_group: form.value.muscle_group,
      equipment: form.value.equipment,
      description: form.value.instructions?.trim() || null, // Sesuai kolom database backend teman
      instructions: form.value.instructions?.trim() || null
    }

    if (isEditing.value) {
      await api.put(`/admin/exercises/${form.value.id}`, payload)
      notify('Latihan berhasil diperbarui.')
    } else {
      await api.post('/admin/exercises', payload)
      notify('Latihan berhasil ditambahkan.')
    }
    closeModal()
    fetchExercises()
    fetchStats()
  } catch (err) {
    if (err.response?.data?.errors) {
      const firstKey = Object.keys(err.response.data.errors)[0]
      modalError.value = err.response.data.errors[firstKey][0]
    } else {
      modalError.value = err.response?.data?.message || 'Gagal menyimpan data ke backend.'
    }
  } finally {
    modalLoading.value = false
  }
}

// Delete Exercise
const removeExercise = async (id, name) => {
  if (!confirm(`Hapus latihan "${name}"?`)) return
  try {
    await api.delete(`/admin/exercises/${id}`)
    notify('Latihan berhasil dihapus.')
    fetchExercises()
    fetchStats()
  } catch (err) {
    notify('Gagal menghapus latihan.', 'error')
  }
}

// Logout
const logout = async () => {
  try { await api.post('/logout') } catch (e) {}
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  router.push('/login')
}

onMounted(() => {
  fetchStats()
  fetchExercises()
})
</script>

<template>
  <div class="admin-page">
    <!-- NAVBAR -->
    <header class="navbar">
      <div class="brand">
        <span class="brand-text">APEX<b>STRENGTH</b></span>
        <span class="badge-admin">Admin</span>
      </div>

      <nav class="nav-tabs">
        <button :class="{ active: activeTab === 'dashboard' }" @click="activeTab = 'dashboard'">
          Dashboard
        </button>
        <button :class="{ active: activeTab === 'exercises' }" @click="activeTab = 'exercises'">
          Master Latihan ({{ exercises.length }})
        </button>
      </nav>

      <div class="nav-right">
        <RouterLink to="/home" class="btn-link">Buka Aplikasi Lifter ↗</RouterLink>
        <button @click="logout" class="btn-logout">Logout</button>
      </div>
    </header>

    <!-- TOAST NOTIFICATION -->
    <div v-if="toast.show" :class="['toast', toast.type]">
      {{ toast.message }}
    </div>

    <!-- CONTENT -->
    <main class="content">
      <!-- 1. TAB DASHBOARD -->
      <section v-if="activeTab === 'dashboard'">
        <div class="header-row">
          <div>
            <h2>Dashboard Analitik</h2>
            <p>Ringkasan pengguna dan aktivitas log latihan sistem.</p>
          </div>
          <button @click="fetchStats" class="btn-secondary">Refresh Data</button>
        </div>

        <div class="stats-grid">
          <div class="card stat-card">
            <span class="stat-label">Total Lifters</span>
            <div class="stat-num">{{ loadingStats ? '...' : stats.totalUsers }}</div>
            <span class="stat-desc">User terdaftar di database</span>
          </div>
          <div class="card stat-card">
            <span class="stat-label">Total Sesi Latihan</span>
            <div class="stat-num">{{ loadingStats ? '...' : stats.totalWorkouts }}</div>
            <span class="stat-desc">Logbook tersimpan</span>
          </div>
          <div class="card stat-card">
            <span class="stat-label">Total Volume Beban</span>
            <div class="stat-num highlight">
              {{ loadingStats ? '...' : Number(stats.totalVolumeKg).toLocaleString('id-ID') }} kg
            </div>
            <span class="stat-desc">Akumulasi seluruh angkatan</span>
          </div>
          <div class="card stat-card">
            <span class="stat-label">Katalog Latihan</span>
            <div class="stat-num">{{ exercises.length }}</div>
            <span class="stat-desc">Gerakan resmi aktif</span>
          </div>
        </div>

        <div class="card panel-card">
          <h3>Latihan Paling Sering Dilog</h3>
          <div v-if="stats.popularExercises?.length" class="pop-list">
            <div v-for="(ex, i) in stats.popularExercises" :key="i" class="pop-row">
              <span class="pop-idx">#{{ i + 1 }}</span>
              <span class="pop-name">{{ ex.name }}</span>
              <span class="pop-count"><b>{{ ex.logs }}</b> sesi</span>
            </div>
          </div>
          <p v-else class="empty-text">Belum ada data sesi latihan yang dicatat.</p>
        </div>
      </section>

      <!-- 2. TAB MASTER LATIHAN -->
      <section v-else>
        <div class="header-row">
          <div>
            <h2>Master Data Latihan</h2>
            <p>Kelola daftar gerakan resmi untuk dipilih lifter saat workout.</p>
          </div>
          <button @click="openModal()" class="btn-primary">+ Tambah Latihan</button>
        </div>

        <!-- SEARCH & FILTER -->
        <div class="filter-wrap">
          <input
            type="text"
            v-model="searchQuery"
            placeholder="Cari nama gerakan (misal: Bench Press, Squat)..."
            class="search-input"
          />
          <div class="pill-group">
            <button
              v-for="m in muscles"
              :key="m"
              :class="['pill', { active: selectedMuscle === m }]"
              @click="selectedMuscle = m"
            >
              {{ m === 'ALL' ? 'Semua' : m }}
            </button>
          </div>
        </div>

        <!-- TABLE -->
        <div class="card table-card">
          <div v-if="loadingExercises" class="loading-state">
            Memuat data latihan dari server...
          </div>
          <div v-else-if="filteredList.length === 0" class="empty-state">
            Tidak ada latihan yang ditemukan.
          </div>
          <table v-else class="table">
            <thead>
              <tr>
                <th>NAMA GERAKAN</th>
                <th>OTOT</th>
                <th>ALAT</th>
                <th>PETUNJUK TEKNIK</th>
                <th class="text-right">AKSI</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="ex in filteredList" :key="ex.id">
                <td><b>{{ ex.name }}</b></td>
                <td><span class="tag muscle">{{ ex.muscle_group }}</span></td>
                <td><span class="tag equip">{{ ex.equipment }}</span></td>
                <td class="desc-cell">{{ ex.instructions || '-' }}</td>
                <td class="text-right">
                  <button @click="openModal(ex)" class="btn-sm btn-edit">Edit</button>
                  <button @click="removeExercise(ex.id, ex.name)" class="btn-sm btn-del">Hapus</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>

    <!-- MODAL POPUP FORM -->
    <div v-if="isModalOpen" class="modal-bg" @click.self="closeModal">
      <div class="modal-box">
        <div class="modal-top">
          <h3>{{ isEditing ? 'Edit Latihan' : 'Tambah Latihan Baru' }}</h3>
          <button @click="closeModal" class="close-btn">✕</button>
        </div>

        <div v-if="modalError" class="alert-error">{{ modalError }}</div>

        <form @submit.prevent="saveExercise" class="modal-form">
          <div class="form-group">
            <label>Nama Gerakan Latihan *</label>
            <input type="text" v-model="form.name" placeholder="Contoh: Barbell Bench Press" required />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Target Otot *</label>
              <select v-model="form.muscle_group">
                <option v-for="m in muscles.filter(m => m !== 'ALL')" :key="m" :value="m">{{ m }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Alat *</label>
              <select v-model="form.equipment">
                <option v-for="eq in equipments" :key="eq" :value="eq">{{ eq }}</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Instruksi Singkat (Opsional)</label>
            <textarea rows="3" v-model="form.instructions" placeholder="Tips posisi tubuh dan rentang gerak..."></textarea>
          </div>

          <div class="modal-actions">
            <button type="button" @click="closeModal" class="btn-secondary" :disabled="modalLoading">Batal</button>
            <button type="submit" class="btn-primary" :disabled="modalLoading">
              {{ modalLoading ? 'Menyimpan...' : (isEditing ? 'Simpan' : 'Tambahkan') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* BASE LAYOUT */
.admin-page {
  min-height: 100vh;
  background: #090d14;
  color: #e2e8f0;
  font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  font-size: 14px;
}

/* NAVBAR */
.navbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 2rem;
  background: #0f1522;
  border-bottom: 1px solid #1e2638;
}
.brand { display: flex; align-items: center; gap: 0.6rem; }
.brand-text { font-size: 1.1rem; font-weight: 700; color: #fff; letter-spacing: -0.02em; }
.brand-text b { color: #ccff00; }
.badge-admin {
  background: #1e293b;
  color: #38bdf8;
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
  border: 1px solid #334155;
}

.nav-tabs { display: flex; gap: 0.4rem; background: #090d14; padding: 4px; border-radius: 8px; border: 1px solid #1e2638; }
.nav-tabs button {
  background: transparent;
  border: none;
  color: #94a3b8;
  padding: 0.4rem 0.9rem;
  font-size: 0.82rem;
  font-weight: 600;
  border-radius: 6px;
  cursor: pointer;
  transition: 0.2s;
}
.nav-tabs button.active { background: #1e293b; color: #ccff00; }
.nav-tabs button:hover:not(.active) { color: #fff; }

.nav-right { display: flex; align-items: center; gap: 0.8rem; }
.btn-link { color: #94a3b8; font-size: 0.8rem; font-weight: 600; text-decoration: none; }
.btn-link:hover { color: #ccff00; }
.btn-logout {
  background: #1e2433;
  border: 1px solid #334155;
  color: #f87171;
  padding: 0.35rem 0.75rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}
.btn-logout:hover { background: #331f24; }

/* CONTENT WRAPPER */
.content { max-width: 1100px; margin: 0 auto; padding: 2rem; }
.header-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.5rem; }
.header-row h2 { font-size: 1.4rem; font-weight: 700; color: #fff; margin: 0 0 0.2rem; letter-spacing: -0.01em; }
.header-row p { font-size: 0.85rem; color: #64748b; margin: 0; }

/* BUTTONS */
.btn-primary {
  background: #ccff00;
  color: #090d14;
  border: none;
  font-size: 0.82rem;
  font-weight: 700;
  padding: 0.55rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  transition: 0.2s;
}
.btn-primary:hover { background: #d9ff33; }
.btn-secondary {
  background: #141b29;
  border: 1px solid #222d42;
  color: #cbd5e1;
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.5rem 0.9rem;
  border-radius: 6px;
  cursor: pointer;
}
.btn-secondary:hover { border-color: #3b4d6e; color: #fff; }

/* CARDS */
.card { background: #0f1522; border: 1px solid #1a2233; border-radius: 10px; padding: 1.25rem; }
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
.stat-card { display: flex; flex-direction: column; gap: 0.3rem; }
.stat-label { font-size: 0.72rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
.stat-num { font-size: 1.7rem; font-weight: 700; color: #fff; letter-spacing: -0.02em; }
.stat-num.highlight { color: #ccff00; }
.stat-desc { font-size: 0.75rem; color: #64748b; }

.panel-card h3 { font-size: 0.95rem; font-weight: 700; color: #f1f5f9; margin: 0 0 1rem; }
.pop-list { display: flex; flex-direction: column; gap: 0.5rem; }
.pop-row { display: flex; align-items: center; background: #141c2c; padding: 0.6rem 0.9rem; border-radius: 6px; font-size: 0.85rem; }
.pop-idx { width: 30px; font-weight: 700; color: #ccff00; }
.pop-name { flex: 1; font-weight: 600; }
.pop-count { color: #94a3b8; font-size: 0.8rem; }
.pop-count b { color: #fff; }
.empty-text { font-size: 0.85rem; color: #64748b; }

/* FILTERS */
.filter-wrap { display: flex; flex-direction: column; gap: 0.8rem; margin-bottom: 1.2rem; }
.search-input {
  background: #0f1522;
  border: 1px solid #1a2233;
  color: #fff;
  padding: 0.65rem 0.9rem;
  border-radius: 8px;
  font-size: 0.85rem;
  outline: none;
}
.search-input:focus { border-color: #ccff00; }
.pill-group { display: flex; gap: 0.4rem; flex-wrap: wrap; }
.pill {
  background: #131a29;
  border: 1px solid #1f2a3f;
  color: #94a3b8;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.3rem 0.75rem;
  border-radius: 999px;
  cursor: pointer;
}
.pill.active { background: #ccff00; color: #090d14; border-color: #ccff00; font-weight: 700; }
.pill:hover:not(.active) { color: #fff; }

/* TABLE */
.table-card { padding: 0; overflow: hidden; }
.table { width: 100%; border-collapse: collapse; text-align: left; }
.table th { background: #0a0e17; padding: 0.75rem 1rem; font-size: 0.7rem; font-weight: 700; color: #64748b; border-bottom: 1px solid #1a2233; }
.table td { padding: 0.8rem 1rem; border-bottom: 1px solid #141c2c; font-size: 0.85rem; }
.table tr:last-child td { border-bottom: none; }
.desc-cell { color: #94a3b8; font-size: 0.8rem; max-width: 320px; }
.text-right { text-align: right; }

.tag { font-size: 0.72rem; font-weight: 600; padding: 0.2rem 0.5rem; border-radius: 4px; }
.tag.muscle { background: #132438; color: #38bdf8; border: 1px solid #1d3957; }
.tag.equip { background: #1c2433; color: #94a3b8; }

.btn-sm { font-size: 0.72rem; font-weight: 600; padding: 0.3rem 0.6rem; border-radius: 4px; border: none; cursor: pointer; margin-left: 0.3rem; }
.btn-edit { background: #1e293b; color: #38bdf8; }
.btn-edit:hover { background: #27374d; }
.btn-del { background: #2b171c; color: #f87171; }
.btn-del:hover { background: #3b1f25; }

.loading-state, .empty-state { padding: 2.5rem; text-align: center; color: #64748b; font-size: 0.85rem; }

/* MODAL */
.modal-bg { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 100; padding: 1rem; }
.modal-box { background: #0f1522; border: 1px solid #222d42; border-radius: 10px; width: 100%; max-width: 460px; padding: 1.5rem; }
.modal-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem; }
.modal-top h3 { font-size: 1.1rem; font-weight: 700; color: #fff; margin: 0; }
.close-btn { background: none; border: none; color: #64748b; font-size: 1.1rem; cursor: pointer; }
.close-btn:hover { color: #fff; }

.modal-form { display: flex; flex-direction: column; gap: 0.85rem; }
.form-group { display: flex; flex-direction: column; gap: 0.3rem; }
.form-group label { font-size: 0.75rem; font-weight: 600; color: #94a3b8; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; }
.form-group input, .form-group select, .form-group textarea {
  background: #141c2c;
  border: 1px solid #222d42;
  color: #fff;
  padding: 0.55rem 0.75rem;
  border-radius: 6px;
  font-family: inherit;
  font-size: 0.85rem;
  outline: none;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #ccff00; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.6rem; margin-top: 0.6rem; }
.alert-error { background: #2b171c; border: 1px solid #571e27; color: #f87171; padding: 0.5rem 0.75rem; border-radius: 6px; font-size: 0.8rem; margin-bottom: 0.8rem; }

/* TOAST */
.toast {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  padding: 0.65rem 1.1rem;
  border-radius: 6px;
  font-size: 0.82rem;
  font-weight: 600;
  z-index: 200;
}
.toast.success { background: #0f2b1d; color: #4ade80; border: 1px solid #1b5235; }
.toast.error { background: #2b171c; color: #f87171; border: 1px solid #571e27; }

@media (max-width: 800px) {
  .stats-grid { grid-template-columns: 1fr 1fr; }
  .navbar { flex-direction: column; gap: 0.6rem; }
}
</style>
