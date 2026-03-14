<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold">Penugasan Carrier</h1>
        <p class="text-sm text-gray-500 mt-1">Tugaskan kendaraan dan driver ke job order</p>
      </div>
      <div class="flex items-center gap-3">
        <span v-if="lastRefreshed" class="text-xs text-gray-400">Update: {{ lastRefreshed }}</span>
        <button @click="refreshAll" :disabled="loading"
          class="flex items-center gap-2 border border-gray-200 px-3 py-2 rounded-xl text-sm hover:bg-gray-50 transition disabled:opacity-50">
          <RefreshCw :class="`w-4 h-4 ${loading ? 'animate-spin' : ''}`" />
          Refresh
        </button>
      </div>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-16">
      <Loader2 class="w-6 h-6 animate-spin text-blue-600" />
    </div>

    <template v-else>
      <!-- ① PENDING — belum ada driver -->
      <div class="bg-white rounded-xl border p-5 shadow-sm">
        <h2 class="font-semibold mb-4 flex items-center gap-2">
          Menunggu Penugasan
          <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full">{{ pendingJobs.length }}</span>
        </h2>
        <div class="space-y-3">
          <div v-for="job in pendingJobs" :key="job.id"
            class="border rounded-xl p-4 hover:bg-gray-50 transition">
            <div class="flex items-start justify-between mb-3">
              <div>
                <p class="font-medium text-blue-600">{{ job.job_number }}</p>
                <p class="text-sm text-gray-600">{{ job.customer_name }}</p>
                <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                  <MapPin class="w-3 h-3" />{{ job.origin_city }} → {{ job.destination_city }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5">
                  {{ job.cargo_type }} · {{ job.cargo_weight_kg?.toLocaleString() ?? '-' }} kg
                </p>
              </div>
              <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full font-medium">Pending</span>
            </div>

            <div class="space-y-2 mb-3">
              <div>
                <label class="text-xs font-medium text-gray-600">Kendaraan</label>
                <select v-model="assignments[job.id].vehicle_id"
                  class="w-full mt-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                  <option value="">-- Pilih Kendaraan --</option>
                  <option v-for="v in vehicles" :key="v.id" :value="v.id">
                    {{ v.plate_number }} — {{ v.vehicle_type }}
                    ({{ v.max_weight_kg ? Number(v.max_weight_kg).toLocaleString() + ' kg' : '-' }})
                  </option>
                </select>
              </div>
              <div>
                <label class="text-xs font-medium text-gray-600">Driver</label>
                <select v-model="assignments[job.id].driver_id"
                  class="w-full mt-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                  <option value="">-- Pilih Driver --</option>
                  <option v-for="d in drivers" :key="d.id" :value="d.id">
                    {{ d.name }}
                  </option>
                </select>
              </div>
            </div>

            <button @click="handleAssign(job)"
              :disabled="!assignments[job.id]?.vehicle_id || !assignments[job.id]?.driver_id || assigning === job.id"
              class="w-full bg-blue-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition disabled:opacity-40 flex items-center justify-center gap-2">
              <Loader2 v-if="assigning === job.id" class="w-4 h-4 animate-spin" />
              <Send v-else class="w-4 h-4" />
              Tugaskan & Kirim Notif ke Driver
            </button>

            <p v-if="assignError[job.id]" class="text-xs text-red-500 mt-2">{{ assignError[job.id] }}</p>
          </div>

          <div v-if="pendingJobs.length === 0" class="text-center py-8 text-gray-400">
            <CheckCircle class="w-10 h-10 mx-auto mb-2 opacity-30" />
            <p class="text-sm">Semua job order sudah ditugaskan</p>
          </div>
        </div>
      </div>

      <!-- ② WAITING — menunggu konfirmasi driver -->
      <div class="bg-white rounded-xl border p-5 shadow-sm">
        <h2 class="font-semibold mb-4 flex items-center gap-2">
          Menunggu Konfirmasi Driver
          <span class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full">{{ waitingJobs.length }}</span>
        </h2>
        <div class="space-y-2">
          <div v-for="job in waitingJobs" :key="job.id"
            class="flex items-center gap-3 p-3 rounded-lg border border-orange-100 bg-orange-50/30">
            <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0">
              <Clock class="w-4 h-4 text-orange-600" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium">{{ job.job_number }} — {{ job.customer_name }}</p>
              <p class="text-xs text-gray-400">{{ job.origin_city }} → {{ job.destination_city }}</p>
              <p class="text-xs text-orange-600 font-medium mt-0.5">
                ⏳ Menunggu konfirmasi {{ job.driver?.name ?? 'driver' }}
              </p>
            </div>
            <!-- Batalkan penugasan -->
            <button @click="handleCancelAssign(job)"
              class="text-xs text-red-500 hover:text-red-700 border border-red-200 px-2 py-1 rounded-lg transition">
              Batalkan
            </button>
          </div>

          <div v-if="waitingJobs.length === 0" class="text-center py-6 text-gray-400">
            <p class="text-sm">Tidak ada yang menunggu konfirmasi</p>
          </div>
        </div>
      </div>

      <!-- ③ ASSIGNED & ACTIVE -->
      <div class="bg-white rounded-xl border p-5 shadow-sm">
        <h2 class="font-semibold mb-4 flex items-center gap-2">
          Sudah Berjalan
          <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">{{ runningJobs.length }}</span>
        </h2>
        <div class="space-y-2">
          <div v-for="job in runningJobs" :key="job.id"
            class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition">
            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
              <Truck class="w-4 h-4 text-green-600" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium">{{ job.job_number }} — {{ job.customer_name }}</p>
              <p class="text-xs text-gray-400">{{ job.origin_city }} → {{ job.destination_city }}</p>
              <p class="text-xs text-gray-500" v-if="job.driver">Driver: {{ job.driver.name }}</p>
            </div>
            <span :class="`text-xs px-2 py-0.5 rounded-full font-medium ${statusClass(job.status)}`">
              {{ statusLabel(job.status) }}
            </span>
          </div>

          <div v-if="runningJobs.length === 0" class="text-center py-6 text-gray-400">
            <p class="text-sm">Belum ada pengiriman berjalan</p>
          </div>
        </div>
      </div>
    </template>

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
import { ref, computed, reactive, onMounted, onUnmounted, watch } from 'vue'
import { CheckCircle, Loader2, MapPin, Truck, RefreshCw, Clock, Send } from 'lucide-vue-next'
import api from '@/lib/axios'

const jobOrders     = ref<any[]>([])
const vehicles      = ref<any[]>([])
const drivers       = ref<any[]>([])
const loading       = ref(false)
const assigning     = ref<number | null>(null)
const lastRefreshed = ref('')
const toast         = ref('')
const assignments   = reactive<Record<number, { vehicle_id: number | ''; driver_id: number | '' }>>({})
const assignError   = reactive<Record<number, string>>({})

let pollInterval: ReturnType<typeof setInterval> | null = null

// ── Computed ──────────────────────────────────────────────
const pendingJobs = computed(() =>
  jobOrders.value.filter(j =>
    (j.status === 'pending' || j.status === 'draft') &&
    j.driver_acceptance !== 'waiting'
  )
)

const waitingJobs = computed(() =>
  jobOrders.value.filter(j => j.driver_acceptance === 'waiting')
)

const runningJobs = computed(() =>
  jobOrders.value.filter(j =>
    ['assigned', 'in_progress', 'picked_up', 'in_transit', 'delivered', 'completed'].includes(j.status) &&
    j.driver_acceptance === 'accepted'
  )
)

watch(pendingJobs, initAssignments, { immediate: true })

function initAssignments() {
  for (const job of pendingJobs.value) {
    if (!assignments[job.id]) {
      assignments[job.id] = { vehicle_id: '', driver_id: '' }
    }
  }
}

// ── Fetch ─────────────────────────────────────────────────
async function fetchJobOrders() {
  const res = await api.get('/job-orders', { params: { per_page: 100 } })
  const raw = typeof res.data === 'string'
    ? JSON.parse(res.data.replace(/^=/, ''))
    : res.data
  jobOrders.value = raw.data?.data ?? raw.data ?? []
}

async function fetchVehicles() {
  try {
    const res = await api.get('/admin/carriers', { params: { per_page: 100 } })
    const raw = typeof res.data === 'string'
      ? JSON.parse(res.data.replace(/^=/, ''))
      : res.data
    const carrierList = raw.data?.data ?? raw.data ?? []
    const all: any[] = []
    carrierList.forEach((carrier: any) => {
      (carrier.vehicles ?? []).forEach((v: any) => {
        if (v.status === 'available') all.push({ ...v, carrier_name: carrier.name })
      })
    })
    vehicles.value = all
  } catch { vehicles.value = [] }
}

async function fetchDrivers() {
  try {
    const res = await api.get('/admin/users', { params: { role: 'driver', per_page: 100 } })
    const raw = typeof res.data === 'string'
      ? JSON.parse(res.data.replace(/^=/, ''))
      : res.data
    drivers.value = raw.data?.data ?? raw.data ?? []
  } catch { drivers.value = [] }
}

async function refreshAll() {
  loading.value = true
  try {
    await Promise.all([fetchJobOrders(), fetchVehicles(), fetchDrivers()])
    lastRefreshed.value = new Date().toLocaleTimeString('id-ID', {
      hour: '2-digit', minute: '2-digit', second: '2-digit'
    })
  } finally {
    loading.value = false
  }
}

// ── Assign → kirim notif ke driver ────────────────────────
async function handleAssign(job: any) {
  const form = assignments[job.id]
  if (!form?.vehicle_id || !form?.driver_id) return

  assigning.value     = job.id
  assignError[job.id] = ''

  try {
    await api.post(`/job-orders/${job.id}/assign-driver`, {
      vehicle_id: form.vehicle_id,
      driver_id:  form.driver_id,
    })

    showToast('Notifikasi dikirim ke driver, menunggu konfirmasi...')
    await fetchJobOrders()
    assignments[job.id] = { vehicle_id: '', driver_id: '' }
  } catch (e: any) {
    const raw = e.response?.data
    const msg = typeof raw === 'string'
      ? JSON.parse(raw.replace(/^=/, '')).message
      : raw?.message
    assignError[job.id] = msg || 'Gagal menugaskan'
  } finally {
    assigning.value = null
  }
}

// ── Batalkan penugasan yang masih waiting ─────────────────
async function handleCancelAssign(job: any) {
  try {
    await api.patch(`/job-orders/${job.id}/status`, { status: 'pending' })
    // Reset driver acceptance
    showToast('Penugasan dibatalkan')
    await fetchJobOrders()
  } catch {
    showToast('Gagal membatalkan')
  }
}

// ── Helpers ───────────────────────────────────────────────
function statusLabel(s: string) {
  const map: Record<string, string> = {
    assigned: 'Diterima', in_progress: 'Diproses',
    picked_up: 'Picked Up', in_transit: 'Di Jalan',
    delivered: 'Terkirim', completed: 'Selesai',
  }
  return map[s] ?? s
}

function statusClass(s: string) {
  const map: Record<string, string> = {
    assigned:    'bg-blue-100 text-blue-700',
    in_progress: 'bg-indigo-100 text-indigo-700',
    picked_up:   'bg-purple-100 text-purple-700',
    in_transit:  'bg-orange-100 text-orange-700',
    delivered:   'bg-teal-100 text-teal-700',
    completed:   'bg-green-100 text-green-700',
  }
  return map[s] ?? 'bg-gray-100 text-gray-600'
}

function showToast(msg: string) {
  toast.value = msg
  setTimeout(() => toast.value = '', 3000)
}

// ── Lifecycle ─────────────────────────────────────────────
onMounted(async () => {
  await refreshAll()
  pollInterval = setInterval(refreshAll, 30_000)
})
onUnmounted(() => { if (pollInterval) clearInterval(pollInterval) })
</script>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(10px); }
</style>