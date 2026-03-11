import { ref } from 'vue'
import api from '@/lib/axios'

// ✅ State di luar function — jadi singleton, tidak reset
const vehicles    = ref<any[]>([])   // ← ganti dari carriers
const assignments = ref<any[]>([])
const loading     = ref(false)
const error       = ref('')

export function useCarrierAssignment() {

  // ← ganti dari fetchCarriers → fetchVehicles, endpoint /carriers/available-vehicles
  async function fetchVehicles() {
    loading.value = true
    error.value   = ''
    try {
      const res      = await api.get('/carriers/available-vehicles')
      const raw      = typeof res.data === 'string'
        ? JSON.parse(res.data.replace(/^=/, ''))
        : res.data
      vehicles.value = raw.data ?? []
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal memuat kendaraan'
    } finally {
      loading.value = false
    }
  }

  async function fetchAssignments() {
    loading.value = true
    error.value   = ''
    try {
      const res      = await api.get('/admin/carrier-assignments')
      const raw      = typeof res.data === 'string'
        ? JSON.parse(res.data.replace(/^=/, ''))
        : res.data
      assignments.value = raw.data?.data ?? raw.data ?? []  // ✅ langsung assign, bukan push
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal memuat assignments'
    } finally {
      loading.value = false
    }
  }

  async function createAssignment(payload: any) {
    loading.value = true
    error.value   = ''
    try {
      await api.post('/admin/carrier-assignments', payload)
      await fetchAssignments() // ✅ fetch ulang dari DB, tidak manual unshift
      return true
    } catch (e: any) {
      const raw   = e.response?.data
      error.value = (typeof raw === 'string'
        ? JSON.parse(raw.replace(/^=/, '')).message
        : raw?.message) || 'Gagal membuat assignment'
      return false
    } finally {
      loading.value = false
    }
  }

  async function updateAssignmentStatus(id: string | number, status: string) {
    error.value = ''
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

  async function sendSpk(id: string | number) {
    error.value = ''
    try {
      await api.post(`/admin/carrier-assignments/${id}/send-spk`)
      const idx = assignments.value.findIndex(a => a.id === id)
      if (idx !== -1) assignments.value[idx].spk_sent = true
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal kirim SPK'
      return false
    }
  }

  async function showAssignment(id: string | number) {
    error.value = ''
    try {
      const res = await api.get(`/admin/carrier-assignments/${id}`)
      const raw = typeof res.data === 'string'
        ? JSON.parse(res.data.replace(/^=/, ''))
        : res.data
      return raw.data ?? raw
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal memuat detail assignment'
      return null
    }
  }

  return {
    vehicles,        // ← ganti dari carriers
    assignments,
    loading,
    error,
    fetchVehicles,   // ← ganti dari fetchCarriers
    fetchAssignments,
    createAssignment,
    updateAssignmentStatus,
    sendSpk,
    showAssignment,
  }
}