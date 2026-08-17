<template>
  <router-link :to="to" class="inline-flex items-center gap-2.5 font-black tracking-tight text-slate-900 transition hover:opacity-90" :class="sizeClass">
    <span class="grid place-items-center rounded-xl bg-[#7C3AED] text-white font-black shadow-sm" :class="markClass">
      Q
    </span>
    <span class="font-extrabold text-slate-900">Quiz<span class="text-[#7C3AED]">Flex</span></span>
  </router-link>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  to: {
    type: String,
    default: '/',
  },
  size: {
    type: String,
    default: 'md',
  },
})

const sizeClass = computed(() => props.size === 'lg' ? 'text-2xl' : 'text-xl')
const markClass = computed(() => props.size === 'lg' ? 'h-10 w-10 text-xl' : 'h-8 w-8 text-base')
</script>
