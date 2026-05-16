<template>
  <router-link :to="to" class="inline-flex items-center gap-3 font-black tracking-[-0.04em]" :class="sizeClass">
    <span class="grid place-items-center rounded-2xl text-white shadow-lg" :class="markClass">
      Q
    </span>
    <span>QuizFlex</span>
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
const markClass = computed(() => props.size === 'lg' ? 'h-11 w-11 text-lg' : 'h-9 w-9 text-base')
</script>

<style scoped>
span:first-child {
  background: linear-gradient(135deg, var(--primary), var(--primary-2), var(--accent));
}
</style>
