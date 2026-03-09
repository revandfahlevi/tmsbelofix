import { ref } from 'vue'
import api from '@/lib/axios'

export function useCarrierAssignment() {
  const carriers = ref<any[]>([])
  const assignments = ref<any[]>([])
  const loading = ref(false)
  const error = ref('')

  async function fetchCarriers() {
    loading.value = true
    try {
      const res = await api.get('/admin/carriers')
      carriers.value = res.data.data ?? res.data
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal memuat carriers'
    } finally {
      loading.value = false
    }
  }

  async function fetchAssignments() {
    loading.value = true
    try {
      const res = await api.get('/admin/carrier-assignments')
      assignments.value = res.data.data ?? res.data
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal memuat assignments'
    } finally {
      loading.value = false
    }
  }

  async function createAssignment(payload: any) {
    loading.value = true
    try {
      const res = await api.post('/admin/carrier-assignments', payload)
      assignments.value.unshift(res.data.data ?? res.data)
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal membuat assignment'
      return false
    } finally {
      loading.value = false
    }
  }

  async function updateAssignmentStatus(id: string, status: string) {
    try {
      await api.patch(`/admin/carrier-assignments/${id}/status`, { status })
      const idx = assignments.value.findIndex(a => a.id === id)
      if (idx !== -1) assignments.value[idx].status = status
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal update status'
      return false
    }
  }

  async function sendSpk(id: string) {
    try {
      await api.post(`/admin/carrier-assignments/${id}/send-spk`)
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal kirim SPK'
      return false
    }
  }

  return {
    carriers, assignments, loading, error,
    fetchCarriers, fetchAssignments,
    createAssignment, updateAssignmentStatus, sendSpk
  }
}