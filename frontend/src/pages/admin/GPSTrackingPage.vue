<template>
  <div class="h-screen flex flex-col bg-white overflow-hidden">
    <!-- Header -->
    <div class="flex items-center justify-between px-5 py-3 bg-white border-b border-gray-800 z-10 flex-shrink-0 text-black">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center">
          <Navigation class="w-4 h-4 text-white" />
        </div>
        <div>
          <h1 class="text-sm font-semibold text-black">GPS Tracking</h1>
          <p class="text-xs text-gray-500">{{ activeDrivers.length }} driver aktif</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <div class="flex items-center gap-1.5 bg-white rounded-lg px-3 py-1.5">
          <div class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse" />
          <span class="text-xs text-gray-400">Live</span>
        </div>
        <button @click="refreshAll"
          class="p-2 bg-white hover:bg-gray-700 rounded-lg transition">
          <RefreshCw :class="`w-4 h-4 text-gray-400 ${loading ? 'animate-spin' : ''}`" />
        </button>
      </div>
    </div>

    <div class="flex flex-1 overflow-hidden">
      <!-- Sidebar -->
      <div class="w-72 bg-white flex flex-col overflow-hidden flex-shrink-0">
        <!-- Search -->
        <div class="p-3 ">
          <div class="flex items-center gap-2 bg-white rounded-lg px-3 py-2">
            <Search class="w-3.5 h-3.5 text-gray-500" />
            <input v-model="search" placeholder="Cari driver..."
              class="bg-transparent text-xs text-gray-300 placeholder-gray-600 focus:outline-none flex-1" />
          </div>
        </div>

        <!-- Driver List -->
        <div class="flex-1 overflow-y-auto">
          <div v-if="loading" class="p-3 space-y-2">
            <div v-for="i in 3" :key="i" class="h-16 bg-white animate-pulse rounded-xl" />
          </div>

          <div v-else-if="filteredDrivers.length === 0" class="flex flex-col items-center justify-center h-40 text-gray-600">
            <Truck class="w-8 h-8 mb-2 opacity-30" />
            <p class="text-xs">Tidak ada driver aktif</p>
          </div>

          <div v-else class="p-2 space-y-1">
            <button v-for="driver in filteredDrivers" :key="driver.id"
              @click="selectDriver(driver)"
              :class="`w-full text-left p-3 rounded-xl transition-all ${
                selectedDriver?.id === driver.id
                  ? 'bg-blue-600/20 border border-blue-500/40'
                  : 'hover:bg-gray-800 border border-transparent'
              }`">
              <div class="flex items-center gap-2.5">
                <div class="relative flex-shrink-0">
                  <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xs font-bold">
                    {{ driver.name?.slice(0,2).toUpperCase() }}
                  </div>
                  <div :class="`absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-gray-900 ${
                    driver.is_online ? 'bg-green-400' : 'bg-gray-500'
                  }`" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-medium text-white truncate">{{ driver.name }}</p>
                  <p class="text-xs text-gray-500 truncate">
                    {{ driver.current_job_number ?? 'Tidak ada job aktif' }}
                  </p>
                </div>
                <div class="flex-shrink-0 text-right">
                  <p :class="`text-xs font-medium ${driver.is_online ? 'text-green-400' : 'text-gray-500'}`">
                    {{ driver.is_online ? 'Online' : 'Offline' }}
                  </p>
                  <p class="text-xs text-gray-600">{{ driver.speed_kmh ?? 0 }} km/h</p>
                </div>
              </div>

              <!-- Route preview when selected -->
              <div v-if="selectedDriver?.id === driver.id && selectedJobOrder"
                class="mt-2 pt-2 border-t border-blue-500/20">
                <div class="flex items-center gap-1.5 text-xs text-gray-400">
                  <MapPin class="w-3 h-3 text-green-400 flex-shrink-0" />
                  <span class="truncate">{{ selectedJobOrder.origin_city }}</span>
                </div>
                <div class="flex items-center gap-1.5 text-xs text-gray-400 mt-1">
                  <MapPin class="w-3 h-3 text-red-400 flex-shrink-0" />
                  <span class="truncate">{{ selectedJobOrder.destination_city }}</span>
                </div>
              </div>
            </button>
          </div>
        </div>

        <!-- Selected Driver Detail -->
        <div v-if="selectedDriver"
          class="border-t border-gray-800 p-3 bg-white backdrop-blur space-y-2">
          <p class="text-xs font-semibold text-gray-500">Detail Driver</p>
          <div class="grid grid-cols-2 gap-2">
            <div class="bg-white rounded-lg p-2 text-center">
              <p class="text-sm font-bold text-gray-800 ">{{ selectedDriver.speed_kmh ?? 0 }}</p>
              <p class="text-xs text-gray-500">km/h</p>
            </div>
            <div class="bg-white rounded-lg p-2 text-center">
              <p class="text-sm font-bold text-gray-700">{{ selectedDriver.heading ?? 0 }}°</p>
              <p class="text-xs text-gray-500">Heading</p>
            </div>
          </div>
          <div v-if="selectedJobOrder" class="bg-gray-800 rounded-lg p-2">
            <p class="text-xs text-gray-500 mb-1">Job Order</p>
            <p class="text-xs font-medium text-blue-400">{{ selectedJobOrder.job_number }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ selectedJobOrder.customer_name }}</p>
            <div class="mt-1.5 flex items-center gap-1">
              <span :class="`text-xs px-1.5 py-0.5 rounded font-medium ${statusColor(selectedJobOrder.status)}`">
                {{ selectedJobOrder.status }}
              </span>
            </div>
          </div>
          <button @click="loadRoute"
            :disabled="!selectedJobOrder || loadingRoute"
            class="w-full flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-medium py-2 rounded-lg transition">
            <Loader2 v-if="loadingRoute" class="w-3.5 h-3.5 animate-spin" />
            <Route v-else class="w-3.5 h-3.5" />
            {{ loadingRoute ? 'Memuat Rute...' : 'Tampilkan Rute' }}
          </button>
        </div>
      </div>

      <!-- Map -->
      <div class="flex-1 relative">
        <div id="tms-map" class="w-full h-full" />

        <!-- Map overlay: no selection -->
        <div v-if="!selectedDriver && !loading"
          class="absolute inset-0 flex items-center justify-center pointer-events-none">
          <div class="bg-gray-900/80 backdrop-blur rounded-2xl px-6 py-4 text-center border border-gray-700">
            <Truck class="w-8 h-8 text-gray-500 mx-auto mb-2" />
            <p class="text-sm text-gray-400">Klik driver untuk melihat posisi</p>
            <p class="text-xs text-gray-600 mt-1">Klik "Tampilkan Rute" untuk melihat jalur</p>
          </div>
        </div>

        <!-- Route info bar -->
        <div v-if="routeInfo"
          class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-gray-900/95 backdrop-blur border border-gray-700 rounded-2xl px-5 py-3 flex items-center gap-5 shadow-2xl">
          <div class="text-center">
            <p class="text-xs text-gray-500">Jarak</p>
            <p class="text-sm font-bold text-white">{{ routeInfo.distance }}</p>
          </div>
          <div class="w-px h-8 bg-gray-700" />
          <div class="text-center">
            <p class="text-xs text-gray-500">Estimasi</p>
            <p class="text-sm font-bold text-white">{{ routeInfo.duration }}</p>
          </div>
          <div class="w-px h-8 bg-gray-700" />
          <div class="text-center">
            <p class="text-xs text-gray-500">Via</p>
            <p class="text-sm font-bold text-white">OSM</p>
          </div>
          <button @click="clearRoute" class="ml-2 p-1.5 hover:bg-gray-800 rounded-lg transition">
            <X class="w-4 h-4 text-gray-400" />
          </button>
        </div>

        <!-- Loading overlay -->
        <div v-if="loadingRoute"
          class="absolute inset-0 bg-gray-950/50 flex items-center justify-center">
          <div class="bg-gray-900 border border-gray-700 rounded-xl px-5 py-3 flex items-center gap-3">
            <Loader2 class="w-4 h-4 text-blue-400 animate-spin" />
            <p class="text-sm text-gray-300">Memuat rute...</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import {
  Navigation, Truck, Search, MapPin, RefreshCw,
  Route, Loader2, X
} from 'lucide-vue-next'
import api from '@/lib/axios'

// ── Types ─────────────────────────────────────────────────
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

// ── State ─────────────────────────────────────────────────
const loading        = ref(false)
const loadingRoute   = ref(false)
const search         = ref('')
const activeDrivers  = ref<Driver[]>([])
const selectedDriver = ref<Driver | null>(null)
const selectedJobOrder = ref<any>(null)
const routeInfo      = ref<{ distance: string; duration: string } | null>(null)

let map: any = null
let L: any   = null
let markers: Record<number, any> = {}
let routeLayer: any = null
let pollTimer: any  = null

// ── Computed ──────────────────────────────────────────────
const filteredDrivers = computed(() =>
  activeDrivers.value.filter(d =>
    d.name.toLowerCase().includes(search.value.toLowerCase())
  )
)

// ── Leaflet Init ──────────────────────────────────────────
async function initMap() {
  // Dynamic import Leaflet (dari CDN via script tag)
  await loadLeafletCSS()
  await loadLeafletJS()
  L = (window as any).L

  await nextTick()
  const el = document.getElementById('tms-map')
  if (!el || map) return

  // Default center: Jakarta
  map = L.map('tms-map', { zoomControl: false }).setView([-6.2088, 106.8456], 11)

  // OpenStreetMap tiles (GRATIS)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(map)

  // Zoom control kanan bawah
  L.control.zoom({ position: 'bottomright' }).addTo(map)

  // ── SIAPAN GOOGLE MAPS: tinggal uncomment & hapus OSM tiles di atas ──
  // L.tileLayer(`https://maps.googleapis.com/maps/api/staticmap?center={lat},{lng}&zoom={z}&size=256x256&key=YOUR_GOOGLE_MAPS_API_KEY`, {
  //   maxZoom: 20,
  // }).addTo(map)
  // ATAU pakai @googlemaps/js-api-loader & replace map = L.map dengan google.maps.Map
}

function loadLeafletCSS(): Promise<void> {
  return new Promise(resolve => {
    if (document.getElementById('leaflet-css')) return resolve()
    const link = document.createElement('link')
    link.id   = 'leaflet-css'
    link.rel  = 'stylesheet'
    link.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css'
    link.onload = () => resolve()
    document.head.appendChild(link)
  })
}

function loadLeafletJS(): Promise<void> {
  return new Promise(resolve => {
    if ((window as any).L) return resolve()
    const script = document.createElement('script')
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js'
    script.onload = () => resolve()
    document.head.appendChild(script)
  })
}

// ── Truck Icon ────────────────────────────────────────────
function truckIcon(isOnline: boolean, isSelected: boolean) {
  const color  = isSelected ? '#3b82f6' : isOnline ? '#10b981' : '#6b7280'
  const border = isSelected ? '#93c5fd' : isOnline ? '#6ee7b7' : '#9ca3af'
  const svg = `
    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36">
      <circle cx="18" cy="18" r="17" fill="${color}" stroke="${border}" stroke-width="2" opacity="0.9"/>
      <g transform="translate(8,10)" fill="white">
        <rect x="0" y="4" width="13" height="8" rx="1.5"/>
        <path d="M13 6h4l3 4v2h-7V6z"/>
        <circle cx="4" cy="13" r="2" fill="${color}" stroke="white" stroke-width="1.5"/>
        <circle cx="16" cy="13" r="2" fill="${color}" stroke="white" stroke-width="1.5"/>
      </g>
    </svg>`
  return L.divIcon({
    html: svg,
    iconSize: [36, 36],
    iconAnchor: [18, 18],
    className: '',
  })
}

// ── Fetch Drivers ─────────────────────────────────────────
async function fetchActiveDrivers() {
  loading.value = true
  try {
    const res = await api.get('/gps/active-drivers')
    const raw = typeof res.data === 'string'
      ? JSON.parse(res.data.replace(/^=/, ''))
      : res.data
    activeDrivers.value = raw.data ?? raw ?? []
    updateMarkers()
  } catch {
    // fallback: ambil dari users driver
    try {
      const res2 = await api.get('/admin/users', { params: { role: 'driver', per_page: 50 } })
      const raw2 = typeof res2.data === 'string'
        ? JSON.parse(res2.data.replace(/^=/, ''))
        : res2.data
      activeDrivers.value = (raw2.data?.data ?? raw2.data ?? []).map((u: any) => ({
        id: u.id, name: u.name, is_online: false,
        lat: -6.2088 + (Math.random() - 0.5) * 0.1,
        lng: 106.8456 + (Math.random() - 0.5) * 0.1,
      }))
      updateMarkers()
    } catch {}
  } finally {
    loading.value = false
  }
}

// ── Update Map Markers ────────────────────────────────────
function updateMarkers() {
  if (!map || !L) return
  activeDrivers.value.forEach(driver => {
    const lat = driver.lat ?? -6.2088 + (Math.random()-0.5)*0.05
    const lng = driver.lng ?? 106.8456 + (Math.random()-0.5)*0.05
    const isSelected = selectedDriver.value?.id === driver.id

    if (markers[driver.id]) {
      markers[driver.id].setLatLng([lat, lng])
      markers[driver.id].setIcon(truckIcon(driver.is_online, isSelected))
    } else {
      const m = L.marker([lat, lng], { icon: truckIcon(driver.is_online, isSelected) })
        .addTo(map)
        .bindPopup(`<b>${driver.name}</b><br/>${driver.is_online ? '🟢 Online' : '⚫ Offline'}<br/>${driver.speed_kmh ?? 0} km/h`)
      m.on('click', () => selectDriver(driver))
      markers[driver.id] = m
    }
  })
}

// ── Select Driver ─────────────────────────────────────────
async function selectDriver(driver: Driver) {
  selectedDriver.value  = driver
  selectedJobOrder.value = null
  routeInfo.value       = null
  clearRoute()

  // Update semua icon
  Object.values(markers).forEach((m: any) => {
    const d = activeDrivers.value.find(dr => markers[dr.id] === m)
    if (d) m.setIcon(truckIcon(d.is_online, d.id === driver.id))
  })

  // Fly ke posisi driver
  if (map && driver.lat && driver.lng) {
    map.flyTo([driver.lat, driver.lng], 14, { duration: 1.2 })
  }

  // Ambil job order aktif driver
  if (driver.job_order_id || driver.current_job_number) {
    try {
      const res = await api.get('/job-orders', {
        params: { assigned_driver_id: driver.id, status: 'in_transit,in_progress,picked_up,assigned', per_page: 1 }
      })
      const raw = typeof res.data === 'string'
        ? JSON.parse(res.data.replace(/^=/, ''))
        : res.data
      const jobs = raw.data?.data ?? raw.data ?? []
      selectedJobOrder.value = jobs[0] ?? null
    } catch {}
  }
}

// ── Load Route (OSRM — gratis, no API key) ───────────────
async function loadRoute() {
  if (!selectedDriver.value || !selectedJobOrder.value) return
  loadingRoute.value = true
  clearRoute()

  try {
    const driver = selectedDriver.value
    const job    = selectedJobOrder.value

    // Koordinat origin & destination dari job order
    const originLat  = job.origin_lat  ?? -6.1751
    const originLng  = job.origin_lng  ?? 106.8272
    const destLat    = job.destination_lat  ?? -6.3149
    const destLng    = job.destination_lng  ?? 106.9132
    const driverLat  = driver.lat ?? originLat
    const driverLng  = driver.lng ?? originLng

    // OSRM public API (gratis, no key)
    // Format: /route/v1/{profile}/{lng,lat};{lng,lat}?overview=full&geometries=geojson
    const url = `https://router.project-osrm.org/route/v1/driving/${driverLng},${driverLat};${destLng},${destLat}?overview=full&geometries=geojson`

    const res  = await fetch(url)
    const data = await res.json()

    if (data.code !== 'Ok' || !data.routes?.length) throw new Error('No route')

    const route    = data.routes[0]
    const coords   = route.geometry.coordinates.map(([lng, lat]: number[]) => [lat, lng])
    const distKm   = (route.distance / 1000).toFixed(1)
    const durMin   = Math.round(route.duration / 60)
    const durText  = durMin >= 60 ? `${Math.floor(durMin/60)}j ${durMin%60}m` : `${durMin} mnt`

    // Draw route di map
    routeLayer = L.polyline(coords, {
      color: '#3b82f6', weight: 4, opacity: 0.85,
      dashArray: undefined,
    }).addTo(map)

    // Marker origin (hijau)
    L.circleMarker([originLat, originLng], {
      radius: 8, color: '#10b981', fillColor: '#10b981', fillOpacity: 1, weight: 2
    }).addTo(map).bindPopup(`<b>Origin</b><br/>${job.origin_address ?? job.origin_city}`)

    // Marker destination (merah)
    L.circleMarker([destLat, destLng], {
      radius: 8, color: '#ef4444', fillColor: '#ef4444', fillOpacity: 1, weight: 2
    }).addTo(map).bindPopup(`<b>Tujuan</b><br/>${job.destination_address ?? job.destination_city}`)

    // Fit bounds
    map.fitBounds(routeLayer.getBounds(), { padding: [40, 40] })

    routeInfo.value = { distance: `${distKm} km`, duration: durText }

    // ── SIAPAN GOOGLE MAPS DIRECTIONS API ──────────────────────
    // Uncomment ini & hapus OSRM fetch di atas saat API key sudah ada:
    //
    // const directionsService = new google.maps.DirectionsService()
    // const result = await directionsService.route({
    //   origin: { lat: driverLat, lng: driverLng },
    //   destination: { lat: destLat, lng: destLng },
    //   travelMode: google.maps.TravelMode.DRIVING,
    // })
    // const renderer = new google.maps.DirectionsRenderer()
    // renderer.setMap(googleMap)
    // renderer.setDirections(result)
    // routeInfo.value = {
    //   distance: result.routes[0].legs[0].distance.text,
    //   duration: result.routes[0].legs[0].duration.text,
    // }

  } catch (e) {
    console.error('Route error:', e)
    // Fallback: garis lurus
    if (selectedDriver.value && selectedJobOrder.value) {
      const job = selectedJobOrder.value
      const d   = selectedDriver.value
      const coords = [
        [d.lat ?? -6.2, d.lng ?? 106.8],
        [job.destination_lat ?? -6.31, job.destination_lng ?? 106.91]
      ]
      routeLayer = L.polyline(coords, {
        color: '#f97316', weight: 3, opacity: 0.7, dashArray: '8, 8'
      }).addTo(map)
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

// ── Helpers ───────────────────────────────────────────────
function statusColor(status: string) {
  const map: Record<string, string> = {
    pending: 'bg-yellow-500/20 text-yellow-400',
    assigned: 'bg-blue-500/20 text-blue-400',
    in_progress: 'bg-indigo-500/20 text-indigo-400',
    in_transit: 'bg-orange-500/20 text-orange-400',
    delivered: 'bg-teal-500/20 text-teal-400',
    completed: 'bg-green-500/20 text-green-400',
    cancelled: 'bg-red-500/20 text-red-400',
  }
  return map[status] ?? 'bg-gray-500/20 text-gray-400'
}

// ── Lifecycle ─────────────────────────────────────────────
onMounted(async () => {
  await initMap()
  await fetchActiveDrivers()
  // Poll setiap 30 detik
  pollTimer = setInterval(fetchActiveDrivers, 30_000)
})

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
  if (map) { map.remove(); map = null }
})
</script>

<style scoped>
#tms-map {
  background: #ffffff;
}
/* Override leaflet popup */
:deep(.leaflet-popup-content-wrapper) {
  background: #ffffff;
  color: #e5e7eb;
  border: 1px solid #374151;
  border-radius: 10px;
  font-size: 12px;
}
:deep(.leaflet-popup-tip) {
  background: #ffffff;
}
:deep(.leaflet-control-zoom a) {
  background: #ffffff !important;
  color: #e5e7eb !important;
  border-color: #374151 !important;
}
:deep(.leaflet-control-attribution) {
  background: rgba(255, 255, 255, 0.8) !important;
  color: #6b7280 !important;
  font-size: 9px;
}
</style>