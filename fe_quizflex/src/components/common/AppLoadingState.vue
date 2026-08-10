<template>
  <div class="grid min-h-[360px] place-items-center rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-8 text-center shadow-[var(--shadow-card)]">
    <div class="max-w-md">
      <div class="mx-auto grid h-16 w-16 place-items-center rounded-3xl bg-[var(--primary)]/15 text-3xl text-[var(--primary)]">
        {{ icon }}
      </div>
      <h2 class="mt-5 text-2xl font-black text-[var(--text)]">{{ title }}</h2>
      <p class="mt-3 text-sm font-medium leading-6 text-[var(--muted)]">{{ message }}</p>
      <div class="mt-6 h-2 overflow-hidden rounded-full bg-[var(--surface-soft)]">
        <div class="loading-bar h-full w-2/5 rounded-full bg-gradient-to-r from-[var(--primary)] via-[var(--primary-2)] to-[var(--accent)]"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, default: 'Đang tải dữ liệu...' },
  message: { type: String, default: 'Vui lòng chờ trong giây lát.' },
  icon: { type: String, default: '⏳' },
})
</script>

<style scoped>
.loading-bar {
  animation: loading-slide 1.15s ease-in-out infinite;
}

@keyframes loading-slide {
  0% { transform: translateX(-110%); }
  100% { transform: translateX(260%); }
}
</style>
