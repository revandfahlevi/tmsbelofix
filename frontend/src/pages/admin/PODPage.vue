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
        <p class="text-2xl font-bold text-gray-800">{{ pods.length }}</p>
      </div>
      <div class="bg-gradient-to-br from-white to-green-50 rounded-2xl p-4 border border-green-50 shadow-sm">
        <p class="text-xs text-gray-500 mb-2">Hari Ini</p>
        <p class="text-2xl font-bold text-gray-800">{{ todayCount }}</p>
      </div>
      <div class="bg-gradient-to-br from-white to-orange-50 rounded-2xl p-4 border border-orange-50 shadow-sm">
        <p class="text-xs text-gray-500 mb-2">Dengan Foto</p>
        <p class="text-2xl font-bold text-gray-800">{{ withPhotoCount }}</p>
      </div>
      <div class="bg-gradient-to-br from-white to-purple-50 rounded-2xl p-4 border border-purple-50 shadow-sm">
        <p class="text-xs text-gray-500 mb-2">Kurir Aktif</p>
        <p class="text-2xl font-bold text-gray-800">{{ uniqueDrivers }}</p>
      </div>
    </div>

    <!-- Filter & Search -->
    <div class="flex flex-wrap gap-3 items-center">
      <div class="flex-1 min-w-48 relative">
        <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
        <input v-model="search" placeholder="Cari job order, penerima, kurir..."
          class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>
      <select v-model="filterDriver"
        class="px-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">Semua Kurir</option>
        <option v-for="d in driverList" :key="d" :value="d">{{ d }}</option>
      </select>
    </div>

    <!-- Grid Foto -->
    <div v-if="filteredPods.length === 0" class="text-center py-16 text-gray-400">
      <FileImage class="w-12 h-12 mx-auto mb-3 opacity-20" />
      <p class="text-sm">Belum ada laporan POD</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="pod in filteredPods" :key="pod.id"
        @click="selectedPod = pod"
        class="bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-md transition cursor-pointer">
        <div class="relative h-44 bg-gray-100">
          <img v-if="pod.photo_url" :src="pod.photo_url" alt="Bukti POD"
            class="w-full h-full object-cover" />
          <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
            <FileImage class="w-10 h-10" />
          </div>
          <span class="absolute top-2 right-2 text-xs bg-green-500 text-white px-2 py-0.5 rounded-full font-medium">
            ✓ Verified
          </span>
        </div>
        <div class="p-4">
          <div class="flex items-start justify-between mb-1">
            <p class="font-semibold text-sm text-blue-600">{{ pod.job_order_id }}</p>
            <p class="text-xs text-gray-400">{{ formatDate(pod.captured_at) }}</p>
          </div>
          <p class="text-sm text-gray-800 font-medium">{{ pod.recipient_name }}</p>
          <div class="text-xs text-gray-400 mt-1 space-y-0.5">
            <p v-if="pod.recipient_id">ID: {{ pod.recipient_id }}</p>
            <p v-if="pod.notes" class="truncate">📝 {{ pod.notes }}</p>
            <p class="flex items-center gap-1">
              <Truck class="w-3 h-3" /> {{ pod.captured_by }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Detail -->
    <div v-if="selectedPod"
      class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
      @click.self="selectedPod = null">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="relative">
          <img v-if="selectedPod.photo_url" :src="selectedPod.photo_url" alt="POD"
            class="w-full h-72 object-cover" />
          <div v-else class="w-full h-72 bg-gray-100 flex items-center justify-center text-gray-300">
            <FileImage class="w-16 h-16" />
          </div>
          <button @click="selectedPod = null"
            class="absolute top-3 right-3 bg-white/80 backdrop-blur rounded-full p-1.5 hover:bg-white transition">
            <X class="w-4 h-4 text-gray-700" />
          </button>
          <span class="absolute top-3 left-3 text-xs bg-green-500 text-white px-3 py-1 rounded-full font-medium">
            ✓ Verified
          </span>
        </div>
        <div class="p-5 space-y-3">
          <div class="flex items-center justify-between">
            <p class="font-bold text-blue-600 text-lg">{{ selectedPod.job_order_id }}</p>
            <p class="text-xs text-gray-400">{{ formatDate(selectedPod.captured_at) }}</p>
          </div>
          <div class="grid grid-cols-2 gap-3 text-sm">
            <div class="bg-gray-50 rounded-xl p-3">
              <p class="text-xs text-gray-400 mb-0.5">Penerima</p>
              <p class="font-medium text-gray-800">{{ selectedPod.recipient_name }}</p>
            </div>
            <div class="bg-gray-50 rounded-xl p-3">
              <p class="text-xs text-gray-400 mb-0.5">Kurir</p>
              <p class="font-medium text-gray-800">{{ selectedPod.captured_by }}</p>
            </div>
            <div v-if="selectedPod.recipient_id" class="bg-gray-50 rounded-xl p-3">
              <p class="text-xs text-gray-400 mb-0.5">ID Penerima</p>
              <p class="font-medium text-gray-800">{{ selectedPod.recipient_id }}</p>
            </div>
            <div v-if="selectedPod.notes" class="bg-gray-50 rounded-xl p-3 col-span-2">
              <p class="text-xs text-gray-400 mb-0.5">Catatan</p>
              <p class="font-medium text-gray-800">{{ selectedPod.notes }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { FileImage, Search, Truck, X } from 'lucide-vue-next'
import { usePodStore } from '@/stores/pod'
import { useNotificationStore } from '@/stores/notifications'

const podStore = usePodStore()
const notifStore = useNotificationStore()

// ✅ Semua dari store — real-time update dari driver
const pods = computed(() => podStore.pods)
const todayCount = computed(() => podStore.todayCount)
const withPhotoCount = computed(() => podStore.withPhotoCount)
const uniqueDrivers = computed(() => podStore.uniqueDrivers)

const search = ref('')
const filterDriver = ref('')
const selectedPod = ref<any>(null)

// ✅ Mark notif POD sebagai read saat admin buka halaman ini
notifStore.markAllRead()

const driverList = computed(() =>
  [...new Set(pods.value.map((p: any) => p.captured_by))]
)

const filteredPods = computed(() => {
  let list = pods.value
  if (filterDriver.value) list = list.filter((p: any) => p.captured_by === filterDriver.value)
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter((p: any) =>
      p.job_order_id?.toLowerCase().includes(q) ||
      p.recipient_name?.toLowerCase().includes(q) ||
      p.captured_by?.toLowerCase().includes(q)
    )
  }
  return list
})

function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })
}
</script>