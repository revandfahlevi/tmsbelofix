<template>
  <div class="callback-wrapper">
    <div class="callback-box">
      <div v-if="error" class="callback-error">
        <div class="cb-icon cb-icon-error">✕</div>
        <h2 class="cb-title">Login Gagal</h2>
        <p class="cb-sub">{{ errorMessage }}</p>
        <button class="cb-btn" @click="goLogin">Kembali ke Login</button>
      </div>
      <div v-else class="callback-loading">
        <div class="cb-spinner" />
        <h2 class="cb-title">Memproses login...</h2>
        <p class="cb-sub">Mohon tunggu sebentar</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router    = useRouter()
const authStore = useAuthStore()
const error     = ref(false)
const errorMessage = ref('Terjadi kesalahan saat login dengan Google.')

onMounted(() => {
  const params = new URLSearchParams(window.location.search)
  const token  = params.get('token')
  const userRaw = params.get('user')
  const err    = params.get('error')

  // Handle error dari backend
  if (err) {
    error.value = true
    errorMessage.value =
      err === 'google_failed'      ? 'Login Google gagal. Silakan coba lagi.' :
      err === 'account_suspended'  ? 'Akun Anda telah dinonaktifkan. Hubungi administrator.' :
      'Terjadi kesalahan. Silakan coba lagi.'
    return
  }

  // Simpan token dan user ke store
  if (token && userRaw) {
    try {
      const user = JSON.parse(decodeURIComponent(userRaw))
      authStore.setTokenAndUser(token, user)

      // Redirect sesuai role
      const dest =
        user.role === 'admin'  ? '/admin/dashboard'  :
        user.role === 'driver' ? '/driver/dashboard' :
        '/user/dashboard'

      router.replace(dest)
    } catch {
      error.value = true
    }
  } else {
    error.value = true
  }
})

function goLogin() {
  router.replace('/login')
}
</script>

<style scoped>
.callback-wrapper {
  min-height: 100vh;
  background: #f0f4ff;
  display: flex; align-items: center; justify-content: center;
}
.callback-box {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 40px 48px;
  text-align: center;
  box-shadow: 0 4px 24px rgba(0,0,0,0.08);
  min-width: 320px;
}
.cb-spinner {
  width: 40px; height: 40px;
  border: 3px solid #e2e8f0;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 20px;
}
@keyframes spin { to { transform: rotate(360deg); } }

.cb-icon {
  width: 48px; height: 48px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; font-weight: 700;
  margin: 0 auto 16px;
}
.cb-icon-error { background: #fee2e2; color: #dc2626; }

.cb-title { font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 6px; }
.cb-sub   { font-size: 13px; color: #64748b; margin-bottom: 20px; }

.cb-btn {
  padding: 9px 24px;
  background: #2563eb; color: #ffffff;
  font-size: 13px; font-weight: 600;
  border: none; border-radius: 8px; cursor: pointer;
  transition: background 0.15s;
}
.cb-btn:hover { background: #1d4ed8; }
</style>