// src/lib/firebase.ts
import { initializeApp, getApps, getApp } from 'firebase/app'
import { getMessaging, getToken, onMessage, type MessagePayload } from 'firebase/messaging'

function getFirebaseConfig() {
  const config = {
    apiKey:            import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain:        import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId:         import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket:     import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId:             import.meta.env.VITE_FIREBASE_APP_ID,
  }
  // Debug — hapus setelah berhasil
  console.log('[Firebase] Config loaded:', config)
  if (!config.projectId) {
    throw new Error('[Firebase] projectId kosong — cek .env dan restart npm run dev')
  }
  return config
}

// Lazy init — hanya dijalankan saat pertama kali dipanggil
function getFirebaseApp() {
  if (getApps().length > 0) return getApp()
  return initializeApp(getFirebaseConfig())
}

export async function requestNotificationPermission(): Promise<string | null> {
  try {
    if (!('Notification' in window)) {
      console.warn('[FCM] Browser tidak support notifikasi')
      return null
    }
    if (!('serviceWorker' in navigator)) {
      console.warn('[FCM] Browser tidak support Service Worker')
      return null
    }

    const permission = await Notification.requestPermission()
    if (permission !== 'granted') {
      console.warn('[FCM] Permission ditolak user')
      return null
    }

    const messaging = getMessaging(getFirebaseApp())
    const token = await getToken(messaging, {
      vapidKey: import.meta.env.VITE_FIREBASE_VAPID_KEY,
    })

    console.log('[FCM] Token berhasil:', token)
    return token
  } catch (err) {
    console.error('[FCM] Error:', err)
    return null
  }
}

export function onForegroundMessage(callback: (payload: MessagePayload) => void) {
  try {
    const messaging = getMessaging(getFirebaseApp())
    return onMessage(messaging, callback)
  } catch (err) {
    console.error('[FCM] onForegroundMessage error:', err)
    return () => {}
  }
}