// src/composables/useNotification.ts
import { ref, onMounted, onUnmounted } from 'vue'
import { requestNotificationPermission, onForegroundMessage } from '@/lib/firebase'
import api from '@/lib/axios'

export interface NotifItem {
  id:      string
  title:   string
  body:    string
  type:    string
  time:    Date
  read:    boolean
  data?:   Record<string, any>
}

const notifications = ref<NotifItem[]>([])
const unreadCount   = ref(0)

export function useNotification() {
  let unsubscribe: (() => void) | null = null

  // ── Setup FCM ───────────────────────────────────────────
  async function setupPush() {
    try {
      const token = await requestNotificationPermission()
      if (!token) return

      // Kirim token ke backend untuk disimpan
      await api.put('/auth/update-fcm-token', { fcm_token: token })
      console.log('[Notif] FCM token saved to backend')
    } catch (err) {
      console.error('[Notif] Setup push error:', err)
    }
  }

  // ── Listen foreground messages ──────────────────────────
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

      // Tambah ke list
      notifications.value.unshift(notif)
      unreadCount.value++

      // Tampilkan toast/notif browser manual (karena foreground tidak auto-show)
      showBrowserNotif(notif)
    })
  }

  // ── Show browser notification (foreground) ──────────────
  function showBrowserNotif(notif: NotifItem) {
    if (Notification.permission !== 'granted') return
    const n = new Notification(notif.title, {
      body:  notif.body,
      icon:  '/logo.png',
      badge: '/logo.png',
      tag:   notif.type,
    })
    n.onclick = () => {
      window.focus()
      n.close()
    }
  }

  // ── Mark all as read ────────────────────────────────────
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
    setupPush,
    startListening,
    markAllRead,
    markRead,
    clearAll,
    stopListening: () => unsubscribe?.(),
  }
}