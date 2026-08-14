<template>
  <Transition name="loading-fade">
    <div
      v-if="show"
      class="fixed inset-0 z-[9998] flex items-center justify-center overflow-hidden bg-white/85 px-4 backdrop-blur-xl"
    >
      <!-- Background -->
      <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div
          class="absolute left-1/2 top-1/2 h-[500px] w-[500px]
                 -translate-x-1/2 -translate-y-1/2
                 rounded-full bg-[#7C3AED]/[0.06] blur-[100px]"
        ></div>

        <div
          class="absolute -left-24 -top-24 h-72 w-72
                 rounded-full bg-[#A855F7]/[0.05] blur-[90px]"
        ></div>

        <div
          class="absolute -bottom-24 -right-24 h-80 w-80
                 rounded-full bg-[#8B5CF6]/[0.05] blur-[100px]"
        ></div>
      </div>

      <!-- Loading Card -->
      <div class="relative z-10 w-full max-w-[390px]">
        <div
          class="loading-card relative overflow-hidden rounded-[28px]
                 border border-slate-200/80
                 bg-white/95
                 px-7 py-8
                 text-center
                 shadow-[0_20px_70px_rgba(15,23,42,0.10)]
                 sm:px-9 sm:py-9"
        >
          <!-- Top accent -->
          <div
            class="absolute inset-x-10 top-0 h-[2px]
                   rounded-full bg-gradient-to-r
                   from-transparent via-[#7C3AED] to-transparent opacity-70"
          ></div>

          <!-- Logo -->
          <div class="relative mx-auto mb-6 h-[92px] w-[92px]">
            <!-- soft glow -->
            <div
              class="absolute inset-2 rounded-[25px]
                     bg-[#7C3AED]/10 blur-2xl"
            ></div>

            <!-- rotating border -->
            <div class="loading-ring absolute inset-0 rounded-[26px]"></div>

            <!-- white inner border -->
            <div
              class="absolute inset-[4px] rounded-[23px]
                     border border-[#E9D5FF]
                     bg-white"
            ></div>

            <!-- Q -->
            <div
              class="absolute inset-[10px] flex items-center justify-center
                     rounded-[19px]
                     bg-gradient-to-br from-[#7C3AED] to-[#8B5CF6]
                     text-[42px] font-black leading-none
                     tracking-[-0.12em] text-white
                     shadow-[0_12px_30px_rgba(124,58,237,0.24)]"
            >
              Q
            </div>
          </div>

          <!-- Brand -->
          <div
            class="text-[11px] font-extrabold uppercase
                   tracking-[0.24em] text-[#7C3AED]"
          >
            QuizFlex
          </div>

          <!-- Heading -->
          <h2
            class="mt-2.5 text-[25px] font-extrabold
                   tracking-[-0.035em] text-[#0F172A]"
          >
            Đang tải...
          </h2>

          <p
            class="mx-auto mt-2 max-w-[285px]
                   text-[13px] font-medium leading-5
                   text-[#64748B]"
          >
            Đang chuẩn bị không gian học tập cho bạn
          </p>

          <!-- Progress -->
          <div class="mt-7">
            <div
              class="h-[7px] w-full overflow-hidden
                     rounded-full bg-[#F1F5F9]"
            >
              <div
                class="loading-progress h-full rounded-full
                       bg-gradient-to-r from-[#7C3AED] to-[#A855F7]"
              ></div>
            </div>
          </div>

          <!-- Status -->
          <div class="mt-5 flex items-center justify-center gap-2">
            <span
              class="loading-dot h-1.5 w-1.5 rounded-full bg-[#7C3AED]"
            ></span>

            <span
              class="loading-dot h-1.5 w-1.5 rounded-full
                     bg-[#8B5CF6] [animation-delay:0.15s]"
            ></span>

            <span
              class="loading-dot h-1.5 w-1.5 rounded-full
                     bg-[#A855F7] [animation-delay:0.3s]"
            ></span>
          </div>

          <!-- Bottom text -->
          <div
            class="mt-5 border-t border-slate-100 pt-4
                   text-[10px] font-semibold
                   tracking-wide text-slate-400"
          >
            Trải nghiệm học tập thông minh
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
/* =========================
   Loading Ring
========================= */

.loading-ring {
  background:
    conic-gradient(
      from 0deg,
      transparent 0deg,
      transparent 55deg,
      #7c3aed 105deg,
      #8b5cf6 180deg,
      #a855f7 230deg,
      transparent 285deg,
      transparent 360deg
    );

  padding: 2px;

  mask:
    linear-gradient(#000 0 0) content-box,
    linear-gradient(#000 0 0);

  mask-composite: exclude;

  -webkit-mask:
    linear-gradient(#000 0 0) content-box,
    linear-gradient(#000 0 0);

  -webkit-mask-composite: xor;

  animation: ring-spin 1.6s linear infinite;
}

/* =========================
   Progress
========================= */

.loading-progress {
  width: 35%;
  animation: progress-slide 1.35s ease-in-out infinite;
}

/* =========================
   Dots
========================= */

.loading-dot {
  animation: dot-pulse 0.9s ease-in-out infinite;
}

/* =========================
   Card
========================= */

.loading-card {
  animation: card-enter 0.35s ease-out both;
}

/* =========================
   Transition
========================= */

.loading-fade-enter-active,
.loading-fade-leave-active {
  transition:
    opacity 0.3s ease,
    transform 0.3s ease;
}

.loading-fade-enter-from,
.loading-fade-leave-to {
  opacity: 0;
  transform: scale(0.98);
}

/* =========================
   Animations
========================= */

@keyframes ring-spin {
  to {
    transform: rotate(360deg);
  }
}

@keyframes progress-slide {
  0% {
    transform: translateX(-140%);
  }

  55% {
    transform: translateX(100%);
  }

  100% {
    transform: translateX(300%);
  }
}

@keyframes dot-pulse {
  0%,
  100% {
    transform: translateY(0);
    opacity: 0.35;
  }

  50% {
    transform: translateY(-3px);
    opacity: 1;
  }
}

@keyframes card-enter {
  from {
    opacity: 0;
    transform: translateY(8px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* =========================
   Reduced Motion
========================= */

@media (prefers-reduced-motion: reduce) {
  .loading-ring,
  .loading-progress,
  .loading-dot,
  .loading-card {
    animation: none;
  }
}
</style>