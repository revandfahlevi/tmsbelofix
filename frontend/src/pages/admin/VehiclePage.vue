<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Master Kendaraan</h1>
        <p class="text-sm text-gray-500 mt-1">Kelola armada kendaraan pengiriman</p>
      </div>
      <button @click="openForm()"
        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition">
        <Plus class="w-4 h-4" />
        Tambah Kendaraan
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

    <!-- Filter -->
    <div class="flex flex-wrap gap-3 items-center">
      <div class="flex-1 min-w-48 relative">
        <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
        <input v-model="search" placeholder="Cari plat, merk, tipe..."
          class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>
      <select v-model="filterStatus"
        class="px-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none">
        <option value="">Semua Status</option>
        <option value="available">Tersedia</option>
        <option value="on_trip">Sedang Jalan</option>
        <option value="maintenance">Maintenance</option>
        <option value="inactive">Nonaktif</option>
      </select>
      <select v-model="filterType"
        class="px-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none">
        <option value="">Semua Tipe</option>
        <option v-for="t in vehicleTypes" :key="t" :value="t">{{ t }}</option>
      </select>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="i in 6" :key="i" class="h-48 bg-gray-100 animate-pulse rounded-2xl" />
    </div>

    <!-- Empty -->
    <div v-else-if="filteredVehicles.length === 0" class="text-center py-16 text-gray-400">
      <Truck class="w-12 h-12 mx-auto mb-3 opacity-20" />
      <p class="text-sm">Tidak ada kendaraan ditemukan</p>
    </div>

    <!-- Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="v in filteredVehicles" :key="v.id"
        class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition">

        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
              <Truck class="w-5 h-5 text-blue-600" />
            </div>
            <div>
              <p class="font-bold text-gray-800">{{ v.plate_number }}</p>
              <p class="text-xs text-gray-500">{{ v.brand ?? '-' }} · {{ v.vehicle_type }}</p>
            </div>
          </div>
          <span :class="`text-xs px-2 py-1 rounded-full font-medium ${statusBadge(v.status)}`">
            {{ statusLabel(v.status) }}
          </span>
        </div>

        <div class="grid grid-cols-2 gap-2 mb-4">
          <div class="bg-gray-50 rounded-xl p-2.5">
            <p class="text-xs text-gray-400">Max Berat</p>
            <p class="text-sm font-semibold text-gray-800">
              {{ v.max_weight_kg ? Number(v.max_weight_kg).toLocaleString() + ' kg' : '-' }}
            </p>
          </div>
          <div class="bg-gray-50 rounded-xl p-2.5">
            <p class="text-xs text-gray-400">Max Volume</p>
            <p class="text-sm font-semibold text-gray-800">
              {{ v.max_volume_m3 ? v.max_volume_m3 + ' m³' : '-' }}
            </p>
          </div>
          <div class="bg-gray-50 rounded-xl p-2.5">
            <p class="text-xs text-gray-400">STNK Exp</p>
            <p class="text-sm font-semibold"
              :class="isExpiringSoon(v.stnk_expired_at) ? 'text-red-600' : 'text-gray-800'">
              {{ formatDate(v.stnk_expired_at) }}
            </p>
          </div>
          <div class="bg-gray-50 rounded-xl p-2.5">
            <p class="text-xs text-gray-400">KIR Exp</p>
            <p class="text-sm font-semibold"
              :class="isExpiringSoon(v.kir_expired_at) ? 'text-red-600' : 'text-gray-800'">
              {{ formatDate(v.kir_expired_at) }}
            </p>
          </div>
        </div>

        <p v-if="v.carrier?.name" class="text-xs text-gray-400 mb-3 flex items-center gap-1">
          <Building class="w-3 h-3" /> {{ v.carrier.name }}
        </p>

        <div v-if="isExpiringSoon(v.stnk_expired_at) || isExpiringSoon(v.kir_expired_at)"
          class="bg-red-50 border border-red-100 rounded-xl p-2 mb-3 flex items-center gap-2">
          <AlertTriangle class="w-3.5 h-3.5 text-red-500 flex-shrink-0" />
          <p class="text-xs text-red-600">Dokumen akan segera kadaluarsa</p>
        </div>

        <div class="flex gap-2">
          <button @click="openForm(v)"
            class="flex-1 flex items-center justify-center gap-1 border border-gray-200 text-gray-600 py-1.5 rounded-xl text-xs font-medium hover:bg-gray-50 transition">
            <Pencil class="w-3.5 h-3.5" /> Edit
          </button>
          <select @change="handleStatusChange(v, ($event.target as HTMLSelectElement).value)"
            :value="v.status"
            class="flex-1 border border-gray-200 text-gray-600 py-1.5 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
            <option value="available">Tersedia</option>
            <option value="on_trip">Sedang Jalan</option>
            <option value="maintenance">Maintenance</option>
            <option value="inactive">Nonaktif</option>
          </select>
          <button @click="handleDelete(v.id)"
            class="p-1.5 hover:bg-red-50 text-red-400 rounded-xl transition">
            <Trash2 class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Form -->
    <div v-if="showForm" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
      @click.self="closeForm">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-800">
            {{ editingVehicle ? 'Edit Kendaraan' : 'Tambah Kendaraan' }}
          </h3>
          <button @click="closeForm" class="p-1.5 hover:bg-gray-100 rounded-lg">
            <X class="w-5 h-5 text-gray-500" />
          </button>
        </div>

        <div class="space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs font-medium text-gray-600 mb-1 block">Nomor Plat *</label>
              <input v-model="form.plate_number" placeholder="B 1234 ABC" class="input-field" />
            </div>
            <div>
              <label class="text-xs font-medium text-gray-600 mb-1 block">Tipe Kendaraan *</label>
              <select v-model="form.vehicle_type" class="input-field">
                <option value="">Pilih Tipe</option>
                <option v-for="t in vehicleTypes" :key="t" :value="t">{{ t }}</option>
              </select>
            </div>
          </div>

          <div>
            <label class="text-xs font-medium text-gray-600 mb-1 block">Merk / Model</label>
            <input v-model="form.brand" placeholder="Mitsubishi Canter / Isuzu ELF" class="input-field" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs font-medium text-gray-600 mb-1 block">Max Berat (kg)</label>
              <input v-model.number="form.max_weight_kg" type="number" placeholder="5000" class="input-field" />
            </div>
            <div>
              <label class="text-xs font-medium text-gray-600 mb-1 block">Max Volume (m³)</label>
              <input v-model.number="form.max_volume_m3" type="number" placeholder="20" class="input-field" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs font-medium text-gray-600 mb-1 block">STNK Expired</label>
              <input v-model="form.stnk_expired_at" type="date" class="input-field" />
            </div>
            <div>
              <label class="text-xs font-medium text-gray-600 mb-1 block">KIR Expired</label>
              <input v-model="form.kir_expired_at" type="date" class="input-field" />
            </div>
          </div>

          <div>
            <label class="text-xs font-medium text-gray-600 mb-1 block">Status</label>
            <select v-model="form.status" class="input-field">
              <option value="available">Tersedia</option>
              <option value="on_trip">Sedang Jalan</option>
              <option value="maintenance">Maintenance</option>
              <option value="inactive">Nonaktif</option>
            </select>
          </div>
        </div>

        <div v-if="formError" class="bg-red-50 border border-red-200 rounded-xl px-4 py-3">
          <p class="text-red-600 text-sm">{{ formError }}</p>
        </div>

        <div class="flex gap-2 pt-2">
          <button @click="closeForm"
            class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-xl text-sm hover:bg-gray-50 transition">
            Batal
          </button>
          <button @click="submitForm" :disabled="submitting"
            class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white py-2 rounded-xl text-sm font-medium transition flex items-center justify-center gap-2">
            <Loader2 v-if="submitting" class="w-4 h-4 animate-spin" />
            {{ editingVehicle ? 'Simpan' : 'Tambah' }}
          </button>
        </div>
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
import { ref, computed, reactive, onMounted } from 'vue'
import {
  Plus, X, Truck, Search, Pencil, Trash2,
  Loader2, CheckCircle, Building, AlertTriangle
} from 'lucide-vue-next'
import api from '@/lib/axios'

const vehicles       = ref<any[]>([])
const carriers       = ref<any[]>([])
const loading        = ref(false)
const submitting     = ref(false)
const showForm       = ref(false)
const editingVehicle = ref<any>(null)
const formError      = ref('')
const toast          = ref('')
const search         = ref('')
const filterStatus   = ref('')
const filterType     = ref('')

const vehicleTypes = [
  'Pickup', 'Box Kecil', 'Box Sedang', 'Box Besar',
  'Truk Engkel', 'Truk Tronton', 'Trailer', 'Kontainer',
]

const form = reactive({
  carrier_id:      '' as any,
  plate_number:    '',
  vehicle_type:    '',
  brand:           '',
  max_weight_kg:   null as number | null,
  max_volume_m3:   null as number | null,
  stnk_expired_at: '',
  kir_expired_at:  '',
  status:          'available',
})

const filteredVehicles = computed(() => {
  let list = vehicles.value
  if (filterStatus.value) list = list.filter(v => v.status === filterStatus.value)
  if (filterType.value)   list = list.filter(v => v.vehicle_type === filterType.value)
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter(v =>
      v.plate_number?.toLowerCase().includes(q) ||
      v.brand?.toLowerCase().includes(q) ||
      v.vehicle_type?.toLowerCase().includes(q)
    )
  }
  return list
})

const stats = computed(() => [
  {
    label: 'Total Kendaraan', value: vehicles.value.length,
    icon: Truck, color: 'text-blue-600', bg: 'bg-blue-100'
  },
  {
    label: 'Tersedia', value: vehicles.value.filter(v => v.status === 'available').length,
    icon: CheckCircle, color: 'text-green-600', bg: 'bg-green-100'
  },
  {
    label: 'Sedang Jalan', value: vehicles.value.filter(v => v.status === 'on_trip').length,
    icon: Truck, color: 'text-blue-600', bg: 'bg-blue-100'
  },
  {
    label: 'Maintenance', value: vehicles.value.filter(v => v.status === 'maintenance').length,
    icon: AlertTriangle, color: 'text-yellow-600', bg: 'bg-yellow-100'
  },
])

async function fetchVehicles() {
  loading.value = true
  try {
    const res = await api.get('/admin/carriers', { params: { per_page: 100 } })
    const raw = typeof res.data === 'string'
      ? JSON.parse(res.data.replace(/^=/, ''))
      : res.data
    const carrierList = raw.data?.data ?? raw.data ?? []
    carriers.value = carrierList

    const allVehicles: any[] = []
    carrierList.forEach((carrier: any) => {
      if (carrier.vehicles?.length) {
        carrier.vehicles.forEach((v: any) => {
          allVehicles.push({ ...v, carrier })
        })
      }
    })
    vehicles.value = allVehicles
  } catch {
    vehicles.value = []
  } finally {
    loading.value = false
  }
}

async function submitForm() {
  if (!form.plate_number || !form.vehicle_type) {
    formError.value = 'Nomor plat dan tipe kendaraan wajib diisi'
    return
  }
  if (!form.carrier_id) {
    if (carriers.value.length === 0) {
      formError.value = 'Belum ada carrier terdaftar.'
      return
    }
    form.carrier_id = carriers.value[0].id
  }

  submitting.value = true
  formError.value  = ''

  const payload = {
    plate_number:    form.plate_number,
    vehicle_type:    form.vehicle_type,
    brand:           form.brand || null,
    max_weight_kg:   form.max_weight_kg || null,
    max_volume_m3:   form.max_volume_m3 || null,
    stnk_expired_at: form.stnk_expired_at || null,
    kir_expired_at:  form.kir_expired_at  || null,
    status:          form.status,
  }

  try {
    if (editingVehicle.value) {
      const res = await api.put(
        `/admin/carriers/${form.carrier_id}/vehicles/${editingVehicle.value.id}`,
        payload
      )
      const raw = typeof res.data === 'string'
        ? JSON.parse(res.data.replace(/^=/, ''))
        : res.data
      const updated = raw.data ?? raw
      const idx = vehicles.value.findIndex(v => v.id === editingVehicle.value.id)
      if (idx !== -1) vehicles.value[idx] = { ...vehicles.value[idx], ...updated }
      showToast('Kendaraan berhasil diperbarui')
    } else {
      const res = await api.post(`/admin/carriers/${form.carrier_id}/vehicles`, payload)
      const raw = typeof res.data === 'string'
        ? JSON.parse(res.data.replace(/^=/, ''))
        : res.data
      const carrier = carriers.value.find(c => c.id == form.carrier_id)
      vehicles.value.unshift({ ...(raw.data ?? raw), carrier })
      showToast('Kendaraan berhasil ditambahkan')
    }
    closeForm()
  } catch (e: any) {
    const raw = e.response?.data
    const msg = typeof raw === 'string'
      ? JSON.parse(raw.replace(/^=/, '')).message
      : raw?.message
    formError.value = msg || 'Gagal menyimpan kendaraan'
  } finally {
    submitting.value = false
  }
}

async function handleStatusChange(vehicle: any, newStatus: string) {
  try {
    await api.put(`/admin/carriers/${vehicle.carrier_id}/vehicles/${vehicle.id}`, {
      plate_number: vehicle.plate_number,
      vehicle_type: vehicle.vehicle_type,
      brand:        vehicle.brand || null,
      status:       newStatus,
    })
    const idx = vehicles.value.findIndex(v => v.id === vehicle.id)
    if (idx !== -1) vehicles.value[idx].status = newStatus
    showToast('Status kendaraan diperbarui')
  } catch {
    showToast('Gagal update status')
  }
}

async function handleDelete(id: any) {
  if (!confirm('Hapus kendaraan ini?')) return
  try {
    const vehicle = vehicles.value.find(v => v.id === id)
    if (vehicle) {
      await api.delete(`/admin/carriers/${vehicle.carrier_id}/vehicles/${id}`)
    }
  } catch {}
  vehicles.value = vehicles.value.filter(v => v.id !== id)
  showToast('Kendaraan dihapus')
}

function openForm(vehicle?: any) {
  editingVehicle.value = vehicle ?? null
  if (vehicle) {
    form.carrier_id      = vehicle.carrier_id
    form.plate_number    = vehicle.plate_number ?? ''
    form.vehicle_type    = vehicle.vehicle_type ?? ''
    form.brand           = vehicle.brand ?? ''
    form.max_weight_kg   = vehicle.max_weight_kg ?? null
    form.max_volume_m3   = vehicle.max_volume_m3 ?? null
    form.stnk_expired_at = vehicle.stnk_expired_at?.split('T')[0] ?? ''
    form.kir_expired_at  = vehicle.kir_expired_at?.split('T')[0]  ?? ''
    form.status          = vehicle.status ?? 'available'
  } else {
    Object.assign(form, {
      carrier_id: '', plate_number: '', vehicle_type: '', brand: '',
      max_weight_kg: null, max_volume_m3: null,
      stnk_expired_at: '', kir_expired_at: '', status: 'available',
    })
  }
  formError.value = ''
  showForm.value  = true
}

function closeForm() {
  showForm.value       = false
  editingVehicle.value = null
  formError.value      = ''
}

function isExpiringSoon(dateStr: string) {
  if (!dateStr) return false
  const exp  = new Date(dateStr)
  const soon = new Date()
  soon.setDate(soon.getDate() + 30)
  return exp <= soon
}

function formatDate(val: string) {
  if (!val) return '-'
  return new Date(val).toLocaleDateString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric'
  })
}

function statusBadge(status: string) {
  const map: Record<string, string> = {
    available:   'bg-green-100 text-green-700',
    on_trip:     'bg-blue-100 text-blue-700',
    maintenance: 'bg-yellow-100 text-yellow-700',
    inactive:    'bg-gray-100 text-gray-600',
  }
  return map[status] || 'bg-gray-100 text-gray-600'
}

function statusLabel(status: string) {
  const map: Record<string, string> = {
    available:   'Tersedia',
    on_trip:     'Sedang Jalan',
    maintenance: 'Maintenance',
    inactive:    'Nonaktif',
  }
  return map[status] || status
}

function showToast(msg: string) {
  toast.value = msg
  setTimeout(() => toast.value = '', 3000)
}

onMounted(() => fetchVehicles())
</script>

<style scoped>
.input-field {
  @apply w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(10px); }
</style>