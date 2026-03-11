<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-800">Gudang</h1>
      <p class="text-sm text-gray-500 mt-1">Informasi stok dan status barang di gudang</p>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 border-b border-gray-200">
      <button v-for="tab in tabs" :key="tab.value"
        @click="activeTab = tab.value"
        :class="`px-4 py-2 text-sm font-medium border-b-2 transition ${
          activeTab === tab.value
            ? 'border-blue-600 text-blue-600'
            : 'border-transparent text-gray-500 hover:text-gray-700'
        }`">
        {{ tab.label }}
      </button>
    </div>

    <!-- INBOUND TAB -->
    <div v-if="activeTab === 'inbound'" class="space-y-4">
      <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
          <p class="text-xs text-gray-500">Total Inbound</p>
          <p class="text-2xl font-bold mt-1">{{ inboundItems.length }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
          <p class="text-xs text-gray-500">Pending</p>
          <p class="text-2xl font-bold mt-1 text-yellow-600">{{ inboundItems.filter(i => i.status === 'pending').length }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
          <p class="text-xs text-gray-500">Diterima</p>
          <p class="text-2xl font-bold mt-1 text-green-600">{{ inboundItems.filter(i => i.status === 'received').length }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">No. Inbound</th>
              <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Supplier</th>
              <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Item</th>
              <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Qty</th>
              <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Tanggal</th>
              <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="item in inboundItems" :key="item.id" class="hover:bg-gray-50 transition">
              <td class="px-4 py-3 text-sm font-medium text-blue-600">{{ item.inbound_number }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ item.supplier_name }}</td>
              <td class="px-4 py-3 text-sm text-gray-600">{{ item.item_name }}</td>
              <td class="px-4 py-3 text-sm text-gray-600">{{ item.quantity }} {{ item.unit }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ formatDate(item.received_at) }}</td>
              <td class="px-4 py-3">
                <span :class="`text-xs px-2 py-1 rounded-full font-medium ${inboundStatusClass(item.status)}`">
                  {{ inboundStatusLabel(item.status) }}
                </span>
              </td>
            </tr>
            <tr v-if="inboundItems.length === 0">
              <td colspan="6" class="px-4 py-12 text-center text-gray-400 text-sm">Tidak ada data</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- INVENTORY TAB -->
    <div v-if="activeTab === 'inventory'" class="space-y-4">
      <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
          <p class="text-xs text-gray-500">Total Item</p>
          <p class="text-2xl font-bold mt-1">{{ inventoryItems.length }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
          <p class="text-xs text-gray-500">Stok Rendah</p>
          <p class="text-2xl font-bold mt-1 text-red-600">{{ inventoryItems.filter(i => i.stock <= i.min_stock).length }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
          <p class="text-xs text-gray-500">Stok Aman</p>
          <p class="text-2xl font-bold mt-1 text-green-600">{{ inventoryItems.filter(i => i.stock > i.min_stock).length }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">SKU</th>
              <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Nama Item</th>
              <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Kategori</th>
              <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Stok</th>
              <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Lokasi</th>
              <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="item in inventoryItems" :key="item.id" class="hover:bg-gray-50 transition">
              <td class="px-4 py-3 text-sm font-mono text-blue-600">{{ item.sku }}</td>
              <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ item.name }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ item.category }}</td>
              <td class="px-4 py-3 text-sm font-semibold"
                :class="item.stock <= item.min_stock ? 'text-red-600' : 'text-gray-800'">
                {{ item.stock }} {{ item.unit }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ item.location }}</td>
              <td class="px-4 py-3">
                <span :class="`text-xs px-2 py-1 rounded-full font-medium ${inventoryStatusClass(item)}`">
                  {{ inventoryStatusLabel(item) }}
                </span>
              </td>
            </tr>
            <tr v-if="inventoryItems.length === 0">
              <td colspan="6" class="px-4 py-12 text-center text-gray-400 text-sm">Tidak ada data</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- OUTBOUND TAB -->
    <div v-if="activeTab === 'outbound'" class="space-y-4">
      <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
          <p class="text-xs text-gray-500">Total Outbound</p>
          <p class="text-2xl font-bold mt-1">{{ outboundItems.length }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
          <p class="text-xs text-gray-500">Diproses</p>
          <p class="text-2xl font-bold mt-1 text-blue-600">{{ outboundItems.filter(i => i.status === 'processing').length }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
          <p class="text-xs text-gray-500">Terkirim</p>
          <p class="text-2xl font-bold mt-1 text-green-600">{{ outboundItems.filter(i => i.status === 'shipped').length }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">No. Outbound</th>
              <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Job Order</th>
              <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Tujuan</th>
              <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Item</th>
              <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Qty</th>
              <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Tanggal</th>
              <th class="text-left text-xs font-semibold text-gray-500 px-4 py-3">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="item in outboundItems" :key="item.id" class="hover:bg-gray-50 transition">
              <td class="px-4 py-3 text-sm font-medium text-blue-600">{{ item.outbound_number }}</td>
              <td class="px-4 py-3 text-sm text-gray-600">{{ item.job_order_number ?? '-' }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ item.destination }}</td>
              <td class="px-4 py-3 text-sm text-gray-600">{{ item.item_name }}</td>
              <td class="px-4 py-3 text-sm text-gray-600">{{ item.quantity }} {{ item.unit }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ formatDate(item.shipped_at) }}</td>
              <td class="px-4 py-3">
                <span :class="`text-xs px-2 py-1 rounded-full font-medium ${outboundStatusClass(item.status)}`">
                  {{ outboundStatusLabel(item.status) }}
                </span>
              </td>
            </tr>
            <tr v-if="outboundItems.length === 0">
              <td colspan="7" class="px-4 py-12 text-center text-gray-400 text-sm">Tidak ada data</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const activeTab = ref('inbound')

const tabs = [
  { value: 'inbound', label: 'Inbound' },
  { value: 'inventory', label: 'Inventory' },
  { value: 'outbound', label: 'Outbound' },
]

// Data sama seperti admin tapi read-only
const inboundItems = ref([
  { id: 1, inbound_number: 'INB-2026-00001', supplier_name: 'PT Supplier Jaya', item_name: 'Kardus Packaging', quantity: 500, unit: 'pcs', received_at: '2026-03-10', status: 'received' },
  { id: 2, inbound_number: 'INB-2026-00002', supplier_name: 'CV Maju Bersama', item_name: 'Bubble Wrap Roll', quantity: 100, unit: 'kg', received_at: '2026-03-11', status: 'pending' },
  { id: 3, inbound_number: 'INB-2026-00003', supplier_name: 'PT Logistik Prima', item_name: 'Pallet Kayu', quantity: 50, unit: 'pcs', received_at: '2026-03-11', status: 'pending' },
])

const inventoryItems = ref([
  { id: 1, sku: 'SKU-001', name: 'Kardus Packaging', category: 'Packaging', stock: 450, min_stock: 100, unit: 'pcs', location: 'Rak A-01' },
  { id: 2, sku: 'SKU-002', name: 'Bubble Wrap Roll', category: 'Packaging', stock: 80, min_stock: 100, unit: 'kg', location: 'Rak A-02' },
  { id: 3, sku: 'SKU-003', name: 'Pallet Kayu', category: 'Alat', stock: 50, min_stock: 20, unit: 'pcs', location: 'Rak B-01' },
  { id: 4, sku: 'SKU-004', name: 'Tali Rafia', category: 'Packaging', stock: 5, min_stock: 50, unit: 'kg', location: 'Rak A-03' },
  { id: 5, sku: 'SKU-005', name: 'Forklift Battery', category: 'Alat', stock: 10, min_stock: 5, unit: 'pcs', location: 'Rak C-01' },
])

const outboundItems = ref([
  { id: 1, outbound_number: 'OUT-2026-00001', job_order_number: 'JO-2026-0001', destination: 'PT Sinar Jaya Abadi', item_name: 'Kardus Packaging', quantity: 200, unit: 'pcs', shipped_at: '2026-03-10', status: 'shipped' },
  { id: 2, outbound_number: 'OUT-2026-00002', job_order_number: 'JO-2026-0002', destination: 'CV Maju Bersama', item_name: 'Pallet Kayu', quantity: 20, unit: 'pcs', shipped_at: '2026-03-11', status: 'processing' },
  { id: 3, outbound_number: 'OUT-2026-00003', job_order_number: null, destination: 'PT Logistik Prima', item_name: 'Bubble Wrap Roll', quantity: 50, unit: 'kg', shipped_at: '2026-03-12', status: 'pending' },
])

function formatDate(val: string) {
  if (!val) return '-'
  return new Date(val).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

function inboundStatusClass(status: string) {
  const map: Record<string, string> = { pending: 'bg-yellow-100 text-yellow-700', received: 'bg-green-100 text-green-700', rejected: 'bg-red-100 text-red-700' }
  return map[status] || 'bg-gray-100 text-gray-600'
}
function inboundStatusLabel(status: string) {
  const map: Record<string, string> = { pending: 'Pending', received: 'Diterima', rejected: 'Ditolak' }
  return map[status] || status
}

function inventoryStatusClass(item: any) {
  if (item.stock === 0) return 'bg-red-100 text-red-700'
  if (item.stock <= item.min_stock) return 'bg-yellow-100 text-yellow-700'
  return 'bg-green-100 text-green-700'
}
function inventoryStatusLabel(item: any) {
  if (item.stock === 0) return 'Habis'
  if (item.stock <= item.min_stock) return 'Stok Rendah'
  return 'Aman'
}

function outboundStatusClass(status: string) {
  const map: Record<string, string> = { pending: 'bg-yellow-100 text-yellow-700', processing: 'bg-blue-100 text-blue-700', shipped: 'bg-green-100 text-green-700', cancelled: 'bg-red-100 text-red-700' }
  return map[status] || 'bg-gray-100 text-gray-600'
}
function outboundStatusLabel(status: string) {
  const map: Record<string, string> = { pending: 'Pending', processing: 'Diproses', shipped: 'Terkirim', cancelled: 'Dibatalkan' }
  return map[status] || status
}
</script>