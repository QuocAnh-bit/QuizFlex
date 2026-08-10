<template>
  <div class="grid min-h-[360px] place-items-center rounded-[2rem] border border-rose-500/25 bg-[var(--surface)] p-8 text-center shadow-[var(--shadow-card)]">
    <div class="max-w-md">
      <div class="mx-auto grid h-16 w-16 place-items-center rounded-3xl bg-rose-500/10 text-3xl text-rose-400">!</div>
      <h2 class="mt-5 text-2xl font-black text-[var(--text)]">{{ title }}</h2>
      <p class="mt-3 text-sm font-medium leading-6 text-[var(--muted)]">{{ message }}</p>
      <div class="mt-6 flex flex-wrap justify-center gap-3">
        <button type="button" class="btn-primary" @click="$emit('retry')">Thử lại</button>
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, default: 'Đã xảy ra lỗi' },
  message: { type: String, default: 'Không thể tải dữ liệu. Vui lòng thử lại.' },
})

defineEmits(['retry'])
</script>
