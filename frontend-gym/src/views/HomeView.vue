<script setup>
import { RouterLink } from 'vue-router'

const features = [
  { tag: 'LOGBOOK', title: 'Catat Set & Reps', desc: 'Input beban (kg), repetisi, dan RPE seketika saat jeda latihan.', stat: '⚡ Quick Log' },
  { tag: 'ANALYTICS', title: 'Progressive Overload', desc: 'Grafik volume angkatan mingguan dan estimasi 1RM otomatis.', stat: '📈 Auto 1RM' },
  { tag: 'TIMER', title: 'Smart Rest Timer', desc: 'Countdown otomatis antar set agar intensitas dan waktu istirahat terjaga.', stat: '⏱️ Auto Countdown' }
]
</script>

<template>
  <div class="home">
    <nav class="nav">
      <div class="brand">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="ic"><path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/><path d="m3 10 7-7"/><path d="m14 21 7-7"/></svg>
        <span>APEX<b>STRENGTH</b></span>
      </div>
      <RouterLink to="/login" class="btn-nav">Keluar / Logout ⎋</RouterLink>
    </nav>

    <header class="hero" v-motion :initial="{ opacity: 0, y: 15 }" :enter="{ opacity: 1, y: 0 }">
      <h1 class="hero-title">
        <span class="line-1">CATAT SETIAP SET LATIHAN</span>
        <span class="line-2">PANTAU PROGRESSIVE OVERLOAD</span>
      </h1>
      <p class="sub">Catatan latihan gym minimalis. Lacak kenaikan beban, repetisi, dan Personal Record Progress tanpa ribet.</p>
      
      <div class="cta-wrap">
        <button @click="alert('Membuka formulir pencatatan sesi latihan')" class="btn-main">Mulai Catat Latihan +</button>
        <a href="#fitur" class="btn-sub">Fitur Tracker</a>
      </div>

      <!-- Live Log Preview -->
      <div class="log-card">
        <div class="card-top">
          <div><small>SESSION TODAY • PUSH A</small><h4>Barbell Bench Press</h4></div>
          <span class="pr-badge">🏆 NEW PR</span>
        </div>
        <div class="set-rows">
          <div class="s-row"><span>SET 1</span><b>80 kg × 8 reps</b><span class="ok">✓ SELESAI</span></div>
          <div class="s-row"><span>SET 2</span><b>85 kg × 6 reps</b><span class="ok">✓ SELESAI</span></div>
          <div class="s-row active"><span>SET 3</span><b>90 kg × 5 reps</b><span class="rest">⏱️ REST 90s</span></div>
        </div>
      </div>
    </header>

    <section id="fitur" class="feats">
      <div class="sec-title"><small>FITUR UTAMA</small><h2>Fokus Pada Progres Fisik Anda</h2></div>
      <div class="grid">
        <div v-for="(f, i) in features" :key="i" class="card">
          <small>{{ f.tag }}</small>
          <h3>{{ f.title }}</h3>
          <p>{{ f.desc }}</p>
          <span class="badge">{{ f.stat }}</span>
        </div>
      </div>
    </section>

    <footer class="foot">
      <div class="foot-inner">
        <div>
          <span class="brand">APEX<b>STRENGTH</b></span>
          <p>Workout Tracker & Fitness Logbook minimalis untuk progres latihan harian.</p>
        </div>
        <RouterLink to="/login" class="btn-nav">Keluar / Ganti Akun</RouterLink>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.home { background: #07090e; color: #f1f5f9; min-height: 100vh; font-family: 'Plus Jakarta Sans', sans-serif; }
.nav { max-width: 1080px; margin: 0 auto; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; }
.brand { display: flex; align-items: center; gap: 0.6rem; font-family: 'Syne', sans-serif; font-size: 1.25rem; font-weight: 800; color: #fff; }
.brand b, .card small, .pr-badge, .ok { color: #ccff00; }
.ic { width: 24px; height: 24px; color: #ccff00; }
.btn-nav { background: #121722; border: 1px solid rgba(204,255,0,0.3); color: #ccff00; padding: 0.45rem 1rem; border-radius: 999px; font-size: 0.82rem; font-weight: 700; transition: .2s; }
.btn-nav:hover, .btn-main:hover { background: #d9ff33; color: #07090e; }

.hero { padding: 4rem 1.5rem 2.8rem; text-align: center; max-width: 820px; margin: 0 auto; }
.hero-title {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.3rem;
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: clamp(1.75rem, 3.8vw, 2.75rem);
  font-weight: 800;
  line-height: 1.18;
  letter-spacing: -0.025em;
  margin-bottom: 1.1rem;
}
.line-1 { color: #ffffff; }
.line-2 { color: #ccff00; }
.sub { font-size: 0.98rem; color: #94a3b8; line-height: 1.6; max-width: 580px; margin: 0 auto 1.8rem; }

.cta-wrap { display: flex; justify-content: center; gap: .8rem; margin-bottom: 2.8rem; }
.btn-main { background: #ccff00; color: #07090e; padding: .8rem 1.6rem; border-radius: 10px; font-weight: 800; font-size: .88rem; box-shadow: 0 4px 16px rgba(204,255,0,0.22); transition: .2s; }
.btn-sub { background: #121722; border: 1px solid rgba(255,255,255,0.12); color: #cbd5e1; padding: .8rem 1.4rem; border-radius: 10px; font-size: .88rem; font-weight: 600; }

.log-card { background: #0f1420; border: 1px solid rgba(255,255,255,0.1); border-radius: 14px; max-width: 480px; margin: 0 auto; padding: 1.2rem; text-align: left; box-shadow: 0 10px 25px rgba(0,0,0,0.4); }
.card-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: .9rem; }
.card-top small { font-size: .65rem; font-weight: 800; letter-spacing: 1px; color: #ccff00; display: block; }
.card-top h4 { font-family: 'Syne', sans-serif; font-size: 1.1rem; color: #fff; margin: .2rem 0 0; }
.pr-badge { font-size: .66rem; font-weight: 800; background: rgba(204,255,0,0.15); border: 1px solid rgba(204,255,0,0.3); padding: .2rem .5rem; border-radius: 6px; }
.set-rows { display: flex; flex-direction: column; gap: .45rem; }
.s-row { display: flex; justify-content: space-between; align-items: center; background: #151b28; padding: .55rem .85rem; border-radius: 8px; font-size: .82rem; }
.s-row.active { border: 1px solid rgba(204,255,0,0.35); background: #192233; }
.s-row span { color: #64748b; font-weight: 700; }
.s-row b { color: #f1f5f9; font-weight: 600; }
.ok { font-weight: 700; font-size: .75rem; }
.rest { color: #38bdf8; font-weight: 700; font-size: .75rem; }

.feats { max-width: 1080px; margin: 0 auto; padding: 3.5rem 1.5rem; }
.sec-title { text-align: center; margin-bottom: 2rem; }
.sec-title small { font-size: .7rem; font-weight: 800; letter-spacing: 2px; color: #ccff00; }
.sec-title h2 { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; color: #fff; margin-top: .3rem; }
.grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.2rem; }
.card { background: #0f1420; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 1.4rem; display: flex; flex-direction: column; }
.card small { font-size: .65rem; font-weight: 800; letter-spacing: 1px; margin-bottom: .5rem; }
.card h3 { font-family: 'Syne', sans-serif; font-size: 1.1rem; color: #fff; margin-bottom: .4rem; }
.card p { font-size: .83rem; color: #94a3b8; line-height: 1.5; margin-bottom: 1rem; flex-grow: 1; }
.badge { font-size: .74rem; font-weight: 700; color: #cbd5e1; background: rgba(255,255,255,0.05); padding: .3rem .6rem; border-radius: 6px; align-self: flex-start; }

.foot { border-top: 1px solid rgba(255,255,255,0.08); padding: 2.5rem 1.5rem 1.2rem; background: #05070a; }
.foot-inner { max-width: 1080px; margin: 0 auto 1.5rem; display: flex; justify-content: space-between; align-items: center; }
.foot-inner p { font-size: .82rem; color: #94a3b8; margin-top: .3rem; max-width: 380px; }
@media (max-width: 768px) {
  .hero-title { font-size: 1.5rem; }
  .grid { grid-template-columns: 1fr; }
  .foot-inner { flex-direction: column; gap: 1.2rem; text-align: center; }
}
</style>
