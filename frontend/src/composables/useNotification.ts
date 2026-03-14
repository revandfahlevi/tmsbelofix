// src/composables/useNotification.ts
import { ref } from 'vue'
import { onForegroundMessage } from '@/lib/firebase'
import api from '@/lib/axios'

export interface NotifItem {
  id:    string
  title: string
  body:  string
  type:  string
  time:  Date
  read:  boolean
  data?: Record<string, any>
}

const notifications = ref<NotifItem[]>([])
const unreadCount   = ref(0)

export function useNotification() {
  let unsubscribe: (() => void) | null = null

  function startListening() {
    unsubscribe = onForegroundMessage((payload) => {
      console.log('[Notif] Foreground message:', payload)

      const notif: NotifItem = {
        id:    Date.now().toString(),
        title: payload.notification?.title ?? 'Notifikasi',
        body:  payload.notification?.body  ?? '',
        type:  payload.data?.type ?? 'info',
        time:  new Date(),
        read:  false,
        data:  payload.data,
      }

      notifications.value.unshift(notif)
      unreadCount.value++

      // Tampilkan browser notification manual (foreground tidak auto-show)
      if (Notification.permission === 'granted') {
        new Notification(notif.title, {
          body:  notif.body,
          icon:  '/logo.png',
          tag:   notif.type,
        })
      }
    })
  }

  function markAllRead() {
    notifications.value.forEach(n => n.read = true)
    unreadCount.value = 0
  }

  function markRead(id: string) {
    const n = notifications.value.find(n => n.id === id)
    if (n && !n.read) {
      n.read = true
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    }
  }

  function clearAll() {
    notifications.value = []
    unreadCount.value   = 0
  }

  return {
    notifications,
    unreadCount,
    startListening,
    markAllRead,
    markRead,
    clearAll,
    stopListening: () => unsubscribe?.(),
  }
}