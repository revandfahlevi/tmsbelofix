<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-2xl font-bold">Proof of Delivery (POD)</h1>
      <p class="text-sm text-gray-500 mt-1">Laporan foto bukti pengiriman dari kurir</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-gradient-to-br from-white to-blue-50 rounded-2xl p-4 border border-blue-50 shadow-sm">
        <p class="text-xs text-gray-500 mb-2">Total POD</p>
        <p class="text-2xl font-bold text-gray-800">
          <span v-if="loading" class="inline-block w-8 h-7 bg-gray-200 animate-pulse rounded" />
          <span v-else>{{ meta.total ?? pods.length }}</span>
        </p>
      </div>
      <div class="bg-gradient-to-br from-white to-yellow-50 rounded-2xl p-4 border border-yellow-50 shadow-sm">
        <p class="text-xs text-gray-500 mb-2">Menunggu Verifikasi</p>
        <p class="text-2xl font-bold text-yellow-600">{{ pendingCount }}</p>
      </div>
      <div class="bg-gradient-to-br from-white to-green-50 rounded-2xl p-4 border border-green-50 shadow-sm">
        <p class="text-xs text-gray-500 mb-2">Terverifikasi</p>
        <p class="text-2xl font-bold text-green-600">{{ verifiedCount }}</p>
      </div>
      <div class="bg-gradient-to-br from-white to-red-50 rounded-2xl p-4 border border-red-50 shadow-sm">
        <p class="text-xs text-gray-500 mb-2">Ditolak</p>
        <p class="text-2xl font-bold text-red-600">{{ rejectedCount }}</p>
      </div>
    </div>

    <!-- Filter & Search -->
    <div class="flex flex-wrap gap-3 items-center">
      <div class="flex-1 min-w-48 relative">
        <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
        <input v-model="search" @input="debouncedFetch" placeholder="Cari job order, penerima..."
          class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>
      <select v-model="filterStatus" @change="fetchPods" class="px-3 py-2 text-sm border border-gray-200 rounded-xl">
        <option value="">Semua Status</option>
        <option value="submitted">Menunggu Verifikasi</option>
        <option value="verified">Terverifikasi</option>
        <option value="rejected">Ditolak</option>
      </select>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="i in 6" :key="i" class="h-64 bg-gray-100 animate-pulse rounded-2xl" />
    </div>

    <!-- Empty -->
    <div v-else-if="pods.length === 0" class="text-center py-16 text-gray-400">
      <FileImage class="w-12 h-12 mx-auto mb-3 opacity-20" />
      <p class="text-sm">Belum ada laporan POD</p>
    </div>

    <!-- Grid POD -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="pod in pods" :key="pod.id"
        @click="selectedPod = pod"
        class="bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-md transition cursor-pointer">
        <!-- Foto -->
        <div class="relative h-44 bg-gray-100">
          <img v-if="pod.photos?.length"
            :src="`/storage/${pod.photos[0]}`" alt="Bukti POD"
            class="w-full h-full object-cover" />
          <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
            <FileImage class="w-10 h-10" />
          </div>
          <!-- Status badge -->
          <span :class="`absolute top-2 right-2 text-xs px-2 py-0.5 rounded-full font-medium ${statusBadge(pod.status)}`">
            {{ statusLabel(pod.status) }}
          </span>
        </div>
        <div class="p-4">
          <div class="flex items-start justify-between mb-1">
            <p class="font-semibold text-sm text-blue-600">{{ pod.pod_number }}</p>
            <p class="text-xs text-gray-400">{{ formatDate(pod.delivered_at ?? pod.created_at) }}</p>
          </div>
          <p class="text-xs text-gray-500">{{ pod.job_order?.job_number ?? '-' }}</p>
          <p class="text-sm text-gray-800 font-medium mt-1">{{ pod.recipient_name }}</p>
          <div class="text-xs text-gray-400 mt-1 space-y-0.5">
            <p v-if="pod.notes" class="truncate">📝 {{ pod.notes }}</p>
            <p class="flex items-center gap-1">
              <Truck class="w-3 h-3" /> {{ pod.driver?.name ?? '-' }}
            </p>
          </div>
          <!-- Action buttons untuk submitted -->
          <div v-if="pod.status === 'submitted'" class="flex gap-2 mt-3" @click.stop>
            <button @click="handleVerify(pod)"
              :disabled="actionLoading === pod.id"
              class="flex-1 bg-green-600 text-white py-1.5 rounded-lg text-xs font-medium hover:bg-green-700 disabled:opacity-50 transition flex items-center justify-center gap-1">
              <Loader2 v-if="actionLoading === pod.id" class="w-3 h-3 animate-spin" />
              <CheckCircle v-else class="w-3 h-3" />
              Verifikasi
            </button>
            <button @click="openRejectModal(pod)"
              class="flex-1 border border-red-200 text-red-600 py-1.5 rounded-lg text-xs font-medium hover:bg-red-50 transition">
              Tolak
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="meta.last_page > 1" class="flex items-center justify-center gap-2">
      <button @click="changePage(meta.current_page - 1)" :disabled="meta.current_page === 1"
        class="px-3 py-1.5 text-sm border rounded-lg disabled:opacity-40 hover:bg-gray-50">
        ← Prev
      </button>
      <span class="text-sm text-gray-600">{{ meta.current_page }} / {{ meta.last_page }}</span>
      <button @click="changePage(meta.current_page + 1)" :disabled="meta.current_page === meta.last_page"
        class="px-3 py-1.5 text-sm border rounded-lg disabled:opacity-40 hover:bg-gray-50">
        Next →
      </button>
    </div>

    <!-- Modal Detail -->
    <div v-if="selectedPod" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
      @click.self="selectedPod = null">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden max-h-[90vh] overflow-y-auto">
        <div class="relative">
          <img v-if="selectedPod.photos?.length"
            :src="`/storage/${selectedPod.photos[0]}`" alt="POD"
            class="w-full h-64 object-cover" />
          <div v-else class="w-full h-64 bg-gray-100 flex items-center justify-center text-gray-300">
            <FileImage class="w-16 h-16" />
          </div>
          <button @click="selectedPod = null"
            class="absolute top-3 right-3 bg-white/80 backdrop-blur rounded-full p-1.5 hover:bg-white">
            <X class="w-4 h-4 text-gray-700" />
          </button>
          <span :class="`absolute top-3 left-3 text-xs px-3 py-1 rounded-full font-medium ${statusBadge(selectedPod.status)}`">
            {{ statusLabel(selectedPod.status) }}
          </span>
        </div>
        <div class="p-5 space-y-4">
          <div class="flex items-center justify-between">
            <p class="font-bold text-blue-600 text-lg">{{ selectedPod.pod_number }}</p>
            <p class="text-xs text-gray-400">{{ formatDate(selectedPod.delivered_at ?? selectedPod.created_at) }}</p>
          </div>
          <div class="grid grid-cols-2 gap-3 text-sm">
            <div class="bg-gray-50 rounded-xl p-3">
              <p class="text-xs text-gray-400 mb-0.5">Job Order</p>
              <p class="font-medium">{{ selectedPod.job_order?.job_number ?? '-' }}</p>
            </div>
            <div class="bg-gray-50 rounded-xl p-3">
              <p class="text-xs text-gray-400 mb-0.5">Driver</p>
              <p class="font-medium">{{ selectedPod.driver?.name ?? '-' }}</p>
            </div>
            <div class="bg-gray-50 rounded-xl p-3">
              <p class="text-xs text-gray-400 mb-0.5">Penerima</p>
              <p class="font-medium">{{ selectedPod.recipient_name }}</p>
            </div>
            <div class="bg-gray-50 rounded-xl p-3">
              <p class="text-xs text-gray-400 mb-0.5">Hubungan</p>
              <p class="font-medium">{{ selectedPod.recipient_relationship ?? '-' }}</p>
            </div>
            <div v-if="selectedPod.notes" class="bg-gray-50 rounded-xl p-3 col-span-2">
              <p class="text-xs text-gray-400 mb-0.5">Catatan</p>
              <p class="font-medium">{{ selectedPod.notes }}</p>
            </div>
            <div v-if="selectedPod.rejection_reason" class="bg-red-50 rounded-xl p-3 col-span-2">
              <p class="text-xs text-red-400 mb-0.5">Alasan Ditolak</p>
              <p class="font-medium text-red-700">{{ selectedPod.rejection_reason }}</p>
            </div>
            <div v-if="selectedPod.verified_by" class="bg-green-50 rounded-xl p-3 col-span-2">
              <p class="text-xs text-green-500 mb-0.5">Diverifikasi oleh</p>
              <p class="font-medium text-green-700">{{ selectedPod.verifiedBy?.name ?? '-' }} · {{ formatDate(selectedPod.verified_at) }}</p>
            </div>
          </div>
          <div v-if="selectedPod.status === 'submitted'" class="flex gap-2 pt-2">
            <button @click="handleVerify(selectedPod); selectedPod = null"
              class="flex-1 bg-green-600 text-white py-2 rounded-xl text-sm font-medium hover:bg-green-700 transition">
              ✓ Verifikasi
            </button>
            <button @click="openRejectModal(selectedPod); selectedPod = null"
              class="flex-1 border border-red-200 text-red-600 py-2 rounded-xl text-sm font-medium hover:bg-red-50 transition">
              Tolak
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Reject -->
    <div v-if="rejectModal.show" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
      @click.self="rejectModal.show = false">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
        <h3 class="font-semibold text-gray-800">Tolak POD {{ rejectModal.pod?.pod_number }}</h3>
        <div>
          <label class="text-xs font-medium text-gray-600 mb-1 block">Alasan penolakan *</label>
          <textarea v-model="rejectModal.reason" rows="3"
            placeholder="Jelaskan alasan penolakan..."
            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500" />
        </div>
        <div class="flex gap-2">
          <button @click="rejectModal.show = false"
            class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-xl text-sm hover:bg-gray-50">
            Batal
          </button>
          <button @click="handleReject"
            :disabled="!rejectModal.reason || actionLoading !== null"
            class="flex-1 bg-red-600 text-white py-2 rounded-xl text-sm font-medium hover:bg-red-700 disabled:opacity-50 transition flex items-center justify-center gap-2">
            <Loader2 v-if="actionLoading" class="w-4 h-4 animate-spin" />
            Tolak POD
          </button>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <Transition name="toast">
      <div v-if="toast" class="fixed bottom-6 right-6 bg-gray-900 text-white px-4 py-3 rounded-xl shadow-lg text-sm flex items-center gap-2 z-50">
        <CheckCircle class="w-4 h-4 text-green-400" />
        {{ toast }}
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue'
import { FileImage, Search, Truck, X, CheckCircle, Loader2 } from 'lucide-vue-next'
import api from '@/lib/axios'

const pods      = ref<any[]>([])
const loading   = ref(false)
const search    = ref('')
const filterStatus = ref('')
const selectedPod  = ref<any>(null)
const actionLoading = ref<any>(null)
const toast     = ref('')
const meta      = ref({ current_page: 1, last_page: 1, total: 0 })

const rejectModal = reactive({ show: false, pod: null as any, reason: '' })

// ── Stats ─────────────────────────────────────────────────
const pendingCount  = computed(() => pods.value.filter(p => p.status === 'submitted').length)
const verifiedCount = computed(() => pods.value.filter(p => p.status === 'verified').length)
const rejectedCount = computed(() => pods.value.filter(p => p.status === 'rejected').length)

// ── Fetch ─────────────────────────────────────────────────
async function fetchPods(page = 1) {
  loading.value = true
  try {
    const res = await api.get('/pods', {
      params: {
        page,
        per_page: 12,
        status: filterStatus.value || undefined,
        search: search.value || undefined,
      }
    })
    const raw = typeof res.data === 'string'
      ? JSON.parse(res.data.replace(/^=/, ''))
      : res.data
    const data = raw.data
    pods.value  = data.data ?? data ?? []
    if (data.meta ?? data.current_page) {
      meta.value = {
        current_page: data.meta?.current_page ?? data.current_page ?? 1,
        last_page:    data.meta?.last_page    ?? data.last_page    ?? 1,
        total:        data.meta?.total        ?? data.total        ?? pods.value.length,
      }
    }
  } catch {
    pods.value = []
  } finally {
    loading.value = false
  }
}

// ── Verify ────────────────────────────────────────────────
async function handleVerify(pod: any) {
  actionLoading.value = pod.id
  try {
    await api.patch(`/pods/${pod.id}/verify`)
    const idx = pods.value.findIndex(p => p.id === pod.id)
    if (idx !== -1) pods.value[idx] = { ...pods.value[idx], status: 'verified' }
    showToast('POD berhasil diverifikasi')
  } catch (e: any) {
    showToast('Gagal verifikasi POD')
  } finally {
    actionLoading.value = null
  }
}

// ── Reject ────────────────────────────────────────────────
function openRejectModal(pod: any) {
  rejectModal.pod    = pod
  rejectModal.reason = ''
  rejectModal.show   = true
}

async function handleReject() {
  if (!rejectModal.reason) return
  actionLoading.value = rejectModal.pod.id
  try {
    await api.patch(`/pods/${rejectModal.pod.id}/reject`, {
      rejection_reason: rejectModal.reason,
    })
    const idx = pods.value.findIndex(p => p.id === rejectModal.pod.id)
    if (idx !== -1) pods.value[idx] = {
      ...pods.value[idx],
      status: 'rejected',
      rejection_reason: rejectModal.reason,
    }
    rejectModal.show = false
    showToast('POD ditolak')
  } catch {
    showToast('Gagal menolak POD')
  } finally {
    actionLoading.value = null
  }
}

function changePage(page: number) {
  if (page < 1 || page > meta.value.last_page) return
  fetchPods(page)
}

// ── Debounce search ───────────────────────────────────────
let searchTimer: any = null
function debouncedFetch() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => fetchPods(), 400)
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
    submitted: 'Menunggu', verified: '✓ Verified',
    rejected: 'Ditolak', pending: 'Pending',
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

function showToast(msg: string) {
  toast.value = msg
  setTimeout(() => toast.value = '', 3000)
}

onMounted(() => fetchPods())
</script>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(10px); }
</style>