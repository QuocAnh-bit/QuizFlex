<template>
  <div class="relative overflow-hidden rounded-[2.5rem] border border-rose-500/30 bg-rose-500/10 p-8 text-center shadow-[var(--shadow-soft)] backdrop-blur-2xl">
    <div class="relative z-10">
      <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-3xl bg-rose-500/20 text-2xl text-rose-400 border border-rose-500/30">
        ⚠️
      </div>

      <h3 class="text-xl font-black text-rose-300 tracking-[-0.03em]">
        {{ title }}
      </h3>

      <p class="mt-2 text-xs font-bold text-rose-300/80 max-w-md mx-auto leading-relaxed">
        {{ message }}
      </p>

      <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
        <button 
          v-if="showRetry"
          type="button" 
          class="rounded-full border border-rose-500/40 bg-rose-500/20 px-6 py-2.5 text-xs font-black text-rose-200 transition hover:bg-rose-500/30 hover:scale-105 active:scale-95 shadow-sm"
          @click="$emit('retry')"
        >
          🔄 Thử lại
        </button>
        <slot name="actions"></slot>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    default: 'Không thể tải dữ liệu',
  },
  message: {
    type: String,
    default: 'Đã xảy ra lỗi khi kết nối với máy chủ.',
  },
  showRetry: {
    type: Boolean,
    default: true,
  },
})

defineEmits(['retry'])
</script>
