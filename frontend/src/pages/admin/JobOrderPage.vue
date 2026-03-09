<template>
  <div class="p-6 space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-lg font-semibold text-gray-800">Job Orders</h1>
        <p class="text-sm text-gray-500">Kelola semua pesanan pengiriman</p>
      </div>
      <button @click="showCreate = true"
        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition">
        <Plus class="w-4 h-4" /> Buat Job Order
      </button>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 flex flex-wrap gap-3">
      <input v-model="search" @input="onFilter"
        placeholder="Cari job number, customer..."
        class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 flex-1 min-w-48" />
      <select v-model="filterStatus" @change="onFilter"
        class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">Semua Status</option>
        <option value="draft">Draft</option>
        <option value="pending">Pending</option>
        <option value="assigned">Assigned</option>
        <option value="in_progress">In Progress</option>
        <option value="in_transit">In Transit</option>
        <option value="delivered">Delivered</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
      </select>
      <select v-model="filterPriority" @change="onFilter"
        class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">Semua Prioritas</option>
        <option value="low">Low</option>
        <option value="normal">Normal</option>
        <option value="high">High</option>
        <option value="urgent">Urgent</option>
      </select>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
      <!-- Loading -->
      <div v-if="loading" class="flex items-center justify-center py-16">
        <Loader2 class="w-6 h-6 animate-spin text-blue-600" />
      </div>

      <!-- Empty -->
      <div v-else-if="jobOrders.length === 0" class="text-center py-16">
        <Package class="w-10 h-10 text-gray-300 mx-auto mb-3" />
        <p class="text-sm text-gray-500">Belum ada job order</p>
      </div>

      <!-- Data -->
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500">Job Number</th>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500">Customer</th>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500">Rute</th>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500">Status</th>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500">Prioritas</th>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500">Est. Biaya</th>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500">Pickup</th>
              <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="jo in jobOrders" :key="jo.id" class="hover:bg-gray-50 transition">
              <td class="px-4 py-3 font-medium text-blue-600 whitespace-nowrap">{{ jo.job_number }}</td>
              <td class="px-4 py-3">
                <p class="font-medium text-gray-800">{{ jo.customer_name }}</p>
                <p class="text-xs text-gray-400">{{ jo.customer_phone }}</p>
              </td>
              <td class="px-4 py-3">
                <p class="text-gray-700">{{ jo.origin_city }}</p>
                <p class="text-xs text-gray-400">→ {{ jo.destination_city }}</p>
              </td>
              <td class="px-4 py-3"><StatusBadge :status="jo.status" /></td>
              <td class="px-4 py-3"><PriorityBadge :priority="jo.priority" /></td>
              <td class="px-4 py-3 text-gray-700 whitespace-nowrap">
                {{ jo.estimated_cost ? formatCurrency(jo.estimated_cost) : '-' }}
              </td>
              <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">
                {{ jo.pickup_scheduled_at ? formatDate(jo.pickup_scheduled_at) : '-' }}
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-1">
                  <button @click="selected = jo"
                    class="p-1.5 hover:bg-blue-50 rounded-lg text-blue-600 transition" title="Detail">
                    <Eye class="w-4 h-4" />
                  </button>
                  <button @click="statusTarget = jo"
                    class="p-1.5 hover:bg-green-50 rounded-lg text-green-600 transition" title="Update Status">
                    <RefreshCw class="w-4 h-4" />
                  </button>
                  <button v-if="['draft','cancelled'].includes(jo.status)"
                    @click="handleDelete(jo)"
                    class="p-1.5 hover:bg-red-50 rounded-lg text-red-500 transition" title="Hapus">
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1"
        class="flex items-center justify-between px-4 py-3 border-t border-gray-100">
        <p class="text-xs text-gray-500">Total {{ pagination.total }} data</p>
        <div class="flex gap-1">
          <button v-for="p in pagination.last_page" :key="p" @click="goPage(p)"
            :class="`px-3 py-1.5 rounded-lg text-xs transition ${
              p === pagination.current_page
                ? 'bg-blue-600 text-white'
                : 'hover:bg-gray-100 text-gray-600'
            }`">{{ p }}</button>
        </div>
      </div>
    </div>

    <!-- Modal Create -->
    <div v-if="showCreate" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h3 class="font-semibold text-gray-800">Buat Job Order</h3>
          <button @click="showCreate = false" class="text-gray-400 hover:text-gray-600">
            <X class="w-5 h-5" />
          </button>
        </div>
        <div class="p-6 space-y-5">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-xs font-medium text-gray-600 block mb-1">Nama Customer *</label>
              <input v-model="form.customer_name" type="text"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="PT ABC Logistics" />
            </div>
            <div>
              <label class="text-xs font-medium text-gray-600 block mb-1">No. Telepon</label>
              <input v-model="form.customer_phone" type="text"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="08123456789" />
            </div>
            <div>
              <label class="text-xs font-medium text-gray-600 block mb-1">Alamat Asal *</label>
              <input v-model="form.origin_address" type="text"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="text-xs font-medium text-gray-600 block mb-1">Kota Asal *</label>
              <input v-model="form.origin_city" type="text"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="text-xs font-medium text-gray-600 block mb-1">Alamat Tujuan *</label>
              <input v-model="form.destination_address" type="text"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="text-xs font-medium text-gray-600 block mb-1">Kota Tujuan *</label>
              <input v-model="form.destination_city" type="text"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="text-xs font-medium text-gray-600 block mb-1">Jenis Kargo *</label>
              <input v-model="form.cargo_type" type="text"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="General Cargo" />
            </div>
            <div>
              <label class="text-xs font-medium text-gray-600 block mb-1">Berat (kg)</label>
              <input v-model.number="form.cargo_weight_kg" type="number"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="text-xs font-medium text-gray-600 block mb-1">Prioritas</label>
              <select v-model="form.priority"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="low">Low</option>
                <option value="normal">Normal</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>
            <div>
              <label class="text-xs font-medium text-gray-600 block mb-1">Estimasi Biaya</label>
              <input v-model.number="form.estimated_cost" type="number"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="text-xs font-medium text-gray-600 block mb-1">Jadwal Pickup</label>
              <input v-model="form.pickup_scheduled_at" type="datetime-local"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="text-xs font-medium text-gray-600 block mb-1">Jadwal Delivery</label>
              <input v-model="form.delivery_scheduled_at" type="datetime-local"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
          </div>
          <div v-if="createError" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3">
            <p class="text-red-600 text-sm">{{ createError }}</p>
          </div>
        </div>
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t">
          <button @click="showCreate = false"
            class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition">Batal</button>
          <button @click="handleCreate" :disabled="submitting"
            class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg transition flex items-center gap-2">
            <Loader2 v-if="submitting" class="w-4 h-4 animate-spin" />
            Simpan
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Detail -->
    <div v-if="selected" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <div>
            <h3 class="font-semibold text-gray-800">{{ selected.job_number }}</h3>
            <div class="flex gap-2 mt-1">
              <StatusBadge :status="selected.status" />
              <PriorityBadge :priority="selected.priority" />
            </div>
          </div>
          <button @click="selected = null" class="text-gray-400 hover:text-gray-600">
            <X class="w-5 h-5" />
          </button>
        </div>
        <div class="p-6 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-xs text-gray-400">Customer</p>
              <p class="text-sm font-medium">{{ selected.customer_name }}</p>
              <p class="text-xs text-gray-500">{{ selected.customer_phone }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400">Driver</p>
              <p class="text-sm font-medium">{{ selected.driver?.name ?? '-' }}</p>
            </div>
          </div>
          <div class="bg-gray-50 rounded-xl p-4">
            <div class="flex items-start gap-3">
              <div class="flex flex-col items-center gap-1 mt-0.5">
                <div class="w-2.5 h-2.5 rounded-full bg-green-500"></div>
                <div class="w-0.5 h-8 bg-gray-300"></div>
                <div class="w-2.5 h-2.5 rounded-full bg-red-500"></div>
              </div>
              <div class="space-y-3">
                <div>
                  <p class="text-xs text-gray-400">Asal</p>
                  <p class="text-sm font-medium">{{ selected.origin_city }}</p>
                  <p class="text-xs text-gray-500">{{ selected.origin_address }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-400">Tujuan</p>
                  <p class="text-sm font-medium">{{ selected.destination_city }}</p>
                  <p class="text-xs text-gray-500">{{ selected.destination_address }}</p>
                </div>
              </div>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-3">
            <div class="bg-gray-50 rounded-xl p-3 text-center">
              <p class="text-xs text-gray-400">Jenis Kargo</p>
              <p class="text-sm font-medium mt-1">{{ selected.cargo_type }}</p>
            </div>
            <div class="bg-gray-50 rounded-xl p-3 text-center">
              <p class="text-xs text-gray-400">Berat</p>
              <p class="text-sm font-medium mt-1">{{ selected.cargo_weight_kg ? selected.cargo_weight_kg + ' kg' : '-' }}</p>
            </div>
            <div class="bg-gray-50 rounded-xl p-3 text-center">
              <p class="text-xs text-gray-400">Est. Biaya</p>
              <p class="text-sm font-medium mt-1">{{ selected.estimated_cost ? formatCurrency(selected.estimated_cost) : '-' }}</p>
            </div>
          </div>
        </div>
        <div class="flex justify-end px-6 py-4 border-t">
          <button @click="selected = null"
            class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg transition">Tutup</button>
        </div>
      </div>
    </div>

    <!-- Modal Update Status -->
    <div v-if="statusTarget" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h3 class="font-semibold text-gray-800">Update Status</h3>
          <button @click="statusTarget = null" class="text-gray-400 hover:text-gray-600">
            <X class="w-5 h-5" />
          </button>
        </div>
        <div class="p-6 space-y-4">
          <div>
            <p class="text-xs text-gray-400 mb-1">Job Order</p>
            <p class="font-medium text-gray-800">{{ statusTarget.job_number }}</p>
            <StatusBadge :status="statusTarget.status" class="mt-1" />
          </div>
          <div>
            <label class="text-xs font-medium text-gray-600 block mb-1">Status Baru *</label>
            <select v-model="newStatus"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="">Pilih status</option>
              <option v-for="s in allowedStatuses" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>
          </div>
          <div>
            <label class="text-xs font-medium text-gray-600 block mb-1">Catatan</label>
            <textarea v-model="statusNotes" rows="3"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Catatan update status..." />
          </div>
        </div>
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t">
          <button @click="statusTarget = null"
            class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition">Batal</button>
          <button @click="handleUpdateStatus" :disabled="!newStatus || submitting"
            class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg transition flex items-center gap-2">
            <Loader2 v-if="submitting" class="w-4 h-4 animate-spin" />
            Update
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Plus, Eye, RefreshCw, Trash2, Loader2, Package, X } from 'lucide-vue-next'
import { useJobOrders } from '@/composables/useJobOrders'
import StatusBadge from './StatusBadge.vue'
import PriorityBadge from './PriorityBadge.vue'

const { jobOrders, loading, error, pagination, fetchJobOrders, createJobOrder, updateStatus, deleteJobOrder } = useJobOrders()

// Filter
const search = ref('')
const filterStatus = ref('')
const filterPriority = ref('')

function onFilter() {
  pagination.value.current_page = 1
  fetchJobOrders({ search: search.value, status: filterStatus.value, priority: filterPriority.value })
}

function goPage(p: number) {
  pagination.value.current_page = p
  fetchJobOrders({ search: search.value, status: filterStatus.value, priority: filterPriority.value, page: p })
}

// Detail
const selected = ref<any>(null)

// Create
const showCreate = ref(false)
const submitting = ref(false)
const createError = ref('')
const form = ref({
  customer_name: '', customer_phone: '', origin_address: '', origin_city: '',
  destination_address: '', destination_city: '', cargo_type: '', cargo_weight_kg: undefined,
  priority: 'normal', estimated_cost: undefined, pickup_scheduled_at: '', delivery_scheduled_at: ''
})

async function handleCreate() {
  if (!form.value.customer_name || !form.value.origin_address || !form.value.origin_city ||
      !form.value.destination_address || !form.value.destination_city || !form.value.cargo_type) {
    createError.value = 'Harap isi semua field yang wajib (*)'
    return
  }
  submitting.value = true
  createError.value = ''
  const ok = await createJobOrder(form.value)
  submitting.value = false
  if (ok) {
    showCreate.value = false
    form.value = {
      customer_name: '', customer_phone: '', origin_address: '', origin_city: '',
      destination_address: '', destination_city: '', cargo_type: '', cargo_weight_kg: undefined,
      priority: 'normal', estimated_cost: undefined, pickup_scheduled_at: '', delivery_scheduled_at: ''
    }
    fetchJobOrders()
  } else {
    createError.value = error.value
  }
}

// Update Status
const statusTarget = ref<any>(null)
const newStatus = ref('')
const statusNotes = ref('')

const STATUS_TRANSITIONS: Record<string, { value: string; label: string }[]> = {
  draft:       [{ value: 'pending', label: 'Pending' }, { value: 'cancelled', label: 'Cancelled' }],
  pending:     [{ value: 'assigned', label: 'Assigned' }, { value: 'cancelled', label: 'Cancelled' }],
  assigned:    [{ value: 'in_progress', label: 'In Progress' }, { value: 'cancelled', label: 'Cancelled' }],
  in_progress: [{ value: 'picked_up', label: 'Picked Up' }, { value: 'failed', label: 'Failed' }, { value: 'cancelled', label: 'Cancelled' }],
  picked_up:   [{ value: 'in_transit', label: 'In Transit' }, { value: 'failed', label: 'Failed' }],
  in_transit:  [{ value: 'delivered', label: 'Delivered' }, { value: 'failed', label: 'Failed' }],
  delivered:   [{ value: 'completed', label: 'Completed' }],
  failed:      [{ value: 'pending', label: 'Pending (Retry)' }],
}

const allowedStatuses = computed(() => STATUS_TRANSITIONS[statusTarget.value?.status] ?? [])

async function handleUpdateStatus() {
  if (!newStatus.value || !statusTarget.value) return
  submitting.value = true
  const ok = await updateStatus(statusTarget.value.id, newStatus.value, statusNotes.value)
  submitting.value = false
  if (ok) {
    statusTarget.value = null
    newStatus.value = ''
    statusNotes.value = ''
  }
}

// Delete
async function handleDelete(jo: any) {
  if (!confirm(`Hapus job order ${jo.job_number}?`)) return
  await deleteJobOrder(jo.id)
}

// Format
function formatCurrency(val: number) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val)
}
function formatDate(val: string) {
  return new Date(val).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

onMounted(() => fetchJobOrders())
</script>