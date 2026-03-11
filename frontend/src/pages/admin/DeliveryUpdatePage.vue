<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold">Update Pengiriman</h1>
        <p class="text-sm text-gray-500 mt-1">Perbarui status pengiriman secara real-time</p>
      </div>
      <button @click="fetchJobs"
        class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition">
        <RefreshCw :class="`w-4 h-4 ${loading ? 'animate-spin' : ''}`" />
        Refresh
      </button>
    </div>

    <!-- Filter -->
    <div class="flex gap-2 flex-wrap">
      <button v-for="s in filters" :key="s.value"
        @click="filterStatus = s.value"
        :class="`px-3 py-1.5 rounded-full text-xs font-medium transition ${
          filterStatus === s.value
            ? 'bg-blue-600 text-white'
            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
        }`">
        {{ s.label }}
        <span v-if="s.value !== 'all'" class="ml-1 opacity-70">
          {{ countByStatus(s.value) }}
        </span>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="h-48 bg-gray-100 animate-pulse rounded-xl" />
    </div>

    <!-- Empty -->
    <div v-else-if="filteredJobs.length === 0"
      class="text-center py-16 text-gray-400">
      <Truck class="w-10 h-10 mx-auto mb-2 opacity-20" />
      <p class="text-sm">Tidak ada job order</p>
    </div>

    <!-- Job List -->
    <div v-else class="space-y-4">
      <div v-for="job in filteredJobs" :key="job.id"
        class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-start justify-between mb-4">
          <div>
            <p class="font-semibold text-blue-600">{{ job.job_number }}</p>
            <p class="text-sm text-gray-600">{{ job.customer_name }}</p>
            <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
              <MapPin class="w-3 h-3" />
              {{ job.origin_city }} → {{ job.destination_city }}
            </p>
          </div>
          <span :class="`text-xs px-2 py-0.5 rounded-full font-medium ${statusClass(job.status)}`">
            {{ statusLabel(job.status) }}
          </span>
        </div>

        <!-- Progress -->
        <div class="mb-4">
          <div class="flex justify-between text-xs text-gray-400 mb-1">
            <span>Progress</span>
            <span>{{ progressWidth(job.status) }}</span>
          </div>
          <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
            <div :class="`h-full rounded-full transition-all duration-500 ${progressColor(job.status)}`"
              :style="{ width: progressWidth(job.status) }" />
          </div>
        </div>

        <!-- Timeline -->
        <div class="mb-4">
          <p class="text-xs font-medium text-gray-600 mb-2">Timeline Status:</p>
          <div class="flex items-center gap-1 flex-wrap">
            <div v-for="(step, i) in statusSteps" :key="step.value"
              class="flex items-center gap-1">
              <div :class="`w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium ${
                isCompleted(job.status, step.value)
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-100 text-gray-400'
              }`">
                {{ i + 1 }}
              </div>
              <span :class="`text-xs ${
                isCompleted(job.status, step.value)
                  ? 'text-blue-600 font-medium'
                  : 'text-gray-400'
              }`">
                {{ step.label }}
              </span>
              <span v-if="i < statusSteps.length - 1" class="text-gray-300 text-xs">→</span>
            </div>
          </div>
        </div>

        <!-- Update Status Buttons -->
        <div class="flex gap-2 flex-wrap">
          <button v-for="next in nextStatuses(job.status)" :key="next.value"
            @click="updateStatus(job, next.value)"
            :disabled="updatingJob === job.id"
            :class="`px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center gap-1 disabled:opacity-50 ${next.class}`">
            <Loader2 v-if="updatingJob === job.id" class="w-3 h-3 animate-spin" />
            {{ next.label }}
          </button>
        </div>

        <!-- Notes -->
        <div class="mt-3">
          <input v-model="notes[job.id]"
            @keyup.enter="submitNote(job)"
            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Tambah catatan pengiriman... (Enter untuk kirim)" />
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
import { ref, computed, reactive, onMounted, onUnmounted } from 'vue'
import { MapPin, RefreshCw, Loader2, CheckCircle, Truck } from 'lucide-vue-next'
import api from '@/lib/axios'

// ── State ──────────────────────────────────────────────────
const jobOrders   = ref<any[]>([])
const loading     = ref(false)
const updatingJob = ref<number | null>(null)
const filterStatus = ref('all')
const notes       = reactive<Record<number, string>>({})
const toast       = ref('')
let pollInterval: ReturnType<typeof setInterval> | null = null

// ── Filters ────────────────────────────────────────────────
const filters = [
  { value: 'all',         label: 'Semua'           },
  { value: 'pending',     label: 'Pending'         },
  { value: 'assigned',    label: 'Ditugaskan'      },
  { value: 'in_progress', label: 'Diproses'        },
  { value: 'picked_up',   label: 'Picked Up'       },
  { value: 'in_transit',  label: 'Dalam Perjalanan'},
  { value: 'delivered',   label: 'Terkirim'        },
]

const statusSteps = [
  { value: 'pending',     label: 'Pending'   },
  { value: 'assigned',    label: 'Ditugaskan'},
  { value: 'in_progress', label: 'Diproses'  },
  { value: 'picked_up',   label: 'Picked Up' },
  { value: 'in_transit',  label: 'Di Jalan'  },
  { value: 'delivered',   label: 'Terkirim'  },
]

const statusOrder = ['pending','assigned','in_progress','picked_up','in_transit','delivered','completed']

// ── Computed ───────────────────────────────────────────────
const filteredJobs = computed(() => {
  if (filterStatus.value === 'all') return jobOrders.value
  return jobOrders.value.filter(j => j.status === filterStatus.value)
})

function countByStatus(status: string) {
  return jobOrders.value.filter(j => j.status === status).length || ''
}

// ── Fetch ──────────────────────────────────────────────────
async function fetchJobs() {
  loading.value = true
  try {
    // Ambil semua job yang aktif untuk driver ini
    const res = await api.get('/driver/my-jobs', {
      params: { filter: 'active', per_page: 100 }
    })
    const raw = typeof res.data === 'string'
      ? JSON.parse(res.data.replace(/^=/, ''))
      : res.data
    const result = raw?.data?.data ?? raw?.data ?? raw
    jobOrders.value = Array.isArray(result) ? result : []
  } catch (e) {
    console.error(e)
    jobOrders.value = []
  } finally {
    loading.value = false
  }
}

// ── Update Status ──────────────────────────────────────────
const NEXT_STATUS_MAP: Record<string, { value: string; label: string; class: string }[]> = {
  pending: [
    { value: 'assigned', label: 'Tugaskan', class: 'bg-blue-100 text-blue-700 hover:bg-blue-200' },
  ],
  assigned: [
    { value: 'in_progress', label: 'Mulai Proses', class: 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200' },
  ],
  in_progress: [
    { value: 'picked_up', label: 'Picked Up', class: 'bg-purple-100 text-purple-700 hover:bg-purple-200' },
  ],
  picked_up: [
    { value: 'in_transit', label: 'Mulai Perjalanan', class: 'bg-orange-100 text-orange-700 hover:bg-orange-200' },
  ],
  in_transit: [
    { value: 'delivered', label: 'Tandai Terkirim', class: 'bg-green-100 text-green-700 hover:bg-green-200' },
  ],
  delivered: [],
  completed: [],
  cancelled: [],
}

function nextStatuses(status: string) {
  return NEXT_STATUS_MAP[status] ?? []
}

async function updateStatus(job: any, newStatus: string) {
  updatingJob.value = job.id
  try {
    await api.patch(`/driver/my-jobs/${job.id}/update-status`, { status: newStatus })

    // Update lokal
    const idx = jobOrders.value.findIndex(j => j.id === job.id)
    if (idx !== -1) {
      if (newStatus === 'delivered') {
        jobOrders.value.splice(idx, 1)
      } else {
        jobOrders.value[idx] = { ...jobOrders.value[idx], status: newStatus }
      }
    }
    showToast(`Status diperbarui: ${statusLabel(newStatus)}`)
  } catch (e: any) {
    const raw = e.response?.data
    const msg = typeof raw === 'string'
      ? JSON.parse(raw.replace(/^=/, '')).message
      : raw?.message
    showToast(msg ?? 'Gagal update status')
    await fetchJobs() // sync ulang
  } finally {
    updatingJob.value = null
  }
}

async function submitNote(job: any) {
  const note = notes[job.id]?.trim()
  if (!note) return
  try {
    await api.patch(`/driver/my-jobs/${job.id}/update-status`, {
      status: job.status, // status sama, hanya update notes
      notes: note,
    })
    notes[job.id] = ''
    showToast('Catatan disimpan')
  } catch {
    showToast('Gagal menyimpan catatan')
  }
}

// ── Helpers ────────────────────────────────────────────────
function isCompleted(current: string, step: string) {
  return statusOrder.indexOf(current) >= statusOrder.indexOf(step)
}

function statusLabel(status: string) {
  const map: Record<string, string> = {
    pending:     'Pending',
    assigned:    'Ditugaskan',
    in_progress: 'Diproses',
    picked_up:   'Picked Up',
    in_transit:  'Dalam Perjalanan',
    delivered:   'Terkirim',
    completed:   'Selesai',
    cancelled:   'Dibatalkan',
  }
  return map[status] || status
}

function statusClass(status: string) {
  const map: Record<string, string> = {
    pending:     'bg-yellow-100 text-yellow-700',
    assigned:    'bg-blue-100 text-blue-700',
    in_progress: 'bg-indigo-100 text-indigo-700',
    picked_up:   'bg-purple-100 text-purple-700',
    in_transit:  'bg-orange-100 text-orange-700',
    delivered:   'bg-green-100 text-green-700',
    completed:   'bg-green-100 text-green-800',
    cancelled:   'bg-red-100 text-red-700',
  }
  return map[status] || 'bg-gray-100 text-gray-700'
}

function progressWidth(status: string) {
  const map: Record<string, string> = {
    pending:     '10%',
    assigned:    '25%',
    in_progress: '40%',
    picked_up:   '55%',
    in_transit:  '75%',
    delivered:   '100%',
    completed:   '100%',
    cancelled:   '0%',
  }
  return map[status] || '0%'
}

function progressColor(status: string) {
  const map: Record<string, string> = {
    pending:     'bg-yellow-400',
    assigned:    'bg-blue-400',
    in_progress: 'bg-indigo-400',
    picked_up:   'bg-purple-400',
    in_transit:  'bg-orange-400',
    delivered:   'bg-green-500',
    completed:   'bg-green-500',
    cancelled:   'bg-red-400',
  }
  return map[status] || 'bg-gray-300'
}

function showToast(msg: string) {
  toast.value = msg
  setTimeout(() => { toast.value = '' }, 3000)
}

// ── Lifecycle ──────────────────────────────────────────────
onMounted(async () => {
  await fetchJobs()
  pollInterval = setInterval(fetchJobs, 30_000)
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})
</script>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(10px); }
</style>