<template>
  <div class="relative overflow-hidden rounded-[2.5rem] border border-[var(--border)] bg-[var(--surface)] p-12 text-center shadow-[var(--shadow-soft)] backdrop-blur-2xl">
    <!-- Decorative Orbs -->
    <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
    <div class="pointer-events-none absolute -left-24 -bottom-24 h-72 w-72 rounded-full bg-[var(--accent)]/10 blur-3xl"></div>

    <div class="relative z-10">
      <div class="mx-auto mb-5 grid h-16 w-16 place-items-center rounded-3xl bg-gradient-to-br from-[var(--primary)]/20 via-[var(--primary-2)]/20 to-[var(--accent)]/20 border border-[var(--primary)]/30 text-2xl text-[var(--primary)] shadow-lg animate-bounce">
        {{ icon }}
      </div>

      <h3 class="text-xl font-black tracking-[-0.03em] text-[var(--text)] sm:text-2xl">
        {{ title }}
      </h3>

      <p class="mt-2 text-xs font-bold text-[var(--muted)] max-w-sm mx-auto">
        {{ message }}
      </p>

      <div class="mt-6 mx-auto max-w-xs overflow-hidden rounded-full border border-[var(--border)] bg-[var(--surface-soft)] p-1">
        <div class="relative h-1.5 overflow-hidden rounded-full bg-[var(--surface-soft)]">
          <div class="loading-progress absolute inset-y-0 left-0 rounded-full bg-gradient-to-r from-[var(--primary)] via-[var(--primary-2)] to-[var(--accent)]"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    default: 'Đang tải dữ liệu...',
  },
  message: {
    type: String,
    default: 'Vui lòng chờ trong giây lát để hệ thống xử lý.',
  },
  icon: {
    type: String,
    default: '⚡',
  },
})
</script>

<style scoped>
.loading-progress {
  width: 40%;
  animation: progress-slide 1.2s ease-in-out infinite;
}

@keyframes progress-slide {
  0% {
    transform: translateX(-120%);
  }
  55% {
    transform: translateX(95%);
  }
  100% {
    transform: translateX(270%);
  }
}
</style>
