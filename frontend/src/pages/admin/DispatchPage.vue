<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-2xl font-bold">Dispatch</h1>
      <p class="text-sm text-gray-500 mt-1">Kelola pengiriman yang siap diberangkatkan</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div v-for="stat in stats" :key="stat.label"
        class="bg-white rounded-xl border p-4 shadow-sm">
        <div class="flex items-center justify-between">
          <p class="text-xs text-gray-500">{{ stat.label }}</p>
          <div :class="`w-8 h-8 rounded-lg ${stat.bg} flex items-center justify-center`">
            <component :is="stat.icon" :class="`w-4 h-4 ${stat.color}`" />
          </div>
        </div>
        <p class="text-2xl font-bold mt-2">{{ stat.value }}</p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-16">
      <Loader2 class="w-6 h-6 animate-spin text-blue-600" />
    </div>

    <template v-else>
      <!-- Ready to Dispatch -->
      <div class="bg-white rounded-xl border p-5 shadow-sm">
        <h2 class="font-semibold mb-4">Siap Diberangkatkan</h2>
        <div class="space-y-3">
          <div v-for="job in readyJobs" :key="job.id"
            class="border rounded-xl p-4 hover:bg-gray-50 transition">
            <div class="flex items-start justify-between mb-3">
              <div>
                <p class="font-medium text-blue-600">{{ job.job_number }}</p>
                <p class="text-sm text-gray-600">{{ job.customer_name }}</p>
                <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                  <MapPin class="w-3 h-3" />
                  {{ job.origin_city }} → {{ job.destination_city }}
                </p>
              </div>
              <div class="text-right">
                <PriorityBadge :priority="job.priority" />
                <p class="text-xs text-gray-400 mt-1">
                  {{ job.pickup_scheduled_at ? formatDate(job.pickup_scheduled_at) : '-' }}
                </p>
              </div>
            </div>

            <div class="flex items-center gap-3 mb-3 text-xs text-gray-500">
              <span class="flex items-center gap-1">
                <Package class="w-3 h-3" />
                {{ job.cargo_type }}
              </span>
              <span>{{ job.cargo_weight_kg ? job.cargo_weight_kg.toLocaleString() + ' kg' : '-' }}</span>
            </div>

            <div class="flex gap-2">
              #rubah jangan buta mbut
              <button @click="handleDispatch(job)">
  <Loader2 v-if="dispatching === job.id" class="w-4 h-4 animate-spin" />
  <Truck v-else class="w-4 h-4" />
  {{ dispatchLabel(job.status) }}
</button>
              <button @click="selectedJob = job"
                class="px-4 border border-gray-300 text-gray-600 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
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
      <div class="bg-white rounded-xl border p-5 shadow-sm">
        <h2 class="font-semibold mb-4">Sedang Dalam Perjalanan</h2>
        <div class="space-y-2">
          <div v-for="job in inTransitJobs" :key="job.id"
            class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition">
            <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0">
              <Truck class="w-4 h-4 text-orange-600" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium">{{ job.job_number }} — {{ job.customer_name }}</p>
              <p class="text-xs text-gray-400">{{ job.origin_city }} → {{ job.destination_city }}</p>
            </div>
            <div class="text-right">
              <span class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full font-medium">
                Di Jalan
              </span>
              <p class="text-xs text-gray-400 mt-1">
                {{ job.cargo_weight_kg ? job.cargo_weight_kg.toLocaleString() + ' kg' : '-' }}
              </p>
            </div>
          </div>
          <div v-if="inTransitJobs.length === 0" class="text-center py-6 text-gray-400">
            <p class="text-sm">Tidak ada pengiriman dalam perjalanan</p>
          </div>
        </div>
      </div>

      <!-- Completed -->
      <div class="bg-white rounded-xl border p-5 shadow-sm">
        <h2 class="font-semibold mb-4">Selesai Hari Ini</h2>
        <div class="space-y-2">
          <div v-for="job in completedJobs" :key="job.id"
            class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition">
            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
              <CheckCircle class="w-4 h-4 text-green-600" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium">{{ job.job_number }} — {{ job.customer_name }}</p>
              <p class="text-xs text-gray-400">{{ job.origin_city }} → {{ job.destination_city }}</p>
            </div>
            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">
              Terkirim
            </span>
          </div>
          <div v-if="completedJobs.length === 0" class="text-center py-6 text-gray-400">
            <p class="text-sm">Belum ada pengiriman selesai hari ini</p>
          </div>
        </div>
      </div>
    </template>

    <!-- Modal Detail -->
    <div v-if="selectedJob" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h3 class="font-semibold">{{ selectedJob.job_number }}</h3>
          <button @click="selectedJob = null" class="text-gray-400 hover:text-gray-600">
            <X class="w-5 h-5" />
          </button>
        </div>
        <div class="p-6 space-y-3">
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Customer</span>
            <span class="font-medium">{{ selectedJob.customer_name }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Asal</span>
            <span class="font-medium">{{ selectedJob.origin_city }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Tujuan</span>
            <span class="font-medium">{{ selectedJob.destination_city }}</span>
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
            <span class="text-gray-500">Status</span>
            <StatusBadge :status="selectedJob.status" />
          </div>
        </div>
        <div class="flex justify-end px-6 py-4 border-t">
          <button @click="selectedJob = null"
            class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg transition">
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Truck, Package, MapPin, CheckCircle, Clock, Loader2, X } from 'lucide-vue-next'
import { useJobOrders } from '@/composables/useJobOrders'
import StatusBadge from './StatusBadge.vue'
import PriorityBadge from './PriorityBadge.vue'

const { jobOrders, loading, fetchJobOrders, updateStatus } = useJobOrders()

const dispatching = ref<number | null>(null)
const selectedJob = ref<any>(null)

const readyJobs = computed(() =>
  jobOrders.value.filter(j => ['assigned', 'in_progress'].includes(j.status))
)

const inTransitJobs = computed(() =>
  jobOrders.value.filter(j => ['in_transit', 'picked_up'].includes(j.status))
)

const completedJobs = computed(() =>
  jobOrders.value.filter(j => ['delivered', 'completed'].includes(j.status))
)

const stats = computed(() => [
  { label: 'Siap Berangkat',    value: readyJobs.value.length,     icon: Clock,        color: 'text-yellow-600', bg: 'bg-yellow-50' },
  { label: 'Dalam Perjalanan',  value: inTransitJobs.value.length,  icon: Truck,        color: 'text-orange-600', bg: 'bg-orange-50' },
  { label: 'Selesai',           value: completedJobs.value.length,  icon: CheckCircle,  color: 'text-green-600',  bg: 'bg-green-50'  },
  { label: 'Total',             value: jobOrders.value.length,      icon: Package,      color: 'text-blue-600',   bg: 'bg-blue-50'   },
])

async function handleDispatch(job: any) {
  dispatching.value = job.id
  
  // Jika masih assigned, harus in_progress dulu
  if (job.status === 'assigned') {
    await updateStatus(job.id, 'in_progress', 'Driver berangkat menuju lokasi pickup')
  } else if (job.status === 'in_progress') {
    await updateStatus(job.id, 'picked_up', 'Kargo sudah diambil')
  }
  
  dispatching.value = null
}
function dispatchLabel(status: string) {
  if (status === 'assigned') return 'Mulai Proses'
  if (status === 'in_progress') return 'Konfirmasi Pickup'
  return 'Berangkatkan'
}

function formatDate(val: string) {
  return new Date(val).toLocaleDateString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric'
  })
}

onMounted(() => fetchJobOrders({ per_page: 100 }))
</script>