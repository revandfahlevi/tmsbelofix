// src/lib/firebase.ts
import { initializeApp, getApps, getApp, type FirebaseApp } from 'firebase/app'
import { getMessaging, getToken, onMessage, type MessagePayload } from 'firebase/messaging'

function getFirebaseConfig() {
  return {
    apiKey:            import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain:        import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId:         import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket:     import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId:             import.meta.env.VITE_FIREBASE_APP_ID,
  }
}

export function getFirebaseApp(): FirebaseApp {
  if (getApps().length > 0) return getApp()
  const config = getFirebaseConfig()
  console.log('Firebase Project ID:', config.projectId)
  return initializeApp(config)
}

// Dipanggil dari KLIK TOMBOL — butuh user gesture
export async function requestPermissionAndGetToken(): Promise<string | null> {
  try {
    if (!('Notification' in window) || !('serviceWorker' in navigator)) {
      console.warn('[FCM] Browser tidak support')
      return null
    }
    const permission = await Notification.requestPermission()
    if (permission !== 'granted') {
      console.warn('[FCM] Permission ditolak:', permission)
      return null
    }
    const sw = await navigator.serviceWorker.ready
    const messaging = getMessaging(getFirebaseApp())
    const token = await getToken(messaging, {
      vapidKey: import.meta.env.VITE_FIREBASE_VAPID_KEY,
      serviceWorkerRegistration: sw,
    })
    console.log('[FCM] Token:', token)
    return token
  } catch (err) {
    console.error('[FCM] error:', err)
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