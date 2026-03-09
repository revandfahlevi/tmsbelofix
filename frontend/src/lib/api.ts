/**
 * TMS API Service Layer
 * Ready to connect to Laravel backend
 * Base URL: set VITE_API_URL in .env or use default
 */

const BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

class ApiClient {
  private token: string | null = null;

  setToken(token: string) {
    this.token = token;
    localStorage.setItem('tms_token', token);
  }

  getToken(): string | null {
    return this.token || localStorage.getItem('tms_token');
  }

  clearToken() {
    this.token = null;
    localStorage.removeItem('tms_token');
  }

  private headers(): HeadersInit {
    const h: HeadersInit = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
    const t = this.getToken();
    if (t) h['Authorization'] = `Bearer ${t}`;
    return h;
  }

  async get<T>(path: string): Promise<T> {
    const res = await fetch(`${BASE_URL}${path}`, { headers: this.headers() });
    if (!res.ok) throw new Error(await res.text());
    return res.json();
  }

  async post<T>(path: string, body: unknown): Promise<T> {
    const res = await fetch(`${BASE_URL}${path}`, { method: 'POST', headers: this.headers(), body: JSON.stringify(body) });
    if (!res.ok) throw new Error(await res.text());
    return res.json();
  }

  async put<T>(path: string, body: unknown): Promise<T> {
    const res = await fetch(`${BASE_URL}${path}`, { method: 'PUT', headers: this.headers(), body: JSON.stringify(body) });
    if (!res.ok) throw new Error(await res.text());
    return res.json();
  }

  async patch<T>(path: string, body: unknown): Promise<T> {
    const res = await fetch(`${BASE_URL}${path}`, { method: 'PATCH', headers: this.headers(), body: JSON.stringify(body) });
    if (!res.ok) throw new Error(await res.text());
    return res.json();
  }

  async delete<T>(path: string): Promise<T> {
    const res = await fetch(`${BASE_URL}${path}`, { method: 'DELETE', headers: this.headers() });
    if (!res.ok) throw new Error(await res.text());
    return res.json();
  }
}

export const api = new ApiClient();

// ─── Auth Endpoints ──────────────────────────────────────────
export const authApi = {
  login: (email: string, password: string) => api.post<{ token: string; user: User }>('/auth/login', { email, password }),
  logout: () => api.post('/auth/logout', {}),
  me: () => api.get<User>('/auth/me'),
};

// ─── Job Order Endpoints ──────────────────────────────────────
export const jobOrderApi = {
  list: (params?: Record<string, string>) => api.get<PaginatedResponse<JobOrder>>(`/job-orders?${new URLSearchParams(params)}`),
  get: (id: string) => api.get<JobOrder>(`/job-orders/${id}`),
  create: (data: Partial<JobOrder>) => api.post<JobOrder>('/job-orders', data),
  update: (id: string, data: Partial<JobOrder>) => api.put<JobOrder>(`/job-orders/${id}`, data),
  delete: (id: string) => api.delete(`/job-orders/${id}`),
  updateStatus: (id: string, status: string) => api.patch<JobOrder>(`/job-orders/${id}/status`, { status }),
};

// ─── Route Planning Endpoints ─────────────────────────────────
export const routePlanApi = {
  list: () => api.get<Route[]>('/routes'),
  get: (id: string) => api.get<Route>(`/routes/${id}`),
  create: (data: Partial<Route>) => api.post<Route>('/routes', data),
  optimize: (id: string) => api.post<Route>(`/routes/${id}/optimize`, {}),
  delete: (id: string) => api.delete(`/routes/${id}`),
};

// ─── Cost Estimation Endpoints ────────────────────────────────
export const costApi = {
  estimate: (data: CostEstimateRequest) => api.post<CostEstimate>('/costs/estimate', data),
  list: () => api.get<CostEstimate[]>('/costs'),
  get: (id: string) => api.get<CostEstimate>(`/costs/${id}`),
};

// ─── Carrier Assignment Endpoints ─────────────────────────────
export const carrierApi = {
  list: () => api.get<Carrier[]>('/carriers'),
  assign: (jobOrderId: string, carrierId: string, driverId: string) =>
    api.post('/carrier-assignments', { job_order_id: jobOrderId, carrier_id: carrierId, driver_id: driverId }),
  pending: () => api.get<CarrierAssignment[]>('/carrier-assignments/pending'),
  apply: (assignmentId: string) => api.patch(`/carrier-assignments/${assignmentId}/apply`, {}),
  reject: (assignmentId: string, reason: string) => api.patch(`/carrier-assignments/${assignmentId}/reject`, { reason }),
};

// ─── Dispatch Endpoints ───────────────────────────────────────
export const dispatchApi = {
  dispatch: (jobOrderId: string) => api.post('/dispatches', { job_order_id: jobOrderId }),
  list: () => api.get<Dispatch[]>('/dispatches'),
  get: (id: string) => api.get<Dispatch>(`/dispatches/${id}`),
};

// ─── GPS Tracking Endpoints ───────────────────────────────────
export const trackingApi = {
  livePositions: () => api.get<VehiclePosition[]>('/tracking/live'),
  vehicleHistory: (vehicleId: string, date: string) => api.get<TrackPoint[]>(`/tracking/${vehicleId}/history?date=${date}`),
  updatePosition: (vehicleId: string, lat: number, lng: number) =>
    api.post('/tracking/update', { vehicle_id: vehicleId, lat, lng }),
};

// ─── POD Endpoints ────────────────────────────────────────────
export const podApi = {
  capture: (jobOrderId: string, data: PODData) => api.post('/pod', { job_order_id: jobOrderId, ...data }),
  get: (jobOrderId: string) => api.get<POD>(`/pod/${jobOrderId}`),
  list: () => api.get<POD[]>('/pod'),
};

// ─── Billing Endpoints ────────────────────────────────────────
export const billingApi = {
  list: () => api.get<Invoice[]>('/invoices'),
  get: (id: string) => api.get<Invoice>(`/invoices/${id}`),
  create: (jobOrderId: string) => api.post<Invoice>('/invoices', { job_order_id: jobOrderId }),
  markPaid: (id: string) => api.patch<Invoice>(`/invoices/${id}/pay`, {}),
  send: (id: string) => api.post(`/invoices/${id}/send`, {}),
};

// ─── Utilization Endpoints ────────────────────────────────────
export const utilizationApi = {
  fleet: () => api.get<FleetUtilization>('/utilization/fleet'),
  driver: () => api.get<DriverUtilization[]>('/utilization/drivers'),
  summary: (from: string, to: string) => api.get<UtilizationSummary>(`/utilization/summary?from=${from}&to=${to}`),
};

// ─── Load Optimization Endpoints ──────────────────────────────
export const loadApi = {
  optimize: (vehicleId: string, items: LoadItem[]) => api.post<LoadPlan>('/load/optimize', { vehicle_id: vehicleId, items }),
  list: () => api.get<LoadPlan[]>('/load-plans'),
};

// ─── Driver Endpoints ─────────────────────────────────────────
export const driverApi = {
  list: () => api.get<Driver[]>('/drivers'),
  get: (id: string) => api.get<Driver>(`/drivers/${id}`),
  myAssignments: () => api.get<CarrierAssignment[]>('/drivers/my-assignments'),
  updateStatus: (status: string) => api.patch('/drivers/status', { status }),
};

// ─── Notification Endpoints ───────────────────────────────────
export const notificationApi = {
  list: () => api.get<Notification[]>('/notifications'),
  markRead: (id: string) => api.patch(`/notifications/${id}/read`, {}),
  markAllRead: () => api.post('/notifications/read-all', {}),
};

// ─── Types ────────────────────────────────────────────────────
export interface User {
  id: string; name: string; email: string;
  role: 'admin' | 'driver' | 'user';
  avatar?: string; phone?: string;
}

export interface PaginatedResponse<T> {
  data: T[]; total: number; page: number; per_page: number; last_page: number;
}

export interface JobOrder {
  id: string; order_number: string;
  customer_name: string; customer_phone: string;
  origin: string; origin_lat: number; origin_lng: number;
  destination: string; dest_lat: number; dest_lng: number;
  cargo_type: string; cargo_weight: number; cargo_volume: number;
  status: 'pending' | 'assigned' | 'dispatched' | 'in_transit' | 'delivered' | 'cancelled';
  priority: 'normal' | 'urgent' | 'express';
  scheduled_date: string; notes?: string;
  driver_id?: string; vehicle_id?: string;
  created_at: string; updated_at: string;
}

export interface Route {
  id: string; name: string; waypoints: Waypoint[];
  total_distance: number; estimated_duration: number;
  optimized: boolean; job_order_id?: string;
}

export interface Waypoint { lat: number; lng: number; address: string; order: number; }

export interface CostEstimateRequest {
  origin: string; destination: string;
  distance: number; cargo_weight: number;
  vehicle_type: string;
}

export interface CostEstimate {
  id: string; base_cost: number; fuel_cost: number;
  toll_cost: number; driver_fee: number; insurance: number;
  total: number; currency: string; job_order_id?: string;
}

export interface Carrier {
  id: string; name: string; type: string;
  plate_number: string; capacity: number;
  status: 'available' | 'busy' | 'maintenance';
}

export interface CarrierAssignment {
  id: string; job_order_id: string; carrier_id: string;
  driver_id: string; status: 'pending' | 'accepted' | 'rejected';
  assigned_at: string; driver?: Driver; carrier?: Carrier;
  job_order?: JobOrder;
}

export interface Dispatch {
  id: string; job_order_id: string; dispatched_at: string;
  driver_id: string; vehicle_id: string; status: string;
}

export interface VehiclePosition {
  vehicle_id: string; driver_name: string; plate_number: string;
  lat: number; lng: number; speed: number; heading: number;
  status: string; last_updated: string;
}

export interface TrackPoint { lat: number; lng: number; timestamp: string; speed: number; }

export interface PODData {
  signature_base64: string; photo_base64?: string;
  recipient_name: string; recipient_id?: string; notes?: string;
}

export interface POD extends PODData {
  id: string; job_order_id: string; captured_at: string;
  captured_by: string;
}

export interface Invoice {
  id: string; invoice_number: string; job_order_id: string;
  customer_name: string; amount: number; tax: number; total: number;
  status: 'draft' | 'sent' | 'paid' | 'overdue'; due_date: string;
  issued_at: string; items: InvoiceItem[];
}

export interface InvoiceItem { description: string; qty: number; unit_price: number; total: number; }

export interface Driver {
  id: string; name: string; phone: string; license_number: string;
  vehicle_id?: string; status: 'available' | 'on_duty' | 'off_duty' | 'rest';
  total_trips: number; rating: number;
}

export interface FleetUtilization {
  total_vehicles: number; active: number; idle: number; maintenance: number;
  utilization_rate: number;
}

export interface DriverUtilization {
  driver_id: string; driver_name: string; trips_completed: number;
  distance_covered: number; hours_driven: number; utilization_pct: number;
}

export interface UtilizationSummary {
  period: string; total_trips: number; total_distance: number;
  avg_utilization: number; cost_per_km: number;
}

export interface LoadItem {
  id: string; name: string; weight: number;
  volume: number; fragile: boolean;
}

export interface LoadPlan {
  id: string; vehicle_id: string; items: LoadItem[];
  total_weight: number; total_volume: number;
  utilization_pct: number; optimized_at: string;
}

export interface Notification {
  id: string; title: string; message: string;
  type: 'assignment' | 'dispatch' | 'delivery' | 'alert' | 'info';
  read: boolean; created_at: string; data?: Record<string, unknown>;
}
