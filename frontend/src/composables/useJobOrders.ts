import { ref } from 'vue'
import api from '@/lib/axios'

export function useJobOrders() {
  const jobOrders = ref<any[]>([])
  const loading = ref(false)
  const error = ref('')
  const pagination = ref({ current_page: 1, last_page: 1, total: 0 })

  async function fetchJobOrders(params?: Record<string, any>) {
    loading.value = true
    error.value = ''
    try {
      const res = await api.get('/job-orders', { params })
      const raw = typeof res.data === 'string'
        ? JSON.parse(res.data.replace(/^=/, ''))
        : res.data
      const d = raw.data
      // paginated response
      if (d?.data && Array.isArray(d.data)) {
        jobOrders.value = d.data
        pagination.value = {
          current_page: d.current_page,
          last_page: d.last_page,
          total: d.total,
        }
      } else {
        jobOrders.value = Array.isArray(d) ? d : []
      }
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal memuat job orders'
    } finally {
      loading.value = false
    }
  }

  async function createJobOrder(payload: any) {
    loading.value = true
    error.value = ''
    try {
      const res = await api.post('/job-orders', payload)
      const raw = typeof res.data === 'string'
        ? JSON.parse(res.data.replace(/^=/, ''))
        : res.data
      jobOrders.value.unshift(raw.data ?? raw)
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal membuat job order'
      return false
    } finally {
      loading.value = false
    }
  }

  async function updateStatus(id: number, status: string, notes?: string) {
    error.value = ''
    try {
      await api.patch(`/job-orders/${id}/status`, { status, notes })
      const idx = jobOrders.value.findIndex(j => j.id === id)
      if (idx !== -1) jobOrders.value[idx].status = status
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal update status'
      return false
    }
  }

  async function assignDriver(id: number, driverId: number) {
    error.value = ''
    try {
      await api.post(`/job-orders/${id}/assign-driver`, { driver_id: driverId })
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal assign driver'
      return false
    }
  }

  async function deleteJobOrder(id: number) {
    error.value = ''
    try {
      await api.delete(`/job-orders/${id}`)
      jobOrders.value = jobOrders.value.filter(j => j.id !== id)
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal menghapus'
      return false
    }
  }

  return {
    jobOrders, loading, error, pagination,
    fetchJobOrders, createJobOrder, updateStatus, assignDriver, deleteJobOrder
  }
}