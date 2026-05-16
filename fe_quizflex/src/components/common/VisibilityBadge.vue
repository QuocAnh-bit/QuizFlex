<template>
  <span
    class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-black"
    :class="meta.className"
  >
    <span>{{ meta.icon }}</span>
    <span>{{ meta.text }}</span>
  </span>
</template>

<script setup>
import { computed } from 'vue'
import { visibilityLabels } from '@/data/demoData'

const props = defineProps({
  value: {
    type: String,
    default: 'public',
  },
})

const meta = computed(() => visibilityLabels[props.value] || visibilityLabels.public)
</script>
