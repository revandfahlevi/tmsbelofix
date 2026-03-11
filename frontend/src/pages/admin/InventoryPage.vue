<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Inventory</h1>
        <p class="text-sm text-gray-500 mt-1">Kelola stok barang di gudang</p>
      </div>
      <button @click="showForm = true"
        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition">
        <Plus class="w-4 h-4" />
        Tambah Item
      </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div v-for="stat in stats" :key="stat.label"
        class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
        <div class="flex items-center justify-between mb-2">
          <p class="text-xs text-gray-500 font-medium">{{ stat.label }}</p>
          <div :class="`w-7 h-7 rounded-lg ${stat.bg} flex items-center justify-center`">
            <component :is="stat.icon" :class="`w-3.5 h-3.5 ${stat.color}`" />
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-800">{{ stat.value }}</p>
      </div>
    </div>

    <!-- Filter & Search -->
    <div class="flex flex-wrap gap-3 items-center">
      <div class="flex-1 min-w-48 relative">
        <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
        <input v-model="search" placeholder="Cari item..."
          class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>
      <div class="flex gap-2">
        <button v-for="f in filters" :key="f.value"
          @click="activeFilter = f.value"
          :class="`px-3 py-2 text-xs rounded-xl font-medium transition ${
            activeFilter === f.value
              ? 'bg-blue-600 text-white'
              : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'
          }`">
          {{ f.label }} ({{ f.count }})
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div v-if="loading" class="flex items-center justify-center py-16">
        <Loader2 class="w-6 h-6 animate-spin text-blue-600" />
      </div>
      <table v-else class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">SKU</th>
            <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Nama Item</th>
            <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Kategori</th>
            <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Stok</th>
            <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Min. Stok</th>
            <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Lokasi</th>
            <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Status</th>
            <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="item in filteredItems" :key="item.id"
            class="hover:bg-gray-50 transition">
            <td class="px-4 py-3 text-sm font-mono text-blue-600">{{ item.sku }}</td>
            <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ item.name }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ item.category }}</td>
            <td class="px-4 py-3 text-sm font-semibold"
              :class="item.stock <= item.min_stock ? 'text-red-600' : 'text-gray-800'">
              {{ item.stock }} {{ item.unit }}
            </td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ item.min_stock }} {{ item.unit }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ item.location }}</td>
            <td class="px-4 py-3">
              <span :class="`text-xs px-2 py-1 rounded-full font-medium ${stockStatusClass(item)}`">
                {{ stockStatusLabel(item) }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="flex gap-1">
                <button @click="openAdjust(item)"
                  class="text-xs bg-blue-50 text-blue-600 px-2 py-1 rounded-lg hover:bg-blue-100 transition">
                  Adjust
                </button>
                <button @click="selectedItem = item"
                  class="text-xs bg-gray-50 text-gray-600 px-2 py-1 rounded-lg hover:bg-gray-100 transition">
                  Detail
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="filteredItems.length === 0">
            <td colspan="8" class="px-4 py-12 text-center text-gray-400 text-sm">
              Tidak ada data inventory
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Tambah Item -->
    <div v-if="showForm" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
      @click.self="showForm = false">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold">Tambah Item Inventory</h3>
          <button @click="showForm = false" class="p-1.5 hover:bg-gray-100 rounded-lg">
            <X class="w-5 h-5 text-gray-500" />
          </button>
        </div>
        <div class="space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs font-medium text-gray-600 mb-1 block">SKU *</label>
              <input v-model="form.sku" placeholder="SKU-001" class="input-field" />
            </div>
            <div>
              <label class="text-xs font-medium text-gray-600 mb-1 block">Kategori</label>
              <input v-model="form.category" placeholder="Packaging" class="input-field" />
            </div>
          </div>
          <div>
            <label class="text-xs font-medium text-gray-600 mb-1 block">Nama Item *</label>
            <input v-model="form.name" placeholder="Nama barang" class="input-field" />
          </div>
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="text-xs font-medium text-gray-600 mb-1 block">Stok Awal *</label>
              <input v-model.number="form.stock" type="number" placeholder="0" class="input-field" />
            </div>
            <div>
              <label class="text-xs font-medium text-gray-600 mb-1 block">Min. Stok</label>
              <input v-model.number="form.min_stock" type="number" placeholder="10" class="input-field" />
            </div>
            <div>
              <label class="text-xs font-medium text-gray-600 mb-1 block">Satuan</label>
              <select v-model="form.unit" class="input-field">
                <option value="pcs">Pcs</option>
                <option value="kg">Kg</option>
                <option value="box">Box</option>
                <option value="carton">Karton</option>
                <option value="liter">Liter</option>
              </select>
            </div>
          </div>
          <div>
            <label class="text-xs font-medium text-gray-600 mb-1 block">Lokasi Gudang</label>
            <input v-model="form.location" placeholder="Rak A-01" class="input-field" />
          </div>
          <div v-if="formError" class="bg-red-50 border border-red-200 rounded-lg px-4 py-2">
            <p class="text-red-600 text-sm">{{ formError }}</p>
          </div>
        </div>
        <div class="flex gap-2 pt-2">
          <button @click="showForm = false"
            class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-xl text-sm hover:bg-gray-50 transition">
            Batal
          </button>
          <button @click="submitForm" :disabled="submitting"
            class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white py-2 rounded-xl text-sm font-medium transition flex items-center justify-center gap-2">
            <Loader2 v-if="submitting" class="w-4 h-4 animate-spin" />
            Simpan
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Adjust Stok -->
    <div v-if="showAdjust" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
      @click.self="showAdjust = false">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold">Adjust Stok</h3>
          <button @click="showAdjust = false" class="p-1.5 hover:bg-gray-100 rounded-lg">
            <X class="w-5 h-5 text-gray-500" />
          </button>
        </div>
        <div class="space-y-3">
          <div class="bg-gray-50 rounded-xl p-3">
            <p class="text-xs text-gray-500">Item</p>
            <p class="font-semibold text-sm mt-1">{{ adjustItem?.name }}</p>
            <p class="text-xs text-gray-400 mt-0.5">Stok saat ini: {{ adjustItem?.stock }} {{ adjustItem?.unit }}</p>
          </div>
          <div>
            <label class="text-xs font-medium text-gray-600 mb-1 block">Tipe Penyesuaian</label>
            <select v-model="adjustForm.type" class="input-field">
              <option value="add">Tambah Stok</option>
              <option value="subtract">Kurangi Stok</option>
              <option value="set">Set Stok</option>
            </select>
          </div>
          <div>
            <label class="text-xs font-medium text-gray-600 mb-1 block">Jumlah *</label>
            <input v-model.number="adjustForm.amount" type="number" placeholder="0" class="input-field" />
          </div>
          <div>
            <label class="text-xs font-medium text-gray-600 mb-1 block">Alasan</label>
            <input v-model="adjustForm.reason" placeholder="Alasan penyesuaian..." class="input-field" />
          </div>
        </div>
        <div class="flex gap-2 pt-2">
          <button @click="showAdjust = false"
            class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-xl text-sm hover:bg-gray-50 transition">
            Batal
          </button>
          <button @click="submitAdjust"
            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-xl text-sm font-medium transition">
            Simpan
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Detail -->
    <div v-if="selectedItem" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
      @click.self="selectedItem = null">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold">{{ selectedItem.name }}</h3>
          <button @click="selectedItem = null" class="p-1.5 hover:bg-gray-100 rounded-lg">
            <X class="w-5 h-5 text-gray-500" />
          </button>
        </div>
        <div class="space-y-3">
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">SKU</span>
            <span class="font-mono font-medium">{{ selectedItem.sku }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Kategori</span>
            <span class="font-medium">{{ selectedItem.category }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Stok</span>
            <span class="font-medium" :class="selectedItem.stock <= selectedItem.min_stock ? 'text-red-600' : ''">
              {{ selectedItem.stock }} {{ selectedItem.unit }}
            </span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Min. Stok</span>
            <span class="font-medium">{{ selectedItem.min_stock }} {{ selectedItem.unit }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Lokasi</span>
            <span class="font-medium">{{ selectedItem.location }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Status</span>
            <span :class="`text-xs px-2 py-1 rounded-full font-medium ${stockStatusClass(selectedItem)}`">
              {{ stockStatusLabel(selectedItem) }}
            </span>
          </div>
        </div>
        <button @click="selectedItem = null"
          class="w-full border border-gray-200 text-gray-600 py-2 rounded-xl text-sm hover:bg-gray-50 transition">
          Tutup
        </button>
      </div>
    </div>

    <!-- Toast -->
    <Transition name="toast">
      <div v-if="toast"
        class="fixed bottom-6 right-6 bg-gray-900 text-white px-4 py-3 rounded-xl shadow-lg text-sm flex items-center gap-2 z-50">
        <CheckCircle class="w-4 h-4 text-green-400" />
        {{ toast }}
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Plus, X, Search, Loader2, CheckCircle, Package, AlertTriangle, Boxes } from 'lucide-vue-next'

const loading = ref(false)
const showForm = ref(false)
const showAdjust = ref(false)
const submitting = ref(false)
const formError = ref('')
const search = ref('')
const activeFilter = ref('all')
const toast = ref('')
const selectedItem = ref<any>(null)
const adjustItem = ref<any>(null)

const items = ref<any[]>([
  { id: 1, sku: 'SKU-001', name: 'Kardus Packaging', category: 'Packaging', stock: 450, min_stock: 100, unit: 'pcs', location: 'Rak A-01' },
  { id: 2, sku: 'SKU-002', name: 'Bubble Wrap Roll', category: 'Packaging', stock: 80, min_stock: 100, unit: 'kg', location: 'Rak A-02' },
  { id: 3, sku: 'SKU-003', name: 'Pallet Kayu', category: 'Alat', stock: 50, min_stock: 20, unit: 'pcs', location: 'Rak B-01' },
  { id: 4, sku: 'SKU-004', name: 'Tali Rafia', category: 'Packaging', stock: 5, min_stock: 50, unit: 'kg', location: 'Rak A-03' },
  { id: 5, sku: 'SKU-005', name: 'Forklift Battery', category: 'Alat', stock: 10, min_stock: 5, unit: 'pcs', location: 'Rak C-01' },
])

const form = ref({
  sku: '', name: '', category: '',
  stock: 0, min_stock: 10, unit: 'pcs', location: '',
})

const adjustForm = ref({
  type: 'add', amount: 0, reason: ''
})

const filters = computed(() => [
  { value: 'all', label: 'Semua', count: items.value.length },
  { value: 'low', label: 'Stok Rendah', count: items.value.filter(i => i.stock <= i.min_stock).length },
  { value: 'ok', label: 'Stok Aman', count: items.value.filter(i => i.stock > i.min_stock).length },
])

const filteredItems = computed(() => {
  let list = items.value
  if (activeFilter.value === 'low') list = list.filter(i => i.stock <= i.min_stock)
  if (activeFilter.value === 'ok') list = list.filter(i => i.stock > i.min_stock)
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter(i =>
      i.sku.toLowerCase().includes(q) ||
      i.name.toLowerCase().includes(q) ||
      i.category.toLowerCase().includes(q)
    )
  }
  return list
})

const stats = computed(() => [
  { label: 'Total Item', value: items.value.length, icon: Package, color: 'text-blue-600', bg: 'bg-blue-100' },
  { label: 'Stok Rendah', value: items.value.filter(i => i.stock <= i.min_stock).length, icon: AlertTriangle, color: 'text-red-600', bg: 'bg-red-100' },
  { label: 'Stok Aman', value: items.value.filter(i => i.stock > i.min_stock).length, icon: CheckCircle, color: 'text-green-600', bg: 'bg-green-100' },
  { label: 'Total SKU', value: items.value.length, icon: Boxes, color: 'text-purple-600', bg: 'bg-purple-100' },
])

function stockStatusClass(item: any) {
  if (item.stock === 0) return 'bg-red-100 text-red-700'
  if (item.stock <= item.min_stock) return 'bg-yellow-100 text-yellow-700'
  return 'bg-green-100 text-green-700'
}

function stockStatusLabel(item: any) {
  if (item.stock === 0) return 'Habis'
  if (item.stock <= item.min_stock) return 'Stok Rendah'
  return 'Aman'
}

function openAdjust(item: any) {
  adjustItem.value = item
  adjustForm.value = { type: 'add', amount: 0, reason: '' }
  showAdjust.value = true
}

function submitAdjust() {
  if (!adjustForm.value.amount) return
  const item = items.value.find(i => i.id === adjustItem.value.id)
  if (!item) return
  if (adjustForm.value.type === 'add') item.stock += adjustForm.value.amount
  else if (adjustForm.value.type === 'subtract') item.stock = Math.max(0, item.stock - adjustForm.value.amount)
  else if (adjustForm.value.type === 'set') item.stock = adjustForm.value.amount
  showAdjust.value = false
  showToast(`Stok ${item.name} berhasil diperbarui`)
}

async function submitForm() {
  if (!form.value.sku || !form.value.name) {
    formError.value = 'SKU dan nama item wajib diisi'
    return
  }
  submitting.value = true
  formError.value = ''
  await new Promise(r => setTimeout(r, 500))
  items.value.unshift({ id: Date.now(), ...form.value })
  showForm.value = false
  form.value = { sku: '', name: '', category: '', stock: 0, min_stock: 10, unit: 'pcs', location: '' }
  submitting.value = false
  showToast('Item berhasil ditambahkan')
}

function showToast(msg: string) {
  toast.value = msg
  setTimeout(() => toast.value = '', 3000)
}
</script>

<style scoped>
.input-field {
  @apply w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(10px); }
</style>