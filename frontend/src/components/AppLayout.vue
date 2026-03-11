<template>
  <div class="min-h-screen bg-gray-50 flex">
    <!-- Sidebar -->
    <aside :class="`fixed inset-y-0 left-0 z-50 w-64 bg-white border-r transform transition-transform duration-300 ${
      sidebarOpen ? 'translate-x-0' : '-translate-x-full'
    } lg:translate-x-0`">
      <!-- Logo -->
      <div class="flex items-center gap-3 px-6 py-5 border-b">
        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
          <Truck class="w-4 h-4 text-white" />
        </div>
        <div>
          <p class="font-bold text-sm">Transport Management System</p>
          <p class="text-xs text-gray-400 capitalize">{{ auth.user?.role }}</p>
        </div>
      </div>

      <!-- Nav -->
      <nav class="p-4 space-y-6 overflow-y-auto h-full pb-32">
        <div v-for="section in currentNav" :key="section.section">
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
            {{ section.section }}
          </p>
          <div class="space-y-1">
            <RouterLink
              v-for="item in section.items"
              :key="item.to"
              :to="item.to"
              @click="sidebarOpen = false"
              :class="`flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors ${
                route.path === item.to
                  ? 'bg-blue-50 text-blue-600 font-medium'
                  : 'text-gray-600 hover:bg-gray-100'
              }`">
              <component :is="item.icon" class="w-4 h-4 flex-shrink-0" />
              {{ item.label }}
            </RouterLink>
          </div>
        </div>
      </nav>

      <!-- User & Logout -->
      <div class="absolute bottom-0 left-0 right-0 p-4 border-t bg-white">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xs font-bold">
            {{ auth.user?.name?.slice(0, 2).toUpperCase() }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium truncate">{{ auth.user?.name }}</p>
            <p class="text-xs text-gray-400 truncate">{{ auth.user?.email }}</p>
          </div>
        </div>
        <button @click="handleLogout"
          class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg transition">
          <LogOut class="w-4 h-4" />
          Keluar
        </button>
      </div>
    </aside>

    <!-- Overlay mobile -->
    <div v-if="sidebarOpen"
      @click="sidebarOpen = false"
      class="fixed inset-0 bg-black/50 z-40 lg:hidden" />

    <!-- Main -->
    <div class="flex-1 flex flex-col min-w-0 lg:ml-64">
      <!-- Topbar -->
      <header class="sticky top-0 z-30 bg-white border-b px-4 py-3 flex items-center justify-between">
        <button @click="sidebarOpen = !sidebarOpen"
          class="lg:hidden p-2 rounded-lg hover:bg-gray-100">
          <Menu class="w-5 h-5" />
        </button>
        <div class="flex-1 lg:flex-none">
          <h2 class="text-sm font-medium text-gray-600">{{ pageTitle }}</h2>
        </div>
        <div class="flex items-center gap-2">
          <NotificationBell />
        </div>
      </header>

      <!-- Content -->
      <main class="flex-1 overflow-auto">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  Truck, LogOut, Menu,
  LayoutDashboard, Package, Map, Activity,
  FileImage, Calculator, BarChart3, Layers,
  Navigation, Receipt, Warehouse, ArrowDownToLine,
  ArrowUpFromLine, BoxesIcon
} from 'lucide-vue-next'
import NotificationBell from '@/components/NotificationBell.vue'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const sidebarOpen = ref(false)

const ADMIN_NAV = [
  { section: 'Dashboard', items: [
    { label: 'Dashboard', icon: LayoutDashboard, to: '/admin' },
  ]},
  { section: 'Order Management', items: [
    { label: 'Job Order', icon: Package, to: '/admin/job-orders' },
  ]},
  { section: 'Transport Planning', items: [
    { label: 'Route Planning', icon: Map, to: '/admin/routes' },
    { label: 'Cost Estimation', icon: Calculator, to: '/admin/cost-estimation' },
  ]},
  { section: 'Transport Optimization', items: [
    { label: 'Utilization', icon: BarChart3, to: '/admin/utilization' },
    { label: 'Load Optimization', icon: Layers, to: '/admin/load-optimization' },
  ]},
  { section: 'Carrier & Dispatch', items: [
    { label: 'Carrier Assignment', icon: Truck, to: '/admin/carrier-assignment' },
    { label: 'Dispatch Truck', icon: Navigation, to: '/admin/dispatch' },
  ]},
  { section: 'Tracking', items: [
    { label: 'GPS Tracking', icon: Activity, to: '/admin/gps-tracking' },
    { label: 'Update Status', icon: Package, to: '/admin/delivery-update' },
  ]},
  { section: 'Delivery', items: [
    { label: 'POD Captured', icon: FileImage, to: '/admin/pod' },
  ]},
  { section: 'Finance', items: [
    { label: 'Billing & Invoice', icon: Receipt, to: '/admin/billing' },
  ]},
  { section: 'Warehouse Management', items: [
    { label: 'Inbound', icon: ArrowDownToLine, to: '/admin/warehouse/inbound' },
    { label: 'Inventory', icon: BoxesIcon, to: '/admin/warehouse/inventory' },
    { label: 'Outbound', icon: ArrowUpFromLine, to: '/admin/warehouse/outbound' },
  ]},
  { section: 'Armada', items: [
    { label: 'Master Kendaraan', icon: Truck, to: '/admin/vehicles' },
  ]},
]

const DRIVER_NAV = [
  { section: 'Utama', items: [
    { label: 'Dashboard', icon: LayoutDashboard, to: '/driver' },
    { label: 'GPS & Navigasi', icon: Navigation, to: '/driver/navigation' },
    { label: 'POD Capture', icon: FileImage, to: '/driver/pod' },
    { label: 'Update Status', icon: Activity, to: '/driver/status-update' },
  ]},
]

const USER_NAV = [
  { section: 'Pantau', items: [
    { label: 'Dashboard', icon: LayoutDashboard, to: '/user' },
    { label: 'Live Tracking', icon: Activity, to: '/user/tracking' },
    { label: 'Pengiriman Saya', icon: Package, to: '/user/deliveries' },
    { label: 'Laporan', icon: BarChart3, to: '/user/reports' },
  ]},
  { section: 'Warehouse', items: [
    { label: 'Lihat Gudang', icon: Layers, to: '/user/warehouse' },
  ]},
]

const currentNav = computed(() => {
  if (auth.user?.role === 'admin') return ADMIN_NAV
  if (auth.user?.role === 'driver') return DRIVER_NAV
  return USER_NAV
})

const pageTitle = computed(() => {
  const all = [...ADMIN_NAV, ...DRIVER_NAV, ...USER_NAV].flatMap(s => s.items)
  return all.find(i => i.to === route.path)?.label || 'Transport Management System'
})

function handleLogout() {
  auth.logout()
  router.push('/login')
}
</script>