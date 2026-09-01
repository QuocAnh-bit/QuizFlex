<template>
  <Teleport to="body">
    <div class="source-modal-overlay fixed inset-0 z-[100] grid place-items-center overflow-y-auto bg-slate-950/40 p-4 backdrop-blur-sm" @click.self="$emit('close')">
      <section class="source-modal-panel w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <header class="flex items-start justify-between border-b border-slate-200 px-5 py-4">
          <div>
            <p class="text-[11px] font-black uppercase tracking-[0.16em] text-violet-600">QuizFlex Studio</p>
            <h2 class="mt-1 text-xl font-black text-slate-900">Thêm câu hỏi từ nguồn</h2>
            <p class="mt-1 text-xs text-slate-500">Chọn cách bạn muốn bổ sung câu hỏi vào quiz.</p>
          </div>
          <button type="button" class="grid h-9 w-9 place-items-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Đóng" @click="$emit('close')"><X class="h-5 w-5" /></button>
        </header>

        <div class="grid gap-3 p-5 sm:grid-cols-2">
          <button v-for="option in sourceOptions" :key="option.value" type="button" class="source-option group" @click="$emit('choose', option.value)">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl" :class="option.iconClass"><component :is="option.icon" class="h-5 w-5" /></span>
            <span class="min-w-0 text-left"><span class="block text-sm font-black text-slate-900">{{ option.title }}</span><span class="mt-1 block text-xs leading-5 text-slate-500">{{ option.description }}</span></span>
            <ChevronRight class="ml-auto h-4 w-4 shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-violet-500" />
          </button>
        </div>
      </section>
    </div>
  </Teleport>
</template>

<script setup>
import { Bot, ChevronRight, Library, PenLine, UserRound, X } from 'lucide-vue-next'

defineEmits(['choose', 'close'])

const sourceOptions = [
  { value: 'new', title: 'Tạo câu hỏi mới', description: 'Bắt đầu với một câu hỏi trống trong editor.', icon: PenLine, iconClass: 'bg-violet-100 text-violet-700' },
  { value: 'personal', title: 'Chọn từ kho cá nhân', description: 'Duyệt các câu hỏi bạn đã tạo trước đây.', icon: UserRound, iconClass: 'bg-emerald-100 text-emerald-700' },
  { value: 'bank', title: 'Tìm trong ngân hàng', description: 'Chọn câu hỏi được chia sẻ trong cộng đồng.', icon: Library, iconClass: 'bg-sky-100 text-sky-700' },
  { value: 'ai', title: 'Tạo bằng AI', description: 'Tạo câu hỏi từ chủ đề hoặc tài liệu.', icon: Bot, iconClass: 'bg-fuchsia-100 text-fuchsia-700' },
]
</script>

<style scoped>
.source-option { @apply flex min-h-[92px] items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-violet-300 hover:bg-violet-50/40 hover:shadow-sm; }
@media (max-width: 639px) {
  .source-modal-overlay { align-items: end; padding: 0; }
  .source-modal-panel { max-height: 94dvh; overflow-y: auto; border-radius: 1.25rem 1.25rem 0 0; }
  .source-modal-panel header { padding: 1rem; }
  .source-modal-panel header + div { grid-template-columns: 1fr; gap: .625rem; padding: 1rem; }
  .source-option { min-height: 72px; padding: .75rem; }
}
</style>
