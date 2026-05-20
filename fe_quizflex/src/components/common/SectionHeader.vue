<template>
  <div class="mb-7 items-end justify-between gap-6 md:flex">
    <div>
      <p v-if="eyebrow" class="mb-3 text-sm font-black uppercase tracking-[0.22em]" style="color: var(--primary)">
        {{ eyebrow }}
      </p>
      <h2 class="section-title">{{ title }}</h2>
    </div>
    <p v-if="description" class="muted-text mt-4 max-w-xl leading-7 md:mt-0">
      {{ description }}
    </p>
  </div>
</template>

<script setup>
defineProps({
  eyebrow: {
    type: String,
    default: '',
  },
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
})
</script>
