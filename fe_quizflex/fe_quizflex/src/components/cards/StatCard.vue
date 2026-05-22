<template>
  <article class="glass-card rounded-2xl p-5">
    <p class="muted-text text-sm">{{ label }}</p>
    <p class="mt-2 text-3xl font-black tracking-[-0.05em]">{{ value }}</p>
    <p v-if="hint" class="muted-text mt-1 text-xs">{{ hint }}</p>
  </article>
</template>

<script setup>
defineProps({
  label: {
    type: String,
    required: true,
  },
  value: {
    type: String,
    required: true,
  },
  hint: {
    type: String,
    default: '',
  },
})
</script>
