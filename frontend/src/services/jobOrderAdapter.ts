import { MOCK_JOB_ORDERS } from '@/lib/mockData'

// ============================================
// INTERFACE — kontrak data yang TMS gunakan
// Tidak berubah apapun ERP-nya
// ============================================
export interface JobOrderData {
  id: number | string
  job_number: string
  customer_name: string
  customer_phone?: string
  customer_email?: string
  origin_address: string
  origin_city: string
  destination_address: string
  destination_city: string
  cargo_type: string
  cargo_weight_kg?: number
  cargo_volume_m3?: number
  cargo_description?: string
  status: string
  priority: string
  estimated_cost?: number
  payment_status: string
  pickup_scheduled_at?: string
  delivery_scheduled_at?: string
  driver?: { id: number; name: string } | null
  created_at: string
}

export interface CreateJobOrderData {
  customer_name: string
  customer_phone?: string
  origin_address: string
  origin_city: string
  destination_address: string
  destination_city: string
  cargo_type: string
  cargo_weight_kg?: number
  priority?: string
  estimated_cost?: number
  pickup_scheduled_at?: string
  delivery_scheduled_at?: string
  notes?: string
}

// ============================================
// ADAPTER INTERFACE — semua adapter wajib implement ini
// ============================================
interface JobOrderAdapter {
  fetchAll(params?: Record<string, any>): Promise<JobOrderData[]>
  fetchOne(id: number | string): Promise<JobOrderData>
  create(payload: CreateJobOrderData): Promise<JobOrderData>
  updateStatus(id: number | string, status: string, notes?: string): Promise<void>
  delete(id: number | string): Promise<void>
}

// ============================================
// ADAPTER 1: MOCK (aktif sekarang)
// ============================================
class MockAdapter implements JobOrderAdapter {
  async fetchAll(params?: Record<string, any>): Promise<JobOrderData[]> {
    await delay(300)
    let data = [...MOCK_JOB_ORDERS]
    if (params?.status) data = data.filter(j => j.status === params.status)
    if (params?.priority) data = data.filter(j => j.priority === params.priority)
    if (params?.search) {
      const q = params.search.toLowerCase()
      data = data.filter(j =>
        j.job_number.toLowerCase().includes(q) ||
        j.customer_name.toLowerCase().includes(q)
      )
    }
    return data
  }

  async fetchOne(id: number | string): Promise<JobOrderData> {
    await delay(200)
    const found = MOCK_JOB_ORDERS.find(j => j.id === id)
    if (!found) throw new Error('Job order not found')
    return found
  }

  async create(payload: CreateJobOrderData): Promise<JobOrderData> {
    await delay(400)
    return {
      ...payload,
      id: Date.now(),
      job_number: `JO-2026-${String(Math.floor(Math.random() * 9000) + 1000)}`,
      status: 'draft',
      payment_status: 'unpaid',
      driver: null,
      created_at: new Date().toISOString(),
    }
  }

  async updateStatus(id: number | string, status: string): Promise<void> {
    await delay(200)
    // mock — tidak perlu persist
  }

  async delete(id: number | string): Promise<void> {
    await delay(200)
  }
}

// ============================================
// ADAPTER 2: TMS API (Laravel backend kita)
// ============================================
class TMSApiAdapter implements JobOrderAdapter {
  async fetchAll(params?: Record<string, any>): Promise<JobOrderData[]> {
    const { default: api } = await import('@/lib/axios')
    const res = await api.get('/job-orders', { params })
    const raw = typeof res.data === 'string'
      ? JSON.parse(res.data.replace(/^=/, ''))
      : res.data
    const d = raw.data
    return d?.data ?? (Array.isArray(d) ? d : [])
  }

  async fetchOne(id: number | string): Promise<JobOrderData> {
    const { default: api } = await import('@/lib/axios')
    const res = await api.get(`/job-orders/${id}`)
    const raw = typeof res.data === 'string'
      ? JSON.parse(res.data.replace(/^=/, ''))
      : res.data
    return raw.data
  }

  async create(payload: CreateJobOrderData): Promise<JobOrderData> {
    const { default: api } = await import('@/lib/axios')
    const res = await api.post('/job-orders', payload)
    const raw = typeof res.data === 'string'
      ? JSON.parse(res.data.replace(/^=/, ''))
      : res.data
    return raw.data
  }

  async updateStatus(id: number | string, status: string, notes?: string): Promise<void> {
    const { default: api } = await import('@/lib/axios')
    await api.patch(`/job-orders/${id}/status`, { status, notes })
  }

  async delete(id: number | string): Promise<void> {
    const { default: api } = await import('@/lib/axios')
    await api.delete(`/job-orders/${id}`)
  }
}

// ============================================
// ADAPTER 3: SAP (siap, tinggal isi config)
// ============================================
class SAPAdapter implements JobOrderAdapter {
  private baseURL = import.meta.env.VITE_SAP_BASE_URL || ''

  // Mapping SAP field → TMS field
  // Sesuaikan dengan schema SAP yang diberikan tim SAP nanti
  private mapFromSAP(sap: any): JobOrderData {
    return {
      id:                   sap.DeliveryOrder        ?? sap.SalesOrder,
      job_number:           sap.DeliveryOrder        ?? sap.SalesOrder,
      customer_name:        sap.SoldToPartyName      ?? sap.CustomerName,
      customer_phone:       sap.CustomerPhone,
      customer_email:       sap.CustomerEmail,
      origin_address:       sap.ShippingPointAddress ?? sap.PickupAddress,
      origin_city:          sap.ShippingPointCity    ?? sap.OriginCity,
      destination_address:  sap.ShipToPartyAddress   ?? sap.DeliveryAddress,
      destination_city:     sap.ShipToPartyCity      ?? sap.DestinationCity,
      cargo_type:           sap.MaterialGroup        ?? 'General Cargo',
      cargo_weight_kg:      sap.TotalNetWeight,
      cargo_volume_m3:      sap.TotalVolume,
      cargo_description:    sap.DeliveryNotes,
      status:               this.mapStatus(sap.OverallDeliveryStatus),
      priority:             this.mapPriority(sap.Priority),
      estimated_cost:       sap.NetAmount,
      payment_status:       sap.PaymentStatus ?? 'unpaid',
      pickup_scheduled_at:  sap.PlannedGoodsIssueDate,
      delivery_scheduled_at: sap.DeliveryDate,
      driver:               null,
      created_at:           sap.CreationDate ?? new Date().toISOString(),
    }
  }

  private mapStatus(sapStatus: string): string {
    // Sesuaikan kode status SAP dengan TMS
    const map: Record<string, string> = {
      'A': 'pending',
      'B': 'assigned',
      'C': 'in_transit',
      'D': 'completed',
      'E': 'cancelled',
    }
    return map[sapStatus] ?? 'pending'
  }

  private mapPriority(sapPriority: string): string {
    const map: Record<string, string> = {
      '1': 'urgent',
      '2': 'high',
      '3': 'normal',
      '4': 'low',
    }
    return map[sapPriority] ?? 'normal'
  }

  async fetchAll(params?: Record<string, any>): Promise<JobOrderData[]> {
    // TODO: Isi saat SAP sudah siap
    // const res = await sapApi.get('/DeliveryOrders', { params })
    // return res.data.value.map(this.mapFromSAP.bind(this))
    throw new Error('SAP adapter belum dikonfigurasi')
  }

  async fetchOne(id: number | string): Promise<JobOrderData> {
    // const res = await sapApi.get(`/DeliveryOrders('${id}')`)
    // return this.mapFromSAP(res.data)
    throw new Error('SAP adapter belum dikonfigurasi')
  }

  async create(payload: CreateJobOrderData): Promise<JobOrderData> {
    // SAP biasanya read-only dari sisi TMS
    // Order dibuat di SAP, TMS hanya consume
    throw new Error('Create order dilakukan di SAP')
  }

  async updateStatus(id: number | string, status: string): Promise<void> {
    // const res = await sapApi.patch(`/DeliveryOrders('${id}')`, { Status: status })
    throw new Error('SAP adapter belum dikonfigurasi')
  }

  async delete(id: number | string): Promise<void> {
    throw new Error('Delete tidak diizinkan via SAP adapter')
  }
}

// ============================================
// ADAPTER 4: Odoo (template, jika ternyata pakai Odoo)
// ============================================
class OdooAdapter implements JobOrderAdapter {
  async fetchAll(params?: Record<string, any>): Promise<JobOrderData[]> {
    // Odoo pakai JSON-RPC
    // const res = await axios.post(`${ODOO_URL}/web/dataset/call_kw`, {
    //   jsonrpc: '2.0', method: 'call',
    //   params: {
    //     model: 'stock.picking',
    //     method: 'search_read',
    //     args: [[['state', '!=', 'cancel']]],
    //     kwargs: { fields: ['name', 'partner_id', ...], limit: 50 }
    //   }
    // })
    // return res.data.result.map(this.mapFromOdoo)
    throw new Error('Odoo adapter belum dikonfigurasi')
  }

  async fetchOne(id: number | string): Promise<JobOrderData> {
    throw new Error('Odoo adapter belum dikonfigurasi')
  }

  async create(payload: CreateJobOrderData): Promise<JobOrderData> {
    throw new Error('Odoo adapter belum dikonfigurasi')
  }

  async updateStatus(id: number | string, status: string): Promise<void> {
    throw new Error('Odoo adapter belum dikonfigurasi')
  }

  async delete(id: number | string): Promise<void> {
    throw new Error('Odoo adapter belum dikonfigurasi')
  }
}

// ============================================
// FACTORY — satu tempat untuk ganti adapter
// Cukup ubah baris ini saat mau swap ERP
// ============================================
type AdapterType = 'mock' | 'tms-api' | 'sap' | 'odoo'

const ACTIVE_ADAPTER: AdapterType = 'tms-api' // ← GANTI INI SAJA

function createAdapter(): JobOrderAdapter {
  switch (ACTIVE_ADAPTER) {
    case 'mock':    return new MockAdapter()
    case 'tms-api': return new TMSApiAdapter()
    case 'sap':     return new SAPAdapter()
    case 'odoo':    return new OdooAdapter()
    default:        return new MockAdapter()
  }
}

export const jobOrderAdapter = createAdapter()

// Helper
function delay(ms: number) {
  return new Promise(resolve => setTimeout(resolve, ms))
}