<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-2xl font-bold">Estimasi Biaya</h1>
      <p class="text-sm text-gray-500 mt-1">Hitung estimasi biaya pengiriman</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Form Kalkulasi -->
      <div class="bg-white rounded-xl border p-5 shadow-sm space-y-4">
        <h2 class="font-semibold">Kalkulator Biaya</h2>

        <div>
          <label class="text-xs font-medium text-gray-600">Kota Asal</label>
          <input v-model="form.origin" class="input-field" placeholder="Jakarta" />
        </div>
        <div>
          <label class="text-xs font-medium text-gray-600">Kota Tujuan</label>
          <input v-model="form.destination" class="input-field" placeholder="Surabaya" />
        </div>
        <div>
          <label class="text-xs font-medium text-gray-600">Jarak (km)</label>
          <input v-model.number="form.distance" type="number" class="input-field" placeholder="750" />
        </div>
        <div>
          <label class="text-xs font-medium text-gray-600">Berat Kargo (kg)</label>
          <input v-model.number="form.cargo_weight" type="number" class="input-field" placeholder="5000" />
        </div>
        <div>
          <label class="text-xs font-medium text-gray-600">Jenis Kendaraan</label>
          <select v-model="form.vehicle_type" class="input-field">
            <option value="truck_small">Truk Kecil (< 5 ton)</option>
            <option value="truck_medium">Truk Sedang (5-10 ton)</option>
            <option value="truck_large">Truk Besar (> 10 ton)</option>
            <option value="container">Container</option>
          </select>
        </div>

        <button @click="calculate"
          class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition font-medium">
          Hitung Estimasi
        </button>
      </div>

      <!-- Hasil -->
      <div class="space-y-4">
        <div v-if="result" class="bg-white rounded-xl border p-5 shadow-sm space-y-4">
          <h2 class="font-semibold">Hasil Estimasi</h2>

          <div class="space-y-3">
            <div class="flex justify-between items-center py-2 border-b">
              <span class="text-sm text-gray-600">Biaya Dasar</span>
              <span class="font-medium">Rp {{ result.base_cost.toLocaleString('id-ID') }}</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b">
              <span class="text-sm text-gray-600">Biaya BBM</span>
              <span class="font-medium">Rp {{ result.fuel_cost.toLocaleString('id-ID') }}</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b">
              <span class="text-sm text-gray-600">Biaya Tol</span>
              <span class="font-medium">Rp {{ result.toll_cost.toLocaleString('id-ID') }}</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b">
              <span class="text-sm text-gray-600">Biaya Driver</span>
              <span class="font-medium">Rp {{ result.driver_fee.toLocaleString('id-ID') }}</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b">
              <span class="text-sm text-gray-600">Asuransi</span>
              <span class="font-medium">Rp {{ result.insurance.toLocaleString('id-ID') }}</span>
            </div>
            <div class="flex justify-between items-center py-3 bg-blue-50 rounded-lg px-3">
              <span class="font-bold text-blue-700">Total Estimasi</span>
              <span class="font-bold text-blue-700 text-lg">
                Rp {{ result.total.toLocaleString('id-ID') }}
              </span>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3 text-xs text-gray-500 bg-gray-50 rounded-lg p-3">
            <div>
              <p class="font-medium text-gray-700">Rute</p>
              <p>{{ form.origin }} → {{ form.destination }}</p>
            </div>
            <div>
              <p class="font-medium text-gray-700">Jarak</p>
              <p>{{ form.distance }} km</p>
            </div>
            <div>
              <p class="font-medium text-gray-700">Berat</p>
              <p>{{ form.cargo_weight.toLocaleString() }} kg</p>
            </div>
            <div>
              <p class="font-medium text-gray-700">Kendaraan</p>
              <p>{{ vehicleLabel(form.vehicle_type) }}</p>
            </div>
          </div>
        </div>

        <!-- Riwayat Estimasi -->
        <div class="bg-white rounded-xl border p-5 shadow-sm">
          <h2 class="font-semibold mb-3">Riwayat Estimasi</h2>
          <div v-if="history.length === 0" class="text-center py-6 text-gray-400">
            <p class="text-sm">Belum ada riwayat estimasi</p>
          </div>
          <div v-else class="space-y-2">
            <div v-for="(h, i) in history" :key="i"
              class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition">
              <div>
                <p class="text-sm font-medium">{{ h.origin }} → {{ h.destination }}</p>
                <p class="text-xs text-gray-400">{{ h.distance }} km · {{ h.cargo_weight.toLocaleString() }} kg</p>
              </div>
              <p class="font-medium text-blue-600 text-sm">
                Rp {{ h.total.toLocaleString('id-ID') }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'

const form = reactive({
  origin: '',
  destination: '',
  distance: 0,
  cargo_weight: 0,
  vehicle_type: 'truck_medium',
})

const result = ref<any>(null)
const history = ref<any[]>([])

function calculate() {
  if (!form.distance || !form.cargo_weight) return

  const multiplier: Record<string, number> = {
    truck_small: 1,
    truck_medium: 1.5,
    truck_large: 2,
    container: 2.5,
  }

  const m = multiplier[form.vehicle_type] || 1
  const base_cost = form.distance * 2000 * m
  const fuel_cost = form.distance * 1500 * m
  const toll_cost = form.distance * 500
  const driver_fee = form.distance * 800
  const insurance = (base_cost + fuel_cost) * 0.02
  const total = base_cost + fuel_cost + toll_cost + driver_fee + insurance

  result.value = {
    base_cost: Math.round(base_cost),
    fuel_cost: Math.round(fuel_cost),
    toll_cost: Math.round(toll_cost),
    driver_fee: Math.round(driver_fee),
    insurance: Math.round(insurance),
    total: Math.round(total),
  }

  history.value.unshift({
    origin: form.origin || 'Asal',
    destination: form.destination || 'Tujuan',
    distance: form.distance,
    cargo_weight: form.cargo_weight,
    total: Math.round(total),
  })

  if (history.value.length > 5) history.value.pop()
}

function vehicleLabel(type: string) {
  const map: Record<string, string> = {
    truck_small: 'Truk Kecil',
    truck_medium: 'Truk Sedang',
    truck_large: 'Truk Besar',
    container: 'Container',
  }
  return map[type] || type
}
</script>

<style scoped>
.input-field {
  @apply w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500;
}
</style>