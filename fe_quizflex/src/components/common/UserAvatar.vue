<template>
  <div :class="['grid shrink-0 place-items-center overflow-hidden bg-[var(--surface-soft)] text-[var(--primary)]', roundedClass, sizeClass, ringClass, shadowClass]">
    <img v-if="avatarUrl" :src="avatarUrl" :alt="`${displayName} avatar`" class="h-full w-full object-cover" />
    <span v-else :class="['font-black uppercase', textClass]">{{ initials }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  user: {
    type: Object,
    default: () => ({}),
  },
  sizeClass: {
    type: String,
    default: 'h-10 w-10',
  },
  roundedClass: {
    type: String,
    default: 'rounded-full',
  },
  textClass: {
    type: String,
    default: 'text-sm',
  },
  ringClass: {
    type: String,
    default: '',
  },
  shadowClass: {
    type: String,
    default: '',
  },
})

const displayName = computed(() => props.user?.name || props.user?.email || 'Guest')
const avatarUrl = computed(() => props.user?.avatar || '')
const initials = computed(() => {
  const parts = String(displayName.value).trim().split(/\s+/).filter(Boolean)
  if (!parts.length) return 'U'
  return parts.slice(0, 2).map((part) => part.charAt(0)).join('').toUpperCase()
})
</script>
