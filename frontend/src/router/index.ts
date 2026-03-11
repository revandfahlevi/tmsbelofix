import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import path from 'path'

export const router = createRouter({
  history: createWebHistory(),
  routes: [
    // Public
    { path: '/login', component: () => import('@/pages/LoginPage.vue') },
    { path: '/', component: () => import('@/pages/Index.vue') },

    // Admin
    {
      path: '/admin',
      component: () => import('@/components/AppLayout.vue'),
      meta: { requiresAuth: true, role: 'admin' },
      children: [
        { path: '', component: () => import('@/pages/admin/AdminDashboard.vue') },
        { path: 'job-orders', component: () => import('@/pages/admin/JobOrderPage.vue') },
        { path: 'billing', component: () => import('@/pages/admin/BillingPage.vue') },
        { path: 'carrier-assignment', component: () => import('@/pages/admin/CarrierAssignmentPage.vue') },
        { path: 'cost-estimation', component: () => import('@/pages/admin/CostEstimationPage.vue') },
        { path: 'delivery-update', component: () => import('@/pages/admin/DeliveryUpdatePage.vue') },
        { path: 'dispatch', component: () => import('@/pages/admin/DispatchPage.vue') },
        { path: 'gps-tracking', component: () => import('@/pages/admin/GPSTrackingPage.vue') },
        { path: 'vehicles', component: () => import('@/pages/admin/VehiclePage.vue') },
        { path: 'load-optimization', component: () => import('@/pages/admin/LoadOptimizationPage.vue') },
        { path: 'pod', component: () => import('@/pages/admin/PODPage.vue') },
        { path: 'routes', component: () => import('@/pages/admin/RoutePlanningPage.vue') },
        { path: 'utilization', component: () => import('@/pages/admin/UtilizationPage.vue') },
        { path: 'warehouse/inbound', component: () => import('@/pages/admin/InboundPage.vue') },
        { path: 'warehouse/inventory', component: () => import('@/pages/admin/InventoryPage.vue') },
        { path: 'warehouse/outbound', component: () => import('@/pages/admin/OutboundPage.vue') },
      ]
    },

    // Driver
    {
      path: '/driver',
      component: () => import('@/components/AppLayout.vue'),
      meta: { requiresAuth: true, role: 'driver' },
      children: [
        { path: '', component: () => import('@/pages/driver/DriverDashboard.vue') },
        { path: 'navigation', component: () => import('@/pages/admin/GPSTrackingPage.vue') },
        { path: 'status-update', component: () => import('@/pages/admin/DeliveryUpdatePage.vue') },
        { path: 'pod', component: () => import('@/pages/driver/PODPage.vue') },
      ]
    },

    // User
    {
      path: '/user',
      component: () => import('@/components/AppLayout.vue'),
      meta: { requiresAuth: true, role: 'user' },
      children: [
        { path: '', component: () => import('@/pages/user/UserDashboard.vue') },
        { path: 'tracking', component: () => import('@/pages/admin/GPSTrackingPage.vue') },
        { path: 'deliveries', component: () => import('@/pages/admin/JobOrderPage.vue') },
        { path: 'reports', component: () => import('@/pages/admin/UtilizationPage.vue') },
        { path: 'warehouse', component: () => import('@/pages/user/WarehousePage.vue') },
      ]
    },

    // 404
    { path: '/:pathMatch(.*)*', component: () => import('@/pages/NotFound.vue') },
  ]
})

router.beforeEach((to) => {
  const auth = useAuthStore()

  if (!auth.user) {
    const stored = localStorage.getItem('tms_user')
    if (stored && stored !== 'undefined') {
      try {
        auth.user = JSON.parse(stored)
      } catch {
        localStorage.removeItem('tms_user')
      }
    }
  }

  if (to.meta.requiresAuth && !auth.user) return '/login'
  if (to.meta.role && auth.user?.role !== to.meta.role) return `/${auth.user?.role}`

  return true
})