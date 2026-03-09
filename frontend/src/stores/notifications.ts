import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'

const STORAGE_KEY = 'tms_notifications'

export const useNotificationStore = defineStore('notifications', () => {
  // Load dari localStorage
  const saved = localStorage.getItem(STORAGE_KEY)
  const notifications = ref<any[]>(saved ? JSON.parse(saved) : [])

  // Auto save ke localStorage setiap ada perubahan
  watch(notifications, (val) => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(val))
  }, { deep: true })

  // Listen perubahan dari tab lain
  window.addEventListener('storage', (e) => {
    if (e.key === STORAGE_KEY && e.newValue) {
      notifications.value = JSON.parse(e.newValue)
    }
  })

  function addNotification(notif: any) {
    notifications.value.unshift({
      id: Date.now().toString(),
      read: false,
      created_at: new Date().toISOString(),
      ...notif,
    })
  }

  function markRead(id: string) {
    const n = notifications.value.find(n => n.id === id)
    if (n) n.read = true
  }

  function markAllRead() {
    notifications.value.forEach(n => n.read = true)
  }

  const unreadCount = computed(() => notifications.value.filter(n => !n.read).length)

  return { notifications, addNotification, markRead, markAllRead, unreadCount }
})