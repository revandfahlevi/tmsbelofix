import { ref } from 'vue'
import api from '@/lib/axios'

export function useRoutePlanning() {
  const routes = ref<any[]>([])
  const loading = ref(false)
  const error = ref('')

  async function fetchRoutes() {
    loading.value = true
    error.value = ''
    try {
      const res = await api.get('/route-plans')
      routes.value = res.data.data ?? res.data
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal memuat route plans'
    } finally {
      loading.value = false
    }
  }

  async function createRoute(payload: any) {
    loading.value = true
    try {
      const res = await api.post('/route-plans', payload)
      routes.value.unshift(res.data.data ?? res.data)
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal membuat route'
      return false
    } finally {
      loading.value = false
    }
  }

  async function deleteRoute(id: string) {
    try {
      await api.delete(`/route-plans/${id}`)
      routes.value = routes.value.filter(r => r.id !== id)
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal menghapus route'
      return false
    }
  }

  async function updateStatus(id: string, status: string) {
    try {
      await api.patch(`/route-plans/${id}/status`, { status })
      const idx = routes.value.findIndex(r => r.id === id)
      if (idx !== -1) routes.value[idx].status = status
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal update status'
      return false
    }
  }

  async function optimizeRoute(id: string) {
    loading.value = true
    try {
      const res = await api.post('/route-plans/optimize', { route_plan_id: id })
      const idx = routes.value.findIndex(r => r.id === id)
      if (idx !== -1) routes.value[idx] = res.data.data ?? res.data
      return res.data.data ?? res.data
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal optimasi route'
      return null
    } finally {
      loading.value = false
    }
  }

  return { routes, loading, error, fetchRoutes, createRoute, deleteRoute, updateStatus, optimizeRoute }
}