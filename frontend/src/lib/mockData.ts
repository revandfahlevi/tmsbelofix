import type { JobOrder, Carrier, Driver, CarrierAssignment, Invoice, VehiclePosition, Notification, POD } from '@/lib/api';

export const MOCK_USERS = [
  { id: '1', name: 'Admin Logistik', email: 'admin@tms.com', password: 'admin123', role: 'admin' as const, avatar: 'AL', phone: '08112345678' },
  { id: '2', name: 'Budi Santoso', email: 'driver@tms.com', password: 'driver123', role: 'driver' as const, avatar: 'BS', phone: '08223456789' },
  { id: '3', name: 'Sari Dewi', email: 'user@tms.com', password: 'user123', role: 'user' as const, avatar: 'SD', phone: '08334567890' },
];

export const MOCK_JOB_ORDERS = [
  {
    id: 1,
    job_number: 'JO-2026-0001',
    customer_name: 'PT Sinar Jaya Abadi',
    customer_phone: '08123456789',
    customer_email: 'sinar@jaya.com',
    origin_address: 'Jl. Industri Raya No. 10, Cakung',
    origin_city: 'Jakarta',
    destination_address: 'Jl. Raya Darmo No. 5, Wonokromo',
    destination_city: 'Surabaya',
    cargo_type: 'General Cargo',
    cargo_weight_kg: 2500,
    cargo_volume_m3: 15,
    cargo_description: 'Barang elektronik',
    status: 'pending',
    priority: 'normal',
    estimated_cost: 5500000,
    payment_status: 'unpaid',
    pickup_scheduled_at: '2026-03-10T08:00:00',
    delivery_scheduled_at: '2026-03-13T17:00:00',
    driver: null,
    created_at: '2026-03-09T07:00:00',
  },
  {
    id: 2,
    job_number: 'JO-2026-0002',
    customer_name: 'CV Maju Bersama',
    customer_phone: '08567891234',
    customer_email: 'maju@bersama.com',
    origin_address: 'Jl. Margonda Raya No. 22',
    origin_city: 'Depok',
    destination_address: 'Jl. Setiabudi No. 10',
    destination_city: 'Bandung',
    cargo_type: 'Furniture',
    cargo_weight_kg: 800,
    cargo_volume_m3: 20,
    cargo_description: 'Meja dan kursi kantor',
    status: 'assigned',
    priority: 'high',
    estimated_cost: 3200000,
    payment_status: 'paid',
    pickup_scheduled_at: '2026-03-11T09:00:00',
    delivery_scheduled_at: '2026-03-13T15:00:00',
    driver: { id: 2, name: 'Budi Santoso' },
    created_at: '2026-03-09T08:00:00',
  },
  {
    id: 3,
    job_number: 'JO-2026-0003',
    customer_name: 'PT Nusantara Logistics',
    customer_phone: '08211223344',
    customer_email: 'nusantara@logistics.com',
    origin_address: 'Kawasan Industri MM2100',
    origin_city: 'Bekasi',
    destination_address: 'Jl. Pemuda No. 45',
    destination_city: 'Semarang',
    cargo_type: 'Spare Parts',
    cargo_weight_kg: 1200,
    cargo_volume_m3: 8,
    cargo_description: 'Spare part mesin industri',
    status: 'in_transit',
    priority: 'urgent',
    estimated_cost: 4800000,
    payment_status: 'paid',
    pickup_scheduled_at: '2026-03-09T06:00:00',
    delivery_scheduled_at: '2026-03-11T12:00:00',
    driver: { id: 3, name: 'Joko Widiarso' },
    created_at: '2026-03-08T20:00:00',
  },
  {
    id: 4,
    job_number: 'JO-2026-0004',
    customer_name: 'PT Agro Makmur',
    customer_phone: '08399887766',
    customer_email: 'agro@makmur.com',
    origin_address: 'Jl. Raya Bogor KM 35',
    origin_city: 'Bogor',
    destination_address: 'Pasar Induk Kramat Jati',
    destination_city: 'Jakarta',
    cargo_type: 'Fresh Produce',
    cargo_weight_kg: 5000,
    cargo_volume_m3: 25,
    cargo_description: 'Sayuran dan buah segar',
    status: 'completed',
    priority: 'urgent',
    estimated_cost: 2100000,
    payment_status: 'paid',
    pickup_scheduled_at: '2026-03-09T04:00:00',
    delivery_scheduled_at: '2026-03-09T14:00:00',
    driver: { id: 2, name: 'Budi Santoso' },
    created_at: '2026-03-08T18:00:00',
  },
  {
    id: 5,
    job_number: 'JO-2026-0005',
    customer_name: 'PT Teknologi Masa Depan',
    customer_phone: '08155667788',
    customer_email: 'tech@masadepan.com',
    origin_address: 'Jl. TB Simatupang No. 88',
    origin_city: 'Jakarta',
    destination_address: 'Jl. Malioboro No. 1',
    destination_city: 'Yogyakarta',
    cargo_type: 'Electronics',
    cargo_weight_kg: 300,
    cargo_volume_m3: 3,
    cargo_description: 'Laptop dan peralatan IT',
    status: 'draft',
    priority: 'normal',
    estimated_cost: 1800000,
    payment_status: 'unpaid',
    pickup_scheduled_at: '2026-03-12T10:00:00',
    delivery_scheduled_at: '2026-03-14T16:00:00',
    driver: null,
    created_at: '2026-03-09T09:00:00',
  },
  {
    id: 6,
    job_number: 'JO-2026-0006',
    customer_name: 'PT Kimia Farma Tbk',
    customer_phone: '08100112233',
    customer_email: 'logistik@kimiafarma.com',
    origin_address: 'Jl. Veteran No. 9',
    origin_city: 'Jakarta',
    destination_address: 'Jl. Diponegoro No. 71',
    destination_city: 'Surabaya',
    cargo_type: 'Pharmaceutical',
    cargo_weight_kg: 450,
    cargo_volume_m3: 5,
    cargo_description: 'Obat-obatan dan alat kesehatan',
    status: 'in_progress',
    priority: 'high',
    estimated_cost: 6200000,
    payment_status: 'paid',
    pickup_scheduled_at: '2026-03-09T07:00:00',
    delivery_scheduled_at: '2026-03-10T18:00:00',
    driver: { id: 3, name: 'Joko Widiarso' },
    created_at: '2026-03-09T06:00:00',
  },
]

export const MOCK_DRIVERS = [
  { id: 2, name: 'Budi Santoso',  status: 'on_duty',   total_trips: 142, rating: 4.8 },
  { id: 3, name: 'Joko Widiarso', status: 'on_duty',   total_trips: 98,  rating: 4.6 },
  { id: 4, name: 'Agus Salim',    status: 'available',  total_trips: 75,  rating: 4.9 },
  { id: 5, name: 'Rudi Hartono',  status: 'off_duty',   total_trips: 210, rating: 4.7 },
]

export const DASHBOARD_STATS = {
  totalOrders: 6,
  activeOrders: 3,
  completedToday: 1,
  activeDrivers: 2,
  avgDeliveryTime: 18.5,
  pendingOrders: 1,
}

export const MOCK_CARRIERS: Carrier[] = [
  { id: 'V001', name: 'Truk Box 8 Ton', type: 'Box Truck', plate_number: 'B 1234 TMS', capacity: 8000, status: 'busy' },
  { id: 'V002', name: 'Truk Fuso 15 Ton', type: 'Fuso', plate_number: 'B 5678 TMS', capacity: 15000, status: 'busy' },
  { id: 'V003', name: 'Pick Up 1.5 Ton', type: 'Pick Up', plate_number: 'B 9012 TMS', capacity: 1500, status: 'available' },
  { id: 'V004', name: 'Tronton 24 Ton', type: 'Tronton', plate_number: 'B 3456 TMS', capacity: 24000, status: 'busy' },
  { id: 'V005', name: 'Truk Box 6 Ton', type: 'Box Truck', plate_number: 'B 7890 TMS', capacity: 6000, status: 'available' },
  { id: 'V006', name: 'Container 40ft', type: 'Container', plate_number: 'B 2345 TMS', capacity: 30000, status: 'maintenance' },
];


export const MOCK_CARRIER_ASSIGNMENTS: CarrierAssignment[] = [
  { id: 'CA001', job_order_id: 'JO002', carrier_id: 'V001', driver_id: '2', status: 'pending', assigned_at: '2025-03-05T08:00:00Z', job_order: MOCK_JOB_ORDERS[1] },
  { id: 'CA002', job_order_id: 'JO006', carrier_id: 'V005', driver_id: '2', status: 'pending', assigned_at: '2025-03-05T09:30:00Z', job_order: MOCK_JOB_ORDERS[5] },
];

export const MOCK_INVOICES: Invoice[] = [
  { id: 'INV001', invoice_number: 'INV-2025-001', job_order_id: 'JO004', customer_name: 'UD Berkah Jaya', amount: 1850000, tax: 185000, total: 2035000, status: 'paid', due_date: '2025-03-18', issued_at: '2025-03-04T17:00:00Z', items: [{ description: 'Biaya Pengiriman JKT-Depok', qty: 1, unit_price: 1500000, total: 1500000 }, { description: 'Biaya Bongkar Muat', qty: 1, unit_price: 350000, total: 350000 }] },
  { id: 'INV002', invoice_number: 'INV-2025-002', job_order_id: 'JO001', customer_name: 'PT Maju Bersama', amount: 4200000, tax: 420000, total: 4620000, status: 'sent', due_date: '2025-03-20', issued_at: '2025-03-05T08:00:00Z', items: [{ description: 'Biaya Pengiriman JKT-Bekasi', qty: 1, unit_price: 3500000, total: 3500000 }, { description: 'Asuransi Kargo', qty: 1, unit_price: 700000, total: 700000 }] },
  { id: 'INV003', invoice_number: 'INV-2025-003', job_order_id: 'JO003', customer_name: 'PT Teknologi Nusantara', amount: 8500000, tax: 850000, total: 9350000, status: 'draft', due_date: '2025-03-25', issued_at: '2025-03-05T09:00:00Z', items: [{ description: 'Biaya Pengiriman JKT-Bandung', qty: 1, unit_price: 7000000, total: 7000000 }, { description: 'Biaya Khusus Mesin Berat', qty: 1, unit_price: 1500000, total: 1500000 }] },
  { id: 'INV004', invoice_number: 'INV-2025-004', job_order_id: 'JO002', customer_name: 'CV Sumber Makmur', amount: 3200000, tax: 320000, total: 3520000, status: 'overdue', due_date: '2025-03-01', issued_at: '2025-02-20T10:00:00Z', items: [{ description: 'Biaya Pengiriman Priok-Tangerang', qty: 1, unit_price: 2800000, total: 2800000 }, { description: 'Handling Makanan & Minuman', qty: 1, unit_price: 400000, total: 400000 }] },
];

export const MOCK_GPS_POSITIONS: VehiclePosition[] = [
  { vehicle_id: 'V001', driver_name: 'Budi Santoso', plate_number: 'B 1234 TMS', lat: -6.2100, lng: 106.9200, speed: 65, heading: 120, status: 'in_transit', last_updated: new Date().toISOString() },
  { vehicle_id: 'V002', driver_name: 'Agus Wijaya', plate_number: 'B 5678 TMS', lat: -6.3500, lng: 107.2000, speed: 80, heading: 95, status: 'in_transit', last_updated: new Date().toISOString() },
  { vehicle_id: 'V004', driver_name: 'Hendra Kurniawan', plate_number: 'B 3456 TMS', lat: -6.2900, lng: 106.8500, speed: 45, heading: 200, status: 'in_transit', last_updated: new Date().toISOString() },
];

export const MOCK_NOTIFICATIONS: Notification[] = [
  { id: 'N001', title: '🚨 Pesanan Baru - URGENT', message: 'Job Order JO-2025-002 dari CV Sumber Makmur telah ditetapkan ke Anda.', type: 'assignment', read: false, created_at: new Date(Date.now() - 2 * 60000).toISOString() },
  { id: 'N002', title: '📦 Pesanan Baru', message: 'Job Order JO-2025-006 dari CV Abadi Sentosa telah ditetapkan ke Anda.', type: 'assignment', read: false, created_at: new Date(Date.now() - 15 * 60000).toISOString() },
  { id: 'N003', title: '✅ Pengiriman Selesai', message: 'JO-2025-004 telah berhasil dikirim ke UD Berkah Jaya.', type: 'delivery', read: true, created_at: new Date(Date.now() - 2 * 3600000).toISOString() },
  { id: 'N004', title: '⚠️ Peringatan Kapasitas', message: 'Kendaraan B 1234 TMS melebihi batas berat maksimum 10%.', type: 'alert', read: false, created_at: new Date(Date.now() - 30 * 60000).toISOString() },
  { id: 'N005', title: 'ℹ️ Pembaruan Sistem', message: 'Fitur GPS Tracking telah diperbarui ke versi terbaru.', type: 'info', read: true, created_at: new Date(Date.now() - 24 * 3600000).toISOString() },
];

export const MOCK_PODS: POD[] = [
  { id: 'POD001', job_order_id: 'JO004', signature_base64: '', recipient_name: 'Pak Andi Suprapto', recipient_id: 'KTP-3271234567', notes: 'Barang diterima dalam kondisi baik', captured_at: '2025-03-04T17:00:00Z', captured_by: 'Budi Santoso' },
];

export const MOCK_ROUTES = [
  {
    id: 'R001',
    name: 'Jakarta - Surabaya',
    waypoints: [
      { lat: -6.1231, lng: 106.8200, address: 'Jakarta', order: 1 },
      { lat: -6.9175, lng: 107.6191, address: 'Bandung', order: 2 },
      { lat: -7.2575, lng: 112.7521, address: 'Surabaya', order: 3 },
    ],
    total_distance: 780,
    estimated_duration: 12,
    optimized: true,
    job_order_id: 'JO001',
  },
  {
    id: 'R002',
    name: 'Jakarta - Semarang',
    waypoints: [
      { lat: -6.1231, lng: 106.8200, address: 'Jakarta', order: 1 },
      { lat: -6.9667, lng: 110.4167, address: 'Semarang', order: 2 },
    ],
    total_distance: 450,
    estimated_duration: 7,
    optimized: false,
    job_order_id: 'JO002',
  },
];

