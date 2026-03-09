<template>
  <RouterLink v-bind="$attrs" :to="to" :class="computedClass">
    <slot />
  </RouterLink>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const props = defineProps<{
  to: string
  class?: string
  activeClass?: string
}>()

const route = useRoute()

const computedClass = computed(() => {
  const isActive = route.path === props.to
  return [props.class, isActive ? props.activeClass : ''].filter(Boolean).join(' ')
})
</script>