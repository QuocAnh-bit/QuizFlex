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

const visibilityLabels = {
  public: { text: 'Public', icon: '🌐', className: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400' },
  private: { text: 'Private', icon: '🔒', className: 'border-slate-500/25 bg-slate-500/10 text-slate-300' },
  group: { text: 'Group', icon: '👥', className: 'border-amber-500/25 bg-amber-500/10 text-amber-400' },
}

const props = defineProps({
  value: {
    type: String,
    default: 'public',
  },
})

const meta = computed(() => visibilityLabels[props.value] || visibilityLabels.public)
</script>
