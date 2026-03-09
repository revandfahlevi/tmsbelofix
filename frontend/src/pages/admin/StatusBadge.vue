<template>
  <span :class="['inline-flex px-2 py-0.5 rounded-full text-xs font-medium', config.class]">
    {{ config.label }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'
const props = defineProps<{ status: string }>()
const MAP: Record<string, { label: string; class: string }> = {
  draft:       { label: 'Draft',       class: 'bg-gray-100 text-gray-600' },
  pending:     { label: 'Pending',     class: 'bg-yellow-100 text-yellow-700' },
  assigned:    { label: 'Assigned',    class: 'bg-blue-100 text-blue-700' },
  in_progress: { label: 'In Progress', class: 'bg-indigo-100 text-indigo-700' },
  picked_up:   { label: 'Picked Up',   class: 'bg-purple-100 text-purple-700' },
  in_transit:  { label: 'In Transit',  class: 'bg-cyan-100 text-cyan-700' },
  delivered:   { label: 'Delivered',   class: 'bg-teal-100 text-teal-700' },
  completed:   { label: 'Completed',   class: 'bg-green-100 text-green-700' },
  cancelled:   { label: 'Cancelled',   class: 'bg-red-100 text-red-600' },
  failed:      { label: 'Failed',      class: 'bg-red-200 text-red-700' },
}
const config = computed(() => MAP[props.status] ?? { label: props.status, class: 'bg-gray-100 text-gray-500' })
</script>