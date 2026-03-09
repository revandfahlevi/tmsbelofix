<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-2xl font-bold">GPS Tracking</h1>
      <p class="text-sm text-gray-500 mt-1">Pantau posisi kendaraan secara real-time</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div v-for="stat in stats" :key="stat.label"
        class="bg-white rounded-xl border p-4 shadow-sm">
        <p class="text-xs text-gray-500">{{ stat.label }}</p>
        <p class="text-2xl font-bold mt-1">{{ stat.value }}</p>
        <p class="text-xs mt-1" :class="stat.color">{{ stat.sub }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Map Placeholder -->
      <div class="lg:col-span-2 bg-white rounded-xl border shadow-sm overflow-hidden">
        <div class="p-4 border-b flex items-center justify-between">
          <h2 class="font-semibold">Peta Live</h2>
          <div class="flex items-center gap-2">
            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse" />
            <span class="text-xs text-green-600">Live</span>
          </div>
        </div>
        <div class="relative bg-blue-50 h-96 flex items-center justify-center">
          <!-- Simulasi Map -->
          <div class="absolute inset-0 p-4">
            <div class="w-full h-full bg-gradient-to-br from-blue-100 to-green-100 rounded-lg relative overflow-hidden">
              <!-- Grid lines -->
              <div class="absolute inset-0 opacity-20"
                style="background-image: linear-gradient(#3b82f6 1px, transparent 1px), linear-gradient(90deg, #3b82f6 1px, transparent 1px); background-size: 40px 40px;" />

              <!-- Vehicle markers -->
              <div v-for="pos in positions" :key="pos.vehicle_id"
                class="absolute transform -translate-x-1/2 -translate-y-1/2 cursor-pointer"
                :style="{ left: mapX(pos.lng) + '%', top: mapY(pos.lat) + '%' }"
                @click="selectedVehicle = pos">
                <div class="relative">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center shadow-lg"
                    :class="selectedVehicle?.vehicle_id === pos.vehicle_id ? 'bg-blue-600' : 'bg-orange-500'">
                    <Truck class="w-4 h-4 text-white" />
                  </div>
                  <div class="absolute -top-6 left-1/2 -translate-x-1/2 bg-white text-xs px-1.5 py-0.5 rounded shadow whitespace-nowrap">
                    {{ pos.plate_number }}
                  </div>
                  <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-green-500 rounded-full animate-pulse" />
                </div>
              </div>

              <!-- Map label -->
              <div class="absolute bottom-2 right-2 bg-white/80 text-xs px-2 py-1 rounded">
                Area Jabodetabek
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Vehicle List -->
      <div class="bg-white rounded-xl border shadow-sm">
        <div class="p-4 border-b">
          <h2 class="font-semibold">Kendaraan Aktif</h2>
        </div>
        <div class="divide-y">
          <div v-for="pos in positions" :key="pos.vehicle_id"
            @click="selectedVehicle = pos"
            :class="`p-4 cursor-pointer transition ${
              selectedVehicle?.vehicle_id === pos.vehicle_id
                ? 'bg-blue-50'
                : 'hover:bg-gray-50'
            }`">
            <div class="flex items-start justify-between mb-2">
              <div>
                <p class="font-medium text-sm">{{ pos.plate_number }}</p>
                <p class="text-xs text-gray-400">{{ pos.driver_name }}</p>
              </div>
              <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse mt-1" />
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs text-gray-500">
              <div class="flex items-center gap-1">
                <Gauge class="w-3 h-3" />
                {{ pos.speed }} km/h
              </div>
              <div class="flex items-center gap-1">
                <MapPin class="w-3 h-3" />
                {{ pos.status }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Selected Vehicle Detail -->
    <div v-if="selectedVehicle"
      class="bg-white rounded-xl border p-5 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold">Detail Kendaraan — {{ selectedVehicle.plate_number }}</h2>
        <button @click="selectedVehicle = null" class="text-gray-400 hover:text-gray-600">
          <X class="w-4 h-4" />
        </button>
      </div>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gray-50 rounded-lg p-3">
          <p class="text-xs text-gray-500">Driver</p>
          <p class="font-medium text-sm mt-1">{{ selectedVehicle.driver_name }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
          <p class="text-xs text-gray-500">Kecepatan</p>
          <p class="font-medium text-sm mt-1">{{ selectedVehicle.speed }} km/h</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
          <p class="text-xs text-gray-500">Koordinat</p>
          <p class="font-medium text-sm mt-1">{{ selectedVehicle.lat.toFixed(4) }}, {{ selectedVehicle.lng.toFixed(4) }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
          <p class="text-xs text-gray-500">Update Terakhir</p>
          <p class="font-medium text-sm mt-1">Baru saja</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Truck, MapPin, X, Gauge } from 'lucide-vue-next'
import { MOCK_GPS_POSITIONS } from '@/lib/mockData'
import type { VehiclePosition } from '@/lib/api'

const positions = ref([...MOCK_GPS_POSITIONS])
const selectedVehicle = ref<VehiclePosition | null>(null)

const stats = computed(() => [
  { label: 'Total Kendaraan', value: positions.value.length, sub: 'Terpantau', color: 'text-blue-600' },
  { label: 'Sedang Bergerak', value: positions.value.filter(p => p.speed > 0).length, sub: 'Aktif', color: 'text-green-600' },
  { label: 'Kecepatan Rata-rata', value: Math.round(positions.value.reduce((a, b) => a + b.speed, 0) / positions.value.length) + ' km/h', sub: 'Saat ini', color: 'text-orange-600' },
  { label: 'Area', value: 'Jabodetabek', sub: 'Coverage', color: 'text-purple-600' },
])

// Convert koordinat ke posisi % di peta
function mapX(lng: number) {
  const minLng = 106.60, maxLng = 107.20
  return ((lng - minLng) / (maxLng - minLng)) * 80 + 10
}

function mapY(lat: number) {
  const minLat = -6.50, maxLat = -6.00
  return ((lat - minLat) / (maxLat - minLat)) * 80 + 10
}
</script>