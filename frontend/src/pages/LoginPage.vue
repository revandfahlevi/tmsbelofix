<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-white flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="w-14 h-14 bg-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
          <Truck class="w-7 h-7 text-white" />
        </div>
        <h1 class="text-2xl font-bold text-gray-800">FleetWise Connect</h1>
        <p class="text-sm text-gray-500 mt-1">Transport Management System</p>
      </div>

      <!-- Card -->
      <div class="bg-white rounded-2xl shadow-xl border border-blue-50 p-8">
        <h2 class="text-lg font-semibold text-gray-800 mb-6">Masuk ke akun Anda</h2>

        <!-- Error -->
        <div v-if="auth.error"
          class="bg-red-50 border border-red-200 rounded-xl p-3 mb-4 flex items-center gap-2">
          <AlertCircle class="w-4 h-4 text-red-500 flex-shrink-0" />
          <p class="text-sm text-red-600">{{ auth.error }}</p>
        </div>

        <div class="space-y-4">
          <div>
            <label class="text-xs font-medium text-gray-600 block mb-1">Email</label>
            <div class="relative">
              <Mail class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input
                v-model="email"
                type="email"
                placeholder="admin@tms.com"
                @keyup.enter="handleLogin"
                class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
          </div>

          <div>
            <label class="text-xs font-medium text-gray-600 block mb-1">Password</label>
            <div class="relative">
              <Lock class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
                @keyup.enter="handleLogin"
                class="w-full pl-9 pr-10 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
              <button @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                <Eye v-if="!showPassword" class="w-4 h-4" />
                <EyeOff v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <button
            @click="handleLogin"
            :disabled="auth.loading || !email || !password"
            class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white py-2.5 rounded-xl text-sm font-medium transition flex items-center justify-center gap-2">
            <Loader2 v-if="auth.loading" class="w-4 h-4 animate-spin" />
            <span>{{ auth.loading ? 'Memproses...' : 'Masuk' }}</span>
          </button>
        </div>
<!-- Divider -->
<div class="flex items-center gap-3 my-2">
  <div class="flex-1 h-px bg-gray-200"></div>
  <span class="text-xs text-gray-400">atau</span>
  <div class="flex-1 h-px bg-gray-200"></div>
</div>

<!-- Google Button -->
<button
  @click="handleGoogleLogin"
  :disabled="auth.loading"
  class="w-full flex items-center justify-center gap-3 border border-gray-200 hover:bg-gray-50 disabled:opacity-50 py-2.5 rounded-xl text-sm font-medium text-gray-700 transition">
  <svg class="w-4 h-4" viewBox="0 0 24 24">
    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
  </svg>
  Masuk dengan Google
</button>
        <!-- Demo accounts — hapus saat production -->
        <div class="mt-6 p-4 bg-gray-50 rounded-xl">
          <p class="text-xs font-medium text-gray-500 mb-2">Demo Akun:</p>
          <div class="space-y-1">
            <button v-for="acc in demoAccounts" :key="acc.email"
              @click="fillDemo(acc)"
              class="w-full text-left px-3 py-1.5 rounded-lg hover:bg-white transition text-xs text-gray-600 flex items-center justify-between">
              <span>{{ acc.label }}</span>
              <span class="text-gray-400">{{ acc.email }}</span>
            </button>
          </div>
        </div>
      </div>

      <p class="text-center text-xs text-gray-400 mt-6">
        © 2026 FleetWise Connect. All rights reserved.
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { Truck, Mail, Lock, Eye, EyeOff, AlertCircle, Loader2 } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const email = ref('')
const password = ref('')
const showPassword = ref(false)

const demoAccounts = [
  { label: 'Admin',  email: 'admin@tms.local',   password: 'Admin@TMS2024!' },
  { label: 'Driver', email: 'driver1@tms.local',  password: 'Driver@123!' },
  { label: 'User',   email: 'user@tms.local',   password: 'User@TMS2024!' },
] 

function fillDemo(acc: any) {
  email.value = acc.email
  password.value = acc.password
}

async function handleLogin() {
  if (!email.value || !password.value) return
  const success = await auth.login(email.value, password.value)
  if (success) {
    const role = auth.user?.role
    if (role === 'admin') router.push('/admin')
    else if (role === 'driver') router.push('/driver')
    else router.push('/user')
  }
}
</script>