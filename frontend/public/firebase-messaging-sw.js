// public/firebase-messaging-sw.js
importScripts('https://www.gstatic.com/firebasejs/10.7.0/firebase-app-compat.js')
importScripts('https://www.gstatic.com/firebasejs/10.7.0/firebase-messaging-compat.js')

firebase.initializeApp({
  apiKey:            'AIzaSyDAjz3GXDHDBX4W46N1Zk8Xx3yhgkG15Cw',
  authDomain:        'tms-laravel-9f4d6.firebaseapp.com',
  projectId:         'tms-laravel-9f4d6',
  storageBucket:     'tms-laravel-9f4d6.firebasestorage.app',
  messagingSenderId: '610127909213',
  appId:             '1:610127909213:web:fb84cf2ef6b53037bbd8b4',
})

const messaging = firebase.messaging()

// Handle background notifications
messaging.onBackgroundMessage((payload) => {
  console.log('[SW] Background message:', payload)

  const { title, body, icon, data } = payload.notification ?? {}

  self.registration.showNotification(title ?? 'TMS Notification', {
    body:    body ?? '',
    icon:    icon ?? '/logo.png',
    badge:   '/logo.png',
    tag:     data?.type ?? 'tms',
    data:    payload.data ?? {},
    actions: getActions(payload.data?.type),
    vibrate: [200, 100, 200],
  })
})

// Notification click handler
self.addEventListener('notificationclick', (event) => {
  event.notification.close()

  const data = event.notification.data
  let url = '/'

  if (data?.type === 'job_assigned')   url = '/driver/dashboard'
  if (data?.type === 'driver_applied') url = '/admin/job-orders'
  if (data?.type === 'pod_submitted')  url = '/admin/pod'

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if (client.url.includes(url) && 'focus' in client) return client.focus()
      }
      if (clients.openWindow) return clients.openWindow(url)
    })
  )
})

function getActions(type) {
  if (type === 'job_assigned') return [{ action: 'view', title: 'Lihat Job' }]
  if (type === 'driver_applied') return [{ action: 'view', title: 'Review' }]
  if (type === 'pod_submitted') return [{ action: 'view', title: 'Verifikasi' }]
  return []
}