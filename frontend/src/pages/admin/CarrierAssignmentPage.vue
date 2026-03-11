<template>
  <div class="p-6 space-y-6">
   <!-- Ganti header section -->
<div class="flex items-center justify-between">
  <div>
    <h1 class="text-2xl font-bold">Penugasan Carrier</h1>
    <p class="text-sm text-gray-500 mt-1">Tugaskan kendaraan dan driver ke job order</p>
  </div>
  <div class="flex items-center gap-3">
    <span v-if="lastRefreshed" class="text-xs text-gray-400">
      Update: {{ lastRefreshed }}
    </span>
    <button @click="refreshAll" :disabled="loading"
      class="flex items-center gap-2 border border-gray-200 px-3 py-2 rounded-xl text-sm hover:bg-gray-50 transition disabled:opacity-50">
      <RefreshCw :class="`w-4 h-4 ${loading ? 'animate-spin' : ''}`" />
      Refresh
    </button>
  </div>
</div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-16">
      <Loader2 class="w-6 h-6 animate-spin text-blue-600" />
    </div>

    <template v-else>
      <!-- Pending Jobs -->
      <div class="bg-white rounded-xl border p-5 shadow-sm">
        <h2 class="font-semibold mb-4">Job Order Menunggu Penugasan
          <span class="ml-2 text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full">
            {{ pendingJobs.length }}
          </span>
        </h2>
        <div class="space-y-3">
          <div v-for="job in pendingJobs" :key="job.id"
            class="border rounded-xl p-4 hover:bg-gray-50 transition">
            <div class="flex items-start justify-between mb-3">
              <div>
                <p class="font-medium text-blue-600">{{ job.job_number }}</p>
                <p class="text-sm text-gray-600">{{ job.customer_name }}</p>
                <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                  <MapPin class="w-3 h-3" />
                  {{ job.origin_city }} → {{ job.destination_city }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5">
                  {{ job.cargo_type }} · {{ job.cargo_weight_kg?.toLocaleString() ?? '-' }} kg
                </p>
              </div>
              <PriorityBadge :priority="job.priority" />
            </div>

            <!-- Pilih Kendaraan -->
            <div class="mb-3">
            <!-- Pilih Kendaraan -->
<select v-model="assignments[job.id].vehicle_id"
  class="w-full mt-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
  <option value="">-- Pilih Kendaraan --</option>
  <option v-for="v in vehicles" :key="v.id" :value="v.id">
    {{ v.plate_number }} — {{ v.vehicle_type }}
    ({{ v.max_weight_kg ? Number(v.max_weight_kg).toLocaleString() + ' kg' : '-' }})
  </option>
</select>
            </div>

            <!-- Pilih Driver -->
            <div class="mb-3">
              <label class="text-xs font-medium text-gray-600">Pilih Driver</label>
              <select v-model="assignments[job.id].driver_id"
                class="w-full mt-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">-- Pilih Driver --</option>
                <option v-for="d in drivers" :key="d.id" :value="d.id">
                  {{ d.name }}
                  <template v-if="d.status"> ({{ d.status }})</template>
                </option>
              </select>
            </div>

            <button @click="handleAssign(job)"
              :disabled="!assignments[job.id].vehicle_id || !assignments[job.id].driver_id || assigning === job.id"
              class="w-full bg-blue-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2">
              <Loader2 v-if="assigning === job.id" class="w-4 h-4 animate-spin" />
              <UserCheck v-else class="w-4 h-4" />
              Tugaskan Kendaraan & Driver
            </button>

            <!-- Error -->
            <p v-if="assignError[job.id]" class="text-xs text-red-500 mt-2">
              {{ assignError[job.id] }}
            </p>
          </div>

          <div v-if="pendingJobs.length === 0" class="text-center py-8 text-gray-400">
            <CheckCircle class="w-10 h-10 mx-auto mb-2 opacity-30" />
            <p class="text-sm">Semua job order sudah ditugaskan</p>
          </div>
        </div>
      </div>

      <!-- Assigned Jobs -->
      <div class="bg-white rounded-xl border p-5 shadow-sm">
        <h2 class="font-semibold mb-4">Sudah Ditugaskan
          <span class="ml-2 text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">
            {{ assignedJobs.length }}
          </span>
        </h2>
        <div class="space-y-2">
          <div v-for="job in assignedJobs" :key="job.id"
            class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition">
            <CheckCircle class="w-5 h-5 text-green-500 flex-shrink-0" />
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium">{{ job.job_number }} — {{ job.customer_name }}</p>
              <p class="text-xs text-gray-400">{{ job.origin_city }} → {{ job.destination_city }}</p>
              <p class="text-xs text-gray-400" v-if="job.driver">
                Driver: {{ job.driver.name }}
              </p>
            </div>
            <StatusBadge :status="job.status" />
          </div>

          <div v-if="assignedJobs.length === 0" class="text-center py-6 text-gray-400">
            <p class="text-sm">Belum ada yang ditugaskan</p>
          </div>
        </div>
      </div>

      <!-- In Progress / Transit -->
      <div class="bg-white rounded-xl border p-5 shadow-sm">
        <h2 class="font-semibold mb-4">Sedang Berjalan
          <span class="ml-2 text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">
            {{ activeJobs.length }}
          </span>
        </h2>
        <div class="space-y-2">
          <div v-for="job in activeJobs" :key="job.id"
            class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition">
            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
              <Truck class="w-4 h-4 text-blue-600" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium">{{ job.job_number }} — {{ job.customer_name }}</p>
              <p class="text-xs text-gray-400">{{ job.origin_city }} → {{ job.destination_city }}</p>
              <p class="text-xs text-gray-400" v-if="job.driver">Driver: {{ job.driver.name }}</p>
            </div>
            <StatusBadge :status="job.status" />
          </div>

          <div v-if="activeJobs.length === 0" class="text-center py-6 text-gray-400">
            <p class="text-sm">Tidak ada pengiriman aktif</p>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onMounted, onUnmounted, watch } from 'vue'
import { CheckCircle, Loader2, MapPin, Truck, UserCheck, RefreshCw } from 'lucide-vue-next'
import api from '@/lib/axios'
import { useJobOrders } from '@/composables/useJobOrders'
import StatusBadge from './StatusBadge.vue'
import PriorityBadge from './PriorityBadge.vue'

const { jobOrders, loading, fetchJobOrders } = useJobOrders()

const vehicles       = ref<any[]>([])
const drivers        = ref<any[]>([])
const assigning      = ref<number | null>(null)
const lastRefreshed  = ref('')
const assignments    = reactive<Record<number, { vehicle_id: number | ''; driver_id: number | '' }>>({})
const assignError    = reactive<Record<number, string>>({})

let pollInterval: ReturnType<typeof setInterval> | null = null

// ── Computed ──────────────────────────────────────────────────────────
const pendingJobs = computed(() =>
  jobOrders.value.filter(j => j.status === 'pending' || j.status === 'draft')
)
const assignedJobs = computed(() =>
  jobOrders.value.filter(j => j.status === 'assigned')
)
const activeJobs = computed(() =>
  jobOrders.value.filter(j =>
    ['in_progress', 'picked_up', 'in_transit'].includes(j.status)
  )
)

// Kendaraan aktif saja yang muncul di dropdown
const activeVehicles = computed(() =>
  vehicles.value.filter(v => v.status === 'active')
)

// ── Watch: init form tiap kali pendingJobs berubah ────────────────────
watch(pendingJobs, () => initAssignments(), { immediate: true })

function initAssignments() {
  for (const job of pendingJobs.value) {
    if (!assignments[job.id]) {
      assignments[job.id] = { vehicle_id: '', driver_id: '' }
    }
  }
}

// ── Fetch Kendaraan — ambil dari /admin/carriers sama seperti VehiclePage ──

// Di CarrierAssignmentPage.vue fetchVehicles
async function fetchVehicles() {
  try {
    const res = await api.get('/admin/carriers', { params: { per_page: 100 } })
    const raw = typeof res.data === 'string'
      ? JSON.parse(res.data.replace(/^=/, ''))
      : res.data
    const carrierList = raw.data?.data ?? raw.data ?? []
    console.log('carrier 0 vehicles:', carrierList[0]?.vehicles) // ← cek ini

    const allVehicles: any[] = []
    carrierList.forEach((carrier: any) => {
      const vehicleList = carrier.vehicles ?? []
      vehicleList.forEach((v: any) => {
        if (v.status === 'available') {
          allVehicles.push({ ...v, carrier_name: carrier.name })
        }
      })
    })
    console.log('allVehicles:', allVehicles)
    vehicles.value = allVehicles
  } catch (e) {
    console.error(e)
    vehicles.value = []
  }
}

async function fetchDrivers() {
  try {
    const res = await api.get('/admin/users', { params: { role: 'driver', per_page: 100 } })
    const raw = typeof res.data === 'string'
      ? JSON.parse(res.data.replace(/^=/, ''))
      : res.data
    drivers.value = raw.data?.data ?? raw.data ?? []
  } catch {
    drivers.value = []
  }
}

// ── Refresh semua data (dipanggil manual & polling) ───────────────────
async function refreshAll() {
  await Promise.all([
    fetchJobOrders({ per_page: 100 }),
    fetchVehicles(),
    fetchDrivers(),
  ])
  lastRefreshed.value = new Date().toLocaleTimeString('id-ID', {
    hour: '2-digit', minute: '2-digit', second: '2-digit'
  })
}

// ── Assign ────────────────────────────────────────────────────────────
async function handleAssign(job: any) {
  const form = assignments[job.id]
  if (!form.vehicle_id || !form.driver_id) return

  assigning.value   = job.id
  assignError[job.id] = ''

  try {
    const res = await api.post(`/job-orders/${job.id}/assign-driver`, {
      vehicle_id: form.vehicle_id,
      driver_id:  form.driver_id,
    })
    const raw = typeof res.data === 'string'
      ? JSON.parse(res.data.replace(/^=/, ''))
      : res.data

    if (raw.success) {
      // Update local state langsung tanpa tunggu polling
      const idx = jobOrders.value.findIndex(j => j.id === job.id)
      if (idx !== -1) {
        jobOrders.value[idx].status = 'assigned'
        const driver  = drivers.value.find(d => d.id === form.driver_id)
        const vehicle = vehicles.value.find(v => v.id === form.vehicle_id)
        if (driver)  jobOrders.value[idx].driver  = { id: driver.id, name: driver.name }
        if (vehicle) jobOrders.value[idx].vehicle = { id: vehicle.id, plate_number: vehicle.plate_number }
      }
      assignments[job.id] = { vehicle_id: '', driver_id: '' }
    }
  } catch (e: any) {
    const raw = e.response?.data
    const msg = typeof raw === 'string'
      ? JSON.parse(raw.replace(/^=/, '')).message
      : raw?.message
    assignError[job.id] = msg || 'Gagal menugaskan kendaraan & driver'
  } finally {
    assigning.value = null
  }
}

// ── Lifecycle ─────────────────────────────────────────────────────────
onMounted(async () => {
  await refreshAll()
  // Polling tiap 30 detik — data kendaraan & job order selalu fresh
  pollInterval = setInterval(refreshAll, 30000)
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})
</script>