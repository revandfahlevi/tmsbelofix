<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Inbound</h1>
        <p class="text-sm text-gray-500 mt-1">Kelola penerimaan barang masuk ke gudang</p>
      </div>
      <button @click="showForm = true"
        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition">
        <Plus class="w-4 h-4" />
        Tambah Inbound
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
        <input v-model="search" placeholder="Cari inbound..."
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
            <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">No. Inbound</th>
            <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Supplier</th>
            <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Item</th>
            <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Qty</th>
            <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Tanggal</th>
            <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Status</th>
            <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="item in filteredItems" :key="item.id"
            class="hover:bg-gray-50 transition">
            <td class="px-4 py-3 text-sm font-medium text-blue-600">{{ item.inbound_number }}</td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ item.supplier_name }}</td>
            <td class="px-4 py-3 text-sm text-gray-600">{{ item.item_name }}</td>
            <td class="px-4 py-3 text-sm text-gray-600">{{ item.quantity }} {{ item.unit }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ formatDate(item.received_at) }}</td>
            <td class="px-4 py-3">
              <span :class="`text-xs px-2 py-1 rounded-full font-medium ${statusClass(item.status)}`">
                {{ statusLabel(item.status) }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="flex gap-1">
                <button v-if="item.status === 'pending'"
                  @click="handleReceive(item)"
                  class="text-xs bg-green-50 text-green-600 px-2 py-1 rounded-lg hover:bg-green-100 transition">
                  Terima
                </button>
                <button @click="selectedItem = item"
                  class="text-xs bg-gray-50 text-gray-600 px-2 py-1 rounded-lg hover:bg-gray-100 transition">
                  Detail
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="filteredItems.length === 0">
            <td colspan="7" class="px-4 py-12 text-center text-gray-400 text-sm">
              Tidak ada data inbound
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Form -->
    <div v-if="showForm" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
      @click.self="showForm = false">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold">Tambah Inbound</h3>
          <button @click="showForm = false" class="p-1.5 hover:bg-gray-100 rounded-lg">
            <X class="w-5 h-5 text-gray-500" />
          </button>
        </div>
        <div class="space-y-3">
          <div>
            <label class="text-xs font-medium text-gray-600 mb-1 block">Nama Supplier *</label>
            <input v-model="form.supplier_name" placeholder="PT Supplier ABC" class="input-field" />
          </div>
          <div>
            <label class="text-xs font-medium text-gray-600 mb-1 block">Nama Item *</label>
            <input v-model="form.item_name" placeholder="Nama barang" class="input-field" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs font-medium text-gray-600 mb-1 block">Jumlah *</label>
              <input v-model.number="form.quantity" type="number" placeholder="100" class="input-field" />
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
            <label class="text-xs font-medium text-gray-600 mb-1 block">Tanggal Terima</label>
            <input v-model="form.received_at" type="date" class="input-field" />
          </div>
          <div>
            <label class="text-xs font-medium text-gray-600 mb-1 block">Catatan</label>
            <textarea v-model="form.notes" placeholder="Catatan tambahan..." rows="2" class="input-field resize-none" />
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

    <!-- Modal Detail -->
    <div v-if="selectedItem" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
      @click.self="selectedItem = null">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold">{{ selectedItem.inbound_number }}</h3>
          <button @click="selectedItem = null" class="p-1.5 hover:bg-gray-100 rounded-lg">
            <X class="w-5 h-5 text-gray-500" />
          </button>
        </div>
        <div class="space-y-3">
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Supplier</span>
            <span class="font-medium">{{ selectedItem.supplier_name }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Item</span>
            <span class="font-medium">{{ selectedItem.item_name }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Jumlah</span>
            <span class="font-medium">{{ selectedItem.quantity }} {{ selectedItem.unit }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Tanggal</span>
            <span class="font-medium">{{ formatDate(selectedItem.received_at) }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Status</span>
            <span :class="`text-xs px-2 py-1 rounded-full font-medium ${statusClass(selectedItem.status)}`">
              {{ statusLabel(selectedItem.status) }}
            </span>
          </div>
          <div v-if="selectedItem.notes" class="flex justify-between text-sm">
            <span class="text-gray-500">Catatan</span>
            <span class="font-medium text-right max-w-48">{{ selectedItem.notes }}</span>
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
import { ref, computed, onMounted } from 'vue'
import { Plus, X, Search, Loader2, CheckCircle, Package, Clock, ArrowDownToLine, AlertCircle } from 'lucide-vue-next'

const loading = ref(false)
const showForm = ref(false)
const submitting = ref(false)
const formError = ref('')
const search = ref('')
const activeFilter = ref('all')
const toast = ref('')
const selectedItem = ref<any>(null)

const items = ref<any[]>([
  { id: 1, inbound_number: 'INB-2026-00001', supplier_name: 'PT Supplier Jaya', item_name: 'Kardus Packaging', quantity: 500, unit: 'pcs', received_at: '2026-03-10', status: 'received', notes: '' },
  { id: 2, inbound_number: 'INB-2026-00002', supplier_name: 'CV Maju Bersama', item_name: 'Bubble Wrap Roll', quantity: 100, unit: 'kg', received_at: '2026-03-11', status: 'pending', notes: 'Cek kondisi dulu' },
  { id: 3, inbound_number: 'INB-2026-00003', supplier_name: 'PT Logistik Prima', item_name: 'Pallet Kayu', quantity: 50, unit: 'pcs', received_at: '2026-03-11', status: 'pending', notes: '' },
])

const form = ref({
  supplier_name: '',
  item_name: '',
  quantity: 0,
  unit: 'pcs',
  received_at: new Date().toISOString().split('T')[0],
  notes: '',
})

const filters = computed(() => [
  { value: 'all', label: 'Semua', count: items.value.length },
  { value: 'pending', label: 'Pending', count: items.value.filter(i => i.status === 'pending').length },
  { value: 'received', label: 'Diterima', count: items.value.filter(i => i.status === 'received').length },
])

const filteredItems = computed(() => {
  let list = items.value
  if (activeFilter.value !== 'all') list = list.filter(i => i.status === activeFilter.value)
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter(i =>
      i.inbound_number.toLowerCase().includes(q) ||
      i.supplier_name.toLowerCase().includes(q) ||
      i.item_name.toLowerCase().includes(q)
    )
  }
  return list
})

const stats = computed(() => [
  { label: 'Total Inbound', value: items.value.length, icon: ArrowDownToLine, color: 'text-blue-600', bg: 'bg-blue-100' },
  { label: 'Pending', value: items.value.filter(i => i.status === 'pending').length, icon: Clock, color: 'text-yellow-600', bg: 'bg-yellow-100' },
  { label: 'Diterima', value: items.value.filter(i => i.status === 'received').length, icon: CheckCircle, color: 'text-green-600', bg: 'bg-green-100' },
  { label: 'Total Item', value: items.value.reduce((a, b) => a + b.quantity, 0).toLocaleString(), icon: Package, color: 'text-purple-600', bg: 'bg-purple-100' },
])

function statusClass(status: string) {
  const map: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-700',
    received: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-700',
  }
  return map[status] || 'bg-gray-100 text-gray-600'
}

function statusLabel(status: string) {
  const map: Record<string, string> = {
    pending: 'Pending',
    received: 'Diterima',
    rejected: 'Ditolak',
  }
  return map[status] || status
}

function formatDate(val: string) {
  if (!val) return '-'
  return new Date(val).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

function handleReceive(item: any) {
  item.status = 'received'
  showToast(`${item.inbound_number} berhasil diterima`)
}

async function submitForm() {
  if (!form.value.supplier_name || !form.value.item_name || !form.value.quantity) {
    formError.value = 'Supplier, item, dan jumlah wajib diisi'
    return
  }
  submitting.value = true
  formError.value = ''
  await new Promise(r => setTimeout(r, 500))
  const newItem = {
    id: Date.now(),
    inbound_number: `INB-2026-${String(items.value.length + 1).padStart(5, '0')}`,
    ...form.value,
    status: 'pending',
  }
  items.value.unshift(newItem)
  showForm.value = false
  form.value = { supplier_name: '', item_name: '', quantity: 0, unit: 'pcs', received_at: new Date().toISOString().split('T')[0], notes: '' }
  submitting.value = false
  showToast('Inbound berhasil ditambahkan')
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