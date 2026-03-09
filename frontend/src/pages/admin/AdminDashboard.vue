<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
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
          <span v-if="loadingStats" class="inline-block w-12 h-6 bg-gray-200 animate-pulse rounded" />
          <span v-else>{{ stat.value }}</span>
        </p>
        <p class="text-xs mt-1" :class="stat.trendColor">{{ stat.sub }}</p>
      </div>
    </div>

    <!-- Chart Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Line Chart -->
      <div class="lg:col-span-2 bg-white rounded-2xl border border-blue-50 shadow-sm p-5">
        <div class="flex items-center justify-between mb-1">
          <div>
            <h2 class="font-semibold text-gray-800">Tren Revenue</h2>
            <p class="text-xs text-gray-400">30 hari terakhir</p>
          </div>
        </div>
        <div class="flex items-end gap-3 mb-4">
          <p class="text-3xl font-bold text-gray-800">Rp 245,0jt</p>
          <div class="flex items-center gap-1 mb-1">
            <TrendingUp class="w-4 h-4 text-green-500" />
            <span class="text-xs text-green-600 font-medium">+12.5% dari bulan lalu</span>
          </div>
        </div>
        <div class="h-56">
          <Line :data="revenueChartData" :options="chartOptions" />
        </div>
      </div>

      <!-- Donut - Status dari API -->
      <div class="bg-white rounded-2xl border border-blue-50 shadow-sm p-5">
        <h2 class="font-semibold text-gray-800 mb-1">Status Job Order</h2>
        <p class="text-xs text-gray-400 mb-4">Distribusi saat ini</p>
        <div class="h-44">
          <Doughnut :data="statusChartData" :options="doughnutOptions" />
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

    <!-- Second Chart Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white rounded-2xl border border-blue-50 shadow-sm p-5">
        <div class="mb-4">
          <h2 class="font-semibold text-gray-800">Pengiriman Per Hari</h2>
          <p class="text-xs text-gray-400">7 hari terakhir</p>
        </div>
        <div class="h-48">
          <Bar :data="deliveryBarData" :options="barOptions" />
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-blue-50 shadow-sm p-5">
        <div class="mb-4">
          <h2 class="font-semibold text-gray-800">Aktivitas Driver</h2>
          <p class="text-xs text-gray-400">7 hari terakhir</p>
        </div>
        <div class="h-48">
          <Line :data="driverChartData" :options="driverChartOptions" />
        </div>
      </div>
    </div>

    <!-- Bottom Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Recent Job Orders dari API -->
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
                {{ job.cargo_weight_kg ? job.cargo_weight_kg.toLocaleString() + ' kg' : '-' }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Driver Status (masih mock, belum connect GPS API) -->
      <div class="bg-white rounded-2xl border border-blue-50 shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold text-gray-800">Status Supir</h2>
          <span class="flex items-center gap-1 text-xs text-gray-400 italic">
            demo data
          </span>
        </div>
        <div class="space-y-3">
          <div v-for="driver in MOCK_DRIVERS" :key="driver.id"
            class="flex items-center gap-3 p-2 rounded-xl hover:bg-blue-50 transition">
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
              {{ driver.name.split(' ').map((n: string) => n[0]).join('').slice(0, 2) }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-800 truncate">{{ driver.name }}</p>
              <p class="text-xs text-gray-400">{{ driver.total_trips }} trip · ⭐ {{ driver.rating }}</p>
            </div>
            <span :class="`text-xs px-2 py-0.5 rounded-full font-medium ${driverStatusClass(driver.status)}`">
              {{ driver.status }}
            </span>
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
  AlertTriangle, DollarSign, Users, TrendingUp
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
import { MOCK_DRIVERS } from '@/lib/mockData'

ChartJS.register(
  CategoryScale, LinearScale, PointElement,
  LineElement, BarElement, ArcElement,
  Title, Tooltip, Legend, Filler
)

const auth = useAuthStore()
const { jobOrders, loading: loadingJobs, fetchJobOrders } = useJobOrders()

const loadingStats = ref(false)

// Ambil 5 terbaru
const recentJobs = computed(() => jobOrders.value.slice(0, 5))

// Stats realtime dari jobOrders
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
    sub: 'Completed', trendColor: 'text-green-500',
    icon: CheckCircle, color: 'text-green-600', bg: 'bg-green-100'
  },
  {
    label: 'Pending',
    value: jobOrders.value.filter(j => j.status === 'pending').length,
    sub: 'Perlu ditindak', trendColor: 'text-red-500',
    icon: AlertTriangle, color: 'text-red-500', bg: 'bg-red-100'
  },
  {
    label: 'Draft',
    value: jobOrders.value.filter(j => j.status === 'draft').length,
    sub: 'Belum diproses', trendColor: 'text-gray-400',
    icon: Clock, color: 'text-cyan-600', bg: 'bg-cyan-100'
  },
  {
    label: 'Cancelled',
    value: jobOrders.value.filter(j => j.status === 'cancelled').length,
    sub: 'Dibatalkan', trendColor: 'text-red-400',
    icon: AlertTriangle, color: 'text-red-400', bg: 'bg-red-50'
  },
  {
    label: 'Total Revenue (Rp)', value: '245.0jt',
    sub: 'Belum connect API', trendColor: 'text-gray-400',
    icon: DollarSign, color: 'text-emerald-600', bg: 'bg-emerald-100'
  },
])

// Donut dari data real
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
      borderWidth: 0,
      hoverOffset: 6,
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

// Charts static (belum ada API)
const revenueLabels = ['1 Mar','3 Mar','5 Mar','7 Mar','9 Mar','11 Mar','13 Mar','15 Mar','17 Mar','19 Mar','21 Mar','23 Mar','25 Mar','27 Mar','29 Mar']
const revenueData = [12,18,15,22,19,28,25,32,28,35,30,38,35,42,45].map(v => v * 1000000)

const revenueChartData = computed(() => ({
  labels: revenueLabels,
  datasets: [{
    label: 'Revenue',
    data: revenueData,
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

const chartOptions: any = {
  responsive: true, maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#1e3a5f', padding: 10, cornerRadius: 8,
      callbacks: { label: (ctx: any) => `Rp ${(ctx.raw/1000000).toFixed(1)}jt` }
    }
  },
  scales: {
    x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 10 } } },
    y: { grid: { color: '#f3f4f6' }, ticks: { color: '#9ca3af', font: { size: 10 }, callback: (v: any) => `${(v/1000000).toFixed(0)}jt` } }
  }
}

const deliveryBarData = computed(() => ({
  labels: ['Sen','Sel','Rab','Kam','Jum','Sab','Min'],
  datasets: [{
    label: 'Pengiriman', data: [8,12,10,15,11,7,5],
    backgroundColor: 'rgba(37,99,235,0.15)', borderColor: '#2563eb',
    borderWidth: 2, borderRadius: 8, borderSkipped: false,
  }]
}))

const barOptions: any = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 11 } } },
    y: { grid: { color: '#f3f4f6' }, ticks: { color: '#9ca3af', font: { size: 11 } } }
  }
}

const driverChartData = computed(() => ({
  labels: ['Sen','Sel','Rab','Kam','Jum','Sab','Min'],
  datasets: [
    {
      label: 'On Duty', data: [3,4,3,5,4,2,1],
      borderColor: '#2563eb', fill: true, tension: 0.4,
      backgroundColor: (context: any) => {
        const { ctx, chartArea } = context.chart
        if (!chartArea) return 'transparent'
        const g = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom)
        g.addColorStop(0, 'rgba(37,99,235,0.3)'); g.addColorStop(1, 'rgba(37,99,235,0)')
        return g
      },
      borderWidth: 2, pointRadius: 3, pointBackgroundColor: '#2563eb',
      pointBorderColor: '#fff', pointBorderWidth: 2,
    },
    {
      label: 'Available', data: [2,1,2,0,1,2,3],
      borderColor: '#10b981', fill: true, tension: 0.4,
      backgroundColor: (context: any) => {
        const { ctx, chartArea } = context.chart
        if (!chartArea) return 'transparent'
        const g = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom)
        g.addColorStop(0, 'rgba(16,185,129,0.3)'); g.addColorStop(1, 'rgba(16,185,129,0)')
        return g
      },
      borderWidth: 2, pointRadius: 3, pointBackgroundColor: '#10b981',
      pointBorderColor: '#fff', pointBorderWidth: 2,
    }
  ]
}))

const driverChartOptions: any = {
  responsive: true, maintainAspectRatio: false,
  plugins: {
    legend: { display: true, position: 'top', labels: { color: '#6b7280', font: { size: 11 }, boxWidth: 12, usePointStyle: true } },
  },
  scales: {
    x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 11 } } },
    y: { grid: { color: '#f3f4f6' }, ticks: { color: '#9ca3af', font: { size: 11 } } }
  }
}

const doughnutOptions: any = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  cutout: '70%',
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
    on_duty: 'bg-blue-100 text-blue-700',
    off_duty: 'bg-gray-100 text-gray-600',
    rest: 'bg-yellow-100 text-yellow-700',
  }
  return map[status] || 'bg-gray-100 text-gray-600'
}

onMounted(() => {
  fetchJobOrders({ per_page: 50 })
})
</script>