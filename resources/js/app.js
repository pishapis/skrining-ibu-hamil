import './bootstrap'

// --- Alpine ---
import Alpine from 'alpinejs'
window.Alpine = Alpine

window.registerWizard = function () {
  return {
    step: 0,
    steps: ['Akun', 'Data Ibu', 'Riwayat', 'Suami & Anak'],
    ibu: { tanggal_lahir: '' },
    jumlahAnak: 0,
    anak: [{ nama:'', nik:'', tanggal_lahir:'', jenis_kelamin_id:'', no_jkn:'' }],
    next(){
      const sec = this.$refs['s'+this.step]
      const inputs = [...sec.querySelectorAll('input, select, textarea')]
      for (const el of inputs) { if (el.required && !el.value) { el.reportValidity(); el.focus(); return } }
      if (this.step < this.steps.length-1) this.step++
      if (this.step===3) this.syncAnakArray()
      window.scrollTo({ top: 0, behavior: 'smooth' })
    },
    prev(){ if (this.step>0){ this.step--; window.scrollTo({ top:0, behavior:'smooth' }) } },
    syncAnakArray(){
      const n = Number(this.jumlahAnak||0)
      if (n < 0) return
      while (this.anak.length < n) this.anak.push({ nama:'', nik:'', tanggal_lahir:'', jenis_kelamin_id:'', no_jkn:'' })
      while (this.anak.length > n) this.anak.pop()
    },
    addAnak(){ this.anak.push({ nama:'', nik:'', tanggal_lahir:'', jenis_kelamin_id:'', no_jkn:'' }); this.jumlahAnak = this.anak.length },
    removeAnak(i){ this.anak.splice(i,1); this.jumlahAnak = this.anak.length },
    hitungUmur(d){
      if (!d) return ''
      const t = new Date(d), n = new Date()
      let u = n.getFullYear()-t.getFullYear()
      const m = n.getMonth()-t.getMonth()
      if (m<0 || (m===0 && n.getDate()<t.getDate())) u--
      return u>=0 ? u : ''
    }
  }
}

window.updateWizard = function () {
  return {
    step: 0,
    steps: ['Akun', 'Data Ibu'],
    ibu: { tanggal_lahir: '' },
    jumlahAnak: 0,
    next(){
      const sec = this.$refs['s'+this.step]
      const inputs = [...sec.querySelectorAll('input, select, textarea')]
      for (const el of inputs) { if (el.required && !el.value) { el.reportValidity(); el.focus(); return } }
      if (this.step < this.steps.length-1) this.step++
      window.scrollTo({ top: 0, behavior: 'smooth' })
    },
    prev(){ if (this.step>0){ this.step--; window.scrollTo({ top:0, behavior:'smooth' }) } },
  }
}

Alpine.start() 

// --- Swup + Plugins ---
import Swup from 'swup'
import FormsPlugin from '@swup/forms-plugin'
import ScrollPlugin from '@swup/scroll-plugin'
import HeadPlugin from '@swup/head-plugin'
import PreloadPlugin from '@swup/preload-plugin'
import ScriptsPlugin from '@swup/scripts-plugin'
import ApexCharts from 'apexcharts';

window.ApexCharts = ApexCharts;

const ORIGIN = window.location.origin

if (window.__swup) { try { window.__swup.destroy() } catch {} window.__swup = null }

const INTERNAL = (sel) =>
  `${sel}:not([target]):not([download]):not([data-no-swup]):not([rel="external"])`

window.swup = new Swup({
  animateHistoryBrowsing: true, // animasi juga saat back/forward
  containers: ['#app-frame'],
  plugins: [
    new FormsPlugin({
      formSelector: [
        'form:not([target])',
        ':not([download])',
        ':not([data-no-swup])',
        ':not([enctype="multipart/form-data"])',
      ].join(''),
    }),
    new ScrollPlugin({ animateScroll: true }),
    new HeadPlugin({
      persistTags: (tag) => {
        const src  = tag.getAttribute?.('src')  || ''
        const href = tag.getAttribute?.('href') || ''
        return src.includes('/@vite/client') || href.includes('/@vite/client') ||
               src.includes('/build/') || href.includes('/build/') ||
               tag.getAttribute?.('rel') === 'modulepreload'
      },
      awaitAssets: true, // biar CSS/JS siap sebelum animasi masuk
    }),
    new PreloadPlugin({ preloadVisibleLinks: true, throttle: 5 }),
    new ScriptsPlugin({ head: false }),
  ],
  linkSelector: [
    INTERNAL(`a[href^="${ORIGIN}"]`), // absolute internal
    INTERNAL('a[href^="/"]'),         // relative internal
  ].join(', ')
})

window.__swup = swup

// Re-init Alpine setelah konten baru dirender
const reinit = () => {
  const el = document.getElementById('app-frame')
  if (!el || !window.Alpine) return
  requestAnimationFrame(() => window.Alpine.initTree(el))
}
swup.hooks.on('page:view', reinit)

// ---- Progress bar sederhana (mirip Livewire) ----
const bar = document.getElementById('swup-progress')

const progressStart = () => {
  if (!bar) return
  bar.style.transition = 'none'
  bar.style.width = '0%'
  bar.style.opacity = '1'
  // biar animasi width berjalan
  requestAnimationFrame(() => {
    bar.style.transition = 'width 1.2s ease, opacity .2s ease'
    bar.style.width = '80%'
  })
}

const progressEnd = () => {
  if (!bar) return
  bar.style.transition = 'width .2s ease, opacity .2s ease'
  bar.style.width = '100%'
  setTimeout(() => { bar.style.opacity = '0' }, 200)
}

function readSeed(id = 'dashboard-seed') {
  const el = document.getElementById(id);
  if (!el) return null;
  try { return JSON.parse(el.textContent || '{}'); }
  catch { return null; }
}

function hydrateDashboard() {
  const seed = readSeed();
  if (!seed) return;
  if (typeof window.initAll === 'function') {
    try { window.initAll(seed.epdsTrend || []); } catch {}
  }
}
hydrateDashboard();

function toggleFullscreenLayout() {
    if (window.innerWidth <= 768) {
        document.body.classList.add("fullscreen");
    } else {
        document.body.classList.remove("fullscreen");
    }
}
toggleFullscreenLayout();
window.addEventListener("resize", toggleFullscreenLayout);

swup.hooks.on('page:view', () => {hydrateDashboard();});
swup.hooks.on('page:view', () => {toggleFullscreenLayout();});

// swup.hooks.on('visit:start', () => { try { swup.cache.clear() } catch {} })
swup.hooks.on('visit:start',  progressStart) // klik/link/submit dimulai
swup.hooks.on('page:view',    progressEnd)   // halaman baru siap terlihat
swup.hooks.on('visit:end',    progressEnd)   // fallback selesai
 
