<template>
  <header class="editor-header">
    <div class="min-w-0 flex-1">
      <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.16em] text-violet-600">
        <span class="h-2 w-2 rounded-full bg-violet-500"></span> QuizFlex Studio
      </div>
      <input :value="title" class="quiz-title mt-1 w-full max-w-2xl truncate border-0 bg-transparent p-0 text-xl font-black text-slate-900 outline-none placeholder:text-slate-400 focus:ring-0 sm:text-2xl" aria-label="Tên quiz" placeholder="Đặt tên cho quiz..." @input="$emit('update:title', $event.target.value)" />
    </div>
    <div class="flex shrink-0 items-center gap-2 sm:gap-3">
      <div class="hidden items-center gap-2 rounded-full bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 sm:flex"><Check class="h-3.5 w-3.5" />{{ saveStatus }}</div>
      <button type="button" class="header-button header-button-secondary" @click="$emit('preview')"><Eye class="h-4 w-4" /><span class="hidden sm:inline">Xem trước</span></button>
      <button type="button" class="header-button header-button-primary disabled:cursor-not-allowed disabled:opacity-60" :disabled="saveDisabled" @click="$emit('complete')"><span>Hoàn tất</span><ArrowRight class="h-4 w-4" /></button>
    </div>
  </header>
</template>
<script setup>
import { ArrowRight, Check, Eye } from 'lucide-vue-next'
defineProps({ title: { type: String, default: '' }, saveStatus: { type: String, default: 'Đã lưu bản nháp' }, isSaving: { type: Boolean, default: false }, saveDisabled: { type: Boolean, default: false } })
defineEmits(['update:title', 'preview', 'complete'])
</script>
<style scoped>
.editor-header { @apply flex min-h-[76px] items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 py-3 sm:px-6; }
.header-button { @apply inline-flex items-center justify-center gap-2 rounded-xl px-3.5 py-2.5 text-xs font-black transition sm:px-4; }
.header-button-secondary { @apply border border-slate-200 bg-white text-slate-700 hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700; }
.header-button-primary { @apply bg-violet-600 text-white shadow-lg shadow-violet-200 hover:bg-violet-700; }
@media (max-width: 639px) {
  .editor-header { min-height: 64px; gap: .5rem; padding: .625rem .75rem; }
  .editor-header > div:first-child > div:first-child { display: none; }
  .quiz-title { margin-top: 0; font-size: .95rem; line-height: 1.25rem; }
  .header-button-secondary { display: none; }
  .header-button-primary { padding: .625rem .75rem; white-space: nowrap; }
}
</style>
