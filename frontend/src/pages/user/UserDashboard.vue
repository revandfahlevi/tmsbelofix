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
          @click="currentStatus = s.value"
          :class="`px-4 py-2 rounded-lg text-sm font-medium transition ${
            currentStatus === s.value
              ? 'bg-blue-600 text-white'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          }`">
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
        <p class="text-2xl font-bold mt-2">{{ stat.value }}</p>
      </div>
    </div>

    <!-- Tugas Aktif -->
    <div class="bg-white rounded-xl border p-5 shadow-sm">
      <h2 class="font-semibold mb-4">Tugas Aktif</h2>
      <div v-if="activeJobs.length === 0" class="text-center py-8 text-gray-400">
        <Truck class="w-10 h-10 mx-auto mb-2 opacity-30" />
        <p class="text-sm">Tidak ada tugas aktif</p>
      </div>
      <div v-else class="space-y-3">
        <div v-for="job in activeJobs" :key="job.id"
          class="border rounded-xl p-4 hover:bg-gray-50 transition">
          <div class="flex items-start justify-between mb-2">
            <div>
              <p class="font-medium text-blue-600">{{ job.order_number }}</p>
              <p class="text-sm text-gray-600">{{ job.customer_name }}</p>
            </div>
            <span :class="`text-xs px-2 py-0.5 rounded-full font-medium ${priorityClass(job.priority)}`">
              {{ job.priority }}
            </span>
          </div>
          <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
            <MapPin class="w-3 h-3" />
            <span>{{ job.origin }} → {{ job.destination }}</span>
          </div>
          <div class="flex gap-2">
            <button class="flex-1 bg-green-600 text-white py-1.5 rounded-lg text-xs font-medium hover:bg-green-700 transition">
              Tandai Terkirim
            </button>
            <button class="flex-1 border border-gray-300 text-gray-600 py-1.5 rounded-lg text-xs font-medium hover:bg-gray-50 transition">
              Lihat Rute
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Riwayat -->
    <div class="bg-white rounded-xl border p-5 shadow-sm">
      <h2 class="font-semibold mb-4">Riwayat Pengiriman</h2>
      <div class="space-y-2">
        <div v-for="job in completedJobs" :key="job.id"
          class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition">
          <CheckCircle class="w-5 h-5 text-green-500 flex-shrink-0" />
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium">{{ job.order_number }} — {{ job.customer_name }}</p>
            <p class="text-xs text-gray-400">{{ job.origin }} → {{ job.destination }}</p>
          </div>
          <p class="text-xs text-gray-400">{{ job.scheduled_date }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Truck, MapPin, CheckCircle, Package, Star, Clock } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { MOCK_JOB_ORDERS, MOCK_DRIVERS } from '@/lib/mockData'

const auth = useAuthStore()

const currentStatus = ref('available')

const driverStatuses = [
  { value: 'available', label: 'Tersedia' },
  { value: 'on_duty', label: 'Sedang Bertugas' },
  { value: 'rest', label: 'Istirahat' },
  { value: 'off_duty', label: 'Tidak Bertugas' },
]

const myDriver = computed(() =>
  MOCK_DRIVERS.find(d => d.name === auth.user?.name) || MOCK_DRIVERS[0]
)

const stats = computed(() => [
  { label: 'Total Trip', value: myDriver.value.total_trips, icon: Truck, color: 'text-blue-600', bg: 'bg-blue-50' },
  { label: 'Rating', value: myDriver.value.rating, icon: Star, color: 'text-yellow-600', bg: 'bg-yellow-50' },
  { label: 'Aktif Hari Ini', value: activeJobs.value.length, icon: Package, color: 'text-green-600', bg: 'bg-green-50' },
  { label: 'Selesai', value: completedJobs.value.length, icon: CheckCircle, color: 'text-purple-600', bg: 'bg-purple-50' },
])

const activeJobs = computed(() =>
  MOCK_JOB_ORDERS.filter(j => j.status === 'in_transit' || j.status === 'dispatched').slice(0, 3)
)

const completedJobs = computed(() =>
  MOCK_JOB_ORDERS.filter(j => j.status === 'delivered').slice(0, 5)
)

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
    on_duty: 'bg-blue-100 text-blue-700',
    rest: 'bg-yellow-100 text-yellow-700',
    off_duty: 'bg-gray-100 text-gray-600',
  }
  return map[status] || 'bg-gray-100 text-gray-600'
}

function priorityClass(priority: string) {
  const map: Record<string, string> = {
    normal: 'bg-gray-100 text-gray-600',
    urgent: 'bg-red-100 text-red-700',
    express: 'bg-orange-100 text-orange-700',
  }
  return map[priority] || 'bg-gray-100 text-gray-600'
}
</script>