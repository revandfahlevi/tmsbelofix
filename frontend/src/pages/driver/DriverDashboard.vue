<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-2xl font-bold">Dashboard Driver</h1>
      <p class="text-sm text-gray-500 mt-1">Selamat datang, {{ auth.user?.name }}!</p>
    </div>

    <!-- GPS Tracking Banner -->
    <div :class="`rounded-xl border p-4 shadow-sm transition ${
      gps.isTracking ? 'bg-green-50 border-green-200' : 'bg-white border-gray-100'
    }`">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div :class="`w-10 h-10 rounded-xl flex items-center justify-center ${
            gps.isTracking ? 'bg-green-500' : 'bg-gray-200'
          }`">
            <Navigation :class="`w-5 h-5 ${gps.isTracking ? 'text-white' : 'text-gray-500'}`" />
          </div>
          <div>
            <p class="font-semibold text-sm text-gray-800">GPS Tracking</p>
            <p class="text-xs text-gray-500 mt-0.5">
              <span v-if="gps.isTracking" class="text-green-600 font-medium">
                ● Aktif · {{ gps.lat?.toFixed(5) }}, {{ gps.lng?.toFixed(5) }}
                <span v-if="gps.speed !== null"> · {{ gps.speed }} km/h</span>
              </span>
              <span v-else-if="gps.error" class="text-red-500">{{ gps.error }}</span>
              <span v-else>Belum aktif — aktifkan untuk kirim lokasi</span>
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span v-if="gps.isTracking" class="text-xs text-green-600">
            Update {{ gps.lastUpdate ?? '-' }}
          </span>
          <button @click="toggleGps" :disabled="gps.loading"
            :class="`px-4 py-2 rounded-xl text-sm font-medium transition flex items-center gap-2 ${
              gps.isTracking
                ? 'bg-red-100 text-red-600 hover:bg-red-200'
                : 'bg-green-600 text-white hover:bg-green-700'
            } disabled:opacity-50`">
            <Loader2 v-if="gps.loading" class="w-4 h-4 animate-spin" />
            <Navigation v-else class="w-4 h-4" />
            {{ gps.isTracking ? 'Stop GPS' : 'Aktifkan GPS' }}
          </button>
        </div>
      </div>
      <div v-if="gps.isTracking && activeJobs.length > 0"
        class="mt-3 pt-3 border-t border-green-200">
        <p class="text-xs text-gray-500 mb-2">Kirim lokasi untuk job:</p>
        <div class="flex flex-wrap gap-2">
          <button v-for="job in activeJobs" :key="job.id"
            @click="gps.activeJobOrderId = job.id"
            :class="`px-3 py-1 rounded-lg text-xs font-medium transition ${
              gps.activeJobOrderId === job.id
                ? 'bg-green-600 text-white'
                : 'bg-white border border-green-200 text-green-700 hover:bg-green-50'
            }`">
            {{ job.job_number }}
          </button>
        </div>
      </div>
    </div>

    <!-- Status Driver -->
    <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold">Status Saya</h2>
        <div class="flex items-center gap-2">
          <span :class="`px-3 py-1 rounded-full text-xs font-medium ${statusClass(currentStatus)}`">
            {{ statusLabel(currentStatus) }}
          </span>
          <Loader2 v-if="updatingStatus" class="w-3.5 h-3.5 animate-spin text-gray-400" />
        </div>
      </div>
      <div class="flex gap-2 flex-wrap">
        <button v-for="s in driverStatuses" :key="s.value"
          @click="updateDriverStatus(s.value)"
          :disabled="updatingStatus || currentStatus === s.value"
          :class="`px-4 py-2 rounded-lg text-sm font-medium transition ${
            currentStatus === s.value
              ? 'bg-blue-600 text-white'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          } disabled:opacity-50`">
          {{ s.label }}
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div v-for="stat in stats" :key="stat.label"
        class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
        <div class="flex items-center justify-between">
          <p class="text-xs text-gray-500">{{ stat.label }}</p>
          <div :class="`w-8 h-8 rounded-lg ${stat.bg} flex items-center justify-center`">
            <component :is="stat.icon" :class="`w-4 h-4 ${stat.color}`" />
          </div>
        </div>
        <p class="text-2xl font-bold mt-2">
          <span v-if="loadingJobs" class="inline-block w-10 h-6 bg-gray-200 animate-pulse rounded" />
          <span v-else>{{ stat.value }}</span>
        </p>
      </div>
    </div>

    <!-- Tab Filter Jobs -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="flex border-b border-gray-100">
        <button v-for="tab in tabs" :key="tab.value"
          @click="activeTab = tab.value"
          :class="`flex-1 py-3 text-sm font-medium transition relative ${
            activeTab === tab.value
              ? 'text-blue-600 bg-blue-50'
              : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'
          }`">
          {{ tab.label }}
          <span v-if="tabCount(tab.value) > 0"
            :class="`ml-1 text-xs px-1.5 py-0.5 rounded-full ${
              activeTab === tab.value
                ? 'bg-blue-600 text-white'
                : 'bg-gray-200 text-gray-600'
            }`">
            {{ tabCount(tab.value) }}
          </span>
          <div v-if="activeTab === tab.value"
            class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600" />
        </button>
      </div>

      <div class="p-4">
        <div v-if="loadingJobs" class="space-y-3">
          <div v-for="i in 2" :key="i" class="h-24 bg-gray-100 animate-pulse rounded-xl" />
        </div>

        <div v-else-if="currentTabJobs.length === 0"
          class="text-center py-10 text-gray-400">
          <component :is="tabEmptyIcon" class="w-10 h-10 mx-auto mb-2 opacity-30" />
          <p class="text-sm">{{ tabEmptyText }}</p>
        </div>

        <div v-else class="space-y-3">

          <!-- AVAILABLE -->
          <template v-if="activeTab === 'available'">
            <div v-for="job in currentTabJobs" :key="job.id"
              class="border border-gray-100 rounded-xl p-4 hover:border-blue-200 transition">
              <div class="flex items-start justify-between mb-2">
                <div>
                  <p class="font-semibold text-blue-600">{{ job.job_number }}</p>
                  <p class="text-xs text-gray-400 mt-0.5">
                    Pickup: {{ formatDate(job.pickup_scheduled_at) }}
                  </p>
                </div>
                <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full font-medium">
                  Tersedia
                </span>
              </div>
              <div class="space-y-1 mb-3">
                <div class="flex items-start gap-2 text-xs text-gray-500">
                  <MapPin class="w-3.5 h-3.5 text-green-500 flex-shrink-0 mt-0.5" />
                  {{ job.origin_address }}
                </div>
                <div class="flex items-start gap-2 text-xs text-gray-500">
                  <MapPin class="w-3.5 h-3.5 text-red-500 flex-shrink-0 mt-0.5" />
                  {{ job.destination_address }}
                </div>
              </div>
              <div class="flex items-center gap-2 text-xs text-gray-400 mb-3">
                <Package class="w-3.5 h-3.5" />
                {{ job.cargo_description }} · {{ Number(job.cargo_weight_kg).toLocaleString() }} kg
              </div>
              <textarea v-model="applyNotes[job.id]"
                placeholder="Catatan untuk admin (opsional)..."
                rows="2"
                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 mb-2 resize-none" />
              <button @click="handleApply(job)"
                :disabled="applying === job.id"
                class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white py-2 rounded-xl text-sm font-medium transition flex items-center justify-center gap-2">
                <Loader2 v-if="applying === job.id" class="w-4 h-4 animate-spin" />
                <CheckCircle v-else class="w-4 h-4" />
                Apply Job Ini
              </button>
            </div>
          </template>

          <!-- APPLIED -->
          <template v-if="activeTab === 'applied'">
            <div v-for="job in currentTabJobs" :key="job.id"
              class="border border-yellow-100 bg-yellow-50 rounded-xl p-4">
              <div class="flex items-start justify-between mb-2">
                <div>
                  <p class="font-semibold text-gray-800">{{ job.job_number }}</p>
                  <p class="text-xs text-gray-400">
                    Pickup: {{ formatDate(job.pickup_scheduled_at) }}
                  </p>
                </div>
                <span class="text-xs bg-yellow-200 text-yellow-800 px-2 py-1 rounded-full font-medium flex items-center gap-1">
                  <Clock class="w-3 h-3" /> Menunggu
                </span>
              </div>
              <div class="space-y-1 mb-3">
                <div class="flex items-start gap-2 text-xs text-gray-500">
                  <MapPin class="w-3.5 h-3.5 text-green-500 flex-shrink-0 mt-0.5" />
                  {{ job.origin_address }}
                </div>
                <div class="flex items-start gap-2 text-xs text-gray-500">
                  <MapPin class="w-3.5 h-3.5 text-red-500 flex-shrink-0 mt-0.5" />
                  {{ job.destination_address }}
                </div>
              </div>
              <button @click="handleCancelApply(job)"
                :disabled="cancelling === job.id"
                class="text-xs text-red-500 hover:underline disabled:opacity-50 flex items-center gap-1">
                <Loader2 v-if="cancelling === job.id" class="w-3 h-3 animate-spin" />
                <span v-else>Batalkan Apply</span>
              </button>
            </div>
          </template>

          <!-- ACTIVE -->
          <template v-if="activeTab === 'active'">
            <div v-for="job in currentTabJobs" :key="job.id"
              class="border rounded-xl p-4 transition"
              :class="gps.activeJobOrderId === job.id
                ? 'border-green-300 bg-green-50'
                : 'border-gray-100 hover:bg-gray-50'">
              <div class="flex items-start justify-between mb-2">
                <div>
                  <div class="flex items-center gap-2">
                    <p class="font-semibold text-blue-600">{{ job.job_number }}</p>
                    <span v-if="gps.activeJobOrderId === job.id"
                      class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full flex items-center gap-1">
                      <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse" />
                      GPS
                    </span>
                  </div>
                  <p class="text-xs text-gray-400 mt-0.5">
                    Deadline: {{ formatDate(job.delivery_scheduled_at) }}
                  </p>
                </div>
                <StatusBadge :status="job.status" />
              </div>
              <div class="space-y-1 mb-3">
                <div class="flex items-start gap-2 text-xs text-gray-500">
                  <MapPin class="w-3.5 h-3.5 text-green-500 flex-shrink-0 mt-0.5" />
                  {{ job.origin_address }}
                </div>
                <div class="flex items-start gap-2 text-xs text-gray-500">
                  <MapPin class="w-3.5 h-3.5 text-red-500 flex-shrink-0 mt-0.5" />
                  {{ job.destination_address }}
                </div>
              </div>
              <div class="flex items-center gap-2 text-xs text-gray-400 mb-3">
                <Package class="w-3.5 h-3.5" />
                {{ job.cargo_description }} · {{ Number(job.cargo_weight_kg).toLocaleString() }} kg
              </div>

              <!-- Status Progress -->
              <div class="flex items-center gap-1 mb-3">
                <div v-for="(s, i) in statusFlow" :key="s"
                  class="flex items-center gap-1">
                  <div :class="`w-2 h-2 rounded-full ${
                    statusIndex(job.status) >= i ? 'bg-blue-600' : 'bg-gray-200'
                  }`" />
                  <div v-if="i < statusFlow.length - 1"
                    :class="`flex-1 h-0.5 w-4 ${
                      statusIndex(job.status) > i ? 'bg-blue-600' : 'bg-gray-200'
                    }`" />
                </div>
              </div>

              <div class="flex gap-2">
                <button v-if="NEXT_STATUS[job.status]"
                  @click="handleUpdateStatus(job)"
                  :disabled="updatingJob === job.id"
                  class="flex-1 bg-blue-600 text-white py-2 rounded-xl text-xs font-medium hover:bg-blue-700 disabled:opacity-50 transition flex items-center justify-center gap-1">
                  <Loader2 v-if="updatingJob === job.id" class="w-3 h-3 animate-spin" />
                  <span v-else>{{ nextStatusLabel(job.status) }}</span>
                </button>
                <RouterLink v-if="job.status === 'in_transit'"
                  :to="`/driver/pod/${job.id}`"
                  class="flex-1 bg-orange-500 text-white py-2 rounded-xl text-xs font-medium hover:bg-orange-600 transition text-center flex items-center justify-center gap-1">
                  <Camera class="w-3 h-3" /> Submit POD
                </RouterLink>
              </div>
            </div>
          </template>

          <!-- HISTORY -->
          <template v-if="activeTab === 'history'">
            <div v-for="job in currentTabJobs" :key="job.id"
              class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:bg-gray-50 transition">
              <CheckCircle class="w-5 h-5 text-green-500 flex-shrink-0" />
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800">{{ job.job_number }}</p>
                <p class="text-xs text-gray-400 truncate">
                  {{ job.origin_city }} → {{ job.destination_city }}
                </p>
              </div>
              <div class="text-right flex-shrink-0">
                <StatusBadge :status="job.status" />
                <p class="text-xs text-gray-400 mt-1">{{ formatDate(job.delivery_actual_at ?? job.updated_at) }}</p>
              </div>
            </div>
          </template>

        </div>
      </div>
    </div>

    <!-- Toast -->
    <Transition name="toast">
      <div v-if="toast"
        class="fixed bottom-6 right-6 bg-gray-900 text-white px-4 py-3 rounded-xl shadow-lg text-sm flex items-center gap-2 z-50">
        <CheckCircle class="w-4 h-4 text-green-400" />
        {{ toast }}
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink } from 'vue-router'
import {
  Truck, MapPin, CheckCircle, Package, Star,
  Loader2, Navigation, Clock, Camera, History, Briefcase
} from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import StatusBadge from '@/pages/admin/StatusBadge.vue'
import api from '@/lib/axios'

const auth  = useAuthStore()
const toast = ref('')

// ── State ──────────────────────────────────────────────────
const jobMap = reactive<Record<string, any[]>>({
  available: [], applied: [], active: [], history: []
})
const loadingJobs    = ref(false)
const updatingJob    = ref<number | null>(null)
const cancelling     = ref<number | null>(null)
const applying       = ref<number | null>(null)
const updatingStatus = ref(false)
const currentStatus  = ref('available')
const activeTab      = ref('active')
const applyNotes     = reactive<Record<number, string>>({})

// ── GPS ────────────────────────────────────────────────────
const gps = reactive({
  isTracking: false,
  loading:    false,
  lat:        null as number | null,
  lng:        null as number | null,
  speed:      null as number | null,
  heading:    null as number | null,
  accuracy:   null as number | null,
  lastUpdate: null as string | null,
  error:      null as string | null,
  activeJobOrderId: null as number | null,
})

let watchId: number | null = null
let sendInterval: ReturnType<typeof setInterval> | null = null
let pollInterval: ReturnType<typeof setInterval> | null = null

// ── Tabs ───────────────────────────────────────────────────
const tabs = [
  { label: 'Aktif',    value: 'active'    },
  { label: 'Tersedia', value: 'available' },
  { label: 'Di-apply', value: 'applied'   },
  { label: 'Riwayat',  value: 'history'   },
]

const tabEmptyIcon = computed(() => {
  const map: Record<string, any> = {
    active: Truck, available: Briefcase, applied: Clock, history: History
  }
  return map[activeTab.value] ?? Truck
})

const tabEmptyText = computed(() => ({
  active:    'Tidak ada tugas aktif',
  available: 'Tidak ada job tersedia',
  applied:   'Belum ada job yang di-apply',
  history:   'Belum ada riwayat pengiriman',
}[activeTab.value] ?? '-'))

const currentTabJobs = computed(() => jobMap[activeTab.value] ?? [])
function tabCount(tab: string) { return jobMap[tab]?.length ?? 0 }

// ── Status Flow ───────────────────────────────────────────
const statusFlow = ['assigned', 'in_progress', 'picked_up', 'in_transit', 'delivered']
function statusIndex(s: string) { return statusFlow.indexOf(s) }

const NEXT_STATUS: Record<string, string> = {
  assigned:    'in_progress',
  in_progress: 'picked_up',
  picked_up:   'in_transit',
  in_transit:  'delivered',
}
const NEXT_LABEL: Record<string, string> = {
  assigned:    '🚦 Mulai Proses',
  in_progress: '📦 Picked Up',
  picked_up:   '🚚 Mulai Perjalanan',
  in_transit:  '✅ Tandai Terkirim',
}
function nextStatusLabel(status: string) { return NEXT_LABEL[status] ?? 'Update Status' }

// ── Computed ───────────────────────────────────────────────
const activeJobs = computed(() => jobMap.active)

const stats = computed(() => [
  {
    label: 'Total Trip',
    value: Object.values(jobMap).flat().length,
    icon: Truck, color: 'text-blue-600', bg: 'bg-blue-50'
  },
  {
    label: 'Rating',
    value: auth.user?.driver_profile?.rating ?? '—',
    icon: Star, color: 'text-yellow-600', bg: 'bg-yellow-50'
  },
  {
    label: 'Aktif',
    value: jobMap.active.length,
    icon: Package, color: 'text-green-600', bg: 'bg-green-50'
  },
  {
    label: 'Selesai',
    value: jobMap.history.length,
    icon: CheckCircle, color: 'text-purple-600', bg: 'bg-purple-50'
  },
])

// ── Fetch Jobs ────────────────────────────────────────────
async function fetchMyJobs() {
  loadingJobs.value = true
  try {
    const filters = ['active', 'available', 'applied', 'history']
    const results = await Promise.allSettled(
      filters.map(f => api.get('/driver/my-jobs', { params: { filter: f, per_page: 50 } }))
    )
    results.forEach((res, i) => {
      if (res.status === 'fulfilled') {
        const raw = typeof res.value.data === 'string'
          ? JSON.parse(res.value.data.replace(/^=/, ''))
          : res.value.data
        const result = raw?.data?.data ?? raw?.data ?? raw
        jobMap[filters[i]] = Array.isArray(result) ? result : []
      } else {
        jobMap[filters[i]] = []
      }
    })
    if (!gps.activeJobOrderId && jobMap.active.length > 0) {
      gps.activeJobOrderId = jobMap.active[0].id
    }
  } finally {
    loadingJobs.value = false
  }
}

// ── Fetch Driver Status dari API ──────────────────────────
async function fetchDriverStatus() {
  try {
    const res = await api.get('/auth/me')
    const raw = typeof res.data === 'string'
      ? JSON.parse(res.data.replace(/^=/, ''))
      : res.data
    const user = raw?.data ?? raw
    currentStatus.value = user?.driver_profile?.availability_status ?? 'available'
  } catch {
    currentStatus.value = auth.user?.driver_profile?.availability_status ?? 'available'
  }
}

// ── Update Driver Status ──────────────────────────────────
const driverStatuses = [
  { value: 'available', label: 'Tersedia'        },
  { value: 'on_duty',   label: 'Sedang Bertugas' },
  { value: 'rest',      label: 'Istirahat'        },
  { value: 'off_duty',  label: 'Tidak Bertugas'  },
]

async function updateDriverStatus(status: string) {
  if (currentStatus.value === status) return
  updatingStatus.value = true
  try {
    await api.patch('/driver/status', { availability_status: status })
    currentStatus.value = status
    if (status === 'off_duty' && gps.isTracking) stopGps()
    showToast(`Status: ${statusLabel(status)}`)
  } catch (e: any) {
    const raw = e.response?.data
    const msg = typeof raw === 'string'
      ? JSON.parse(raw.replace(/^=/, '')).message
      : raw?.message
    showToast(msg ?? 'Gagal update status')
  } finally {
    updatingStatus.value = false
  }
}

// ── Job Actions ───────────────────────────────────────────
async function handleApply(job: any) {
  applying.value = job.id
  try {
    await api.post(`/driver/my-jobs/${job.id}/apply`, {
      driver_notes: applyNotes[job.id] || undefined
    })
    jobMap.available = jobMap.available.filter(j => j.id !== job.id)
    jobMap.applied.unshift({ ...job })
    applyNotes[job.id] = ''
    activeTab.value = 'applied'
    showToast('Berhasil apply! Menunggu persetujuan admin.')
  } catch (e: any) {
    showToast(e.response?.data?.message || 'Gagal apply job')
  } finally {
    applying.value = null
  }
}

async function handleCancelApply(job: any) {
  cancelling.value = job.id
  try {
    await api.delete(`/driver/my-jobs/${job.id}/cancel-apply`)
    jobMap.applied = jobMap.applied.filter(j => j.id !== job.id)
    jobMap.available.unshift(job)
    showToast('Apply dibatalkan')
  } catch (e: any) {
    showToast(e.response?.data?.message || 'Gagal membatalkan apply')
  } finally {
    cancelling.value = null
  }
}

async function handleUpdateStatus(job: any) {
  const next = NEXT_STATUS[job.status]
  if (!next) return
  updatingJob.value = job.id
  try {
    await api.patch(`/driver/my-jobs/${job.id}/update-status`, { status: next })
    if (next === 'delivered') {
      jobMap.active = jobMap.active.filter(j => j.id !== job.id)
      jobMap.history.unshift({ ...job, status: 'delivered' })
      activeTab.value = 'history'
    } else {
      const idx = jobMap.active.findIndex(j => j.id === job.id)
      if (idx !== -1) jobMap.active[idx] = { ...jobMap.active[idx], status: next }
    }
    showToast(`Status: ${nextStatusLabel(next)}`)
  } catch (e: any) {
    const raw = e.response?.data
    const msg = typeof raw === 'string'
      ? JSON.parse(raw.replace(/^=/, '')).message
      : raw?.message
    showToast(msg ?? 'Gagal update status')
    // Re-fetch untuk sync
    await fetchMyJobs()
  } finally {
    updatingJob.value = null
  }
}

// ── GPS ────────────────────────────────────────────────────
async function toggleGps() { gps.isTracking ? stopGps() : await startGps() }

async function startGps() {
  if (!navigator.geolocation) { gps.error = 'Browser tidak support GPS'; return }
  gps.loading = true
  gps.error   = null
  navigator.geolocation.getCurrentPosition(
    async (pos) => {
      gps.lat        = pos.coords.latitude
      gps.lng        = pos.coords.longitude
      gps.speed      = pos.coords.speed ? Math.round(pos.coords.speed * 3.6) : 0
      gps.heading    = pos.coords.heading
      gps.accuracy   = Math.round(pos.coords.accuracy)
      gps.loading    = false
      gps.isTracking = true
      await sendLocation()
      watchId = navigator.geolocation.watchPosition(
        (p) => {
          gps.lat     = p.coords.latitude
          gps.lng     = p.coords.longitude
          gps.speed   = p.coords.speed ? Math.round(p.coords.speed * 3.6) : 0
          gps.heading = p.coords.heading
        },
        (err) => { gps.error = err.message },
        { enableHighAccuracy: true, maximumAge: 5000, timeout: 10000 }
      )
      sendInterval = setInterval(sendLocation, 10_000)
      showToast('GPS aktif — lokasi dikirim tiap 10 detik')
    },
    (err) => {
      gps.loading = false
      gps.error   = err.code === 1
        ? 'Izin GPS ditolak — aktifkan di settings browser'
        : 'Gagal mendapatkan lokasi: ' + err.message
    },
    { enableHighAccuracy: true, timeout: 15000 }
  )
}

function stopGps() {
  if (watchId !== null) { navigator.geolocation.clearWatch(watchId); watchId = null }
  if (sendInterval)     { clearInterval(sendInterval); sendInterval = null }
  gps.isTracking = false
  gps.lat = gps.lng = gps.speed = null
  api.post('/gps/offline', {}).catch(() => {})
  showToast('GPS dinonaktifkan')
}

async function sendLocation() {
  if (!gps.lat || !gps.lng) return
  try {
    await api.post('/gps/location', {
      lat:          gps.lat,
      lng:          gps.lng,
      speed:        gps.speed    ?? 0,
      heading:      gps.heading  ?? 0,
      accuracy:     gps.accuracy ?? 0,
      job_order_id: gps.activeJobOrderId,
    })
    gps.lastUpdate = new Date().toLocaleTimeString('id-ID', {
      hour: '2-digit', minute: '2-digit', second: '2-digit'
    })
    gps.error = null
  } catch {
    gps.error = 'Gagal kirim lokasi'
  }
}

// ── Helpers ────────────────────────────────────────────────
function statusLabel(s: string) {
  return ({ available:'Tersedia', on_duty:'Sedang Bertugas', rest:'Istirahat', off_duty:'Tidak Bertugas' }[s] ?? s)
}
function statusClass(s: string) {
  return ({
    available: 'bg-green-100 text-green-700',
    on_duty:   'bg-blue-100 text-blue-700',
    rest:      'bg-yellow-100 text-yellow-700',
    off_duty:  'bg-gray-100 text-gray-600',
  }[s] ?? 'bg-gray-100 text-gray-600')
}
function formatDate(val: string) {
  if (!val) return '-'
  return new Date(val).toLocaleDateString('id-ID', { day:'2-digit', month:'short', year:'numeric' })
}
function showToast(msg: string) {
  toast.value = msg
  setTimeout(() => { toast.value = '' }, 3000)
}

// ── Lifecycle ──────────────────────────────────────────────
onMounted(async () => {
  await Promise.all([fetchMyJobs(), fetchDriverStatus()])
  // Poll jobs tiap 30 detik biar realtime
  pollInterval = setInterval(fetchMyJobs, 30_000)
})

onUnmounted(() => {
  if (gps.isTracking) stopGps()
  if (pollInterval) clearInterval(pollInterval)
})
</script>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(10px); }
</style>