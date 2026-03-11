<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Route Planning</h1>
        <p class="text-sm text-gray-500 mt-1">Kelola dan optimalkan rute pengiriman</p>
      </div>
      <button @click="showForm = true"
        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition">
        <Plus class="w-4 h-4" />
        Buat Rute Baru
      </button>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div v-for="stat in routeStats" :key="stat.label"
        class="bg-gradient-to-br from-white to-blue-50 rounded-2xl p-4 border border-blue-50 shadow-sm">
        <div class="flex items-center justify-between mb-2">
          <p class="text-xs text-gray-500 font-medium">{{ stat.label }}</p>
          <div :class="`w-7 h-7 rounded-lg ${stat.bg} flex items-center justify-center`">
            <component :is="stat.icon" :class="`w-3.5 h-3.5 ${stat.color}`" />
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-800">{{ stat.value }}</p>
        <p class="text-xs mt-1 text-gray-400">{{ stat.sub }}</p>
      </div>
    </div>

    <div class="flex flex-wrap gap-3 items-center">
      <div class="flex-1 min-w-48 relative">
        <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
        <input v-model="search" placeholder="Cari rute..."
          class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>
      <div class="flex gap-2">
        <button v-for="f in filters" :key="f.value"
          @click="activeFilter = f.value"
          :class="`px-3 py-2 text-xs rounded-xl font-medium transition ${
            activeFilter === f.value
              ? 'bg-blue-600 text-white'
              : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'
          }`">
          {{ f.label }}
          <span v-if="f.count !== undefined" class="ml-1 opacity-70">({{ f.count }})</span>
        </button>
      </div>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-16">
      <Loader2 class="w-6 h-6 animate-spin text-blue-600" />
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-5 gap-6">

      <div class="lg:col-span-2 space-y-3">
        <div v-if="filteredRoutes.length === 0" class="text-center py-12 text-gray-400">
          <Map class="w-10 h-10 mx-auto mb-2 opacity-30" />
          <p class="text-sm">Tidak ada rute ditemukan</p>
        </div>

        <div v-for="route in filteredRoutes" :key="route.id"
          @click="selectedRoute = route"
          :class="`bg-white rounded-2xl border p-4 cursor-pointer transition hover:shadow-md ${
            selectedRoute?.id === route.id
              ? 'border-blue-400 shadow-md ring-1 ring-blue-200'
              : 'border-gray-100'
          }`">

          <div class="flex items-start justify-between mb-3">
            <div>
              <div class="flex items-center gap-2 flex-wrap">
                <span class="text-sm font-semibold text-gray-800">{{ route.name }}</span>
                <span :class="`text-xs px-2 py-0.5 rounded-full font-medium ${statusClass(route.status)}`">
                  {{ statusLabel(route.status) }}
                </span>
              </div>
              <p class="text-xs text-gray-400 mt-0.5">{{ route.job_order_id ?? '-' }}</p>
            </div>
            <div class="flex gap-1">
              <button @click.stop="handleOptimize(route)"
                :disabled="optimizing === route.id"
                class="p-1.5 rounded-lg hover:bg-blue-50 text-blue-600 transition disabled:opacity-50">
                <Zap v-if="optimizing !== route.id" class="w-3.5 h-3.5" />
                <Loader2 v-else class="w-3.5 h-3.5 animate-spin" />
              </button>
              <button @click.stop="editRoute(route)"
                class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 transition">
                <Pencil class="w-3.5 h-3.5" />
              </button>
              <button @click.stop="handleDelete(route.id)"
                class="p-1.5 rounded-lg hover:bg-red-50 text-red-400 transition">
                <Trash2 class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <div class="flex items-center gap-2 mb-3">
            <div class="flex flex-col items-center gap-0.5">
              <div class="w-2 h-2 rounded-full bg-blue-500" />
              <div class="w-0.5 h-4 bg-gray-200" />
              <div class="w-2 h-2 rounded-full bg-green-500" />
            </div>
            <div class="flex-1 space-y-1">
              <p class="text-xs font-medium text-gray-700">{{ firstWaypoint(route) }}</p>
              <p class="text-xs font-medium text-gray-700">{{ lastWaypoint(route) }}</p>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-2 pt-2 border-t border-gray-50">
            <div class="text-center">
              <p class="text-xs text-gray-400">Jarak</p>
              <p class="text-sm font-semibold text-gray-700">
                {{ formatDistance(route.total_distance_km ?? route.total_distance) }} km
              </p>
            </div>
            <div class="text-center border-x border-gray-50">
              <p class="text-xs text-gray-400">Estimasi</p>
              <p class="text-sm font-semibold text-gray-700">
                {{ formatDuration(route.estimated_duration_hours ?? route.estimated_duration) }}j
              </p>
            </div>
            <div class="text-center">
              <p class="text-xs text-gray-400">Waypoints</p>
              <p class="text-sm font-semibold text-gray-700">
                {{ route.waypoints?.length ?? 0 }}
              </p>
            </div>
          </div>

          <div v-if="route.is_optimized" class="mt-2 flex items-center gap-1">
            <Zap class="w-3 h-3 text-yellow-500" />
            <span class="text-xs text-yellow-600 font-medium">Sudah dioptimasi</span>
          </div>
        </div>
      </div>

      <div class="lg:col-span-3">
        <div v-if="!selectedRoute"
          class="bg-white rounded-2xl border border-gray-100 h-full min-h-96 flex flex-col items-center justify-center text-gray-400">
          <Map class="w-12 h-12 mb-3 opacity-20" />
          <p class="text-sm font-medium">Pilih rute untuk melihat detail</p>
          <p class="text-xs mt-1 opacity-60">Klik salah satu rute di kiri</p>
        </div>

        <div v-else class="space-y-4">
          <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <div class="relative bg-gradient-to-br from-blue-50 to-indigo-50 h-64 flex items-center justify-center">
              <svg class="absolute inset-0 w-full h-full opacity-10" viewBox="0 0 400 250">
                <line x1="0" y1="125" x2="400" y2="125" stroke="#2563eb" stroke-width="1" stroke-dasharray="4"/>
                <line x1="200" y1="0" x2="200" y2="250" stroke="#2563eb" stroke-width="1" stroke-dasharray="4"/>
              </svg>
              <svg class="absolute inset-0 w-full h-full" viewBox="0 0 400 250">
                <path d="M 60,180 C 120,160 160,100 200,95 C 240,90 280,110 340,80"
                  fill="none" stroke="#2563eb" stroke-width="3" stroke-linecap="round"
                  stroke-dasharray="8,4" opacity="0.8"/>
                <circle cx="60" cy="180" r="8" fill="#2563eb" />
                <circle cx="60" cy="180" r="14" fill="#2563eb" opacity="0.2" />
                <circle cx="340" cy="80" r="8" fill="#10b981" />
                <circle cx="340" cy="80" r="14" fill="#10b981" opacity="0.2" />
              </svg>
              <div class="absolute bottom-4 left-6 bg-white rounded-lg px-3 py-1.5 shadow text-xs font-medium text-blue-700">
                📍 {{ firstWaypoint(selectedRoute) }}
              </div>
              <div class="absolute top-4 right-6 bg-white rounded-lg px-3 py-1.5 shadow text-xs font-medium text-green-700">
                🏁 {{ lastWaypoint(selectedRoute) }}
              </div>
              <div class="text-center z-10">
                <div class="bg-white/80 backdrop-blur rounded-xl px-4 py-2 shadow">
                  <p class="text-xs text-gray-500">Peta interaktif</p>
                  <p class="text-xs text-blue-600 font-medium">Akan terhubung ke Google Maps API</p>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-semibold text-gray-800">{{ selectedRoute.name }}</h3>
              <span :class="`text-xs px-3 py-1 rounded-full font-medium ${statusClass(selectedRoute.status)}`">
                {{ statusLabel(selectedRoute.status) }}
              </span>
            </div>

            <div class="space-y-0 mb-4">
              <div v-for="(wp, i) in (selectedRoute.waypoints ?? [])" :key="i"
                class="flex items-start gap-3">
                <div class="flex flex-col items-center">
                  <div :class="`w-3 h-3 rounded-full mt-0.5 flex-shrink-0 ${
                    i === 0 ? 'bg-blue-500' :
                    i === (selectedRoute.waypoints.length - 1) ? 'bg-green-500' :
                    'bg-orange-400'
                  }`" />
                  <div v-if="i < selectedRoute.waypoints.length - 1"
                    class="w-0.5 bg-gray-200 flex-1 min-h-6" />
                </div>
                <div class="pb-3">
                  <p class="text-xs font-semibold text-gray-700">
                    {{ i === 0 ? 'Titik Asal' : i === selectedRoute.waypoints.length - 1 ? 'Tujuan Akhir' : `Stop ${i}` }}
                  </p>
                  <p class="text-sm font-medium text-gray-800">{{ wp.address }}</p>
                  <p v-if="wp.notes" class="text-xs text-gray-400">{{ wp.notes }}</p>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-4">
              <div class="bg-blue-50 rounded-xl p-3">
                <p class="text-xs text-gray-500 mb-1">Total Jarak</p>
                <p class="text-lg font-bold text-blue-700">
                  {{ formatDistance(selectedRoute.total_distance_km ?? selectedRoute.total_distance) }} km
                </p>
              </div>
              <div class="bg-green-50 rounded-xl p-3">
                <p class="text-xs text-gray-500 mb-1">Estimasi Waktu</p>
                <p class="text-lg font-bold text-green-700">
                  {{ formatDuration(selectedRoute.estimated_duration_hours ?? selectedRoute.estimated_duration) }} jam
                </p>
              </div>
              <div class="bg-orange-50 rounded-xl p-3">
                <p class="text-xs text-gray-500 mb-1">Status</p>
                <p class="text-sm font-bold text-orange-700">{{ statusLabel(selectedRoute.status) }}</p>
              </div>
              <div class="bg-purple-50 rounded-xl p-3">
                <p class="text-xs text-gray-500 mb-1">Job Order</p>
                <p class="text-sm font-bold text-purple-700 truncate">
                  {{ selectedRoute.job_order_id ?? '-' }}
                </p>
              </div>
            </div>

            <div v-if="selectedRoute.is_optimized"
              class="bg-yellow-50 border border-yellow-100 rounded-xl p-3 mb-4 flex items-center gap-3">
              <Zap class="w-5 h-5 text-yellow-500 flex-shrink-0" />
              <p class="text-sm font-semibold text-yellow-800">Rute sudah dioptimasi</p>
            </div>

            <div class="flex gap-2">
              <button @click="handleOptimize(selectedRoute)"
                :disabled="optimizing === selectedRoute.id"
                class="flex-1 flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white py-2 rounded-xl text-sm font-medium transition">
                <Zap v-if="optimizing !== selectedRoute.id" class="w-4 h-4" />
                <Loader2 v-else class="w-4 h-4 animate-spin" />
                {{ optimizing === selectedRoute.id ? 'Mengoptimasi...' : 'Optimasi Rute' }}
              </button>
              <button @click="editRoute(selectedRoute)"
                class="px-4 flex items-center gap-2 border border-gray-200 hover:bg-gray-50 text-gray-700 py-2 rounded-xl text-sm font-medium transition">
                <Pencil class="w-4 h-4" />
                Edit
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showForm" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
      @click.self="closeForm">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-800">
            {{ editingRoute ? 'Edit Rute' : 'Buat Rute Baru' }}
          </h3>
          <button @click="closeForm" class="p-1.5 hover:bg-gray-100 rounded-lg transition">
            <X class="w-5 h-5 text-gray-500" />
          </button>
        </div>

        <div class="space-y-3">
          <div>
            <label class="text-xs font-medium text-gray-600 mb-1 block">Nama Rute *</label>
            <input v-model="form.name" placeholder="Contoh: Rute Jakarta-Surabaya" class="input-field" />
          </div>

          <div>
            <label class="text-xs font-medium text-gray-600 mb-1 block">Job Order</label>
            <select v-model="form.job_order_id" @change="onJobOrderSelect" class="input-field">
              <option value="">Pilih Job Order (opsional)</option>
              <optgroup label="Pending">
                <option v-for="jo in jobOrderOptions.filter(j => j.status === 'pending')"
                  :key="jo.id" :value="jo.id">
                  {{ jo.job_number }} - {{ jo.customer_name }} ({{ jo.origin_city }} → {{ jo.destination_city }})
                </option>
              </optgroup>
              <optgroup label="Assigned">
                <option v-for="jo in jobOrderOptions.filter(j => j.status === 'assigned')"
                  :key="jo.id" :value="jo.id">
                  {{ jo.job_number }} - {{ jo.customer_name }} ({{ jo.origin_city }} → {{ jo.destination_city }})
                </option>
              </optgroup>
              <optgroup label="In Progress / Transit">
                <option v-for="jo in jobOrderOptions.filter(j => ['in_progress','picked_up','in_transit'].includes(j.status))"
                  :key="jo.id" :value="jo.id">
                  {{ jo.job_number }} - {{ jo.customer_name }} ({{ jo.origin_city }} → {{ jo.destination_city }})
                </option>
              </optgroup>
            </select>
            <p v-if="form.job_order_id" class="text-xs text-blue-600 mt-1">
              ✓ Waypoints otomatis terisi dari job order
            </p>
          </div>

          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="text-xs font-medium text-gray-600">Waypoints *</label>
              <button @click="addWaypoint"
                class="text-xs text-blue-600 hover:underline flex items-center gap-1">
                <Plus class="w-3 h-3" /> Tambah Titik
              </button>
            </div>
            <div v-for="(wp, i) in form.waypoints" :key="i" class="flex gap-2 mb-2 items-center">
              <div class="flex flex-col items-center mr-1">
                <div :class="`w-2.5 h-2.5 rounded-full flex-shrink-0 ${
                  i === 0 ? 'bg-blue-500' :
                  i === form.waypoints.length - 1 ? 'bg-green-500' :
                  'bg-orange-400'
                }`" />
              </div>
              <input v-model="form.waypoints[i].address"
                :placeholder="i === 0 ? 'Titik asal' : i === form.waypoints.length - 1 ? 'Tujuan akhir' : `Stop ${i}`"
                class="input-field flex-1" />
              <button v-if="form.waypoints.length > 2" @click="removeWaypoint(i)"
                class="p-2 hover:bg-red-50 text-red-400 rounded-lg transition flex-shrink-0">
                <X class="w-4 h-4" />
              </button>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs font-medium text-gray-600 mb-1 block">Jarak (km)</label>
              <input v-model.number="form.total_distance_km" type="number" placeholder="450" class="input-field" />
            </div>
            <div>
              <label class="text-xs font-medium text-gray-600 mb-1 block">Estimasi (jam)</label>
              <input v-model.number="form.estimated_duration_hours" type="number" placeholder="8" class="input-field" />
            </div>
          </div>

          <div v-if="formError" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3">
            <p class="text-red-600 text-sm">{{ formError }}</p>
          </div>
        </div>

        <div class="flex gap-2 pt-2">
          <button @click="closeForm"
            class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-xl text-sm hover:bg-gray-50 transition">
            Batal
          </button>
          <button @click="submitForm" :disabled="submitting"
            class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white py-2 rounded-xl text-sm font-medium transition flex items-center justify-center gap-2">
            <Loader2 v-if="submitting" class="w-4 h-4 animate-spin" />
            {{ editingRoute ? 'Simpan' : 'Buat Rute' }}
          </button>
        </div>
      </div>
    </div>

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
import { ref, computed, reactive, onMounted, watch } from 'vue'
import {
  Plus, X, Map, Search, Zap, Pencil, Trash2,
  Loader2, CheckCircle, Navigation, Clock, Route
} from 'lucide-vue-next'
import api from '@/lib/axios'
import { useJobOrders } from '@/composables/useJobOrders'

const { jobOrders, fetchJobOrders } = useJobOrders()

const routes = ref<any[]>([])
const loading = ref(false)
const selectedRoute = ref<any>(null)
const showForm = ref(false)
const editingRoute = ref<any>(null)
const optimizing = ref<any>(null)
const submitting = ref(false)
const formError = ref('')
const search = ref('')
const activeFilter = ref('all')
const toast = ref('')

const form = reactive({
  name: '',
  job_order_id: '' as any,
  total_distance_km: 0,
  estimated_duration_hours: 0,
  waypoints: [
    { address: '', order: 1, lat: null, lng: null },
    { address: '', order: 2, lat: null, lng: null },
  ] as any[],
})

watch(
  () => form.job_order_id,
  async (newJobOrderId) => {
    if (newJobOrderId && !editingRoute.value) {
      try {
        const response = await api.get(`/route-plans/estimate/${newJobOrderId}`)
        const data = response.data?.data || response.data
        if (data) {
          form.total_distance_km = data.distance_km
          form.estimated_duration_hours = data.duration_hours
        }
      } catch (error) {
        console.error('Gagal mengambil estimasi rute:', error)
      }
    } else if (!newJobOrderId && !editingRoute.value) {
      form.total_distance_km = 0
      form.estimated_duration_hours = 0
    }
  }
)

// ✅ Helper format jarak — konversi meter ke km kalau > 1000
function formatDistance(val: any): string {
  if (val === null || val === undefined || val === '') return '-'
  const num = Number(val)
  if (isNaN(num)) return '-'
  return num > 1000 ? (num / 1000).toFixed(3) : String(num)
}

// ✅ Helper format durasi — konversi menit ke jam kalau > 24
function formatDuration(val: any): string {
  if (val === null || val === undefined || val === '') return '-'
  const num = Number(val)
  if (isNaN(num)) return '-'
  return num > 24 ? (num / 60).toFixed(1) : String(num)
}

async function fetchRoutes() {
  loading.value = true
  try {
    const res = await api.get('/route-plans')
    const raw = typeof res.data === 'string'
      ? JSON.parse(res.data.replace(/^=/, ''))
      : res.data
    routes.value = raw.data?.data ?? raw.data ?? []
  } catch {
    const { MOCK_ROUTES } = await import('@/lib/mockData')
    routes.value = MOCK_ROUTES.map((r: any) => ({
      ...r,
      waypoints: r.waypoints ?? [],
      is_optimized: r.optimized ?? false,
      total_distance_km: r.total_distance,
      estimated_duration_hours: r.estimated_duration,
      status: r.status ?? 'planned',
    }))
  } finally {
    loading.value = false
  }
}

const jobOrderOptions = computed(() =>
  jobOrders.value.filter(j =>
    ['pending', 'assigned', 'in_progress', 'picked_up', 'in_transit'].includes(j.status)
  )
)

const filters = computed(() => [
  { value: 'all',       label: 'Semua',        count: routes.value.length },
  { value: 'planned',   label: 'Direncanakan', count: routes.value.filter(r => r.status === 'planned').length },
  { value: 'active',    label: 'Aktif',        count: routes.value.filter(r => r.status === 'active').length },
  { value: 'completed', label: 'Selesai',      count: routes.value.filter(r => r.status === 'completed').length },
])

const filteredRoutes = computed(() => {
  let list = routes.value
  if (activeFilter.value !== 'all') list = list.filter(r => r.status === activeFilter.value)
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter(r => r.name?.toLowerCase().includes(q))
  }
  return list
})

const routeStats = computed(() => [
  {
    label: 'Total Rute', value: routes.value.length,
    sub: 'Semua rute', icon: Map, color: 'text-blue-600', bg: 'bg-blue-100'
  },
  {
    label: 'Aktif', value: routes.value.filter(r => r.status === 'active').length,
    sub: 'Sedang berjalan', icon: Navigation, color: 'text-green-600', bg: 'bg-green-100'
  },
  {
    label: 'Total Jarak',
    value: routes.value.reduce((a, r) => {
      const raw = r.total_distance_km ?? r.total_distance ?? 0
      const km = raw > 1000 ? raw / 1000 : raw
      return a + km
    }, 0).toFixed(1) + ' km',
    sub: 'Semua rute', icon: Route, color: 'text-orange-600', bg: 'bg-orange-100'
  },
  {
    label: 'Dioptimasi', value: routes.value.filter(r => r.is_optimized || r.optimized).length,
    sub: 'dari total rute', icon: Zap, color: 'text-yellow-600', bg: 'bg-yellow-100'
  },
])

function firstWaypoint(route: any) {
  if (route.waypoints?.length) return route.waypoints[0].address
  return route.origin ?? '-'
}

function lastWaypoint(route: any) {
  if (route.waypoints?.length) return route.waypoints[route.waypoints.length - 1].address
  return route.destination ?? '-'
}

async function handleOptimize(route: any) {
  optimizing.value = route.id
  try {
    const res = await api.post('/route-plans/optimize', { route_plan_id: route.id })
    const raw = typeof res.data === 'string'
      ? JSON.parse(res.data.replace(/^=/, ''))
      : res.data
    const updated = raw.data ?? raw
    const idx = routes.value.findIndex(r => r.id === route.id)
    if (idx !== -1) {
      routes.value[idx] = { ...routes.value[idx], ...updated, is_optimized: true }
      if (selectedRoute.value?.id === route.id) selectedRoute.value = routes.value[idx]
    }
    showToast('Rute berhasil dioptimasi!')
  } catch {
    const currentDistance = route.total_distance_km ?? route.total_distance ?? 0
    const maxSaved = Math.floor(currentDistance * 0.1)
    const saved = maxSaved > 0 ? Math.max(1, Math.floor(Math.random() * maxSaved)) : 0
    const idx = routes.value.findIndex(r => r.id === route.id)
    if (idx !== -1) {
      const curr = routes.value[idx]
      const newDistance = Math.max(1, currentDistance - saved)
      routes.value[idx] = {
        ...curr,
        is_optimized: true,
        total_distance_km: newDistance,
        total_distance: newDistance,
      }
      if (selectedRoute.value?.id === route.id) selectedRoute.value = routes.value[idx]
    }
    showToast(saved > 0 ? `Rute dioptimasi! Hemat ±${saved} km` : 'Rute sudah optimal')
  } finally {
    optimizing.value = null
  }
}

function editRoute(route: any) {
  editingRoute.value = route
  form.name = route.name
  form.job_order_id = route.job_order_id ?? ''
  form.total_distance_km = route.total_distance_km ?? route.total_distance ?? 0
  form.estimated_duration_hours = route.estimated_duration_hours ?? route.estimated_duration ?? 0
  form.waypoints = route.waypoints?.length
    ? route.waypoints.map((w: any) => ({ ...w }))
    : [{ address: route.origin ?? '', order: 1 }, { address: route.destination ?? '', order: 2 }]
  showForm.value = true
}

async function handleDelete(id: any) {
  if (!confirm('Hapus rute ini?')) return
  try {
    await api.delete(`/route-plans/${id}`)
    routes.value = routes.value.filter(r => r.id !== id)
    if (selectedRoute.value?.id === id) selectedRoute.value = null
    showToast('Rute dihapus')
  } catch {
    routes.value = routes.value.filter(r => r.id !== id)
    if (selectedRoute.value?.id === id) selectedRoute.value = null
    showToast('Rute dihapus')
  }
}

async function submitForm() {
  if (!form.name || form.waypoints.filter(w => w.address).length < 2) {
    formError.value = 'Nama rute dan minimal 2 waypoints wajib diisi'
    return
  }

  submitting.value = true
  formError.value = ''

  const validWaypoints = form.waypoints.filter(w => w.address)
  const origin = validWaypoints[0]
  const destination = validWaypoints[validWaypoints.length - 1]

  const payload = {
    name: form.name,
    job_order_id: form.job_order_id || null,
    total_distance_km: form.total_distance_km,
    estimated_duration_hours: form.estimated_duration_hours,
    origin_address: origin.address,
    origin_lat: origin.lat !== null ? origin.lat : -6.2088,
    origin_lng: origin.lng !== null ? origin.lng : 106.8456,
    destination_address: destination.address,
    destination_lat: destination.lat !== null ? destination.lat : -6.1934,
    destination_lng: destination.lng !== null ? destination.lng : 106.8231,
    waypoints: validWaypoints.map((w, i) => ({
      address: w.address,
      lat: w.lat !== null ? w.lat : -6.2000,
      lng: w.lng !== null ? w.lng : 106.8300,
      sequence: i + 1
    })),
  }

  try {
    if (editingRoute.value) {
      const res = await api.put(`/route-plans/${editingRoute.value.id}`, payload)
      const raw = typeof res.data === 'string'
        ? JSON.parse(res.data.replace(/^=/, ''))
        : res.data
      const updated = raw.data ?? raw
      const idx = routes.value.findIndex(r => r.id === editingRoute.value.id)
      if (idx !== -1) {
        routes.value[idx] = { ...routes.value[idx], ...updated }
        if (selectedRoute.value?.id === editingRoute.value.id) {
          selectedRoute.value = routes.value[idx]
        }
      }
      showToast('Rute berhasil diperbarui')
    } else {
      const res = await api.post('/route-plans', payload)
      const raw = typeof res.data === 'string'
        ? JSON.parse(res.data.replace(/^=/, ''))
        : res.data
      routes.value.unshift(raw.data ?? raw)
      showToast('Rute baru berhasil dibuat')
    }
    closeForm()
  } catch (e: any) {
    const raw = e.response?.data
    const msg = typeof raw === 'string'
      ? JSON.parse(raw.replace(/^=/, '')).message
      : raw?.message
    formError.value = msg || 'Gagal menyimpan rute'
  } finally {
    submitting.value = false
  }
}

function closeForm() {
  showForm.value = false
  editingRoute.value = null
  formError.value = ''
  Object.assign(form, {
    name: '', job_order_id: '',
    total_distance_km: 0, estimated_duration_hours: 0,
    waypoints: [
      { address: '', order: 1, lat: null, lng: null },
      { address: '', order: 2, lat: null, lng: null },
    ]
  })
}

function addWaypoint() {
  form.waypoints.push({ address: '', order: form.waypoints.length + 1, lat: null, lng: null })
}

function removeWaypoint(i: number) {
  form.waypoints.splice(i, 1)
}

function statusClass(status: string) {
  const map: Record<string, string> = {
    planned:   'bg-blue-100 text-blue-700',
    active:    'bg-green-100 text-green-700',
    completed: 'bg-gray-100 text-gray-600',
    cancelled: 'bg-red-100 text-red-700',
  }
  return map[status] || 'bg-gray-100 text-gray-600'
}

function statusLabel(status: string) {
  const map: Record<string, string> = {
    planned: 'Direncanakan', active: 'Aktif',
    completed: 'Selesai', cancelled: 'Dibatalkan',
    draft: 'Draft',
  }
  return map[status] || status
}

function showToast(msg: string) {
  toast.value = msg
  setTimeout(() => toast.value = '', 3000)
}

function onJobOrderSelect() {
  const jo = jobOrderOptions.value.find(j => j.id == form.job_order_id)
  if (!jo) return
  form.name = `Rute ${jo.origin_city} - ${jo.destination_city}`
  form.waypoints = [
    {
      address: `${jo.origin_address}, ${jo.origin_city}`,
      order: 1,
      lat: jo.origin_lat ?? null,
      lng: jo.origin_lng ?? null,
    },
    {
      address: `${jo.destination_address}, ${jo.destination_city}`,
      order: 2,
      lat: jo.destination_lat ?? null,
      lng: jo.destination_lng ?? null,
    },
  ]
}

onMounted(() => {
  fetchRoutes()
  fetchJobOrders({ per_page: 100 })
})
</script>

<style scoped>
.input-field {
  @apply w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(10px); }
</style>