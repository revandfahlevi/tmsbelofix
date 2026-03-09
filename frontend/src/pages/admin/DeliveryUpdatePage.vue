<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-2xl font-bold">Update Pengiriman</h1>
      <p class="text-sm text-gray-500 mt-1">Perbarui status pengiriman secara real-time</p>
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
      </button>
    </div>

    <!-- Job List -->
    <div class="space-y-4">
      <div v-for="job in filteredJobs" :key="job.id"
        class="bg-white rounded-xl border p-5 shadow-sm">
        <div class="flex items-start justify-between mb-4">
          <div>
            <p class="font-medium text-blue-600">{{ job.order_number }}</p>
            <p class="text-sm text-gray-600">{{ job.customer_name }}</p>
            <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
              <MapPin class="w-3 h-3" />
              {{ job.origin }} → {{ job.destination }}
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
        <div class="mb-4 space-y-2">
          <p class="text-xs font-medium text-gray-600">Timeline Status:</p>
          <div class="flex items-center gap-1 flex-wrap">
            <div v-for="(step, i) in statusSteps" :key="step.value"
              class="flex items-center gap-1">
              <div :class="`w-6 h-6 rounded-full flex items-center justify-center text-xs ${
                isCompleted(job.status, step.value)
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-100 text-gray-400'
              }`">
                {{ i + 1 }}
              </div>
              <span :class="`text-xs ${isCompleted(job.status, step.value) ? 'text-blue-600 font-medium' : 'text-gray-400'}`">
                {{ step.label }}
              </span>
              <span v-if="i < statusSteps.length - 1" class="text-gray-300 text-xs">→</span>
            </div>
          </div>
        </div>

        <!-- Update Status -->
        <div class="flex gap-2 flex-wrap">
          <button v-for="next in nextStatuses(job.status)" :key="next.value"
            @click="updateStatus(job.id, next.value)"
            :class="`px-3 py-1.5 rounded-lg text-xs font-medium transition ${next.class}`">
            {{ next.label }}
          </button>
        </div>

        <!-- Notes -->
        <div class="mt-3">
          <input
            v-model="notes[job.id]"
            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Tambah catatan pengiriman..." />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import { MapPin } from 'lucide-vue-next'
import { MOCK_JOB_ORDERS } from '@/lib/mockData'

const filterStatus = ref('all')
const jobOrders = ref([...MOCK_JOB_ORDERS])
const notes = reactive<Record<string, string>>({})

const filters = [
  { value: 'all', label: 'Semua' },
  { value: 'pending', label: 'Pending' },
  { value: 'assigned', label: 'Ditugaskan' },
  { value: 'dispatched', label: 'Dikirim' },
  { value: 'in_transit', label: 'Dalam Perjalanan' },
  { value: 'delivered', label: 'Terkirim' },
]

const statusSteps = [
  { value: 'pending', label: 'Pending' },
  { value: 'assigned', label: 'Ditugaskan' },
  { value: 'dispatched', label: 'Dikirim' },
  { value: 'in_transit', label: 'Di Jalan' },
  { value: 'delivered', label: 'Terkirim' },
]

const statusOrder = ['pending', 'assigned', 'dispatched', 'in_transit', 'delivered']

const filteredJobs = computed(() => {
  if (filterStatus.value === 'all') return jobOrders.value
  return jobOrders.value.filter(j => j.status === filterStatus.value)
})

function isCompleted(current: string, step: string) {
  return statusOrder.indexOf(current) >= statusOrder.indexOf(step)
}

function nextStatuses(current: string) {
  const map: Record<string, { value: string; label: string; class: string }[]> = {
    pending: [
      { value: 'assigned', label: 'Tugaskan', class: 'bg-blue-100 text-blue-700 hover:bg-blue-200' },
      { value: 'cancelled', label: 'Batalkan', class: 'bg-red-100 text-red-700 hover:bg-red-200' },
    ],
    assigned: [
      { value: 'dispatched', label: 'Kirim Sekarang', class: 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200' },
    ],
    dispatched: [
      { value: 'in_transit', label: 'Tandai Di Jalan', class: 'bg-orange-100 text-orange-700 hover:bg-orange-200' },
    ],
    in_transit: [
      { value: 'delivered', label: 'Tandai Terkirim', class: 'bg-green-100 text-green-700 hover:bg-green-200' },
    ],
    delivered: [],
    cancelled: [],
  }
  return map[current] || []
}

function updateStatus(jobId: string, newStatus: string) {
  const index = jobOrders.value.findIndex(j => j.id === jobId)
  if (index !== -1) {
    jobOrders.value[index] = {
      ...jobOrders.value[index],
      status: newStatus as 'pending' | 'assigned' | 'dispatched' | 'in_transit' | 'delivered' | 'cancelled'
    }
  }
}

function statusLabel(status: string) {
  const map: Record<string, string> = {
    pending: 'Pending', assigned: 'Ditugaskan',
    dispatched: 'Dikirim', in_transit: 'Dalam Perjalanan',
    delivered: 'Terkirim', cancelled: 'Dibatalkan',
  }
  return map[status] || status
}

function statusClass(status: string) {
  const map: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-700',
    assigned: 'bg-blue-100 text-blue-700',
    dispatched: 'bg-indigo-100 text-indigo-700',
    in_transit: 'bg-orange-100 text-orange-700',
    delivered: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
  }
  return map[status] || 'bg-gray-100 text-gray-700'
}

function progressWidth(status: string) {
  const map: Record<string, string> = {
    pending: '10%', assigned: '25%',
    dispatched: '50%', in_transit: '75%',
    delivered: '100%', cancelled: '0%',
  }
  return map[status] || '0%'
}

function progressColor(status: string) {
  const map: Record<string, string> = {
    pending: 'bg-yellow-400',
    assigned: 'bg-blue-400',
    dispatched: 'bg-indigo-400',
    in_transit: 'bg-orange-400',
    delivered: 'bg-green-500',
    cancelled: 'bg-red-400',
  }
  return map[status] || 'bg-gray-300'
}
</script>