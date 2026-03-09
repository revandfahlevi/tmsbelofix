<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="text-center space-y-4">
      <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center mx-auto">
        <MapPin class="w-12 h-12 text-blue-600" />
      </div>
      <h1 class="text-6xl font-bold text-gray-900">404</h1>
      <p class="text-xl font-semibold text-gray-700">Halaman Tidak Ditemukan</p>
      <p class="text-gray-400 text-sm">Halaman yang kamu cari tidak ada atau sudah dipindahkan.</p>
      <button @click="router.back()"
        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
        Kembali
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { MapPin } from 'lucide-vue-next'

const router = useRouter()
</script>