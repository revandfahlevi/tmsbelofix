import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/lib/axios'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<any>(null)
  const token = ref<string | null>(null)
  const loading = ref(false)
  const error = ref('')

async function login(email: string, password: string) {
  loading.value = true
  error.value = ''
  try {
    const res = await api.post('/auth/login', { email, password })
    
    // Handle jika response masih string
    const rawData = typeof res.data === 'string' 
      ? JSON.parse(res.data.replace(/^=/, '')) 
      : res.data

    const data = rawData.data
    token.value = data.access_token
    user.value = data.user
    localStorage.setItem('tms_token', token.value!)
    localStorage.setItem('tms_user', JSON.stringify(data.user))
    return true
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Login gagal'
    return false
  } finally {
    loading.value = false
  }
}

  async function logout() {
    try { await api.post('/auth/logout') } catch {}
    user.value = null
    token.value = null
    localStorage.removeItem('tms_token')
    localStorage.removeItem('tms_user')
  }

  function loadFromStorage() {
    const t = localStorage.getItem('tms_token')
    const u = localStorage.getItem('tms_user')
    if (t && u) {
      token.value = t
      user.value = JSON.parse(u)
    }
  }

  const isLoggedIn = () => !!token.value

  return { user, token, loading, error, login, logout, loadFromStorage, isLoggedIn }
})