<template>
  <header class="editor-header">
    <div class="min-w-0 flex-1">
      <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.16em] text-violet-600">
        <span class="h-2 w-2 rounded-full bg-violet-500"></span> QuizFlex Studio
      </div>
      <input :value="title" class="quiz-title mt-1 w-full max-w-2xl truncate border-0 bg-transparent p-0 text-xl font-black text-slate-900 outline-none placeholder:text-slate-400 focus:ring-0 sm:text-2xl" aria-label="Tên quiz" placeholder="Đặt tên cho quiz..." @input="$emit('update:title', $event.target.value)" />
      <div v-if="educationLevelName || gradeName || subjectName || topicName" class="mt-2 flex flex-wrap items-center gap-1.5">
        <span v-if="educationLevelName" class="quiz-context-chip">{{ educationLevelName }}</span>
        <span v-if="gradeName" class="quiz-context-chip">{{ gradeName }}</span>
        <span v-if="subjectName" class="quiz-context-chip quiz-context-chip-subject">{{ subjectName }}</span>
        <span v-if="topicName" class="quiz-context-chip quiz-context-chip-topic" :title="topicName">{{ topicName }}</span>
      </div>
    </div>
    <div class="flex shrink-0 items-center gap-2 sm:gap-3">
      <div class="hidden items-center gap-2 rounded-full bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 sm:flex"><Check class="h-3.5 w-3.5" />{{ saveStatus }}</div>
      <label class="quiz-time-control" title="Thời gian làm bài"><Clock3 class="h-4 w-4 text-violet-600" /><span class="hidden xl:inline">Thời gian</span><input :value="Math.max(1, Math.round(timeLimitSeconds / 60))" type="number" min="1" max="1440" aria-label="Thời gian làm bài, tính bằng phút" @change="$emit('update:time-limit', Number($event.target.value))" /><span>phút</span></label>
      <button type="button" class="header-button header-button-danger disabled:cursor-not-allowed disabled:opacity-50" :disabled="deleteDisabled || isDeleting" title="Xóa quiz" aria-label="Xóa quiz" @click="$emit('delete')"><LoaderCircle v-if="isDeleting" class="h-4 w-4 animate-spin" /><Trash2 v-else class="h-4 w-4" /><span class="hidden lg:inline">Xóa quiz</span></button>
      <button type="button" class="header-button header-button-primary disabled:cursor-not-allowed disabled:opacity-60" :disabled="saveDisabled" @click="$emit('complete')"><span>Hoàn tất</span><ArrowRight class="h-4 w-4" /></button>
    </div>
  </header>
</template>
<script setup>
import { ArrowRight, Check, Clock3, LoaderCircle, Trash2 } from 'lucide-vue-next'
defineProps({ title: { type: String, default: '' }, saveStatus: { type: String, default: 'Đã lưu bản nháp' }, timeLimitSeconds: { type: Number, default: 600 }, educationLevelName: { type: String, default: '' }, gradeName: { type: String, default: '' }, subjectName: { type: String, default: '' }, topicName: { type: String, default: '' }, isSaving: { type: Boolean, default: false }, saveDisabled: { type: Boolean, default: false }, isDeleting: { type: Boolean, default: false }, deleteDisabled: { type: Boolean, default: false } })
defineEmits(['update:title', 'update:time-limit', 'complete', 'delete'])
</script>
<style scoped>
.editor-header { @apply flex min-h-[76px] items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 py-3 sm:px-6; }
.quiz-context-chip { @apply max-w-40 truncate rounded-md border border-slate-200 bg-slate-50 px-2 py-1 text-[10px] font-bold text-slate-600; }
.quiz-context-chip-subject { @apply border-violet-100 bg-violet-50 text-violet-700; }
.quiz-context-chip-topic { @apply max-w-52 border-fuchsia-100 bg-fuchsia-50 text-fuchsia-700; }
.header-button { @apply inline-flex items-center justify-center gap-2 rounded-xl px-3.5 py-2.5 text-xs font-black transition sm:px-4; }
.header-button-secondary { @apply border border-slate-200 bg-white text-slate-700 hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700; }
.header-button-danger { @apply border border-rose-200 bg-white text-rose-600 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700; }
.header-button-primary { @apply bg-violet-600 text-white shadow-lg shadow-violet-200 hover:bg-violet-700; }
.quiz-time-control { @apply inline-flex h-10 items-center gap-1.5 rounded-xl border border-violet-200 bg-violet-50 px-2.5 text-[10px] font-black text-slate-600; }
.quiz-time-control input { @apply w-11 border-0 bg-transparent p-0 text-right text-xs font-black text-violet-700 outline-none focus:ring-0; }
@media (max-width: 639px) {
  .editor-header { min-height: 64px; gap: .5rem; padding: .625rem .75rem; }
  .editor-header > div:first-child > div:first-child { display: none; }
  .quiz-title { margin-top: 0; font-size: .95rem; line-height: 1.25rem; }
  .header-button-secondary { display: none; }
  .quiz-time-control { padding: .5rem; }
  .quiz-time-control > svg { display: none; }
  .header-button-primary { padding: .625rem .75rem; white-space: nowrap; }
}
</style>
