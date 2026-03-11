<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Dispatch</h1>
        <p class="text-sm text-gray-500 mt-1">Kelola pengiriman yang siap diberangkatkan</p>
      </div>
      <button @click="fetchJobOrders({ per_page: 100 })"
        class="flex items-center gap-2 border border-gray-200 hover:bg-gray-50 text-gray-600 px-4 py-2 rounded-xl text-sm font-medium transition">
        <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
        Refresh
      </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div v-for="stat in stats" :key="stat.label"
        class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
        <div class="flex items-center justify-between">
          <p class="text-xs text-gray-500">{{ stat.label }}</p>
          <div :class="`w-8 h-8 rounded-lg ${stat.bg} flex items-center justify-center`">
            <component :is="stat.icon" :class="`w-4 h-4 ${stat.color}`" />
          </div>
        </div>
        <p class="text-2xl font-bold mt-2 text-gray-800">
          <span v-if="loading" class="inline-block w-8 h-6 bg-gray-200 animate-pulse rounded" />
          <span v-else>{{ stat.value }}</span>
        </p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-16">
      <Loader2 class="w-6 h-6 animate-spin text-blue-600" />
    </div>

    <template v-else>
      <!-- Pending — belum ada driver -->
      <div v-if="pendingJobs.length > 0" class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-center gap-2 mb-4">
          <h2 class="font-semibold text-gray-800">Menunggu Assignment Driver</h2>
          <span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-medium">
            {{ pendingJobs.length }} job
          </span>
        </div>
        <div class="space-y-3">
          <div v-for="job in pendingJobs" :key="job.id"
            class="border border-red-100 rounded-2xl p-4 bg-red-50/30">
            <div class="flex items-start justify-between mb-2">
              <div>
                <p class="font-semibold text-blue-600">{{ job.job_number }}</p>
                <p class="text-sm text-gray-600">{{ job.customer_name }}</p>
                <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                  <MapPin class="w-3 h-3" />
                  {{ job.origin_city }} → {{ job.destination_city }}
                </p>
              </div>
              <PriorityBadge :priority="job.priority" />
            </div>
            <p class="text-xs text-red-500 font-medium">⚠ Belum ada driver — assign driver dulu</p>
          </div>
        </div>
      </div>

      <!-- Siap Diberangkatkan -->
      <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-center gap-2 mb-4">
          <h2 class="font-semibold text-gray-800">Siap Diberangkatkan</h2>
          <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">
            {{ readyJobs.length }} job
          </span>
        </div>
        <div class="space-y-3">
          <div v-for="job in readyJobs" :key="job.id"
            class="border border-gray-100 rounded-2xl p-4 hover:shadow-md transition bg-gradient-to-br from-white to-blue-50/30">
            <div class="flex items-start justify-between mb-3">
              <div>
                <p class="font-semibold text-blue-600">{{ job.job_number }}</p>
                <p class="text-sm text-gray-600">{{ job.customer_name }}</p>
                <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                  <MapPin class="w-3 h-3" />
                  {{ job.origin_city }} → {{ job.destination_city }}
                </p>
              </div>
              <div class="text-right space-y-1">
                <PriorityBadge :priority="job.priority" />
                <StatusBadge :status="job.status" />
                <p class="text-xs text-gray-400">
                  {{ job.pickup_scheduled_at ? formatDate(job.pickup_scheduled_at) : '-' }}
                </p>
              </div>
            </div>

            <div class="flex items-center gap-3 mb-3 text-xs text-gray-500">
              <span class="flex items-center gap-1">
                <Package class="w-3 h-3" />
                {{ job.cargo_type }}
              </span>
              <span>{{ job.cargo_weight_kg?.toLocaleString() ?? '-' }} kg</span>
              <span v-if="job.driver?.name" class="flex items-center gap-1 text-green-600">
                <User class="w-3 h-3" />
                {{ job.driver.name }}
              </span>
            </div>

            <div class="flex gap-2">
              <button @click="handleDispatch(job)"
                :disabled="dispatching === job.id"
                :class="`flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-medium transition disabled:opacity-60 ${
                  job.status === 'assigned'
                    ? 'bg-blue-600 hover:bg-blue-700 text-white'
                    : 'bg-orange-500 hover:bg-orange-600 text-white'
                }`">
                <Loader2 v-if="dispatching === job.id" class="w-4 h-4 animate-spin" />
                <Truck v-else class="w-4 h-4" />
                {{ dispatchLabel(job.status) }}
              </button>
              <button @click="selectedJob = job"
                class="px-4 border border-gray-200 text-gray-600 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-50 transition">
                Detail
              </button>
            </div>
          </div>

          <div v-if="readyJobs.length === 0" class="text-center py-8 text-gray-400">
            <Truck class="w-10 h-10 mx-auto mb-2 opacity-30" />
            <p class="text-sm">Tidak ada pengiriman yang siap diberangkatkan</p>
          </div>
        </div>
      </div>

      <!-- In Transit -->
      <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-center gap-2 mb-4">
          <h2 class="font-semibold text-gray-800">Sedang Dalam Perjalanan</h2>
          <span class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full font-medium">
            {{ inTransitJobs.length }} job
          </span>
        </div>
        <div class="space-y-2">
          <div v-for="job in inTransitJobs" :key="job.id"
            class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition border border-gray-50 cursor-pointer"
            @click="selectedJob = job">
            <div class="w-9 h-9 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0">
              <Truck class="w-4 h-4 text-orange-600" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-800">{{ job.job_number }} — {{ job.customer_name }}</p>
              <p class="text-xs text-gray-400">{{ job.origin_city }} → {{ job.destination_city }}</p>
              <p v-if="job.driver?.name" class="text-xs text-green-600 mt-0.5">
                Driver: {{ job.driver.name }}
              </p>
            </div>
            <div class="text-right">
              <StatusBadge :status="job.status" />
              <p class="text-xs text-gray-400 mt-1">
                {{ job.cargo_weight_kg?.toLocaleString() ?? '-' }} kg
              </p>
            </div>
          </div>
          <div v-if="inTransitJobs.length === 0" class="text-center py-6 text-gray-400">
            <p class="text-sm">Tidak ada pengiriman dalam perjalanan</p>
          </div>
        </div>
      </div>

      <!-- Selesai Hari Ini -->
      <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-center gap-2 mb-4">
          <h2 class="font-semibold text-gray-800">Selesai Hari Ini</h2>
          <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">
            {{ todayCompletedJobs.length }} job
          </span>
        </div>
        <div class="space-y-2">
          <div v-for="job in todayCompletedJobs" :key="job.id"
            class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition cursor-pointer"
            @click="selectedJob = job">
            <div class="w-9 h-9 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
              <CheckCircle class="w-4 h-4 text-green-600" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-800">{{ job.job_number }} — {{ job.customer_name }}</p>
              <p class="text-xs text-gray-400">{{ job.origin_city }} → {{ job.destination_city }}</p>
            </div>
            <div class="text-right">
              <StatusBadge :status="job.status" />
              <p class="text-xs text-gray-400 mt-1">
                {{ job.delivery_actual_at ? formatDate(job.delivery_actual_at) : '-' }}
              </p>
            </div>
          </div>
          <div v-if="todayCompletedJobs.length === 0" class="text-center py-6 text-gray-400">
            <p class="text-sm">Belum ada pengiriman selesai hari ini</p>
          </div>
        </div>
      </div>
    </template>

    <!-- Modal Detail -->
    <div v-if="selectedJob" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
      @click.self="selectedJob = null">
      <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h3 class="font-semibold text-gray-800">{{ selectedJob.job_number }}</h3>
          <button @click="selectedJob = null" class="p-1.5 hover:bg-gray-100 rounded-lg">
            <X class="w-5 h-5 text-gray-500" />
          </button>
        </div>
        <div class="p-6 space-y-3">
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Customer</span>
            <span class="font-medium">{{ selectedJob.customer_name }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Asal</span>
            <span class="font-medium">{{ selectedJob.origin_address }}, {{ selectedJob.origin_city }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Tujuan</span>
            <span class="font-medium">{{ selectedJob.destination_address }}, {{ selectedJob.destination_city }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Kargo</span>
            <span class="font-medium">{{ selectedJob.cargo_type }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Berat</span>
            <span class="font-medium">{{ selectedJob.cargo_weight_kg?.toLocaleString() ?? '-' }} kg</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Driver</span>
            <span class="font-medium">{{ selectedJob.driver?.name ?? '-' }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Pickup</span>
            <span class="font-medium">{{ selectedJob.pickup_scheduled_at ? formatDate(selectedJob.pickup_scheduled_at) : '-' }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Estimasi Tiba</span>
            <span class="font-medium">{{ selectedJob.delivery_scheduled_at ? formatDate(selectedJob.delivery_scheduled_at) : '-' }}</span>
          </div>
          <div class="flex justify-between text-sm items-center">
            <span class="text-gray-500">Status</span>
            <StatusBadge :status="selectedJob.status" />
          </div>
        </div>
        <div class="flex gap-2 px-6 py-4 border-t">
          <button v-if="['assigned', 'in_progress'].includes(selectedJob.status)"
            @click="handleDispatch(selectedJob); selectedJob = null"
            :disabled="dispatching === selectedJob.id"
            class="flex-1 bg-blue-600 text-white py-2 rounded-xl text-sm font-medium hover:bg-blue-700 disabled:opacity-50 transition">
            {{ dispatchLabel(selectedJob.status) }}
          </button>
          <button @click="selectedJob = null"
            class="flex-1 px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-xl transition">
            Tutup
          </button>
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
import { ref, computed, onMounted } from 'vue'
import { Truck, Package, MapPin, CheckCircle, Clock, Loader2, X, RefreshCw, User } from 'lucide-vue-next'
import { useJobOrders } from '@/composables/useJobOrders'
import StatusBadge from './StatusBadge.vue'
import PriorityBadge from './PriorityBadge.vue'

const { jobOrders, loading, fetchJobOrders, updateStatus } = useJobOrders()

const dispatching = ref<number | null>(null)
const selectedJob = ref<any>(null)
const toast       = ref('')

// ── Computed ──────────────────────────────────────────────
const pendingJobs = computed(() =>
  jobOrders.value.filter(j => j.status === 'pending')
)

const readyJobs = computed(() =>
  jobOrders.value.filter(j => ['assigned', 'in_progress'].includes(j.status))
)

const inTransitJobs = computed(() =>
  jobOrders.value.filter(j => ['picked_up', 'in_transit'].includes(j.status))
)

const completedJobs = computed(() =>
  jobOrders.value.filter(j => ['delivered', 'completed'].includes(j.status))
)

// Filter selesai hari ini
const todayCompletedJobs = computed(() => {
  const today = new Date().toDateString()
  return completedJobs.value.filter(j => {
    const d = new Date(j.delivery_actual_at ?? j.updated_at)
    return d.toDateString() === today
  })
})

const stats = computed(() => [
  { label: 'Siap Berangkat',   value: readyJobs.value.length,      icon: Clock,       color: 'text-yellow-600', bg: 'bg-yellow-50' },
  { label: 'Dalam Perjalanan', value: inTransitJobs.value.length,  icon: Truck,       color: 'text-orange-600', bg: 'bg-orange-50' },
  { label: 'Selesai Hari Ini', value: todayCompletedJobs.value.length, icon: CheckCircle, color: 'text-green-600', bg: 'bg-green-50' },
  { label: 'Total',            value: jobOrders.value.length,       icon: Package,     color: 'text-blue-600',   bg: 'bg-blue-50'  },
])

// ── Dispatch ──────────────────────────────────────────────
async function handleDispatch(job: any) {
  dispatching.value = job.id
  try {
    if (job.status === 'assigned') {
      await updateStatus(job.id, 'in_progress')
      showToast(`${job.job_number} — Driver berangkat menuju pickup`)
    } else if (job.status === 'in_progress') {
      await updateStatus(job.id, 'picked_up')
      showToast(`${job.job_number} — Kargo sudah diambil`)
    }
  } finally {
    dispatching.value = null
  }
}

function dispatchLabel(status: string) {
  const map: Record<string, string> = {
    assigned:    'Mulai Proses',
    in_progress: 'Konfirmasi Pickup',
  }
  return map[status] ?? 'Update Status'
}

function formatDate(val: string) {
  if (!val) return '-'
  return new Date(val).toLocaleDateString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

function showToast(msg: string) {
  toast.value = msg
  setTimeout(() => toast.value = '', 3000)
}

onMounted(() => fetchJobOrders({ per_page: 100 }))
</script>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(10px); }
</style>