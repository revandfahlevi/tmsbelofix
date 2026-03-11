import { ref } from 'vue'
import api from '@/lib/axios'

// ✅ State di luar — singleton
const inboundItems  = ref<any[]>([])
const inventoryItems = ref<any[]>([])
const outboundItems = ref<any[]>([])
const loading       = ref(false)
const error         = ref('')

export function useWarehouse() {

  // ── INBOUND ────────────────────────────────────────────────────

  async function fetchInbound() {
    loading.value = true
    error.value = ''
    try {
      const res = await api.get('/warehouse/inbound')
      const raw = typeof res.data === 'string'
        ? JSON.parse(res.data.replace(/^=/, ''))
        : res.data
      inboundItems.value = raw.data?.data ?? raw.data ?? []
    } catch {
      // fallback mock
      inboundItems.value = [
        { id: 1, inbound_number: 'INB-2026-00001', supplier_name: 'PT Supplier Jaya', item_name: 'Kardus Packaging', quantity: 500, unit: 'pcs', received_at: '2026-03-10', status: 'received', notes: '' },
        { id: 2, inbound_number: 'INB-2026-00002', supplier_name: 'CV Maju Bersama', item_name: 'Bubble Wrap Roll', quantity: 100, unit: 'kg', received_at: '2026-03-11', status: 'pending', notes: 'Cek kondisi dulu' },
        { id: 3, inbound_number: 'INB-2026-00003', supplier_name: 'PT Logistik Prima', item_name: 'Pallet Kayu', quantity: 50, unit: 'pcs', received_at: '2026-03-11', status: 'pending', notes: '' },
      ]
    } finally {
      loading.value = false
    }
  }

  async function createInbound(payload: any) {
    loading.value = true
    error.value = ''
    try {
      const res = await api.post('/warehouse/inbound', payload)
      const raw = typeof res.data === 'string'
        ? JSON.parse(res.data.replace(/^=/, ''))
        : res.data
      inboundItems.value.unshift(raw.data ?? raw)
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal membuat inbound'
      return false
    } finally {
      loading.value = false
    }
  }

  async function receiveInbound(id: any) {
    error.value = ''
    try {
      await api.patch(`/warehouse/inbound/${id}/receive`)
      const idx = inboundItems.value.findIndex(i => i.id === id)
      if (idx !== -1) inboundItems.value[idx].status = 'received'
      return true
    } catch {
      // update lokal saja kalau API belum ready
      const idx = inboundItems.value.findIndex(i => i.id === id)
      if (idx !== -1) inboundItems.value[idx].status = 'received'
      return true
    }
  }

  // ── INVENTORY ──────────────────────────────────────────────────

  async function fetchInventory() {
    loading.value = true
    error.value = ''
    try {
      const res = await api.get('/warehouse/inventory')
      const raw = typeof res.data === 'string'
        ? JSON.parse(res.data.replace(/^=/, ''))
        : res.data
      inventoryItems.value = raw.data?.data ?? raw.data ?? []
    } catch {
      inventoryItems.value = [
        { id: 1, sku: 'SKU-001', name: 'Kardus Packaging', category: 'Packaging', stock: 450, min_stock: 100, unit: 'pcs', location: 'Rak A-01' },
        { id: 2, sku: 'SKU-002', name: 'Bubble Wrap Roll', category: 'Packaging', stock: 80, min_stock: 100, unit: 'kg', location: 'Rak A-02' },
        { id: 3, sku: 'SKU-003', name: 'Pallet Kayu', category: 'Alat', stock: 50, min_stock: 20, unit: 'pcs', location: 'Rak B-01' },
        { id: 4, sku: 'SKU-004', name: 'Tali Rafia', category: 'Packaging', stock: 5, min_stock: 50, unit: 'kg', location: 'Rak A-03' },
        { id: 5, sku: 'SKU-005', name: 'Forklift Battery', category: 'Alat', stock: 10, min_stock: 5, unit: 'pcs', location: 'Rak C-01' },
      ]
    } finally {
      loading.value = false
    }
  }

  async function createInventory(payload: any) {
    loading.value = true
    error.value = ''
    try {
      const res = await api.post('/warehouse/inventory', payload)
      const raw = typeof res.data === 'string'
        ? JSON.parse(res.data.replace(/^=/, ''))
        : res.data
      inventoryItems.value.unshift(raw.data ?? raw)
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal membuat item'
      return false
    } finally {
      loading.value = false
    }
  }

  async function adjustStock(id: any, type: string, amount: number) {
    error.value = ''
    try {
      await api.patch(`/warehouse/inventory/${id}/adjust`, { type, amount })
      const idx = inventoryItems.value.findIndex(i => i.id === id)
      if (idx !== -1) {
        const item = inventoryItems.value[idx]
        if (type === 'add') item.stock += amount
        else if (type === 'subtract') item.stock = Math.max(0, item.stock - amount)
        else if (type === 'set') item.stock = amount
      }
      return true
    } catch {
      // update lokal saja kalau API belum ready
      const idx = inventoryItems.value.findIndex(i => i.id === id)
      if (idx !== -1) {
        const item = inventoryItems.value[idx]
        if (type === 'add') item.stock += amount
        else if (type === 'subtract') item.stock = Math.max(0, item.stock - amount)
        else if (type === 'set') item.stock = amount
      }
      return true
    }
  }

  // ── OUTBOUND ───────────────────────────────────────────────────

  async function fetchOutbound() {
    loading.value = true
    error.value = ''
    try {
      const res = await api.get('/warehouse/outbound')
      const raw = typeof res.data === 'string'
        ? JSON.parse(res.data.replace(/^=/, ''))
        : res.data
      outboundItems.value = raw.data?.data ?? raw.data ?? []
    } catch {
      outboundItems.value = [
        { id: 1, outbound_number: 'OUT-2026-00001', job_order_number: 'JO-2026-0001', destination: 'PT Sinar Jaya Abadi', item_name: 'Kardus Packaging', quantity: 200, unit: 'pcs', shipped_at: '2026-03-10', status: 'shipped', notes: '' },
        { id: 2, outbound_number: 'OUT-2026-00002', job_order_number: 'JO-2026-0002', destination: 'CV Maju Bersama', item_name: 'Pallet Kayu', quantity: 20, unit: 'pcs', shipped_at: '2026-03-11', status: 'processing', notes: '' },
        { id: 3, outbound_number: 'OUT-2026-00003', job_order_number: null, destination: 'PT Logistik Prima', item_name: 'Bubble Wrap Roll', quantity: 50, unit: 'kg', shipped_at: '2026-03-12', status: 'pending', notes: '' },
      ]
    } finally {
      loading.value = false
    }
  }

  async function createOutbound(payload: any) {
    loading.value = true
    error.value = ''
    try {
      const res = await api.post('/warehouse/outbound', payload)
      const raw = typeof res.data === 'string'
        ? JSON.parse(res.data.replace(/^=/, ''))
        : res.data
      outboundItems.value.unshift(raw.data ?? raw)
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal membuat outbound'
      return false
    } finally {
      loading.value = false
    }
  }

  async function updateOutboundStatus(id: any, status: string) {
    error.value = ''
    try {
      await api.patch(`/warehouse/outbound/${id}/status`, { status })
      const idx = outboundItems.value.findIndex(i => i.id === id)
      if (idx !== -1) outboundItems.value[idx].status = status
      return true
    } catch {
      const idx = outboundItems.value.findIndex(i => i.id === id)
      if (idx !== -1) outboundItems.value[idx].status = status
      return true
    }
  }

  return {
    inboundItems,
    inventoryItems,
    outboundItems,
    loading,
    error,
    fetchInbound,
    createInbound,
    receiveInbound,
    fetchInventory,
    createInventory,
    adjustStock,
    fetchOutbound,
    createOutbound,
    updateOutboundStatus,
  }
}