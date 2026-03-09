<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-2xl font-bold">Proof of Delivery (POD)</h1>
      <p class="text-sm text-gray-500 mt-1">Upload foto bukti pengiriman paket ke customer</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Form Upload POD -->
      <div class="bg-white rounded-xl border p-5 shadow-sm space-y-4">
        <h2 class="font-semibold">Capture POD</h2>

        <!-- Pilih Job Order -->
        <div>
          <label class="text-xs font-medium text-gray-600">Job Order</label>
          <select v-model="selectedJobId" class="input-field">
            <option value="">-- Pilih Job Order --</option>
            <option v-for="job in deliveredJobs" :key="job.id" :value="job.id">
              {{ job.order_number }} — {{ job.customer_name }}
            </option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs font-medium text-gray-600">Nama Penerima</label>
            <input v-model="form.recipient_name" class="input-field" placeholder="Nama lengkap" />
          </div>
          <div>
            <label class="text-xs font-medium text-gray-600">ID Penerima (KTP)</label>
            <input v-model="form.recipient_id" class="input-field" placeholder="Nomor KTP" />
          </div>
        </div>

        <div>
          <label class="text-xs font-medium text-gray-600">Catatan</label>
          <textarea v-model="form.notes" class="input-field resize-none" rows="2"
            placeholder="Kondisi barang, catatan tambahan..." />
        </div>

        <!-- Upload Foto -->
        <div>
          <label class="text-xs font-medium text-gray-600">Foto Bukti Pengiriman</label>
          <div class="mt-1">
            <!-- Preview foto -->
            <div v-if="photoPreview" class="relative mb-2">
              <img :src="photoPreview" alt="Preview"
                class="w-full h-48 object-cover rounded-xl border border-gray-200" />
              <button @click="removePhoto"
                class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition">
                <X class="w-3.5 h-3.5" />
              </button>
            </div>

            <!-- Upload area -->
            <div v-else
              @click="triggerFileInput"
              @dragover.prevent
              @drop.prevent="handleDrop"
              class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition">
              <Camera class="w-8 h-8 mx-auto mb-2 text-gray-400" />
              <p class="text-sm font-medium text-gray-600">Klik atau drag foto ke sini</p>
              <p class="text-xs text-gray-400 mt-1">JPG, PNG, max 5MB</p>
            </div>

            <input
              ref="fileInputRef"
              type="file"
              accept="image/*"
              capture="environment"
              class="hidden"
              @change="handleFileChange"
            />
          </div>
        </div>

        <div v-if="success" class="bg-green-50 border border-green-200 rounded-lg p-3">
          <p class="text-sm text-green-700 font-medium">✅ POD berhasil disimpan!</p>
        </div>

        <button @click="handleCapture"
          :disabled="!selectedJobId || !form.recipient_name || !photoPreview"
          class="w-full bg-blue-600 text-white py-2 rounded-lg font-medium hover:bg-blue-700 transition disabled:opacity-40 disabled:cursor-not-allowed">
          Simpan POD
        </button>
      </div>

      <!-- Riwayat POD Driver ini -->
      <div class="bg-white rounded-xl border p-5 shadow-sm">
        <h2 class="font-semibold mb-4">Riwayat POD Saya</h2>
        <div class="space-y-3">
          <div v-for="pod in pods" :key="pod.id"
            class="border rounded-xl overflow-hidden hover:shadow-sm transition">
            <!-- Foto -->
            <div v-if="pod.photo_url" class="relative">
              <img :src="pod.photo_url" alt="Bukti"
                class="w-full h-36 object-cover" />
              <span class="absolute top-2 right-2 text-xs bg-green-500 text-white px-2 py-0.5 rounded-full font-medium">
                ✓ Verified
              </span>
            </div>

            <div class="p-3">
              <div class="flex items-start justify-between mb-1">
                <p class="font-medium text-sm text-blue-600">{{ pod.job_order_id }}</p>
              </div>
              <p class="text-sm text-gray-700">{{ pod.recipient_name }}</p>
              <div class="space-y-0.5 text-xs text-gray-500 mt-1">
                <p v-if="pod.recipient_id">ID: {{ pod.recipient_id }}</p>
                <p v-if="pod.notes">Catatan: {{ pod.notes }}</p>
                <p>{{ formatDate(pod.captured_at) }}</p>
              </div>
            </div>
          </div>

          <div v-if="pods.length === 0" class="text-center py-8 text-gray-400">
            <FileImage class="w-10 h-10 mx-auto mb-2 opacity-30" />
            <p class="text-sm">Belum ada POD tersimpan</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import { FileImage, Camera, X } from 'lucide-vue-next'
import { MOCK_JOB_ORDERS, MOCK_PODS } from '@/lib/mockData'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const fileInputRef = ref<HTMLInputElement | null>(null)
const photoPreview = ref<string | null>(null)
const success = ref(false)
const selectedJobId = ref('')

const form = reactive({
  recipient_name: '',
  recipient_id: '',
  notes: '',
})

const pods = ref<any[]>([...MOCK_PODS])

const deliveredJobs = computed(() =>
  MOCK_JOB_ORDERS.filter(j =>
    j.status === 'in_transit' || j.status === 'dispatched' || j.status === 'delivered'
  )
)

function triggerFileInput() {
  fileInputRef.value?.click()
}

function handleFileChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (file) loadPreview(file)
}

function handleDrop(e: DragEvent) {
  const file = e.dataTransfer?.files?.[0]
  if (file && file.type.startsWith('image/')) loadPreview(file)
}

function loadPreview(file: File) {
  const reader = new FileReader()
  reader.onload = (e) => { photoPreview.value = e.target?.result as string }
  reader.readAsDataURL(file)
}

function removePhoto() {
  photoPreview.value = null
  if (fileInputRef.value) fileInputRef.value.value = ''
}

function handleCapture() {
  if (!selectedJobId.value || !form.recipient_name || !photoPreview.value) return
  const newPod: any = {
    id: 'POD' + Date.now(),
    job_order_id: selectedJobId.value,
    photo_url: photoPreview.value,
    recipient_name: form.recipient_name,
    recipient_id: form.recipient_id,
    notes: form.notes,
    captured_at: new Date().toISOString(),
    captured_by: auth.user?.name || 'Driver',
  }
  pods.value.unshift(newPod)
  success.value = true
  removePhoto()
  form.recipient_name = ''
  form.recipient_id = ''
  form.notes = ''
  selectedJobId.value = ''
  setTimeout(() => success.value = false, 3000)
}

function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })
}
</script>

<style scoped>
.input-field {
  @apply w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500;
}
</style>