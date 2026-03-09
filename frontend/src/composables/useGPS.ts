import { ref } from 'vue'
import api from '@/lib/axios'

export function useGPS() {
  const activeDrivers = ref<any[]>([])
  const tripHistory = ref<any[]>([])
  const loading = ref(false)
  const error = ref('')

  async function fetchActiveDrivers() {
    loading.value = true
    try {
      const res = await api.get('/gps/active-drivers')
      activeDrivers.value = res.data.data ?? res.data
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal memuat data GPS'
    } finally {
      loading.value = false
    }
  }

  async function updateLocation(lat: number, lng: number, jobOrderId?: string) {
    try {
      await api.post('/gps/location', {
        latitude: lat,
        longitude: lng,
        job_order_id: jobOrderId,
        timestamp: new Date().toISOString(),
      })
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal update lokasi'
      return false
    }
  }

  async function setOffline() {
    try {
      await api.post('/gps/offline')
      return true
    } catch { return false }
  }

  async function fetchTripHistory(jobOrderId: string) {
    loading.value = true
    try {
      const res = await api.get(`/gps/trip-history/${jobOrderId}`)
      tripHistory.value = res.data.data ?? res.data
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal memuat trip history'
    } finally {
      loading.value = false
    }
  }

  return {
    activeDrivers, tripHistory, loading, error,
    fetchActiveDrivers, updateLocation, setOffline, fetchTripHistory
  }
}