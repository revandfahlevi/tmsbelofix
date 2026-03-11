<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-2xl font-bold">Proof of Delivery (POD)</h1>
      <p class="text-sm text-gray-500 mt-1">Upload foto bukti pengiriman paket ke customer</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Form Submit POD -->
      <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm space-y-4">
        <h2 class="font-semibold">Capture POD</h2>

        <!-- Pilih Job Order -->
        <div>
          <label class="text-xs font-medium text-gray-600">Job Order *</label>
          <select v-model="selectedJobId" class="input-field">
            <option value="">-- Pilih Job Order --</option>
            <option v-for="job in activeJobs" :key="job.id" :value="job.id">
              {{ job.job_number }} — {{ job.customer_name }}
              ({{ job.origin_city }} → {{ job.destination_city }})
            </option>
          </select>
          <p v-if="activeJobs.length === 0" class="text-xs text-orange-500 mt-1">
            Tidak ada job order aktif
          </p>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs font-medium text-gray-600">Nama Penerima *</label>
            <input v-model="form.recipient_name" class="input-field" placeholder="Nama lengkap" />
          </div>
          <div>
            <label class="text-xs font-medium text-gray-600">No. HP Penerima</label>
            <input v-model="form.recipient_phone" class="input-field" placeholder="08xxxxxxxxxx" />
          </div>
        </div>

        <div>
          <label class="text-xs font-medium text-gray-600">Hubungan dengan Penerima</label>
          <select v-model="form.recipient_relationship" class="input-field">
            <option value="">-- Pilih --</option>
            <option value="diri sendiri">Diri Sendiri</option>
            <option value="keluarga">Keluarga</option>
            <option value="sekuriti">Sekuriti / Satpam</option>
            <option value="rekan kerja">Rekan Kerja</option>
            <option value="lainnya">Lainnya</option>
          </select>
        </div>

        <div>
          <label class="text-xs font-medium text-gray-600">Catatan</label>
          <textarea v-model="form.notes" class="input-field resize-none" rows="2"
            placeholder="Kondisi barang, catatan tambahan..." />
        </div>

        <!-- Upload Foto -->
        <div>
          <label class="text-xs font-medium text-gray-600">Foto Bukti (maks 5 foto)</label>
          <div class="mt-1">
            <!-- Preview foto -->
            <div v-if="photoPreviews.length > 0" class="grid grid-cols-3 gap-2 mb-2">
              <div v-for="(preview, i) in photoPreviews" :key="i" class="relative">
                <img :src="preview" class="w-full h-24 object-cover rounded-xl border border-gray-200" />
                <button @click="removePhoto(i)"
                  class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-0.5 hover:bg-red-600">
                  <X class="w-3 h-3" />
                </button>
              </div>
              <div v-if="photoPreviews.length < 5"
                @click="triggerFileInput"
                class="h-24 border-2 border-dashed border-gray-300 rounded-xl flex items-center justify-center cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition">
                <Plus class="w-6 h-6 text-gray-400" />
              </div>
            </div>

            <!-- Upload area awal -->
            <div v-else
              @click="triggerFileInput"
              @dragover.prevent
              @drop.prevent="handleDrop"
              class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition">
              <Camera class="w-8 h-8 mx-auto mb-2 text-gray-400" />
              <p class="text-sm font-medium text-gray-600">Klik atau drag foto ke sini</p>
              <p class="text-xs text-gray-400 mt-1">JPG, PNG, max 5MB per foto</p>
            </div>

            <input ref="fileInputRef" type="file" accept="image/*"
              capture="environment" multiple class="hidden" @change="handleFileChange" />
          </div>
        </div>

        <!-- Error -->
        <div v-if="formError" class="bg-red-50 border border-red-200 rounded-lg p-3">
          <p class="text-sm text-red-600">{{ formError }}</p>
        </div>

        <!-- Success -->
        <div v-if="success" class="bg-green-50 border border-green-200 rounded-lg p-3">
          <p class="text-sm text-green-700 font-medium">✅ POD berhasil disubmit!</p>
        </div>

        <button @click="handleSubmit" :disabled="submitting || !selectedJobId || !form.recipient_name"
          class="w-full bg-blue-600 text-white py-2.5 rounded-xl font-medium hover:bg-blue-700 transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2">
          <Loader2 v-if="submitting" class="w-4 h-4 animate-spin" />
          {{ submitting ? 'Mengupload...' : 'Simpan POD' }}
        </button>
      </div>

      <!-- Riwayat POD Driver -->
      <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
        <h2 class="font-semibold mb-4">Riwayat POD Saya</h2>

        <div v-if="loadingHistory" class="space-y-3">
          <div v-for="i in 3" :key="i" class="h-28 bg-gray-100 animate-pulse rounded-xl" />
        </div>

        <div v-else-if="myPods.length === 0" class="text-center py-8 text-gray-400">
          <FileImage class="w-10 h-10 mx-auto mb-2 opacity-30" />
          <p class="text-sm">Belum ada POD tersimpan</p>
        </div>

        <div v-else class="space-y-3 max-h-[600px] overflow-y-auto pr-1">
          <div v-for="pod in myPods" :key="pod.id"
            class="border border-gray-100 rounded-xl overflow-hidden hover:shadow-sm transition">
            <div v-if="pod.photos?.length" class="relative">
              <img :src="`/storage/${pod.photos[0]}`" alt="Bukti"
                class="w-full h-32 object-cover" />
              <span :class="`absolute top-2 right-2 text-xs px-2 py-0.5 rounded-full font-medium ${statusBadge(pod.status)}`">
                {{ statusLabel(pod.status) }}
              </span>
            </div>
            <div class="p-3">
              <div class="flex items-start justify-between mb-1">
                <p class="font-medium text-sm text-blue-600">{{ pod.pod_number }}</p>
                <span v-if="!pod.photos?.length"
                  :class="`text-xs px-2 py-0.5 rounded-full font-medium ${statusBadge(pod.status)}`">
                  {{ statusLabel(pod.status) }}
                </span>
              </div>
              <p class="text-xs text-gray-500">{{ pod.job_order?.job_number ?? '-' }}</p>
              <p class="text-sm text-gray-700 font-medium mt-0.5">{{ pod.recipient_name }}</p>
              <div class="text-xs text-gray-400 mt-1 space-y-0.5">
                <p v-if="pod.notes" class="truncate">📝 {{ pod.notes }}</p>
                <p v-if="pod.rejection_reason" class="text-red-500">❌ {{ pod.rejection_reason }}</p>
                <p>{{ formatDate(pod.delivered_at ?? pod.created_at) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onMounted } from 'vue'
import { FileImage, Camera, X, Plus, Loader2 } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useJobOrders } from '@/composables/useJobOrders'
import api from '@/lib/axios'

const auth = useAuthStore()
const { jobOrders, fetchJobOrders } = useJobOrders()

const fileInputRef    = ref<HTMLInputElement | null>(null)
const photoPreviews   = ref<string[]>([])
const photoFiles      = ref<File[]>([])
const success         = ref(false)
const submitting      = ref(false)
const loadingHistory  = ref(false)
const formError       = ref('')
const selectedJobId   = ref<any>('')
const myPods          = ref<any[]>([])

const form = reactive({
  recipient_name:         '',
  recipient_phone:        '',
  recipient_relationship: '',
  notes:                  '',
})

// ── Job orders aktif milik driver ─────────────────────────
const activeJobs = computed(() =>
  jobOrders.value.filter(j =>
    ['in_transit', 'picked_up', 'in_progress', 'assigned'].includes(j.status) &&
    (j.assigned_driver_id === auth.user?.id || j.driver?.id === auth.user?.id)
  )
)

// ── Fetch riwayat POD driver ──────────────────────────────
async function fetchMyPods() {
  loadingHistory.value = true
  try {
    const res = await api.get('/pods', { params: { per_page: 50 } })
    const raw = typeof res.data === 'string'
      ? JSON.parse(res.data.replace(/^=/, ''))
      : res.data
    myPods.value = raw.data?.data ?? raw.data ?? []
  } catch {
    myPods.value = []
  } finally {
    loadingHistory.value = false
  }
}

// ── Submit POD ────────────────────────────────────────────
async function handleSubmit() {
  if (!selectedJobId.value || !form.recipient_name) {
    formError.value = 'Job order dan nama penerima wajib diisi'
    return
  }

  submitting.value = true
  formError.value  = ''

  try {
    const formData = new FormData()
    formData.append('job_order_id',           selectedJobId.value)
    formData.append('recipient_name',         form.recipient_name)
    formData.append('recipient_phone',        form.recipient_phone)
    formData.append('recipient_relationship', form.recipient_relationship)
    formData.append('notes',                  form.notes)

    // Tambah foto
    photoFiles.value.forEach((file) => {
      formData.append('photos[]', file)
    })

    // Ambil koordinat GPS jika ada
    if (navigator.geolocation) {
      await new Promise<void>((resolve) => {
        navigator.geolocation.getCurrentPosition(
          (pos) => {
            formData.append('delivery_lat', String(pos.coords.latitude))
            formData.append('delivery_lng', String(pos.coords.longitude))
            resolve()
          },
          () => resolve(), // lanjut meski GPS gagal
          { timeout: 5000 }
        )
      })
    }

    const res = await api.post('/pods', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    const raw = typeof res.data === 'string'
      ? JSON.parse(res.data.replace(/^=/, ''))
      : res.data

    // Tambah ke list riwayat
    myPods.value.unshift(raw.data ?? raw)

    // Reset form
    success.value        = true
    selectedJobId.value  = ''
    photoPreviews.value  = []
    photoFiles.value     = []
    form.recipient_name         = ''
    form.recipient_phone        = ''
    form.recipient_relationship = ''
    form.notes                  = ''
    setTimeout(() => success.value = false, 3000)

  } catch (e: any) {
    const raw = e.response?.data
    const msg = typeof raw === 'string'
      ? JSON.parse(raw.replace(/^=/, '')).message
      : raw?.message
    formError.value = msg || 'Gagal submit POD'
  } finally {
    submitting.value = false
  }
}

// ── File handling ─────────────────────────────────────────
function triggerFileInput() { fileInputRef.value?.click() }

function handleFileChange(e: Event) {
  const files = Array.from((e.target as HTMLInputElement).files ?? [])
  addFiles(files)
}

function handleDrop(e: DragEvent) {
  const files = Array.from(e.dataTransfer?.files ?? []).filter(f => f.type.startsWith('image/'))
  addFiles(files)
}

function addFiles(files: File[]) {
  const remaining = 5 - photoPreviews.value.length
  const toAdd = files.slice(0, remaining)
  toAdd.forEach(file => {
    photoFiles.value.push(file)
    const reader = new FileReader()
    reader.onload = (e) => photoPreviews.value.push(e.target?.result as string)
    reader.readAsDataURL(file)
  })
}

function removePhoto(i: number) {
  photoPreviews.value.splice(i, 1)
  photoFiles.value.splice(i, 1)
}

// ── Helpers ───────────────────────────────────────────────
function statusBadge(status: string) {
  const map: Record<string, string> = {
    submitted: 'bg-yellow-100 text-yellow-700',
    verified:  'bg-green-500 text-white',
    rejected:  'bg-red-100 text-red-700',
    pending:   'bg-gray-100 text-gray-600',
  }
  return map[status] || 'bg-gray-100 text-gray-600'
}

function statusLabel(status: string) {
  const map: Record<string, string> = {
    submitted: '⏳ Menunggu', verified: '✓ Verified',
    rejected: '✗ Ditolak', pending: 'Pending',
  }
  return map[status] || status
}

function formatDate(val: string) {
  if (!val) return '-'
  return new Date(val).toLocaleDateString('id-ID', {
    day: 'numeric', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

onMounted(() => {
  fetchJobOrders({ per_page: 100 })
  fetchMyPods()
})
</script>

<style scoped>
.input-field {
  @apply w-full mt-1 px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500;
}
</style>