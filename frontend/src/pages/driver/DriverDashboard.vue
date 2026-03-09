<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-2xl font-bold">Dashboard Driver</h1>
      <p class="text-sm text-gray-500 mt-1">Selamat datang, {{ auth.user?.name }}!</p>
    </div>

    <!-- Status Driver -->
    <div class="bg-white rounded-xl border p-5 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold">Status Saya</h2>
        <span :class="`px-3 py-1 rounded-full text-xs font-medium ${statusClass(currentStatus)}`">
          {{ statusLabel(currentStatus) }}
        </span>
      </div>
      <div class="flex gap-2 flex-wrap">
        <button v-for="s in driverStatuses" :key="s.value"
          @click="updateDriverStatus(s.value)"
          :disabled="updatingStatus"
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
        class="bg-white rounded-xl border p-4 shadow-sm">
        <div class="flex items-center justify-between">
          <p class="text-xs text-gray-500">{{ stat.label }}</p>
          <div :class="`w-8 h-8 rounded-lg ${stat.bg} flex items-center justify-center`">
            <component :is="stat.icon" :class="`w-4 h-4 ${stat.color}`" />
          </div>
        </div>
        <p class="text-2xl font-bold mt-2">
          <span v-if="loading" class="inline-block w-10 h-6 bg-gray-200 animate-pulse rounded" />
          <span v-else>{{ stat.value }}</span>
        </p>
      </div>
    </div>

    <!-- Tugas Aktif -->
    <div class="bg-white rounded-xl border p-5 shadow-sm">
      <h2 class="font-semibold mb-4">Tugas Aktif</h2>

      <div v-if="loading" class="space-y-3">
        <div v-for="i in 2" :key="i" class="h-24 bg-gray-100 animate-pulse rounded-xl" />
      </div>

      <div v-else-if="activeJobs.length === 0" class="text-center py-8 text-gray-400">
        <Truck class="w-10 h-10 mx-auto mb-2 opacity-30" />
        <p class="text-sm">Tidak ada tugas aktif</p>
      </div>

      <div v-else class="space-y-3">
        <div v-for="job in activeJobs" :key="job.id"
          class="border rounded-xl p-4 hover:bg-gray-50 transition">
          <div class="flex items-start justify-between mb-2">
            <div>
              <p class="font-medium text-blue-600">{{ job.job_number }}</p>
              <p class="text-sm text-gray-600">{{ job.customer_name }}</p>
            </div>
            <PriorityBadge :priority="job.priority" />
          </div>
          <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
            <MapPin class="w-3 h-3" />
            <span>{{ job.origin_city }} → {{ job.destination_city }}</span>
          </div>
          <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
            <Package class="w-3 h-3" />
            <span>{{ job.cargo_type }} · {{ job.cargo_weight_kg?.toLocaleString() ?? '-' }} kg</span>
          </div>
          <StatusBadge :status="job.status" class="mb-3" />
          <div class="flex gap-2 mt-3">
            <button @click="handleUpdateStatus(job)"
              :disabled="updatingJob === job.id"
              class="flex-1 bg-green-600 text-white py-1.5 rounded-lg text-xs font-medium hover:bg-green-700 disabled:opacity-50 transition flex items-center justify-center gap-1">
              <Loader2 v-if="updatingJob === job.id" class="w-3 h-3 animate-spin" />
              {{ nextStatusLabel(job.status) }}
            </button>
            <RouterLink :to="`/driver/navigation`"
              class="flex-1 border border-gray-300 text-gray-600 py-1.5 rounded-lg text-xs font-medium hover:bg-gray-50 transition text-center">
              Lihat Rute
            </RouterLink>
          </div>
        </div>
      </div>
    </div>

    <!-- Riwayat -->
    <div class="bg-white rounded-xl border p-5 shadow-sm">
      <h2 class="font-semibold mb-4">Riwayat Pengiriman</h2>

      <div v-if="loading" class="space-y-2">
        <div v-for="i in 3" :key="i" class="h-12 bg-gray-100 animate-pulse rounded-lg" />
      </div>

      <div v-else-if="completedJobs.length === 0" class="text-center py-6 text-gray-400">
        <p class="text-sm">Belum ada riwayat pengiriman</p>
      </div>

      <div v-else class="space-y-2">
        <div v-for="job in completedJobs" :key="job.id"
          class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition">
          <CheckCircle class="w-5 h-5 text-green-500 flex-shrink-0" />
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium">{{ job.job_number }} — {{ job.customer_name }}</p>
            <p class="text-xs text-gray-400">{{ job.origin_city }} → {{ job.destination_city }}</p>
          </div>
          <div class="text-right">
            <StatusBadge :status="job.status" />
            <p class="text-xs text-gray-400 mt-1">
              {{ job.delivery_scheduled_at ? formatDate(job.delivery_scheduled_at) : '-' }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { Truck, MapPin, CheckCircle, Package, Star, Loader2 } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useJobOrders } from '@/composables/useJobOrders'
import StatusBadge from '@/pages/admin/StatusBadge.vue'
import PriorityBadge from '@/pages/admin/PriorityBadge.vue'
import api from '@/lib/axios'

const auth = useAuthStore()
const { jobOrders, loading, fetchJobOrders, updateStatus } = useJobOrders()

const currentStatus = ref('available')
const updatingStatus = ref(false)
const updatingJob = ref<number | null>(null)

const driverStatuses = [
  { value: 'available', label: 'Tersedia' },
  { value: 'on_duty',   label: 'Sedang Bertugas' },
  { value: 'rest',      label: 'Istirahat' },
  { value: 'off_duty',  label: 'Tidak Bertugas' },
]

// Job order milik driver ini
const myJobs = computed(() =>
  jobOrders.value.filter(j =>
    j.driver?.id === auth.user?.id ||
    j.driver?.name === auth.user?.name
  )
)

const activeJobs = computed(() =>
  myJobs.value.filter(j =>
    ['assigned', 'in_progress', 'picked_up', 'in_transit'].includes(j.status)
  )
)

const completedJobs = computed(() =>
  myJobs.value.filter(j =>
    ['delivered', 'completed'].includes(j.status)
  )
)

const stats = computed(() => [
  {
    label: 'Total Trip',
    value: myJobs.value.length,
    icon: Truck, color: 'text-blue-600', bg: 'bg-blue-50'
  },
  {
    label: 'Rating',
    value: '4.8',
    icon: Star, color: 'text-yellow-600', bg: 'bg-yellow-50'
  },
  {
    label: 'Aktif',
    value: activeJobs.value.length,
    icon: Package, color: 'text-green-600', bg: 'bg-green-50'
  },
  {
    label: 'Selesai',
    value: completedJobs.value.length,
    icon: CheckCircle, color: 'text-purple-600', bg: 'bg-purple-50'
  },
])

// Status transition untuk driver
const NEXT_STATUS: Record<string, string> = {
  assigned:    'in_progress',
  in_progress: 'picked_up',
  picked_up:   'in_transit',
  in_transit:  'delivered',
}

const NEXT_LABEL: Record<string, string> = {
  assigned:    'Mulai Proses',
  in_progress: 'Konfirmasi Pickup',
  picked_up:   'Mulai Kirim',
  in_transit:  'Tandai Terkirim',
}

function nextStatusLabel(status: string) {
  return NEXT_LABEL[status] ?? 'Update Status'
}

async function handleUpdateStatus(job: any) {
  const next = NEXT_STATUS[job.status]
  if (!next) return
  updatingJob.value = job.id
  await updateStatus(job.id, next)
  updatingJob.value = null
}

async function updateDriverStatus(status: string) {
  updatingStatus.value = true
  try {
    // Update FCM/status driver jika ada endpoint
    // await api.put('/auth/update-status', { status })
    currentStatus.value = status
  } finally {
    updatingStatus.value = false
  }
}

function statusLabel(status: string) {
  const map: Record<string, string> = {
    available: 'Tersedia', on_duty: 'Sedang Bertugas',
    rest: 'Istirahat', off_duty: 'Tidak Bertugas',
  }
  return map[status] || status
}

function statusClass(status: string) {
  const map: Record<string, string> = {
    available: 'bg-green-100 text-green-700',
    on_duty:   'bg-blue-100 text-blue-700',
    rest:      'bg-yellow-100 text-yellow-700',
    off_duty:  'bg-gray-100 text-gray-600',
  }
  return map[status] || 'bg-gray-100 text-gray-600'
}

function formatDate(val: string) {
  return new Date(val).toLocaleDateString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric'
  })
}

onMounted(() => {
  fetchJobOrders({ per_page: 100 })
})
</script>