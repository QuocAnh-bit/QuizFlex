<template>
  <span
    class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-black"
    :class="meta.className"
  >
    <component :is="meta.icon" class="h-3.5 w-3.5" :stroke-width="2" />
    <span>{{ meta.text }}</span>
  </span>
</template>

<script setup>
import { computed } from 'vue'
import { Globe, Lock, Users } from 'lucide-vue-next'

const visibilityLabels = {
  public: { text: 'Public', icon: Globe, className: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400' },
  private: { text: 'Private', icon: Lock, className: 'border-slate-500/25 bg-slate-500/10 text-slate-300' },
  group: { text: 'Group', icon: Users, className: 'border-amber-500/25 bg-amber-500/10 text-amber-400' },
}

const props = defineProps({
  value: {
    type: String,
    default: 'public',
  },
})

const meta = computed(() => visibilityLabels[props.value] || visibilityLabels.public)
</script>
