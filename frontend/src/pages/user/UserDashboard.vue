<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-2xl font-bold">Dashboard</h1>
      <p class="text-sm text-gray-500 mt-1">Selamat datang, {{ auth.user?.name }}!</p>
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

    <!-- Pengiriman Aktif -->
    <div class="bg-white rounded-xl border p-5 shadow-sm">
      <h2 class="font-semibold mb-4">Pengiriman Aktif</h2>
      <div v-if="activeJobs.length === 0" class="text-center py-8 text-gray-400">
        <Truck class="w-10 h-10 mx-auto mb-2 opacity-30" />
        <p class="text-sm">Tidak ada pengiriman aktif</p>
      </div>
      <div v-else class="space-y-3">
        <div v-for="job in activeJobs" :key="job.id"
          class="border rounded-xl p-4 hover:bg-gray-50 transition">
          <div class="flex items-start justify-between mb-2">
            <div>
              <p class="font-medium text-blue-600">{{ job.job_number }}</p>
              <p class="text-sm text-gray-600">{{ job.customer_name }}</p>
            </div>
            <span :class="`text-xs px-2 py-0.5 rounded-full font-medium ${statusClass(job.status)}`">
              {{ statusLabel(job.status) }}
            </span>
          </div>
          <div class="flex items-center gap-2 text-xs text-gray-500">
            <MapPin class="w-3 h-3" />
            <span>{{ job.origin_city }} → {{ job.destination_city }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Riwayat Pengiriman -->
    <div class="bg-white rounded-xl border p-5 shadow-sm">
      <h2 class="font-semibold mb-4">Riwayat Pengiriman</h2>
      <div v-if="completedJobs.length === 0" class="text-center py-8 text-gray-400">
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
          <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">
            Terkirim
          </span>
        </div>
      </div>
    </div>

    <!-- Info -->
    <div class="bg-blue-50 border border-blue-100 rounded-xl p-5">
      <div class="flex items-start gap-3">
        <Info class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
        <div>
          <p class="text-sm font-semibold text-blue-800">Butuh bantuan?</p>
          <p class="text-xs text-blue-600 mt-1">
            Hubungi tim kami untuk informasi lebih lanjut mengenai pengiriman Anda.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Truck, MapPin, CheckCircle, Package, Clock, Info } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useJobOrders } from '@/composables/useJobOrders'

const auth = useAuthStore()
const { jobOrders, fetchJobOrders } = useJobOrders()

const activeJobs = computed(() =>
  jobOrders.value.filter(j =>
    ['pending', 'assigned', 'in_progress', 'picked_up', 'in_transit'].includes(j.status)
  ).slice(0, 5)
)

const completedJobs = computed(() =>
  jobOrders.value.filter(j =>
    ['delivered', 'completed'].includes(j.status)
  ).slice(0, 5)
)

const stats = computed(() => [
  { label: 'Total Pengiriman', value: jobOrders.value.length, icon: Package, color: 'text-blue-600', bg: 'bg-blue-50' },
  { label: 'Sedang Dikirim', value: activeJobs.value.length, icon: Truck, color: 'text-orange-600', bg: 'bg-orange-50' },
  { label: 'Selesai', value: completedJobs.value.length, icon: CheckCircle, color: 'text-green-600', bg: 'bg-green-50' },
  { label: 'Pending', value: jobOrders.value.filter(j => j.status === 'pending').length, icon: Clock, color: 'text-yellow-600', bg: 'bg-yellow-50' },
])

function statusLabel(status: string) {
  const map: Record<string, string> = {
    pending: 'Pending',
    assigned: 'Ditugaskan',
    in_progress: 'Diproses',
    picked_up: 'Diambil',
    in_transit: 'Dalam Perjalanan',
    delivered: 'Terkirim',
    completed: 'Selesai',
  }
  return map[status] || status
}

function statusClass(status: string) {
  const map: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-700',
    assigned: 'bg-blue-100 text-blue-700',
    in_progress: 'bg-orange-100 text-orange-700',
    picked_up: 'bg-purple-100 text-purple-700',
    in_transit: 'bg-indigo-100 text-indigo-700',
    delivered: 'bg-green-100 text-green-700',
    completed: 'bg-green-100 text-green-700',
  }
  return map[status] || 'bg-gray-100 text-gray-600'
}

onMounted(() => fetchJobOrders({ per_page: 50 }))
</script>