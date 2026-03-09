<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-2xl font-bold">Utilisasi Armada</h1>
      <p class="text-sm text-gray-500 mt-1">Pantau efisiensi penggunaan kendaraan dan driver</p>
    </div>

    <!-- Fleet Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div v-for="stat in fleetStats" :key="stat.label"
        class="bg-white rounded-xl border p-4 shadow-sm">
        <div class="flex items-center justify-between">
          <p class="text-xs text-gray-500">{{ stat.label }}</p>
          <div :class="`w-8 h-8 rounded-lg ${stat.bg} flex items-center justify-center`">
            <component :is="stat.icon" :class="`w-4 h-4 ${stat.color}`" />
          </div>
        </div>
        <p class="text-2xl font-bold mt-2">{{ stat.value }}</p>
        <div class="mt-2 h-1.5 bg-gray-100 rounded-full overflow-hidden">
          <div :class="`h-full rounded-full ${stat.barColor}`"
            :style="{ width: stat.pct + '%' }" />
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Driver Utilization -->
      <div class="bg-white rounded-xl border p-5 shadow-sm">
        <h2 class="font-semibold mb-4">Utilisasi Driver</h2>
        <div class="space-y-4">
          <div v-for="driver in driverUtils" :key="driver.id"
            class="space-y-1">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xs font-bold">
                  {{ driver.name.split(' ').map((n: string) => n[0]).join('').slice(0, 2) }}
                </div>
                <div>
                  <p class="text-sm font-medium">{{ driver.name }}</p>
                  <p class="text-xs text-gray-400">{{ driver.total_trips }} trip</p>
                </div>
              </div>
              <div class="text-right">
                <p class="text-sm font-bold" :class="driver.utilPct >= 70 ? 'text-green-600' : 'text-yellow-600'">
                  {{ driver.utilPct }}%
                </p>
                <p class="text-xs text-gray-400">⭐ {{ driver.rating }}</p>
              </div>
            </div>
            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
              <div :class="`h-full rounded-full transition-all ${
                driver.utilPct >= 70 ? 'bg-green-500' :
                driver.utilPct >= 40 ? 'bg-yellow-500' : 'bg-red-400'
              }`" :style="{ width: driver.utilPct + '%' }" />
            </div>
          </div>
        </div>
      </div>

      <!-- Vehicle Utilization -->
      <div class="bg-white rounded-xl border p-5 shadow-sm">
        <h2 class="font-semibold mb-4">Utilisasi Kendaraan</h2>
        <div class="space-y-3">
          <div v-for="vehicle in vehicleUtils" :key="vehicle.id"
            class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition">
            <div :class="`w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 ${
              vehicle.status === 'available' ? 'bg-green-100' :
              vehicle.status === 'busy' ? 'bg-orange-100' : 'bg-red-100'
            }`">
              <Truck :class="`w-4 h-4 ${
                vehicle.status === 'available' ? 'text-green-600' :
                vehicle.status === 'busy' ? 'text-orange-600' : 'text-red-600'
              }`" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium">{{ vehicle.name }}</p>
              <p class="text-xs text-gray-400">{{ vehicle.plate_number }} · {{ vehicle.capacity.toLocaleString() }} kg</p>
            </div>
            <span :class="`text-xs px-2 py-0.5 rounded-full font-medium ${
              vehicle.status === 'available' ? 'bg-green-100 text-green-700' :
              vehicle.status === 'busy' ? 'bg-orange-100 text-orange-700' :
              'bg-red-100 text-red-700'
            }`">
              {{ vehicle.status === 'available' ? 'Tersedia' :
                 vehicle.status === 'busy' ? 'Dipakai' : 'Maintenance' }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Summary Table -->
    <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
      <div class="p-5 border-b">
        <h2 class="font-semibold">Ringkasan Periode</h2>
      </div>
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Driver</th>
            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Total Trip</th>
            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Rating</th>
            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Status</th>
            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Utilisasi</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <tr v-for="driver in driverUtils" :key="driver.id"
            class="hover:bg-gray-50 transition">
            <td class="px-4 py-3 font-medium">{{ driver.name }}</td>
            <td class="px-4 py-3">{{ driver.total_trips }}</td>
            <td class="px-4 py-3">⭐ {{ driver.rating }}</td>
            <td class="px-4 py-3">
              <span :class="`text-xs px-2 py-0.5 rounded-full font-medium ${
                driver.status === 'available' ? 'bg-green-100 text-green-700' :
                driver.status === 'on_duty' ? 'bg-blue-100 text-blue-700' :
                driver.status === 'off_duty' ? 'bg-gray-100 text-gray-600' :
                'bg-yellow-100 text-yellow-700'
              }`">
                {{ driver.status }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <div class="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                  <div :class="`h-full rounded-full ${driver.utilPct >= 70 ? 'bg-green-500' : 'bg-yellow-500'}`"
                    :style="{ width: driver.utilPct + '%' }" />
                </div>
                <span class="text-xs font-medium">{{ driver.utilPct }}%</span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Truck, Users, BarChart3, Activity } from 'lucide-vue-next'
import { MOCK_DRIVERS, MOCK_CARRIERS } from '@/lib/mockData'

const driverUtils = computed(() =>
  MOCK_DRIVERS.map(d => ({
    ...d,
    utilPct: Math.min(Math.round((d.total_trips / 350) * 100), 100),
  }))
)

const vehicleUtils = computed(() => MOCK_CARRIERS)

const fleetStats = computed(() => [
  {
    label: 'Total Driver',
    value: MOCK_DRIVERS.length,
    pct: 100,
    icon: Users,
    color: 'text-blue-600',
    bg: 'bg-blue-50',
    barColor: 'bg-blue-500',
  },
  {
    label: 'Driver Aktif',
    value: MOCK_DRIVERS.filter(d => d.status === 'on_duty').length,
    pct: Math.round((MOCK_DRIVERS.filter(d => d.status === 'on_duty').length / MOCK_DRIVERS.length) * 100),
    icon: Activity,
    color: 'text-green-600',
    bg: 'bg-green-50',
    barColor: 'bg-green-500',
  },
  {
    label: 'Kendaraan Tersedia',
    value: MOCK_CARRIERS.filter(c => c.status === 'available').length,
    pct: Math.round((MOCK_CARRIERS.filter(c => c.status === 'available').length / MOCK_CARRIERS.length) * 100),
    icon: Truck,
    color: 'text-orange-600',
    bg: 'bg-orange-50',
    barColor: 'bg-orange-500',
  },
  {
    label: 'Rata-rata Rating',
    value: (MOCK_DRIVERS.reduce((a, b) => a + b.rating, 0) / MOCK_DRIVERS.length).toFixed(1),
    pct: Math.round((MOCK_DRIVERS.reduce((a, b) => a + b.rating, 0) / MOCK_DRIVERS.length / 5) * 100),
    icon: BarChart3,
    color: 'text-purple-600',
    bg: 'bg-purple-50',
    barColor: 'bg-purple-500',
  },
])
</script>