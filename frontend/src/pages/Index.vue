<template>
  <div></div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

if (auth.user) {
  router.replace(`/${auth.user.role}`)
} else {
  router.replace('/login')
}
</script>