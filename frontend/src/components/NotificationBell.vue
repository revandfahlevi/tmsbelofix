<template>
  <div class="relative" ref="bellRef">
    <button @click="toggleOpen"
      class="relative p-2 rounded-xl hover:bg-gray-100 transition">
      <Bell class="w-5 h-5 text-gray-600" />
      <span v-if="unreadCount > 0"
        class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold">
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown -->
    <div v-if="isOpen"
      class="absolute right-0 top-10 w-80 bg-white rounded-2xl shadow-xl border border-gray-100 z-50 overflow-hidden">

      <!-- Header -->
      <div class="flex items-center justify-between px-4 py-3 border-b">
        <h3 class="font-semibold text-sm text-gray-800">Notifikasi</h3>
        <button v-if="unreadCount > 0" @click="markAllRead"
          class="text-xs text-blue-600 hover:underline">
          Tandai semua dibaca
        </button>
      </div>

      <!-- List -->
      <div class="max-h-80 overflow-y-auto">
        <div v-if="notifications.length === 0"
          class="text-center py-8 text-gray-400">
          <Bell class="w-8 h-8 mx-auto mb-2 opacity-30" />
          <p class="text-xs">Tidak ada notifikasi</p>
        </div>

        <div v-for="notif in notifications" :key="notif.id"
          @click="markRead(notif.id)"
          :class="`flex gap-3 px-4 py-3 hover:bg-gray-50 cursor-pointer transition border-b border-gray-50 ${
            !notif.read ? 'bg-blue-50/60' : ''
          }`">

          <!-- Icon berdasarkan type -->
          <div :class="`w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 ${iconBg(notif.type)}`">
            <component :is="iconType(notif.type)" class="w-4 h-4 text-white" />
          </div>

          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-800">{{ notif.title }}</p>
            <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">{{ notif.message }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ formatTime(notif.created_at) }}</p>
          </div>

          <div v-if="!notif.read"
            class="w-2 h-2 bg-blue-500 rounded-full mt-1.5 flex-shrink-0" />
        </div>
      </div>

      <!-- Footer -->
      <div class="px-4 py-2 border-t bg-gray-50">
        <p class="text-xs text-gray-400 text-center">{{ notifications.length }} notifikasi total</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Bell, FileImage, Package, Truck, AlertTriangle } from 'lucide-vue-next'
import { useNotificationStore } from '@/stores/notifications'

const notifStore = useNotificationStore()
const isOpen = ref(false)
const bellRef = ref<HTMLElement | null>(null)

// ✅ Semua dari store — real-time
const notifications = computed(() => notifStore.notifications)
const unreadCount = computed(() => notifStore.unreadCount)

function markRead(id: string) { notifStore.markRead(id) }
function markAllRead() { notifStore.markAllRead() }

function toggleOpen() { isOpen.value = !isOpen.value }

// Tutup kalau klik di luar
function handleClickOutside(e: MouseEvent) {
  if (bellRef.value && !bellRef.value.contains(e.target as Node)) {
    isOpen.value = false
  }
}
onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))

function iconType(type: string) {
  const map: Record<string, any> = {
    pod: FileImage,
    job_order: Package,
    dispatch: Truck,
    alert: AlertTriangle,
  }
  return map[type] || Bell
}

function iconBg(type: string) {
  const map: Record<string, string> = {
    pod: 'bg-green-500',
    job_order: 'bg-blue-500',
    dispatch: 'bg-orange-500',
    alert: 'bg-red-500',
  }
  return map[type] || 'bg-gray-400'
}

function formatTime(dateStr: string) {
  const d = new Date(dateStr)
  const now = new Date()
  const diff = Math.floor((now.getTime() - d.getTime()) / 1000)
  if (diff < 60) return 'Baru saja'
  if (diff < 3600) return `${Math.floor(diff / 60)} menit lalu`
  if (diff < 86400) return `${Math.floor(diff / 3600)} jam lalu`
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
}
</script>