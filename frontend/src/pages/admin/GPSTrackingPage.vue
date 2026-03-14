<template>
  <div class="gps-wrapper">

    <!-- Header -->
    <div class="gps-header">
      <div class="gps-header-left">
        <div class="gps-header-icon">
          <Navigation :size="16" color="white" />
        </div>
        <div>
          <h1 class="gps-header-title">GPS Tracking</h1>
          <p class="gps-header-sub">{{ activeDrivers.length }} driver aktif</p>
        </div>
      </div>
      <div class="gps-header-right">
        <div class="live-badge">
          <span class="live-dot" />
          <span class="live-text">Live</span>
        </div>
        <button class="refresh-btn" @click="refreshAll" :disabled="loading">
          <RefreshCw :size="15" :class="loading ? 'spin' : ''" />
        </button>
      </div>
    </div>

    <!-- Body: Map + Panel -->
    <div class="gps-body" @touchstart="onTouchStart" @touchend="onTouchEnd">

      <!-- MAP — full screen -->
      <div id="tms-map" class="gps-map" />

      <!-- Hint tengah saat belum pilih driver -->
      <transition name="fade">
        <div v-if="!selectedDriver && !loading" class="map-hint">
          <div class="map-hint-box">
            <Truck :size="28" color="#94a3b8" />
            <p class="map-hint-title">Pilih driver untuk melihat posisi</p>
            <p class="map-hint-sub">Klik tanda panah di sebelah kanan</p>
          </div>
        </div>
      </transition>

      <!-- Route info bar -->
      <transition name="slide-up">
        <div v-if="routeInfo" class="route-bar">
          <div class="route-bar-item">
            <span class="route-bar-label">Jarak</span>
            <span class="route-bar-val">{{ routeInfo.distance }}</span>
          </div>
          <div class="route-bar-divider" />
          <div class="route-bar-item">
            <span class="route-bar-label">Estimasi</span>
            <span class="route-bar-val">{{ routeInfo.duration }}</span>
          </div>
          <div class="route-bar-divider" />
          <div class="route-bar-item">
            <span class="route-bar-label">Via</span>
            <span class="route-bar-val">OSM</span>
          </div>
          <button class="route-bar-close" @click="clearRoute">
            <X :size="14" />
          </button>
        </div>
      </transition>

      <!-- Loading rute -->
      <div v-if="loadingRoute" class="route-loading">
        <div class="route-loading-box">
          <Loader2 :size="16" class="spin" color="#2563eb" />
          <span>Memuat rute...</span>
        </div>
      </div>

      <!-- Tombol toggle — selalu di ujung kanan layar -->
      <button
        :class="['panel-toggle', panelOpen ? 'panel-toggle-open' : '']"
        @click="togglePanel"
        :title="panelOpen ? 'Tutup panel' : 'Buka panel driver'"
      >
        <ChevronRight
          :size="15"
          :style="{
            transform: panelOpen ? 'rotate(0deg)' : 'rotate(180deg)',
            transition: 'transform 0.3s'
          }"
        />
        <span class="panel-toggle-label">DRIVER</span>
      </button>

      <!-- Panel driver — slide dari kanan -->
      <div :class="['panel-body', panelOpen ? 'panel-open' : 'panel-closed']">

        <!-- Head -->
        <div class="panel-head">
          <p class="panel-section-title">Driver Aktif</p>
          <div class="panel-search">
            <Search :size="13" color="#94a3b8" />
            <input
              v-model="search"
              placeholder="Cari driver..."
              class="panel-search-input"
            />
          </div>
        </div>

        <!-- Filter chips -->
        <div class="panel-filters">
          <button
            v-for="f in filterOptions"
            :key="f.value"
            :class="['filter-chip', activeFilter === f.value ? 'filter-chip-active' : '']"
            @click="activeFilter = f.value"
          >
            {{ f.label }}
          </button>
        </div>

        <!-- List driver -->
        <div class="panel-list">

          <!-- Skeleton loading -->
          <div v-if="loading" class="panel-skeleton">
            <div v-for="i in 4" :key="i" class="skeleton-item" />
          </div>

          <!-- Kosong -->
          <div v-else-if="filteredDrivers.length === 0" class="panel-empty">
            <Truck :size="28" color="#cbd5e1" />
            <p>Tidak ada driver aktif</p>
          </div>

          <!-- Driver items -->
          <template v-else>
            <button
              v-for="driver in filteredDrivers"
              :key="driver.id"
              :class="['driver-item', selectedDriver?.id === driver.id ? 'driver-item-active' : '']"
              @click="selectDriver(driver)"
            >
              <div class="driver-item-row">
                <div class="driver-avatar">
                  <span class="driver-avatar-text">{{ driver.name?.slice(0, 2).toUpperCase() }}</span>
                  <span :class="['driver-dot', driver.is_online ? 'dot-online' : 'dot-offline']" />
                </div>
                <div class="driver-info">
                  <p class="driver-name">{{ driver.name }}</p>
                  <p class="driver-job">{{ driver.current_job_number ?? 'Tidak ada job aktif' }}</p>
                </div>
                <div class="driver-status-col">
                  <span :class="['driver-status-text', driver.is_online ? 'status-online' : 'status-offline']">
                    {{ driver.is_online ? 'Online' : 'Offline' }}
                  </span>
                  <span class="driver-speed-text">{{ driver.speed_kmh ?? 0 }} km/h</span>
                </div>
              </div>

              <!-- Route preview saat driver dipilih -->
              <div v-if="selectedDriver?.id === driver.id && selectedJobOrder" class="route-preview">
                <div class="route-pt">
                  <MapPin :size="11" color="#16a34a" />
                  <span>{{ selectedJobOrder.origin_city }}</span>
                </div>
                <div class="route-pt">
                  <MapPin :size="11" color="#dc2626" />
                  <span>{{ selectedJobOrder.destination_city }}</span>
                </div>
              </div>
            </button>
          </template>

        </div>

        <!-- Detail footer driver terpilih -->
        <div v-if="selectedDriver" class="panel-detail">
          <p class="panel-section-title">Detail Driver</p>
          <div class="detail-grid">
            <div class="detail-card">
              <span class="detail-val">{{ selectedDriver.speed_kmh ?? 0 }}</span>
              <span class="detail-label">km/h</span>
            </div>
            <div class="detail-card">
              <span class="detail-val">{{ selectedDriver.heading ?? 0 }}°</span>
              <span class="detail-label">Heading</span>
            </div>
          </div>

          <div v-if="selectedJobOrder" class="detail-job-card">
            <p class="detail-micro-label">Job Order</p>
            <p class="detail-job-num">{{ selectedJobOrder.job_number }}</p>
            <p class="detail-job-cust">{{ selectedJobOrder.customer_name }}</p>
            <span :class="['status-badge', statusBadgeClass(selectedJobOrder.status)]">
              {{ selectedJobOrder.status }}
            </span>
          </div>

          <button
            class="route-btn"
            @click="loadRoute"
            :disabled="!selectedJobOrder || loadingRoute"
          >
            <Loader2 v-if="loadingRoute" :size="13" class="spin" />
            <RouteIcon v-else :size="13" />
            {{ loadingRoute ? 'Memuat Rute...' : 'Tampilkan Rute' }}
          </button>
        </div>

      </div>
      <!-- end panel-body -->

    </div>
    <!-- end gps-body -->

  </div>
  <!-- end gps-wrapper -->
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import {
  Navigation,
  Truck,
  Search,
  MapPin,
  RefreshCw,
  Route as RouteIcon,
  Loader2,
  X,
  ChevronRight,
} from 'lucide-vue-next'
import api from '@/lib/axios'

// ── Types ──────────────────────────────────────────────────
interface Driver {
  id: number
  name: string
  is_online: boolean
  lat?: number
  lng?: number
  speed_kmh?: number
  heading?: number
  current_job_number?: string
  job_order_id?: number
}

// ── State ──────────────────────────────────────────────────
const loading          = ref(false)
const loadingRoute     = ref(false)
const search           = ref('')
const activeFilter     = ref<'all' | 'online' | 'offline'>('all')
const panelOpen        = ref(false)
const activeDrivers    = ref<Driver[]>([])
const selectedDriver   = ref<Driver | null>(null)
const selectedJobOrder = ref<any>(null)
const routeInfo        = ref<{ distance: string; duration: string } | null>(null)

let map: any                     = null
let L: any                       = null
let markers: Record<number, any> = {}
let routeLayer: any              = null
let pollTimer: any               = null
let touchStartX                  = 0

const filterOptions = [
  { label: 'Semua',   value: 'all'     },
  { label: 'Online',  value: 'online'  },
  { label: 'Offline', value: 'offline' },
] as const

// ── Computed ───────────────────────────────────────────────
const filteredDrivers = computed(() =>
  activeDrivers.value.filter(d => {
    const matchSearch = d.name.toLowerCase().includes(search.value.toLowerCase())
    const matchFilter =
      activeFilter.value === 'all'    ? true :
      activeFilter.value === 'online' ? d.is_online : !d.is_online
    return matchSearch && matchFilter
  })
)

// ── Panel ──────────────────────────────────────────────────
function togglePanel() {
  panelOpen.value = !panelOpen.value
}

// ── Swipe ──────────────────────────────────────────────────
function onTouchStart(e: TouchEvent) {
  touchStartX = e.touches[0].clientX
}
function onTouchEnd(e: TouchEvent) {
  const dx = e.changedTouches[0].clientX - touchStartX
  if (Math.abs(dx) < 40) return
  if (dx < 0 && !panelOpen.value) panelOpen.value = true
  if (dx > 0 && panelOpen.value)  panelOpen.value = false
}

// ── Leaflet ────────────────────────────────────────────────
async function initMap() {
  await loadLeafletCSS()
  await loadLeafletJS()
  L = (window as any).L
  await nextTick()
  const el = document.getElementById('tms-map')
  if (!el || map) return
  map = L.map('tms-map', { zoomControl: false }).setView([-6.2088, 106.8456], 11)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(map)
  L.control.zoom({ position: 'bottomright' }).addTo(map)
}

function loadLeafletCSS(): Promise<void> {
  return new Promise(resolve => {
    if (document.getElementById('leaflet-css')) return resolve()
    const link   = document.createElement('link')
    link.id      = 'leaflet-css'
    link.rel     = 'stylesheet'
    link.href    = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css'
    link.onload  = () => resolve()
    document.head.appendChild(link)
  })
}

function loadLeafletJS(): Promise<void> {
  return new Promise(resolve => {
    if ((window as any).L) return resolve()
    const script    = document.createElement('script')
    script.src      = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js'
    script.onload   = () => resolve()
    document.head.appendChild(script)
  })
}

// ── Truck icon ─────────────────────────────────────────────
function truckIcon(isOnline: boolean, isSelected: boolean) {
  const fill   = isSelected ? '#2563eb' : isOnline ? '#16a34a' : '#9ca3af'
  const stroke = isSelected ? '#93c5fd' : isOnline ? '#86efac' : '#d1d5db'
  const svg = `
    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36">
      <circle cx="18" cy="18" r="17" fill="${fill}" stroke="${stroke}" stroke-width="2" opacity="0.95"/>
      <g transform="translate(8,10)" fill="white">
        <rect x="0" y="4" width="13" height="8" rx="1.5"/>
        <path d="M13 6h4l3 4v2h-7V6z"/>
        <circle cx="4"  cy="13" r="2" fill="${fill}" stroke="white" stroke-width="1.5"/>
        <circle cx="16" cy="13" r="2" fill="${fill}" stroke="white" stroke-width="1.5"/>
      </g>
    </svg>`
  return L.divIcon({ html: svg, iconSize: [36, 36], iconAnchor: [18, 18], className: '' })
}

// ── Fetch drivers ──────────────────────────────────────────
async function fetchActiveDrivers() {
  loading.value = true
  try {
    const res = await api.get('/gps/active-drivers')
    const raw = typeof res.data === 'string' ? JSON.parse(res.data.replace(/^=/, '')) : res.data
    activeDrivers.value = raw.data ?? raw ?? []
    updateMarkers()
  } catch {
    try {
      const res2 = await api.get('/admin/users', { params: { role: 'driver', per_page: 50 } })
      const raw2 = typeof res2.data === 'string' ? JSON.parse(res2.data.replace(/^=/, '')) : res2.data
      activeDrivers.value = (raw2.data?.data ?? raw2.data ?? []).map((u: any) => ({
        id: u.id,
        name: u.name,
        is_online: false,
        lat: -6.2088 + (Math.random() - 0.5) * 0.1,
        lng: 106.8456 + (Math.random() - 0.5) * 0.1,
      }))
      updateMarkers()
    } catch { /* silent */ }
  } finally {
    loading.value = false
  }
}

// ── Markers ────────────────────────────────────────────────
function updateMarkers() {
  if (!map || !L) return
  activeDrivers.value.forEach(driver => {
    const lat   = driver.lat ?? -6.2088 + (Math.random() - 0.5) * 0.05
    const lng   = driver.lng ?? 106.8456 + (Math.random() - 0.5) * 0.05
    const isSel = selectedDriver.value?.id === driver.id

    if (markers[driver.id]) {
      markers[driver.id].setLatLng([lat, lng])
      markers[driver.id].setIcon(truckIcon(driver.is_online, isSel))
    } else {
      const m = L.marker([lat, lng], { icon: truckIcon(driver.is_online, isSel) })
        .addTo(map)
        .bindPopup(
          `<b>${driver.name}</b><br/>` +
          `${driver.is_online ? '🟢 Online' : '⚫ Offline'}<br/>` +
          `${driver.speed_kmh ?? 0} km/h`
        )
      m.on('click', () => selectDriver(driver))
      markers[driver.id] = m
    }
  })
}

// ── Select driver ──────────────────────────────────────────
async function selectDriver(driver: Driver) {
  selectedDriver.value   = driver
  selectedJobOrder.value = null
  routeInfo.value        = null
  clearRoute()

  Object.values(markers).forEach((m: any) => {
    const d = activeDrivers.value.find(dr => markers[dr.id] === m)
    if (d) m.setIcon(truckIcon(d.is_online, d.id === driver.id))
  })

  if (map && driver.lat && driver.lng) {
    map.flyTo([driver.lat, driver.lng], 14, { duration: 1.2 })
  }

  if (driver.job_order_id || driver.current_job_number) {
    try {
      const res = await api.get('/job-orders', {
        params: {
          assigned_driver_id: driver.id,
          status: 'in_transit,in_progress,picked_up,assigned',
          per_page: 1,
        },
      })
      const raw = typeof res.data === 'string' ? JSON.parse(res.data.replace(/^=/, '')) : res.data
      selectedJobOrder.value = (raw.data?.data ?? raw.data ?? [])[0] ?? null
    } catch { /* silent */ }
  }
}

// ── Load route (OSRM) ──────────────────────────────────────
async function loadRoute() {
  if (!selectedDriver.value || !selectedJobOrder.value) return
  loadingRoute.value = true
  clearRoute()

  try {
    const driver = selectedDriver.value
    const job    = selectedJobOrder.value
    const oLat   = job.origin_lat      ?? -6.1751
    const oLng   = job.origin_lng      ?? 106.8272
    const dLat   = job.destination_lat ?? -6.3149
    const dLng   = job.destination_lng ?? 106.9132
    const vLat   = driver.lat ?? oLat
    const vLng   = driver.lng ?? oLng

    const res  = await fetch(
      `https://router.project-osrm.org/route/v1/driving/` +
      `${vLng},${vLat};${dLng},${dLat}?overview=full&geometries=geojson`
    )
    const data = await res.json()
    if (data.code !== 'Ok' || !data.routes?.length) throw new Error('No route')

    const route   = data.routes[0]
    const coords  = route.geometry.coordinates.map(([lng, lat]: number[]) => [lat, lng])
    const distKm  = (route.distance / 1000).toFixed(1)
    const durMin  = Math.round(route.duration / 60)
    const durText = durMin >= 60
      ? `${Math.floor(durMin / 60)}j ${durMin % 60}m`
      : `${durMin} mnt`

    routeLayer = L.polyline(coords, { color: '#2563eb', weight: 4, opacity: 0.85 }).addTo(map)

    L.circleMarker([oLat, oLng], {
      radius: 8, color: '#16a34a', fillColor: '#16a34a', fillOpacity: 1, weight: 2,
    }).addTo(map).bindPopup(`<b>Origin</b><br/>${job.origin_address ?? job.origin_city}`)

    L.circleMarker([dLat, dLng], {
      radius: 8, color: '#dc2626', fillColor: '#dc2626', fillOpacity: 1, weight: 2,
    }).addTo(map).bindPopup(`<b>Tujuan</b><br/>${job.destination_address ?? job.destination_city}`)

    map.fitBounds(routeLayer.getBounds(), { padding: [50, 50] })
    routeInfo.value = { distance: `${distKm} km`, duration: durText }

  } catch {
    // Fallback garis lurus
    if (selectedDriver.value && selectedJobOrder.value) {
      const j = selectedJobOrder.value
      const d = selectedDriver.value
      routeLayer = L.polyline(
        [
          [d.lat ?? -6.2,  d.lng ?? 106.8],
          [j.destination_lat ?? -6.31, j.destination_lng ?? 106.91],
        ],
        { color: '#f97316', weight: 3, opacity: 0.7, dashArray: '8,8' }
      ).addTo(map)
      routeInfo.value = { distance: 'N/A', duration: 'Estimasi tidak tersedia' }
    }
  } finally {
    loadingRoute.value = false
  }
}

function clearRoute() {
  if (routeLayer && map) {
    map.removeLayer(routeLayer)
    routeLayer = null
  }
  routeInfo.value = null
}

async function refreshAll() {
  await fetchActiveDrivers()
}

function statusBadgeClass(status: string): string {
  const statusMap: Record<string, string> = {
    pending:     'badge-yellow',
    assigned:    'badge-blue',
    in_progress: 'badge-indigo',
    in_transit:  'badge-orange',
    delivered:   'badge-teal',
    completed:   'badge-green',
    cancelled:   'badge-red',
  }
  return statusMap[status] ?? 'badge-gray'
}

// ── Lifecycle ──────────────────────────────────────────────
onMounted(async () => {
  await initMap()
  await fetchActiveDrivers()
  pollTimer = setInterval(fetchActiveDrivers, 30_000)
})

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
  if (map) { map.remove(); map = null }
})
</script>

<style scoped>
/* ── Root ── */
.gps-wrapper {
  display: flex;
  flex-direction: column;
  width: 100%;
  height: 100%;
  background: #f8fafc;
  overflow: hidden;
}

/* ══ HEADER ══════════════════════════════════════════════ */
.gps-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 20px;
  background: #ffffff;
  border-bottom: 1px solid #e2e8f0;
  flex-shrink: 0;
  z-index: 10;
}
.gps-header-left { display: flex; align-items: center; gap: 10px; }
.gps-header-icon {
  width: 32px; height: 32px; border-radius: 8px;
  background: #2563eb;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.gps-header-title { font-size: 14px; font-weight: 600; color: #0f172a; }
.gps-header-sub   { font-size: 12px; color: #64748b; margin-top: 1px; }
.gps-header-right { display: flex; align-items: center; gap: 8px; }

.live-badge {
  display: flex; align-items: center; gap: 6px;
  background: #f0fdf4; border: 1px solid #bbf7d0;
  border-radius: 20px; padding: 4px 10px;
}
.live-dot  { width: 7px; height: 7px; border-radius: 50%; background: #22c55e; animation: blink 1.4s infinite; }
.live-text { font-size: 11px; color: #16a34a; font-weight: 500; }
@keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }

.refresh-btn {
  display: flex; align-items: center; justify-content: center;
  width: 32px; height: 32px;
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;
  cursor: pointer; transition: background 0.15s; color: #64748b;
}
.refresh-btn:hover:not(:disabled) { background: #f1f5f9; }
.refresh-btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* ══ BODY ════════════════════════════════════════════════ */
.gps-body { flex: 1; position: relative; overflow: hidden; }

/* ── MAP ── */
.gps-map {
  position: absolute; inset: 0;
  width: 100%; height: 100%;
  z-index: 0; background: #e2e8f0;
}

/* ── Map hint ── */
.map-hint {
  position: absolute; inset: 0; z-index: 5;
  display: flex; align-items: center; justify-content: center;
  pointer-events: none;
}
.map-hint-box {
  background: rgba(255, 255, 255, 0.93);
  backdrop-filter: blur(8px);
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 24px 32px;
  text-align: center;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
}
.map-hint-title { font-size: 14px; font-weight: 500; color: #334155; margin-top: 10px; }
.map-hint-sub   { font-size: 12px; color: #94a3b8; margin-top: 4px; }

/* ── Route bar ── */
.route-bar {
  position: absolute; bottom: 20px; left: 50%;
  transform: translateX(-50%);
  z-index: 15;
  background: rgba(255, 255, 255, 0.97);
  backdrop-filter: blur(8px);
  border: 1px solid #e2e8f0; border-radius: 16px;
  padding: 12px 20px;
  display: flex; align-items: center; gap: 16px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.1);
  white-space: nowrap;
}
.route-bar-item    { display: flex; flex-direction: column; align-items: center; }
.route-bar-label   { font-size: 10px; color: #94a3b8; }
.route-bar-val     { font-size: 13px; font-weight: 600; color: #0f172a; margin-top: 2px; }
.route-bar-divider { width: 1px; height: 28px; background: #e2e8f0; }
.route-bar-close {
  display: flex; align-items: center; justify-content: center;
  width: 26px; height: 26px; border-radius: 6px;
  background: #f1f5f9; border: 1px solid #e2e8f0;
  cursor: pointer; color: #64748b; margin-left: 4px;
}
.route-bar-close:hover { background: #e2e8f0; }

/* ── Route loading overlay ── */
.route-loading {
  position: absolute; inset: 0; z-index: 20;
  background: rgba(248, 250, 252, 0.55);
  display: flex; align-items: center; justify-content: center;
}
.route-loading-box {
  background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px;
  padding: 12px 20px;
  display: flex; align-items: center; gap: 10px;
  font-size: 13px; color: #334155;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

/* ══ TOGGLE BUTTON ═══════════════════════════════════════ */
/*
  Selalu menempel di ujung KANAN layar.
  Saat panel terbuka (panel-toggle-open), geser ke kiri 300px
  mengikuti lebar panel, animasi sama dengan panel.
*/
.panel-toggle {
  position: absolute;
  top: 50%;
  right: 0;
  transform: translateY(-50%);
  z-index: 25;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 4px;
  width: 24px; height: 72px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-right: none;
  border-radius: 10px 0 0 10px;
  cursor: pointer; color: #64748b;
  box-shadow: -3px 0 12px rgba(0, 0, 0, 0.08);
  transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1),
              background 0.15s,
              color 0.15s;
}
.panel-toggle-open        { right: 300px; }
.panel-toggle:hover       { background: #eff6ff; color: #2563eb; }
.panel-toggle-label {
  font-size: 8px; letter-spacing: 0.1em; color: #94a3b8;
  writing-mode: vertical-rl;
}

/* ══ PANEL BODY ══════════════════════════════════════════ */
.panel-body {
  position: absolute; top: 0; right: 0; bottom: 0;
  width: 300px;
  z-index: 20;
  background: #ffffff;
  border-left: 1px solid #e2e8f0;
  display: flex; flex-direction: column;
  box-shadow: -4px 0 24px rgba(0, 0, 0, 0.07);
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  will-change: transform;
}
.panel-open   { transform: translateX(0); }
.panel-closed { transform: translateX(100%); }

/* ── Panel head ── */
.panel-head { padding: 14px 14px 10px; border-bottom: 1px solid #f1f5f9; flex-shrink: 0; }
.panel-section-title {
  font-size: 10px; font-weight: 700; color: #94a3b8;
  letter-spacing: 0.08em; text-transform: uppercase;
  margin-bottom: 8px;
}
.panel-search {
  display: flex; align-items: center; gap: 7px;
  background: #f8fafc; border: 1px solid #e2e8f0;
  border-radius: 8px; padding: 7px 10px;
}
.panel-search-input {
  background: none; border: none; outline: none;
  font-size: 12px; color: #334155; width: 100%;
}
.panel-search-input::placeholder { color: #cbd5e1; }

/* ── Filter chips ── */
.panel-filters {
  display: flex; gap: 6px; padding: 8px 14px;
  border-bottom: 1px solid #f1f5f9; flex-shrink: 0;
}
.filter-chip {
  font-size: 11px; padding: 3px 10px; border-radius: 20px;
  border: 1px solid #e2e8f0; color: #64748b;
  background: transparent; cursor: pointer; transition: all 0.15s;
}
.filter-chip:hover       { border-color: #cbd5e1; color: #334155; }
.filter-chip-active      { background: #eff6ff; border-color: #bfdbfe; color: #2563eb; }

/* ── Driver list ── */
.panel-list { flex: 1; overflow-y: auto; padding: 6px; }
.panel-list::-webkit-scrollbar       { width: 3px; }
.panel-list::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 2px; }

.panel-skeleton { display: flex; flex-direction: column; gap: 6px; padding: 4px; }
.skeleton-item  {
  height: 58px; background: #f1f5f9; border-radius: 10px;
  animation: shimmer 1.4s infinite;
}
@keyframes shimmer { 0%, 100% { opacity: 1; } 50% { opacity: 0.55; } }

.panel-empty {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; height: 160px; gap: 8px;
  color: #cbd5e1; font-size: 12px;
}

/* ── Driver item ── */
.driver-item {
  width: 100%; text-align: left; padding: 10px;
  border-radius: 10px; border: 1px solid transparent;
  cursor: pointer; transition: all 0.15s;
  margin-bottom: 2px; background: transparent;
}
.driver-item:hover    { background: #f8fafc; border-color: #e2e8f0; }
.driver-item-active   { background: #eff6ff; border-color: #bfdbfe; }
.driver-item-row      { display: flex; align-items: center; gap: 10px; }

.driver-avatar {
  position: relative; width: 34px; height: 34px;
  border-radius: 50%; flex-shrink: 0;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  display: flex; align-items: center; justify-content: center;
}
.driver-avatar-text { font-size: 11px; font-weight: 700; color: #ffffff; }
.driver-dot {
  position: absolute; bottom: -1px; right: -1px;
  width: 10px; height: 10px; border-radius: 50%;
  border: 2px solid #ffffff;
}
.dot-online  { background: #22c55e; }
.dot-offline { background: #cbd5e1; }

.driver-info { flex: 1; min-width: 0; }
.driver-name {
  font-size: 12px; font-weight: 600; color: #0f172a;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.driver-job {
  font-size: 11px; color: #94a3b8; margin-top: 1px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.driver-status-col  { flex-shrink: 0; text-align: right; }
.driver-status-text { font-size: 11px; font-weight: 500; display: block; }
.status-online      { color: #16a34a; }
.status-offline     { color: #94a3b8; }
.driver-speed-text  { font-size: 10px; color: #cbd5e1; margin-top: 2px; display: block; }

.route-preview {
  margin-top: 8px; padding-top: 8px;
  border-top: 1px solid #eff6ff;
  display: flex; flex-direction: column; gap: 4px;
}
.route-pt { display: flex; align-items: center; gap: 5px; font-size: 11px; color: #64748b; }

/* ── Detail footer ── */
.panel-detail {
  border-top: 1px solid #f1f5f9;
  padding: 12px 14px;
  flex-shrink: 0;
  background: #fafbfc;
}

.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 8px; }
.detail-card {
  background: #ffffff; border: 1px solid #e2e8f0;
  border-radius: 8px; padding: 8px; text-align: center;
}
.detail-val   { display: block; font-size: 16px; font-weight: 700; color: #0f172a; }
.detail-label { display: block; font-size: 10px; color: #94a3b8; margin-top: 2px; }

.detail-job-card {
  background: #ffffff; border: 1px solid #e2e8f0;
  border-radius: 8px; padding: 10px; margin-bottom: 8px;
}
.detail-micro-label { font-size: 10px; color: #94a3b8; margin-bottom: 3px; }
.detail-job-num     { font-size: 12px; font-weight: 600; color: #2563eb; }
.detail-job-cust    { font-size: 11px; color: #64748b; margin-top: 2px; }

.status-badge {
  display: inline-block; margin-top: 6px;
  font-size: 10px; font-weight: 600;
  padding: 2px 8px; border-radius: 20px;
}
.badge-yellow { background: #fef9c3; color: #854d0e; }
.badge-blue   { background: #dbeafe; color: #1d4ed8; }
.badge-indigo { background: #e0e7ff; color: #3730a3; }
.badge-orange { background: #ffedd5; color: #9a3412; }
.badge-teal   { background: #ccfbf1; color: #0f766e; }
.badge-green  { background: #dcfce7; color: #15803d; }
.badge-red    { background: #fee2e2; color: #b91c1c; }
.badge-gray   { background: #f1f5f9; color: #475569; }

.route-btn {
  display: flex; align-items: center; justify-content: center; gap: 6px;
  width: 100%; padding: 9px;
  background: #2563eb; color: #ffffff;
  font-size: 12px; font-weight: 600;
  border: none; border-radius: 8px;
  cursor: pointer; transition: background 0.15s;
}
.route-btn:hover:not(:disabled) { background: #1d4ed8; }
.route-btn:disabled { opacity: 0.45; cursor: not-allowed; }

/* ══ TRANSITIONS ══════════════════════════════════════════ */
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }

.slide-up-enter-active, .slide-up-leave-active {
  transition: opacity 0.25s, transform 0.25s;
}
.slide-up-enter-from, .slide-up-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(12px);
}

/* ── Spin utility ── */
.spin { animation: rotating 0.8s linear infinite; }
@keyframes rotating { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* ══ LEAFLET OVERRIDES — light theme ══════════════════════ */
:deep(.leaflet-popup-content-wrapper) {
  background: #ffffff;
  color: #1e293b;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 12px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}
:deep(.leaflet-popup-tip)           { background: #ffffff; }
:deep(.leaflet-control-zoom a)      { background: #ffffff !important; color: #334155 !important; border-color: #e2e8f0 !important; }
:deep(.leaflet-control-attribution) { background: rgba(255, 255, 255, 0.85) !important; color: #94a3b8 !important; font-size: 9px; }
</style>