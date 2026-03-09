<template>
  <span :class="['inline-flex px-2 py-0.5 rounded-full text-xs font-medium', config.class]">
    {{ config.label }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'
const props = defineProps<{ priority: string }>()
const MAP: Record<string, { label: string; class: string }> = {
  low:    { label: 'Low',    class: 'bg-gray-100 text-gray-500' },
  normal: { label: 'Normal', class: 'bg-blue-100 text-blue-600' },
  high:   { label: 'High',   class: 'bg-orange-100 text-orange-600' },
  urgent: { label: 'Urgent', class: 'bg-red-100 text-red-600' },
}
const config = computed(() => MAP[props.priority] ?? { label: props.priority, class: 'bg-gray-100 text-gray-500' })
</script>