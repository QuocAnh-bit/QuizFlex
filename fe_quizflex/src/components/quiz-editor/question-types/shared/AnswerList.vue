<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between gap-3"><p class="text-xs text-slate-500">{{ mode === 'single' ? 'Chọn một đáp án đúng bằng nút tròn.' : 'Có thể đánh dấu nhiều đáp án đúng.' }}</p><span class="shrink-0 text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ question.answers.length }} đáp án</span></div>
    <div class="grid gap-2.5">
      <div v-for="(answer, index) in question.answers" :key="answer.id" class="answer-card group" :class="answer.is_correct ? 'answer-card-correct' : 'answer-card-idle'">
        <div class="flex min-w-0 flex-1 items-center gap-3">
          <input v-if="mode === 'single'" type="radio" :name="`answer-${question.id}`" :checked="answer.is_correct" :aria-label="`Chọn đáp án ${letter(index)} là đáp án đúng`" class="h-4 w-4 cursor-pointer accent-violet-600" @change="setSingleCorrect(index)" />
          <input v-else v-model="answer.is_correct" type="checkbox" :aria-label="`Chọn đáp án ${letter(index)} là đáp án đúng`" class="h-4 w-4 cursor-pointer rounded accent-violet-600" />
          <span class="answer-letter" :class="answer.is_correct ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'">{{ letter(index) }}</span>
          <MixedContentEditor v-model="answer.content" compact class="min-w-0 flex-1" placeholder="Nhập đáp án..." @edit-formula="$emit('edit-formula', { type: 'answer', target: answer, formula: $event })" />
        </div>
        <button type="button" class="formula-answer" title="Chèn công thức vào đáp án" @click="$emit('edit-formula', { type: 'answer', target: answer })"><Sigma class="h-4 w-4" /><span class="sr-only">Công thức</span></button>
        <button type="button" class="remove-answer" :disabled="question.answers.length <= 2" title="Xóa đáp án" @click="$emit('remove-answer', index)"><X class="h-4 w-4" /></button>
      </div>
    </div>
    <button type="button" class="add-answer-button" @click="$emit('add-answer')"><Plus class="h-4 w-4" /> Thêm đáp án</button>
  </div>
</template>
<script setup>
import { Plus, Sigma, X } from 'lucide-vue-next'
import MixedContentEditor from '../../MixedContentEditor.vue'
const props = defineProps({ question: { type: Object, required: true }, mode: { type: String, required: true } })
defineEmits(['add-answer', 'remove-answer', 'edit-formula'])
const letter = (index) => String.fromCharCode(65 + index)
const setSingleCorrect = (selectedIndex) => props.question.answers.forEach((answer, index) => { answer.is_correct = index === selectedIndex })
</script>
<style scoped>
.answer-card { @apply flex min-h-[50px] items-center gap-2 rounded-xl border px-3 py-2.5 transition; }
.answer-card-correct { @apply border-emerald-300 bg-emerald-50/50; }
.answer-card-idle { @apply border-slate-200 bg-white hover:border-violet-200 hover:bg-violet-50/20; }
.answer-letter { @apply grid h-7 w-7 shrink-0 place-items-center rounded-lg text-xs font-black; }
.remove-answer { @apply grid h-8 w-8 shrink-0 place-items-center rounded-lg text-slate-400 opacity-0 transition hover:bg-rose-50 hover:text-rose-500 focus:opacity-100 disabled:cursor-not-allowed disabled:hover:bg-transparent disabled:hover:text-slate-300 group-hover:opacity-100; }
.formula-answer { @apply grid h-8 w-8 shrink-0 place-items-center rounded-lg text-slate-400 opacity-0 transition hover:bg-violet-50 hover:text-violet-600 focus:opacity-100 group-hover:opacity-100; }
.add-answer-button { @apply inline-flex items-center gap-2 rounded-xl border border-dashed border-violet-300 bg-violet-50 px-4 py-2.5 text-xs font-black text-violet-700 transition hover:bg-violet-100; }
@media (max-width: 639px) {
  .answer-card { align-items: flex-start; padding: .625rem; }
  .answer-card > div { gap: .5rem; }
  .answer-letter { width: 1.75rem; height: 1.75rem; }
  .remove-answer, .formula-answer { opacity: 1; }
}
</style>
