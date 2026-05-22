<template>
  <Transition name="loading-fade">
    <div
      v-if="show"
      class="fixed inset-0 z-[9998] grid place-items-center overflow-hidden bg-[var(--bg)]/90 backdrop-blur-2xl"
    >
      <div class="pointer-events-none absolute inset-0">
        <div class="absolute left-1/2 top-1/2 h-[520px] w-[520px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-[var(--primary)]/20 blur-[90px]"></div>
        <div class="absolute left-[35%] top-[42%] h-72 w-72 rounded-full bg-[var(--accent)]/10 blur-[80px]"></div>
        <div class="absolute right-[28%] top-[55%] h-72 w-72 rounded-full bg-[var(--accent-2)]/10 blur-[80px]"></div>
      </div>

      <div class="relative z-10 w-[min(92vw,420px)]">
        <div
          class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-8 text-center shadow-[var(--shadow-soft)] backdrop-blur-2xl"
        >
          <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-[var(--border-strong)] to-transparent"></div>
          <div class="absolute -right-24 -top-24 h-56 w-56 rounded-full bg-[var(--primary)]/20 blur-3xl"></div>
          <div class="absolute -bottom-24 -left-24 h-56 w-56 rounded-full bg-[var(--accent)]/10 blur-3xl"></div>

          <div class="relative mx-auto mb-7 grid h-28 w-28 place-items-center">
            <div class="absolute inset-0 rounded-[2rem] bg-gradient-to-br from-[var(--primary)] via-[var(--primary-2)] to-[var(--accent)] opacity-25 blur-xl"></div>

            <div class="absolute inset-0 rounded-[2rem] border border-[var(--border-strong)]"></div>

            <div class="loading-orbit absolute inset-0 rounded-[2rem]"></div>

            <div
              class="relative grid h-20 w-20 place-items-center rounded-[1.6rem] bg-gradient-to-br from-[var(--primary)] via-[var(--primary-2)] to-[var(--accent)] text-5xl font-black tracking-[-0.12em] text-white shadow-[0_24px_60px_rgba(155,44,255,0.38)]"
            >
              Q
            </div>
          </div>

          <p class="text-xs font-black uppercase tracking-[0.28em] text-[var(--primary)]">
            QuizFlex
          </p>

          <h2 class="mt-3 text-3xl font-black tracking-[-0.06em] text-[var(--text)]">
            {{ $t('components.AppLoading.title') }}
          </h2>

          <p class="mx-auto mt-3 max-w-xs text-sm font-semibold leading-6 text-[var(--muted)]">
            {{ $t('components.AppLoading.description') }}
          </p>

          <div class="mt-7 overflow-hidden rounded-full border border-[var(--border)] bg-[var(--surface-soft)] p-1">
            <div class="relative h-2 overflow-hidden rounded-full bg-[var(--surface-soft)]">
              <div class="loading-progress absolute inset-y-0 left-0 rounded-full bg-gradient-to-r from-[var(--primary)] via-[var(--primary-2)] to-[var(--accent)]"></div>
            </div>
          </div>

          <div class="mt-5 flex justify-center gap-2">
            <span class="loading-dot h-2 w-2 rounded-full bg-[var(--primary)]"></span>
            <span class="loading-dot h-2 w-2 rounded-full bg-[var(--primary-2)] [animation-delay:0.14s]"></span>
            <span class="loading-dot h-2 w-2 rounded-full bg-[var(--accent)] [animation-delay:0.28s]"></span>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
defineProps({
  show: {
    type: Boolean,
    default: false,
  },
})
</script>

<style scoped>
.loading-orbit {
  background:
    conic-gradient(
      from 0deg,
      transparent 0deg,
      transparent 80deg,
      var(--primary) 120deg,
      var(--primary-2) 180deg,
      var(--accent) 240deg,
      transparent 300deg,
      transparent 360deg
    );
  mask:
    linear-gradient(#000 0 0) content-box,
    linear-gradient(#000 0 0);
  padding: 2px;
  -webkit-mask:
    linear-gradient(#000 0 0) content-box,
    linear-gradient(#000 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
  animation: orbit-spin 1.15s linear infinite;
}

.loading-progress {
  width: 38%;
  animation: progress-slide 1.2s ease-in-out infinite;
}

.loading-dot {
  animation: dot-bounce 0.85s ease-in-out infinite;
}

.loading-fade-enter-active,
.loading-fade-leave-active {
  transition:
    opacity 0.28s ease,
    transform 0.28s ease;
}

.loading-fade-enter-from,
.loading-fade-leave-to {
  opacity: 0;
  transform: scale(1.02);
}

@keyframes orbit-spin {
  to {
    transform: rotate(360deg);
  }
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

@keyframes dot-bounce {
  0%,
  80%,
  100% {
    transform: translateY(0);
    opacity: 0.45;
  }

  40% {
    transform: translateY(-7px);
    opacity: 1;
  }
}
</style>