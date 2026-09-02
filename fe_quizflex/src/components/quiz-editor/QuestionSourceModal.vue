<template>
  <Teleport to="body">
    <div class="source-modal-overlay fixed inset-0 z-[100] grid place-items-center overflow-y-auto bg-slate-950/40 p-4 backdrop-blur-sm" @click.self="$emit('close')">
      <section class="source-modal-panel w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <header class="flex items-start justify-between border-b border-slate-200 px-5 py-4">
          <div>
            <p class="text-[11px] font-black uppercase tracking-[0.16em] text-violet-600">QuizFlex Studio</p>
            <h2 class="mt-1 text-xl font-black text-slate-900">Thêm câu hỏi</h2>
            <p class="mt-1 text-xs text-slate-500">Chọn cách bạn muốn bổ sung câu hỏi vào Quiz.</p>
          </div>
          <button type="button" class="grid h-9 w-9 place-items-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Đóng" @click="$emit('close')"><X class="h-5 w-5" /></button>
        </header>

        <div class="space-y-5 p-5">
          <section>
            <h3 class="source-group-title">Tạo câu hỏi mới</h3>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
              <button v-for="option in createOptions" :key="option.value" type="button" class="source-option group" @click="$emit('choose', option.value)">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl" :class="option.iconClass"><component :is="option.icon" class="h-5 w-5" /></span>
                <span class="min-w-0 text-left"><span class="block text-sm font-black text-slate-900">{{ option.title }}</span><span class="mt-1 block text-xs leading-5 text-slate-500">{{ option.description }}</span></span>
                <ChevronRight class="ml-auto h-4 w-4 shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-violet-500" />
              </button>
              <button type="button" class="source-option group sm:col-span-2" @click="$emit('choose', ocrOption.value)">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl" :class="ocrOption.iconClass"><component :is="ocrOption.icon" class="h-5 w-5" /></span>
                <span class="min-w-0 text-left"><span class="block text-sm font-black text-slate-900">{{ ocrOption.title }}</span><span class="mt-1 block text-xs leading-5 text-slate-500">{{ ocrOption.description }}</span></span>
                <ChevronRight class="ml-auto h-4 w-4 shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-violet-500" />
              </button>
            </div>
          </section>

          <section class="border-t border-slate-200 pt-5">
            <h3 class="source-group-title">Chọn câu hỏi có sẵn</h3>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
              <button v-for="option in existingOptions" :key="option.value" type="button" class="source-option group" @click="$emit('choose', option.value)">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl" :class="option.iconClass"><component :is="option.icon" class="h-5 w-5" /></span>
                <span class="min-w-0 text-left"><span class="block text-sm font-black text-slate-900">{{ option.title }}</span><span class="mt-1 block text-xs leading-5 text-slate-500">{{ option.description }}</span></span>
                <ChevronRight class="ml-auto h-4 w-4 shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-violet-500" />
              </button>
            </div>
          </section>
        </div>
      </section>
    </div>
  </Teleport>
</template>

<script setup>
import { Bot, Camera, ChevronRight, Library, PenLine, UserRound, X } from 'lucide-vue-next'

defineEmits(['choose', 'close'])

const createOptions = [
  { value: 'new', title: 'Tạo thủ công', description: 'Tự soạn câu hỏi trực tiếp trong editor.', icon: PenLine, iconClass: 'bg-violet-100 text-violet-700' },
  { value: 'ai', title: 'Tạo bằng AI', description: 'Sinh câu hỏi theo lớp, môn và chủ đề của Quiz.', icon: Bot, iconClass: 'bg-fuchsia-100 text-fuchsia-700' },
]
const ocrOption = { value: 'ocr', title: 'Quét ảnh / tài liệu', description: 'Nhận diện câu hỏi từ ảnh hoặc file PDF.', icon: Camera, iconClass: 'bg-amber-100 text-amber-700' }
const existingOptions = [
  { value: 'personal', title: 'Kho cá nhân', description: 'Dùng lại các câu hỏi bạn đã tạo trước đây.', icon: UserRound, iconClass: 'bg-emerald-100 text-emerald-700' },
  { value: 'bank', title: 'Ngân hàng câu hỏi', description: 'Tìm câu hỏi được chia sẻ và phù hợp với Quiz.', icon: Library, iconClass: 'bg-sky-100 text-sky-700' },
]
</script>

<style scoped>
.source-option { @apply flex min-h-[92px] items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-violet-300 hover:bg-violet-50/40 hover:shadow-sm; }
.source-group-title { @apply text-[10px] font-black uppercase tracking-[.16em] text-slate-500; }
@media (max-width: 639px) {
  .source-modal-overlay { align-items: end; padding: 0; }
  .source-modal-panel { max-height: 94dvh; overflow-y: auto; border-radius: 1.25rem 1.25rem 0 0; }
  .source-modal-panel header { padding: 1rem; }
  .source-modal-panel header + div { padding: 1rem; }
  .source-option { min-height: 72px; padding: .75rem; }
}
</style>
