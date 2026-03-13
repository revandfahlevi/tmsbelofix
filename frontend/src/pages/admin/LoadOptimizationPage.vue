<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-2xl font-bold">Load Optimization</h1>
      <p class="text-sm text-gray-500 mt-1">Optimalkan muatan kendaraan untuk efisiensi maksimal</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Form -->
      <div class="bg-white rounded-xl border p-5 shadow-sm space-y-4">
        <h2 class="font-semibold">Tambah Item Muatan</h2>

        <!-- Pilih Kendaraan: Inline Table -->
        <div>
          <label class="text-xs font-medium text-gray-600">Pilih Kendaraan</label>
          <div class="mt-1 border border-gray-200 rounded-lg overflow-hidden">
            <table class="w-full text-sm border-collapse">
              <thead class="bg-gray-50">
                <tr>
                  <th class="text-left px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide">Kendaraan</th>
                  <th class="text-right px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide">Kapasitas</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="c in carriers"
                  :key="c.id"
                  @click="selectedVehicle = c.id"
                  class="border-t border-gray-100 cursor-pointer transition-colors"
                  :class="selectedVehicle === c.id ? 'bg-blue-600 text-white' : 'hover:bg-gray-50'"
                >
                  <td class="px-3 py-2.5 font-medium">{{ c.name }}</td>
                  <td class="px-3 py-2.5 text-right text-xs" :class="selectedVehicle === c.id ? 'text-blue-100' : 'text-gray-400'">
                    {{ c.capacity.toLocaleString() }} kg
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Vehicle capacity bar -->
        <div v-if="selectedVehicle" class="bg-gray-50 rounded-lg p-3">
          <div class="flex justify-between text-xs mb-1">
            <span class="font-medium">Kapasitas Terpakai</span>
            <span :class="utilizationPct > 90 ? 'text-red-600' : 'text-green-600'">
              {{ totalWeight.toLocaleString() }} / {{ selectedCarrier?.capacity.toLocaleString() }} kg
              ({{ utilizationPct }}%)
            </span>
          </div>
          <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
            <div :class="`h-full rounded-full transition-all ${
              utilizationPct > 90 ? 'bg-red-500' :
              utilizationPct > 70 ? 'bg-yellow-500' : 'bg-green-500'
            }`" :style="{ width: Math.min(utilizationPct, 100) + '%' }" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs font-medium text-gray-600">Nama Item</label>
            <input v-model="form.name" class="input-field" placeholder="Elektronik" />
          </div>
          <div>
            <label class="text-xs font-medium text-gray-600">Berat (kg)</label>
            <input v-model.number="form.weight" type="number" class="input-field" placeholder="500" />
          </div>
          <div>
            <label class="text-xs font-medium text-gray-600">Volume (m³)</label>
            <input v-model.number="form.volume" type="number" class="input-field" placeholder="2.5" />
          </div>
          <div class="flex items-end pb-1">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.fragile" type="checkbox"
                class="w-4 h-4 rounded border-gray-300 text-blue-600" />
              <span class="text-xs font-medium text-gray-600">Mudah Pecah</span>
            </label>
          </div>
        </div>

        <button @click="addItem"
          :disabled="!form.name || !form.weight || !selectedVehicle"
          class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition font-medium disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2">
          <Plus class="w-4 h-4" />
          Tambah Item
        </button>
      </div>

      <!-- Item List -->
      <div class="bg-white rounded-xl border p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold">Daftar Muatan</h2>
          <span class="text-xs text-gray-500">{{ items.length }} item</span>
        </div>

        <div v-if="items.length === 0" class="text-center py-8 text-gray-400">
          <Layers class="w-10 h-10 mx-auto mb-2 opacity-30" />
          <p class="text-sm">Belum ada item ditambahkan</p>
        </div>

        <div v-else class="space-y-2">
          <div v-for="(item, i) in items" :key="item.id"
            class="flex items-center gap-3 p-3 border rounded-lg hover:bg-gray-50 transition">
            <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
              <Package class="w-4 h-4 text-blue-600" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium">{{ item.name }}</p>
              <p class="text-xs text-gray-400">
                {{ item.weight.toLocaleString() }} kg · {{ item.volume }} m³
                <span v-if="item.fragile" class="text-orange-500 ml-1">⚠ Fragile</span>
              </p>
            </div>
            <button @click="removeItem(i)" class="text-gray-300 hover:text-red-500 transition">
              <X class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Summary -->
        <div v-if="items.length > 0" class="mt-4 pt-4 border-t space-y-1">
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Total Berat</span>
            <span class="font-medium">{{ totalWeight.toLocaleString() }} kg</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Total Volume</span>
            <span class="font-medium">{{ totalVolume.toFixed(1) }} m³</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Utilisasi</span>
            <span :class="`font-medium ${utilizationPct > 90 ? 'text-red-600' : 'text-green-600'}`">
              {{ utilizationPct }}%
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { Plus, X, Layers, Package } from 'lucide-vue-next'
import { MOCK_CARRIERS } from '@/lib/mockData'

const carriers = MOCK_CARRIERS
const selectedVehicle = ref('')
const items = ref<any[]>([])

const form = reactive({
  name: '',
  weight: 0,
  volume: 0,
  fragile: false,
})

const selectedCarrier = computed(() =>
  carriers.find(c => c.id === selectedVehicle.value)
)

const totalWeight = computed(() =>
  items.value.reduce((sum, i) => sum + i.weight, 0)
)

const totalVolume = computed(() =>
  items.value.reduce((sum, i) => sum + i.volume, 0)
)

const utilizationPct = computed(() => {
  if (!selectedCarrier.value) return 0
  return Math.round((totalWeight.value / selectedCarrier.value.capacity) * 100)
})

function addItem() {
  if (!form.name || !form.weight) return
  items.value.push({
    id: Date.now().toString(),
    name: form.name,
    weight: form.weight,
    volume: form.volume,
    fragile: form.fragile,
  })
  form.name = ''
  form.weight = 0
  form.volume = 0
  form.fragile = false
}

function removeItem(index: number) {
  items.value.splice(index, 1)
}
</script>

<style scoped>
.input-field {
  @apply w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500;
}
</style>