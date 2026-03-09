import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'
import { MOCK_PODS } from '@/lib/mockData'

const STORAGE_KEY = 'tms_pods'

export const usePodStore = defineStore('pod', () => {
  const saved = localStorage.getItem(STORAGE_KEY)
  const pods = ref<any[]>(saved ? JSON.parse(saved) : [...MOCK_PODS])

  watch(pods, (val) => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(val))
  }, { deep: true })

  window.addEventListener('storage', (e) => {
    if (e.key === STORAGE_KEY && e.newValue) {
      pods.value = JSON.parse(e.newValue)
    }
  })

  const withPhotoCount = computed(() => pods.value.filter((p: any) => p.photo_url).length)

  const todayCount = computed(() => {
    const today = new Date().toDateString()
    return pods.value.filter((p: any) => new Date(p.captured_at).toDateString() === today).length
  })

  const uniqueDrivers = computed(() =>
    new Set(pods.value.map((p: any) => p.captured_by)).size
  )

  function addPod(pod: any) {
    pods.value.unshift(pod)
  }

  return { pods, withPhotoCount, todayCount, uniqueDrivers, addPod }
})