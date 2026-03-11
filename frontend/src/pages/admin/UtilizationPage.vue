<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Utilization & Analytics</h1>
        <p class="text-sm text-gray-500 mt-1">Performa operasional kendaraan, driver, dan pengiriman</p>
      </div>
      <div class="flex items-center gap-3 flex-wrap">
        <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-3 py-2">
          <Calendar class="w-4 h-4 text-gray-400" />
          <input v-model="dateStart" type="date" class="text-sm focus:outline-none" />
          <span class="text-gray-400 text-sm">—</span>
          <input v-model="dateEnd" type="date" class="text-sm focus:outline-none" />
        </div>
        <button @click="fetchData" :disabled="loading"
          class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-4 py-2 rounded-xl text-sm font-medium transition">
          <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
          <BarChart3 v-else class="w-4 h-4" />
          Tampilkan
        </button>
      </div>
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div v-for="i in 4" :key="i" class="h-28 bg-gray-100 animate-pulse rounded-2xl" />
    </div>

    <template v-else-if="data">
      <!-- Summary Cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div v-for="card in summaryCards" :key="card.label"
          class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
          <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-medium text-gray-500">{{ card.label }}</p>
            <div :class="`w-8 h-8 rounded-xl ${card.bg} flex items-center justify-center`">
              <component :is="card.icon" :class="`w-4 h-4 ${card.color}`" />
            </div>
          </div>
          <p class="text-2xl font-bold text-gray-800">{{ card.value }}</p>
          <p v-if="card.sub" class="text-xs text-gray-400 mt-1">{{ card.sub }}</p>
        </div>
      </div>

      <!-- Charts Row 1 -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Revenue Chart -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
          <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <TrendingUp class="w-4 h-4 text-blue-600" />
            Revenue Harian
          </h3>
          <div v-if="data.revenue_chart.length === 0" class="text-center py-10 text-gray-400">
            <BarChart3 class="w-8 h-8 mx-auto mb-2 opacity-20" />
            <p class="text-sm">Belum ada data revenue</p>
          </div>
          <div v-else class="space-y-2">
            <div v-for="item in data.revenue_chart" :key="item.date"
              class="flex items-center gap-3">
              <p class="text-xs text-gray-400 w-20 flex-shrink-0">{{ formatDate(item.date) }}</p>
              <div class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
                <div class="h-full bg-blue-500 rounded-full transition-all"
                  :style="`width: ${revenuePercent(item.revenue)}%`" />
              </div>
              <p class="text-xs font-medium text-gray-700 w-24 text-right flex-shrink-0">
                {{ formatCurrency(item.revenue) }}
              </p>
              <p class="text-xs text-gray-400 w-12 flex-shrink-0">{{ item.count }} job</p>
            </div>
          </div>
        </div>

        <!-- Status Breakdown -->
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
          <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <PieChart class="w-4 h-4 text-purple-600" />
            Status Job Order
          </h3>
          <div class="space-y-3">
            <div v-for="item in data.status_chart" :key="item.status"
              class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div :class="`w-2.5 h-2.5 rounded-full ${statusColor(item.status)}`" />
                <p class="text-sm text-gray-600 capitalize">{{ statusLabel(item.status) }}</p>
              </div>
              <div class="flex items-center gap-2">
                <div class="w-20 bg-gray-100 rounded-full h-1.5">
                  <div :class="`h-full rounded-full ${statusColor(item.status)}`"
                    :style="`width: ${statusPercent(item.count)}%`" />
                </div>
                <span class="text-sm font-semibold text-gray-800 w-6 text-right">{{ item.count }}</span>
              </div>
            </div>
            <div v-if="data.status_chart.length === 0" class="text-center py-6 text-gray-400">
              <p class="text-sm">Belum ada data</p>
            </div>
          </div>

          <!-- Completion rate -->
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500 mb-1">Completion Rate</p>
            <div class="flex items-center gap-2">
              <div class="flex-1 bg-gray-100 rounded-full h-2">
                <div class="h-full bg-green-500 rounded-full"
                  :style="`width: ${completionRate}%`" />
              </div>
              <span class="text-sm font-bold text-green-600">{{ completionRate }}%</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Driver Performance -->
      <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
          <Users class="w-4 h-4 text-green-600" />
          Performa Driver
        </h3>
        <div v-if="data.driver_stats.length === 0" class="text-center py-8 text-gray-400">
          <Users class="w-8 h-8 mx-auto mb-2 opacity-20" />
          <p class="text-sm">Belum ada data driver</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-100">
                <th class="text-left text-xs font-medium text-gray-500 pb-3">Driver</th>
                <th class="text-center text-xs font-medium text-gray-500 pb-3">Total Trip</th>
                <th class="text-center text-xs font-medium text-gray-500 pb-3">Selesai</th>
                <th class="text-center text-xs font-medium text-gray-500 pb-3">Dibatalkan</th>
                <th class="text-center text-xs font-medium text-gray-500 pb-3">Success Rate</th>
                <th class="text-right text-xs font-medium text-gray-500 pb-3">Revenue</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="d in data.driver_stats" :key="d.assigned_driver_id"
                class="hover:bg-gray-50 transition">
                <td class="py-3">
                  <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xs font-bold">
                      {{ d.driver?.name?.slice(0,2).toUpperCase() ?? '??' }}
                    </div>
                    <span class="font-medium text-gray-800">{{ d.driver?.name ?? 'Unknown' }}</span>
                  </div>
                </td>
                <td class="py-3 text-center text-gray-600">{{ d.total_trips }}</td>
                <td class="py-3 text-center">
                  <span class="text-green-600 font-medium">{{ d.completed }}</span>
                </td>
                <td class="py-3 text-center">
                  <span class="text-red-500 font-medium">{{ d.cancelled }}</span>
                </td>
                <td class="py-3 text-center">
                  <div class="flex items-center justify-center gap-2">
                    <div class="w-16 bg-gray-100 rounded-full h-1.5">
                      <div class="h-full bg-green-500 rounded-full"
                        :style="`width: ${d.total_trips > 0 ? Math.round(d.completed/d.total_trips*100) : 0}%`" />
                    </div>
                    <span class="text-xs text-gray-600">
                      {{ d.total_trips > 0 ? Math.round(d.completed/d.total_trips*100) : 0 }}%
                    </span>
                  </div>
                </td>
                <td class="py-3 text-right font-medium text-gray-800">
                  {{ formatCurrency(d.revenue) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Vehicle Utilization -->
      <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
          <Truck class="w-4 h-4 text-orange-600" />
          Utilisasi Kendaraan
        </h3>
        <div v-if="data.vehicle_stats.length === 0" class="text-center py-8 text-gray-400">
          <Truck class="w-8 h-8 mx-auto mb-2 opacity-20" />
          <p class="text-sm">Belum ada data kendaraan</p>
        </div>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
          <div v-for="v in data.vehicle_stats" :key="v.assigned_vehicle_id"
            class="border border-gray-100 rounded-xl p-4 hover:bg-gray-50 transition">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center">
                <Truck class="w-4 h-4 text-orange-600" />
              </div>
              <div>
                <p class="font-semibold text-sm text-gray-800">
                  {{ v.vehicle?.plate_number ?? 'Unknown' }}
                </p>
                <p class="text-xs text-gray-400">{{ v.vehicle?.vehicle_type ?? '-' }}</p>
              </div>
            </div>
            <div class="grid grid-cols-3 gap-2 text-center">
              <div class="bg-blue-50 rounded-lg p-2">
                <p class="text-lg font-bold text-blue-600">{{ v.total_trips }}</p>
                <p class="text-xs text-gray-400">Trip</p>
              </div>
              <div class="bg-green-50 rounded-lg p-2">
                <p class="text-lg font-bold text-green-600">{{ v.completed }}</p>
                <p class="text-xs text-gray-400">Selesai</p>
              </div>
              <div class="bg-gray-50 rounded-lg p-2">
                <p class="text-lg font-bold text-gray-600">
                  {{ v.total_weight ? Math.round(Number(v.total_weight)/1000) + 't' : '-' }}
                </p>
                <p class="text-xs text-gray-400">Muatan</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Cargo Type -->
      <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
          <Package class="w-4 h-4 text-indigo-600" />
          Tipe Kargo
        </h3>
        <div v-if="data.cargo_chart.length === 0" class="text-center py-8 text-gray-400">
          <p class="text-sm">Belum ada data</p>
        </div>
        <div v-else class="space-y-3">
          <div v-for="item in data.cargo_chart" :key="item.cargo_type"
            class="flex items-center gap-3">
            <p class="text-sm text-gray-600 w-32 flex-shrink-0 capitalize">
              {{ item.cargo_type ?? 'Lainnya' }}
            </p>
            <div class="flex-1 bg-gray-100 rounded-full h-2">
              <div class="h-full bg-indigo-500 rounded-full"
                :style="`width: ${cargoPercent(item.count)}%`" />
            </div>
            <p class="text-xs font-medium text-gray-700 w-16 text-right flex-shrink-0">
              {{ item.count }} job
            </p>
            <p class="text-xs text-gray-400 w-24 text-right flex-shrink-0">
              {{ item.total_weight ? Number(item.total_weight).toLocaleString() + ' kg' : '-' }}
            </p>
          </div>
        </div>
      </div>
    </template>

    <!-- Empty state -->
    <div v-else class="text-center py-16 text-gray-400">
      <BarChart3 class="w-12 h-12 mx-auto mb-3 opacity-20" />
      <p class="text-sm">Pilih periode dan klik Tampilkan</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import {
  BarChart3, TrendingUp, PieChart, Users, Truck, Package,
  Calendar, Loader2, CheckCircle, XCircle, Activity
} from 'lucide-vue-next'
import api from '@/lib/axios'

const loading   = ref(false)
const data      = ref<any>(null)

// Default 30 hari terakhir
const today     = new Date()
const dateEnd   = ref(today.toISOString().split('T')[0])
const dateStart = ref(new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0])

// ── Computed ──────────────────────────────────────────────
const summaryCards = computed(() => {
  if (!data.value) return []
  const s = data.value.summary
  return [
    {
      label: 'Total Job Order', value: s.totalJobs,
      icon: Package, color: 'text-blue-600', bg: 'bg-blue-100',
      sub: `Periode ${dateStart.value} s/d ${dateEnd.value}`
    },
    {
      label: 'Selesai', value: s.completedJobs,
      icon: CheckCircle, color: 'text-green-600', bg: 'bg-green-100',
      sub: `${s.totalJobs > 0 ? Math.round(s.completedJobs/s.totalJobs*100) : 0}% completion rate`
    },
    {
      label: 'Aktif', value: s.activeJobs,
      icon: Activity, color: 'text-yellow-600', bg: 'bg-yellow-100',
      sub: 'Sedang berjalan'
    },
    {
      label: 'Total Revenue', value: formatCurrency(s.totalRevenue),
      icon: TrendingUp, color: 'text-purple-600', bg: 'bg-purple-100',
      sub: `Est. ${formatCurrency(s.estimatedRev)}`
    },
  ]
})

const completionRate = computed(() => {
  if (!data.value) return 0
  const s = data.value.summary
  return s.totalJobs > 0 ? Math.round(s.completedJobs / s.totalJobs * 100) : 0
})

const maxRevenue = computed(() => {
  if (!data.value?.revenue_chart?.length) return 1
  return Math.max(...data.value.revenue_chart.map((r: any) => Number(r.revenue) || 0)) || 1
})

const maxStatus = computed(() => {
  if (!data.value?.status_chart?.length) return 1
  return Math.max(...data.value.status_chart.map((s: any) => s.count)) || 1
})

const maxCargo = computed(() => {
  if (!data.value?.cargo_chart?.length) return 1
  return Math.max(...data.value.cargo_chart.map((c: any) => c.count)) || 1
})

function revenuePercent(rev: any) {
  return Math.round((Number(rev) / maxRevenue.value) * 100)
}
function statusPercent(count: number) {
  return Math.round((count / maxStatus.value) * 100)
}
function cargoPercent(count: number) {
  return Math.round((count / maxCargo.value) * 100)
}

// ── Fetch ─────────────────────────────────────────────────
async function fetchData() {
  loading.value = true
  data.value    = null
  try {
    const res = await api.get('/analytics/utilization', {
      params: { start: dateStart.value, end: dateEnd.value }
    })
    const raw = typeof res.data === 'string'
      ? JSON.parse(res.data.replace(/^=/, ''))
      : res.data
    data.value = raw.data
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

// ── Helpers ───────────────────────────────────────────────
function formatDate(val: string) {
  if (!val) return '-'
  return new Date(val).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' })
}

function formatCurrency(val: any) {
  if (!val || val == 0) return 'Rp 0'
  return 'Rp ' + Number(val).toLocaleString('id-ID')
}

function statusLabel(s: string) {
  const map: Record<string, string> = {
    draft: 'Draft', pending: 'Pending', assigned: 'Ditugaskan',
    in_progress: 'Proses', picked_up: 'Picked Up',
    in_transit: 'Transit', delivered: 'Terkirim',
    completed: 'Selesai', cancelled: 'Dibatalkan', failed: 'Gagal',
  }
  return map[s] ?? s
}

function statusColor(s: string) {
  const map: Record<string, string> = {
    draft: 'bg-gray-400', pending: 'bg-yellow-400',
    assigned: 'bg-blue-400', in_progress: 'bg-blue-500',
    picked_up: 'bg-indigo-400', in_transit: 'bg-purple-400',
    delivered: 'bg-teal-400', completed: 'bg-green-500',
    cancelled: 'bg-red-400', failed: 'bg-red-600',
  }
  return map[s] ?? 'bg-gray-400'
}

let pollInterval: ReturnType<typeof setInterval> | null = null

onMounted(() => {
  fetchData()
  pollInterval = setInterval(fetchData, 30_000)
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})
</script>