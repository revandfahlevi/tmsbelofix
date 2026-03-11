<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-800">Dashboard Admin</h1>
      <p class="text-sm text-gray-500 mt-1">Selamat datang, {{ auth.user?.name }}! Berikut ringkasan operasional hari ini.</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div v-for="stat in stats" :key="stat.label"
        class="rounded-2xl p-4 shadow-sm border border-blue-50 bg-gradient-to-br from-white to-blue-50">
        <div class="flex items-center justify-between mb-3">
          <p class="text-xs text-gray-500 font-medium">{{ stat.label }}</p>
          <div :class="`w-8 h-8 rounded-xl ${stat.bg} flex items-center justify-center`">
            <component :is="stat.icon" :class="`w-4 h-4 ${stat.color}`" />
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-800">
          <span v-if="loadingJobs" class="inline-block w-12 h-6 bg-gray-200 animate-pulse rounded" />
          <span v-else>{{ stat.value }}</span>
        </p>
        <p class="text-xs mt-1" :class="stat.trendColor">{{ stat.sub }}</p>
      </div>
    </div>

    <!-- Chart Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Revenue Chart — realtime -->
      <div class="lg:col-span-2 bg-white rounded-2xl border border-blue-50 shadow-sm p-5">
        <div class="flex items-center justify-between mb-1">
          <div>
            <h2 class="font-semibold text-gray-800">Tren Revenue</h2>
            <p class="text-xs text-gray-400">30 hari terakhir</p>
          </div>
        </div>
        <div class="flex items-end gap-3 mb-4">
          <p class="text-3xl font-bold text-gray-800">
            <span v-if="loadingRevenue" class="inline-block w-32 h-8 bg-gray-200 animate-pulse rounded" />
            <span v-else>{{ formatCurrency(revenueTotal) }}</span>
          </p>
          <div v-if="revenueChange !== 0" class="flex items-center gap-1 mb-1">
            <TrendingUp v-if="revenueChange >= 0" class="w-4 h-4 text-green-500" />
            <TrendingDown v-else class="w-4 h-4 text-red-500" />
            <span :class="`text-xs font-medium ${revenueChange >= 0 ? 'text-green-600' : 'text-red-500'}`">
              {{ revenueChange >= 0 ? '+' : '' }}{{ revenueChange }}% dari bulan lalu
            </span>
          </div>
        </div>
        <div class="h-56">
          <Line v-if="!loadingRevenue && revenueChartData.labels.length > 0"
            :data="revenueChartData" :options="chartOptions" />
          <div v-else-if="loadingRevenue" class="h-full bg-gray-50 animate-pulse rounded-xl" />
          <div v-else class="h-full flex items-center justify-center text-gray-400 text-sm">
            Belum ada data revenue
          </div>
        </div>
      </div>

      <!-- Donut - Status realtime -->
      <div class="bg-white rounded-2xl border border-blue-50 shadow-sm p-5">
        <h2 class="font-semibold text-gray-800 mb-1">Status Job Order</h2>
        <p class="text-xs text-gray-400 mb-4">Distribusi saat ini</p>
        <div class="h-44">
          <Doughnut v-if="!loadingJobs" :data="statusChartData" :options="doughnutOptions" />
          <div v-else class="h-full bg-gray-50 animate-pulse rounded-full mx-8" />
        </div>
        <div class="space-y-2 mt-4">
          <div v-for="item in statusLegend" :key="item.label"
            class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-2.5 h-2.5 rounded-full" :style="{ background: item.color }" />
              <span class="text-xs text-gray-600">{{ item.label }}</span>
            </div>
            <span class="text-xs font-medium text-gray-800">{{ item.value }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Second Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Delivery per hari dari analytics -->
      <div class="bg-white rounded-2xl border border-blue-50 shadow-sm p-5">
        <div class="mb-4">
          <h2 class="font-semibold text-gray-800">Job Order Per Hari</h2>
          <p class="text-xs text-gray-400">30 hari terakhir</p>
        </div>
        <div class="h-48">
          <Bar v-if="!loadingAnalytics && deliveryBarData.labels.length > 0"
            :data="deliveryBarData" :options="barOptions" />
          <div v-else-if="loadingAnalytics" class="h-full bg-gray-50 animate-pulse rounded-xl" />
          <div v-else class="h-full flex items-center justify-center text-gray-400 text-sm">
            Belum ada data
          </div>
        </div>
      </div>

      <!-- Driver status realtime -->
      <div class="bg-white rounded-2xl border border-blue-50 shadow-sm p-5">
        <div class="mb-4">
          <h2 class="font-semibold text-gray-800">Status Driver</h2>
          <p class="text-xs text-gray-400">Dari data GPS terbaru</p>
        </div>
        <div v-if="loadingDrivers" class="space-y-3">
          <div v-for="i in 3" :key="i" class="h-12 bg-gray-100 animate-pulse rounded-xl" />
        </div>
        <div v-else-if="drivers.length === 0" class="text-center py-8 text-gray-400">
          <Users class="w-8 h-8 mx-auto mb-2 opacity-20" />
          <p class="text-sm">Belum ada driver terdaftar</p>
        </div>
        <div v-else class="space-y-3">
          <div v-for="driver in drivers" :key="driver.id"
            class="flex items-center gap-3 p-2 rounded-xl hover:bg-blue-50 transition">
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
              {{ driver.name?.slice(0,2).toUpperCase() }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-800 truncate">{{ driver.name }}</p>
              <p class="text-xs text-gray-400">{{ driver.phone ?? '-' }}</p>
            </div>
            <span :class="`text-xs px-2 py-0.5 rounded-full font-medium ${driverStatusClass(driver.driver_profile?.availability_status)}`">
              {{ driverStatusLabel(driver.driver_profile?.availability_status) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Recent Job Orders -->
      <div class="lg:col-span-2 bg-white rounded-2xl border border-blue-50 shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold text-gray-800">Job Order Terbaru</h2>
          <RouterLink to="/admin/job-orders"
            class="text-xs text-blue-600 hover:underline font-medium">
            Lihat Semua →
          </RouterLink>
        </div>

        <div v-if="loadingJobs" class="space-y-3">
          <div v-for="i in 5" :key="i" class="h-12 bg-gray-100 animate-pulse rounded-xl" />
        </div>

        <div v-else-if="recentJobs.length === 0" class="text-center py-8">
          <Package class="w-8 h-8 text-gray-300 mx-auto mb-2" />
          <p class="text-sm text-gray-400">Belum ada job order</p>
        </div>

        <div v-else class="space-y-2">
          <div v-for="job in recentJobs" :key="job.id"
            class="flex items-center gap-3 p-3 rounded-xl hover:bg-blue-50 transition cursor-pointer">
            <div class="w-2 h-2 rounded-full flex-shrink-0" :class="dotColor(job.status)" />
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-800 truncate">
                {{ job.job_number }} — {{ job.customer_name }}
              </p>
              <p class="text-xs text-gray-400">{{ job.origin_city }} → {{ job.destination_city }}</p>
            </div>
            <div class="text-right flex-shrink-0">
              <StatusBadge :status="job.status" />
              <p class="text-xs text-gray-400 mt-1">
                {{ job.cargo_weight_kg ? Number(job.cargo_weight_kg).toLocaleString() + ' kg' : '-' }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="bg-white rounded-2xl border border-blue-50 shadow-sm p-5">
        <h2 class="font-semibold text-gray-800 mb-4">Ringkasan Cepat</h2>
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500">Completion Rate</p>
            <p class="text-sm font-bold text-green-600">{{ completionRate }}%</p>
          </div>
          <div class="w-full bg-gray-100 rounded-full h-2">
            <div class="h-full bg-green-500 rounded-full transition-all"
              :style="`width: ${completionRate}%`" />
          </div>

          <div class="flex items-center justify-between pt-2">
            <p class="text-sm text-gray-500">Cancellation Rate</p>
            <p class="text-sm font-bold text-red-500">{{ cancellationRate }}%</p>
          </div>
          <div class="w-full bg-gray-100 rounded-full h-2">
            <div class="h-full bg-red-400 rounded-full transition-all"
              :style="`width: ${cancellationRate}%`" />
          </div>

          <div class="pt-2 border-t border-gray-100 space-y-3">
            <div v-for="item in quickStats" :key="item.label"
              class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div :class="`w-6 h-6 rounded-lg ${item.bg} flex items-center justify-center`">
                  <component :is="item.icon" :class="`w-3 h-3 ${item.color}`" />
                </div>
                <p class="text-xs text-gray-500">{{ item.label }}</p>
              </div>
              <p class="text-xs font-semibold text-gray-800">{{ item.value }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import {
  Package, Truck, CheckCircle, Clock,
  AlertTriangle, DollarSign, Users, TrendingUp, TrendingDown
} from 'lucide-vue-next'
import {
  Chart as ChartJS,
  CategoryScale, LinearScale, PointElement,
  LineElement, BarElement, ArcElement,
  Title, Tooltip, Legend, Filler
} from 'chart.js'
import { Line, Bar, Doughnut } from 'vue-chartjs'
import { useAuthStore } from '@/stores/auth'
import { useJobOrders } from '@/composables/useJobOrders'
import StatusBadge from './StatusBadge.vue'
import api from '@/lib/axios'

ChartJS.register(
  CategoryScale, LinearScale, PointElement,
  LineElement, BarElement, ArcElement,
  Title, Tooltip, Legend, Filler
)

const auth = useAuthStore()
const { jobOrders, loading: loadingJobs, fetchJobOrders } = useJobOrders()

const loadingRevenue  = ref(false)
const loadingDrivers  = ref(false)
const loadingAnalytics = ref(false)

const revenueTotal    = ref(0)
const revenueChange   = ref(0)
const revenueRawChart = ref<any[]>([])
const analyticsData   = ref<any[]>([])
const drivers         = ref<any[]>([])

// ── Computed ──────────────────────────────────────────────
const recentJobs = computed(() => jobOrders.value.slice(0, 5))

const completionRate = computed(() => {
  const total = jobOrders.value.length
  if (!total) return 0
  const done = jobOrders.value.filter(j => j.status === 'completed').length
  return Math.round(done / total * 100)
})

const cancellationRate = computed(() => {
  const total = jobOrders.value.length
  if (!total) return 0
  const cancelled = jobOrders.value.filter(j => j.status === 'cancelled').length
  return Math.round(cancelled / total * 100)
})

const stats = computed(() => [
  {
    label: 'Total Job Order', value: jobOrders.value.length,
    sub: 'Semua status', trendColor: 'text-blue-500',
    icon: Package, color: 'text-blue-600', bg: 'bg-blue-100'
  },
  {
    label: 'Aktif / Transit',
    value: jobOrders.value.filter(j => ['assigned','in_progress','picked_up','in_transit'].includes(j.status)).length,
    sub: `${jobOrders.value.filter(j => j.priority === 'urgent').length} urgent`,
    trendColor: 'text-orange-500',
    icon: Truck, color: 'text-orange-600', bg: 'bg-orange-100'
  },
  {
    label: 'Selesai',
    value: jobOrders.value.filter(j => j.status === 'completed').length,
    sub: `${completionRate.value}% completion rate`, trendColor: 'text-green-500',
    icon: CheckCircle, color: 'text-green-600', bg: 'bg-green-100'
  },
  {
    label: 'Pending',
    value: jobOrders.value.filter(j => j.status === 'pending').length,
    sub: 'Perlu ditindak', trendColor: 'text-red-500',
    icon: AlertTriangle, color: 'text-red-500', bg: 'bg-red-100'
  },
])

const quickStats = computed(() => [
  {
    label: 'Total Driver', value: drivers.value.length,
    icon: Users, color: 'text-blue-600', bg: 'bg-blue-100'
  },
  {
    label: 'Driver Aktif',
    value: drivers.value.filter(d => d.driver_profile?.availability_status === 'on_duty').length,
    icon: Truck, color: 'text-green-600', bg: 'bg-green-100'
  },
  {
    label: 'Revenue Total', value: formatCurrency(revenueTotal.value),
    icon: DollarSign, color: 'text-purple-600', bg: 'bg-purple-100'
  },
])

const statusChartData = computed(() => {
  const jo = jobOrders.value
  return {
    labels: ['Pending', 'Assigned', 'In Transit', 'Completed', 'Cancelled'],
    datasets: [{
      data: [
        jo.filter(j => j.status === 'pending').length,
        jo.filter(j => ['assigned','in_progress','picked_up'].includes(j.status)).length,
        jo.filter(j => j.status === 'in_transit').length,
        jo.filter(j => j.status === 'completed').length,
        jo.filter(j => j.status === 'cancelled').length,
      ],
      backgroundColor: ['#fbbf24','#3b82f6','#f97316','#10b981','#ef4444'],
      borderWidth: 0, hoverOffset: 6,
    }]
  }
})

const statusLegend = computed(() => {
  const jo = jobOrders.value
  return [
    { label: 'Pending',    color: '#fbbf24', value: jo.filter(j => j.status === 'pending').length },
    { label: 'Assigned',   color: '#3b82f6', value: jo.filter(j => ['assigned','in_progress','picked_up'].includes(j.status)).length },
    { label: 'In Transit', color: '#f97316', value: jo.filter(j => j.status === 'in_transit').length },
    { label: 'Completed',  color: '#10b981', value: jo.filter(j => j.status === 'completed').length },
    { label: 'Cancelled',  color: '#ef4444', value: jo.filter(j => j.status === 'cancelled').length },
  ]
})

const revenueChartData = computed(() => ({
  labels: revenueRawChart.value.map((r: any) =>
    new Date(r.date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' })
  ),
  datasets: [{
    label: 'Revenue',
    data: revenueRawChart.value.map((r: any) => Number(r.revenue) || 0),
    borderColor: '#2563eb',
    backgroundColor: (context: any) => {
      const { ctx, chartArea } = context.chart
      if (!chartArea) return 'transparent'
      const g = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom)
      g.addColorStop(0, 'rgba(37,99,235,0.35)')
      g.addColorStop(1, 'rgba(37,99,235,0)')
      return g
    },
    borderWidth: 2.5, fill: true, tension: 0.4,
    pointRadius: 3, pointHoverRadius: 6,
    pointBackgroundColor: '#2563eb', pointBorderColor: '#fff', pointBorderWidth: 2,
  }]
}))

const deliveryBarData = computed(() => ({
  labels: analyticsData.value.map((r: any) =>
    new Date(r.date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' })
  ),
  datasets: [{
    label: 'Job Order',
    data: analyticsData.value.map((r: any) => r.count),
    backgroundColor: 'rgba(37,99,235,0.15)', borderColor: '#2563eb',
    borderWidth: 2, borderRadius: 8, borderSkipped: false,
  }]
}))

// ── Chart Options ─────────────────────────────────────────
const chartOptions: any = {
  responsive: true, maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#1e3a5f', padding: 10, cornerRadius: 8,
      callbacks: { label: (ctx: any) => `Rp ${Number(ctx.raw).toLocaleString('id-ID')}` }
    }
  },
  scales: {
    x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 10 } } },
    y: { grid: { color: '#f3f4f6' }, ticks: { color: '#9ca3af', font: { size: 10 }, callback: (v: any) => `${(v/1000000).toFixed(0)}jt` } }
  }
}

const barOptions: any = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 10 } } },
    y: { grid: { color: '#f3f4f6' }, ticks: { color: '#9ca3af', font: { size: 10 } } }
  }
}

const doughnutOptions: any = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  cutout: '70%',
}

// ── Fetch ─────────────────────────────────────────────────
async function fetchRevenue() {
  loadingRevenue.value = true
  try {
    const res = await api.get('/dashboard/revenue')
    const raw = typeof res.data === 'string'
      ? JSON.parse(res.data.replace(/^=/, ''))
      : res.data
    revenueTotal.value    = raw.total_revenue ?? 0
    revenueChange.value   = raw.percentage_change ?? 0
    revenueRawChart.value = raw.chart_data ?? []
  } catch {
    revenueTotal.value = 0
  } finally {
    loadingRevenue.value = false
  }
}

async function fetchAnalytics() {
  loadingAnalytics.value = true
  try {
    const res = await api.get('/analytics/utilization')
    const raw = typeof res.data === 'string'
      ? JSON.parse(res.data.replace(/^=/, ''))
      : res.data
    analyticsData.value = raw.data?.revenue_chart ?? []
  } catch {
    analyticsData.value = []
  } finally {
    loadingAnalytics.value = false
  }
}

async function fetchDrivers() {
  loadingDrivers.value = true
  try {
    const res = await api.get('/admin/users', { params: { role: 'driver', per_page: 20 } })
    const raw = typeof res.data === 'string'
      ? JSON.parse(res.data.replace(/^=/, ''))
      : res.data
    drivers.value = raw.data?.data ?? raw.data ?? []
  } catch {
    drivers.value = []
  } finally {
    loadingDrivers.value = false
  }
}

// ── Helpers ───────────────────────────────────────────────
function formatCurrency(val: number) {
  if (!val || val === 0) return 'Rp 0'
  if (val >= 1_000_000_000) return `Rp ${(val/1_000_000_000).toFixed(1)}M`
  if (val >= 1_000_000)     return `Rp ${(val/1_000_000).toFixed(1)}jt`
  // Ganti ini — pakai toLocaleString dengan locale id-ID
  return 'Rp ' + Number(val).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
}
function dotColor(status: string) {
  const map: Record<string, string> = {
    pending: 'bg-yellow-400', assigned: 'bg-blue-500',
    in_progress: 'bg-indigo-500', in_transit: 'bg-orange-500',
    completed: 'bg-green-500', cancelled: 'bg-red-400',
  }
  return map[status] || 'bg-gray-300'
}

function driverStatusClass(status: string) {
  const map: Record<string, string> = {
    available: 'bg-green-100 text-green-700',
    on_duty:   'bg-blue-100 text-blue-700',
    off_duty:  'bg-gray-100 text-gray-600',
    rest:      'bg-yellow-100 text-yellow-700',
  }
  return map[status] || 'bg-gray-100 text-gray-600'
}

function driverStatusLabel(status: string) {
  const map: Record<string, string> = {
    available: 'Tersedia',
    on_duty:   'Bertugas',
    off_duty:  'Tidak Bertugas',
    rest:      'Istirahat',
  }
  return map[status] || 'Unknown'
}

onMounted(async () => {
  await Promise.all([
    fetchJobOrders({ per_page: 100 }),
    fetchRevenue(),
    fetchDrivers(),
    fetchAnalytics(),
  ])
})
</script>