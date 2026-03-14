<template>
  <div class="relative" ref="bellRef">
    <!-- Bell Button -->
    <button @click="toggleDropdown"
      class="relative p-2 rounded-xl hover:bg-gray-100 transition">
      <Bell class="w-5 h-5 text-gray-600" />
      <span v-if="unreadCount > 0"
        class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold animate-pulse">
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown -->
    <Transition name="dropdown">
      <div v-if="open"
        class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-gray-100 z-50 overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
          <h3 class="font-semibold text-gray-800 text-sm">Notifikasi</h3>
          <div class="flex items-center gap-2">
            <button v-if="unreadCount > 0"
              @click="markAllRead"
              class="text-xs text-blue-600 hover:underline">
              Tandai semua dibaca
            </button>
            <button v-if="notifications.length > 0"
              @click="clearAll"
              class="text-xs text-gray-400 hover:text-red-500">
              Hapus semua
            </button>
          </div>
        </div>

        <!-- List -->
        <div class="max-h-80 overflow-y-auto divide-y divide-gray-50">
          <div v-if="notifications.length === 0"
            class="text-center py-10 text-gray-400">
            <BellOff class="w-8 h-8 mx-auto mb-2 opacity-20" />
            <p class="text-xs">Tidak ada notifikasi</p>
          </div>

          <div v-for="notif in notifications" :key="notif.id"
            @click="handleClick(notif)"
            :class="`flex gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50 transition ${
              !notif.read ? 'bg-blue-50/50' : ''
            }`">
            <!-- Icon -->
            <div :class="`w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center ${iconBg(notif.type)}`">
              <component :is="iconComponent(notif.type)" class="w-4 h-4" />
            </div>
            <!-- Content -->
            <div class="flex-1 min-w-0">
              <p :class="`text-sm leading-snug ${!notif.read ? 'font-semibold text-gray-800' : 'text-gray-700'}`">
                {{ notif.title }}
              </p>
              <p class="text-xs text-gray-500 mt-0.5 truncate">{{ notif.body }}</p>
              <p class="text-xs text-gray-400 mt-1">{{ timeAgo(notif.time) }}</p>
            </div>
            <!-- Unread dot -->
            <div v-if="!notif.read" class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0 mt-1.5" />
          </div>
        </div>

        <!-- Footer -->
        <div v-if="notifications.length > 0"
          class="px-4 py-2 border-t border-gray-100 text-center">
          <p class="text-xs text-gray-400">{{ notifications.length }} notifikasi</p>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Bell, BellOff, Package, FileImage, UserCheck } from 'lucide-vue-next'
import { useRouter } from 'vue-router'
import { useNotification } from '@/composables/useNotification'

const router = useRouter()
const { notifications, unreadCount, markAllRead, markRead, clearAll } = useNotification()

const open    = ref(false)
const bellRef = ref<HTMLElement | null>(null)

function toggleDropdown() { open.value = !open.value }

// Close on click outside
function handleOutside(e: MouseEvent) {
  if (bellRef.value && !bellRef.value.contains(e.target as Node)) {
    open.value = false
  }
}

function handleClick(notif: any) {
  markRead(notif.id)
  open.value = false
  // Navigate berdasarkan type
  if (notif.type === 'job_assigned')   router.push('/driver/dashboard')
  if (notif.type === 'driver_applied') router.push('/admin/job-orders')
  if (notif.type === 'pod_submitted')  router.push('/admin/pod')
}

function iconBg(type: string) {
  const map: Record<string, string> = {
    job_assigned:   'bg-blue-100 text-blue-600',
    driver_applied: 'bg-green-100 text-green-600',
    pod_submitted:  'bg-purple-100 text-purple-600',
  }
  return map[type] ?? 'bg-gray-100 text-gray-500'
}

function iconComponent(type: string) {
  if (type === 'job_assigned')   return Package
  if (type === 'driver_applied') return UserCheck
  if (type === 'pod_submitted')  return FileImage
  return Bell
}

function timeAgo(time: Date) {
  const diff = Date.now() - new Date(time).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1)  return 'Baru saja'
  if (mins < 60) return `${mins} menit lalu`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `${hours} jam lalu`
  return `${Math.floor(hours / 24)} hari lalu`
}

onMounted(() => document.addEventListener('click', handleOutside))
onUnmounted(() => document.removeEventListener('click', handleOutside))
</script>

<style scoped>
.dropdown-enter-active, .dropdown-leave-active { transition: all 0.2s ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-8px); }
</style>