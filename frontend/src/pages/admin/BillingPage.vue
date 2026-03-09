<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold">Billing & Invoice</h1>
        <p class="text-sm text-gray-500 mt-1">Kelola tagihan dan pembayaran</p>
      </div>
      <button @click="showForm = true"
        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
        <Plus class="w-4 h-4" /> Buat Invoice
      </button>
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

    <!-- Filter -->
    <div class="flex gap-2 flex-wrap">
      <button v-for="s in statusFilters" :key="s.value"
        @click="filterStatus = s.value"
        :class="`px-3 py-1.5 rounded-full text-xs font-medium transition ${
          filterStatus === s.value
            ? 'bg-blue-600 text-white'
            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
        }`">
        {{ s.label }}
      </button>
    </div>

    <!-- Invoice Table -->
    <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">No. Invoice</th>
            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Customer</th>
            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Total</th>
            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Status</th>
            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Jatuh Tempo</th>
            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <tr v-for="inv in filteredInvoices" :key="inv.id"
            class="hover:bg-gray-50 transition">
            <td class="px-4 py-3 font-medium text-blue-600">{{ inv.invoice_number }}</td>
            <td class="px-4 py-3">{{ inv.customer_name }}</td>
            <td class="px-4 py-3 font-medium">Rp {{ inv.total.toLocaleString('id-ID') }}</td>
            <td class="px-4 py-3">
              <span :class="`text-xs px-2 py-0.5 rounded-full font-medium ${statusClass(inv.status)}`">
                {{ statusLabel(inv.status) }}
              </span>
            </td>
            <td class="px-4 py-3 text-gray-500">{{ inv.due_date }}</td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <button @click="markPaid(inv.id)"
                  v-if="inv.status !== 'paid'"
                  class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded hover:bg-green-200 transition">
                  Tandai Lunas
                </button>
                <button class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded hover:bg-gray-200 transition">
                  Kirim
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div v-if="showForm"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-bold">Buat Invoice Baru</h2>
          <button @click="showForm = false">
            <X class="w-5 h-5 text-gray-400" />
          </button>
        </div>
        <div class="space-y-3">
          <div>
            <label class="text-xs font-medium text-gray-600">Nama Customer</label>
            <input v-model="form.customer_name" class="input-field" placeholder="PT. Maju Jaya" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs font-medium text-gray-600">Subtotal (Rp)</label>
              <input v-model="form.amount" type="number" class="input-field" placeholder="5000000" />
            </div>
            <div>
              <label class="text-xs font-medium text-gray-600">Pajak (Rp)</label>
              <input v-model="form.tax" type="number" class="input-field" placeholder="550000" />
            </div>
          </div>
          <div>
            <label class="text-xs font-medium text-gray-600">Jatuh Tempo</label>
            <input v-model="form.due_date" type="date" class="input-field" />
          </div>
        </div>
        <div class="flex gap-3 pt-2">
          <button @click="showForm = false"
            class="flex-1 border border-gray-300 text-gray-600 py-2 rounded-lg hover:bg-gray-50 transition">
            Batal
          </button>
          <button @click="submitForm"
            class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
            Simpan
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Plus, X } from 'lucide-vue-next'
import { MOCK_INVOICES } from '@/lib/mockData'

const filterStatus = ref('all')
const showForm = ref(false)
const invoices = ref([...MOCK_INVOICES])

const form = ref({
  customer_name: '',
  amount: 0,
  tax: 0,
  due_date: '',
})

const statusFilters = [
  { value: 'all', label: 'Semua' },
  { value: 'draft', label: 'Draft' },
  { value: 'sent', label: 'Terkirim' },
  { value: 'paid', label: 'Lunas' },
  { value: 'overdue', label: 'Jatuh Tempo' },
]

const stats = computed(() => [
  {
    label: 'Total Invoice',
    value: invoices.value.length,
    sub: 'Semua waktu',
    color: 'text-gray-400'
  },
  {
    label: 'Lunas',
    value: invoices.value.filter(i => i.status === 'paid').length,
    sub: 'Sudah dibayar',
    color: 'text-green-600'
  },
  {
    label: 'Menunggu',
    value: invoices.value.filter(i => i.status === 'sent').length,
    sub: 'Belum dibayar',
    color: 'text-yellow-600'
  },
  {
    label: 'Jatuh Tempo',
    value: invoices.value.filter(i => i.status === 'overdue').length,
    sub: 'Perlu tindakan',
    color: 'text-red-600'
  },
])

const filteredInvoices = computed(() => {
  if (filterStatus.value === 'all') return invoices.value
  return invoices.value.filter(i => i.status === filterStatus.value)
})

function markPaid(id: string) {
  const inv = invoices.value.find(i => i.id === id)
  if (inv) inv.status = 'paid'
}

function submitForm() {
  const newInv = {
    id: 'INV-' + Date.now(),
    invoice_number: 'INV-' + String(invoices.value.length + 1).padStart(4, '0'),
    job_order_id: '',
    customer_name: form.value.customer_name,
    amount: form.value.amount,
    tax: form.value.tax,
    total: form.value.amount + form.value.tax,
    status: 'draft' as const,
    due_date: form.value.due_date,
    issued_at: new Date().toISOString(),
    items: [],
  }
  invoices.value.unshift(newInv)
  showForm.value = false
}

function statusLabel(status: string) {
  const map: Record<string, string> = {
    draft: 'Draft', sent: 'Terkirim',
    paid: 'Lunas', overdue: 'Jatuh Tempo',
  }
  return map[status] || status
}

function statusClass(status: string) {
  const map: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-600',
    sent: 'bg-blue-100 text-blue-700',
    paid: 'bg-green-100 text-green-700',
    overdue: 'bg-red-100 text-red-700',
  }
  return map[status] || 'bg-gray-100 text-gray-600'
}
</script>

<style scoped>
.input-field {
  @apply w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500;
}
</style>