<template>
  <aside class="flex min-h-0 flex-col border-r border-slate-200 bg-white">
    <div class="border-b border-slate-200 p-4">
      <div class="flex items-center justify-between">
        <div><h2 class="text-sm font-black text-slate-900">Danh sách câu hỏi</h2><p class="mt-0.5 text-xs text-slate-500">{{ questions.length }} câu · {{ totalPoints }} điểm</p></div>
        <button type="button" class="icon-add" aria-label="Thêm câu hỏi" @click="$emit('add-question')"><Plus class="h-4 w-4" /></button>
      </div>
      <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-gradient-to-r from-violet-500 to-fuchsia-500" :style="{ width: completionWidth }"></div></div>
    </div>
    <nav class="flex-1 space-y-2 overflow-y-auto p-3" aria-label="Danh sách câu hỏi">
      <button v-for="(question, index) in questions" :key="question.id" :ref="(element) => setQuestionItemRef(element, question.id)" type="button" class="question-item group relative" :class="[invalidQuestionIds.has(question.id) ? 'question-item-invalid' : (question.id === activeQuestionId ? 'question-item-valid-active' : 'question-item-valid'), selectedQuestionIds.includes(question.id) ? 'ring-2 ring-fuchsia-400' : '', highlightQuestionIds.includes(question.id) ? 'question-item-added' : '', draggedQuestionId === question.id ? 'question-item-dragging' : '', dragTargetId === question.id ? (dropPosition === 'before' ? 'question-drop-before' : 'question-drop-after') : '']" :draggable="!selectionMode" @click="handleQuestionClick(question.id)" @dragstart="startQuestionDrag($event, question.id)" @dragover.prevent="updateQuestionDrop($event, question.id)" @drop.prevent="finishQuestionDrop(question.id)" @dragend="resetQuestionDrag" @mouseenter="showQuestionTooltip($event, question)" @mousemove="moveQuestionTooltip" @mouseleave="hideQuestionTooltip">
        <input v-if="selectionMode" type="checkbox" class="h-4 w-4 shrink-0 accent-fuchsia-600" :checked="selectedQuestionIds.includes(question.id)" tabindex="-1" aria-hidden="true" />
        <GripVertical v-else class="h-4 w-4 shrink-0 cursor-grab text-slate-300 transition group-hover:text-violet-500" aria-hidden="true" />
        <span class="question-number" :class="invalidQuestionIds.has(question.id) ? 'bg-rose-100 text-rose-700' : (question.id === activeQuestionId ? 'bg-violet-600 text-white' : 'bg-slate-100 text-slate-600')">{{ index + 1 }}</span>
        <span class="min-w-0 flex-1"><span class="question-summary text-xs font-bold text-slate-800"><MathText v-if="question.content" :content="question.content" compact /><template v-else>Câu hỏi chưa có nội dung</template></span><span class="mt-1 flex flex-wrap items-center gap-1.5 text-[10px] font-semibold text-slate-500"><span>{{ typeLabels[question.type] || question.type }}</span><span class="h-1 w-1 rounded-full bg-slate-300"></span><span>{{ question.points }} điểm</span></span><span class="mt-1.5 flex flex-wrap items-center gap-1"><span class="question-tag" :class="difficultyConfig[question.difficulty || 'medium'].class">{{ difficultyConfig[question.difficulty || 'medium'].label }}</span><span v-if="questionOrigins[question.id]" class="question-tag" :class="sourceConfig[questionOrigins[question.id]].class">{{ sourceConfig[questionOrigins[question.id]].label }}</span></span></span>
        <ChevronRight class="h-4 w-4 shrink-0 text-slate-300 transition group-hover:text-violet-500" />
      </button>
    </nav>
    <Teleport to="body"><div v-if="hoveredQuestion" class="question-hover-tooltip" :style="tooltipStyle"><p class="mb-1 text-[9px] font-black uppercase tracking-wider text-fuchsia-300">Nội dung câu hỏi</p><MathText :content="hoveredQuestion.content || 'Câu hỏi chưa có nội dung'" compact /></div></Teleport>
    <div class="border-t border-slate-200 p-3"><button type="button" class="flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-violet-300 bg-violet-50 px-3 py-3 text-xs font-black text-violet-700 transition hover:bg-violet-100" @click="$emit('add-question')"><Plus class="h-4 w-4" /> Thêm câu hỏi mới</button></div>
  </aside>
</template>
<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { ChevronRight, GripVertical, Plus } from 'lucide-vue-next'
import MathText from '../MathText.vue'
const props = defineProps({ questions: { type: Array, default: () => [] }, activeQuestionId: { type: [Number, String], default: null }, questionOrigins: { type: Object, default: () => ({}) }, invalidQuestionIds: { type: Set, default: () => new Set() }, selectionMode: Boolean, selectedQuestionIds: { type: Array, default: () => [] }, highlightQuestionIds: { type: Array, default: () => [] } })
const emit = defineEmits(['select-question', 'toggle-question', 'reorder-question', 'add-question'])
const hoveredQuestion = ref(null)
const draggedQuestionId = ref(null)
const dragTargetId = ref(null)
const dropPosition = ref('after')
const questionItemRefs = new Map()
const setQuestionItemRef = (element, id) => {
  if (element) questionItemRefs.set(id, element)
  else questionItemRefs.delete(id)
}
const tooltipPosition = ref({ x: 0, y: 0 })
const tooltipStyle = computed(() => ({ left: `${tooltipPosition.value.x}px`, top: `${tooltipPosition.value.y}px` }))
const updateTooltipPosition = (event) => {
  const width = 340
  const height = 160
  tooltipPosition.value = {
    x: Math.max(12, Math.min(event.clientX + 14, window.innerWidth - width - 12)),
    y: Math.max(12, Math.min(event.clientY + 14, window.innerHeight - height - 12)),
  }
}
const showQuestionTooltip = (event, question) => {
  hoveredQuestion.value = question
  updateTooltipPosition(event)
}
const moveQuestionTooltip = (event) => { if (hoveredQuestion.value) updateTooltipPosition(event) }
const hideQuestionTooltip = () => { hoveredQuestion.value = null }
const startQuestionDrag = (event, questionId) => {
  if (props.selectionMode) return event.preventDefault()
  draggedQuestionId.value = questionId
  event.dataTransfer.effectAllowed = 'move'
  event.dataTransfer.setData('text/plain', String(questionId))
}
const updateQuestionDrop = (event, questionId) => {
  if (!draggedQuestionId.value || draggedQuestionId.value === questionId) return
  const bounds = event.currentTarget.getBoundingClientRect()
  dragTargetId.value = questionId
  dropPosition.value = event.clientY < bounds.top + bounds.height / 2 ? 'before' : 'after'
  event.dataTransfer.dropEffect = 'move'
}
const resetQuestionDrag = () => {
  draggedQuestionId.value = null
  dragTargetId.value = null
  dropPosition.value = 'after'
}
const finishQuestionDrop = (targetId) => {
  const draggedId = draggedQuestionId.value
  if (draggedId && draggedId !== targetId) emit('reorder-question', { draggedId, targetId, position: dropPosition.value })
  resetQuestionDrag()
}
watch(() => props.highlightQuestionIds, async (ids) => {
  if (!ids.length) return
  await nextTick()
  questionItemRefs.get(ids[0])?.scrollIntoView({ behavior: 'smooth', block: 'center' })
}, { deep: true })
const handleQuestionClick = (id) => emit(props.selectionMode ? 'toggle-question' : 'select-question', id)
const typeLabels = { single_choice: 'Một đáp án', multi_choice: 'Nhiều đáp án', fill_in: 'Điền đáp án', true_false: 'Đúng / Sai' }
const difficultyConfig = { easy: { label: 'Dễ', class: 'bg-emerald-50 text-emerald-700' }, medium: { label: 'Vừa', class: 'bg-amber-50 text-amber-700' }, hard: { label: 'Khó', class: 'bg-rose-50 text-rose-700' } }
const sourceConfig = { personal: { label: 'Kho cá nhân', class: 'bg-violet-50 text-violet-700' }, bank: { label: 'Ngân hàng', class: 'bg-sky-50 text-sky-700' }, ai: { label: 'AI', class: 'bg-fuchsia-50 text-fuchsia-700' }, ocr: { label: 'OCR', class: 'bg-amber-50 text-amber-700' } }
const totalPoints = computed(() => props.questions.reduce((sum, question) => sum + Number(question.points || 0), 0))
const completionWidth = computed(() => `${Math.min(100, props.questions.length * 20)}%`)
</script>
<style scoped>
.icon-add { @apply grid h-9 w-9 place-items-center rounded-xl bg-violet-600 text-white shadow-md shadow-violet-200 transition hover:bg-violet-700; }
.question-item { @apply flex w-full items-center gap-2 rounded-xl border px-2.5 py-2 text-left transition; }
.question-item-valid-active { @apply border-emerald-500 bg-emerald-50/70 shadow-sm ring-1 ring-emerald-100; }
.question-item-valid { @apply border-emerald-300 bg-white hover:border-emerald-500 hover:bg-emerald-50/50; }
.question-item-invalid { @apply border-2 border-rose-600 bg-rose-50 shadow-sm ring-1 ring-rose-100 hover:border-rose-700 hover:bg-rose-100/70; }
.question-item-added { animation: added-question-flash .55s ease-in-out 3; }
.question-item-dragging { opacity: .45; }
.question-drop-before::before, .question-drop-after::after { content: ''; position: absolute; right: .25rem; left: .25rem; height: 3px; border-radius: 999px; background: rgb(124 58 237); box-shadow: 0 0 0 2px rgb(237 233 254); }
.question-drop-before::before { top: -.35rem; }
.question-drop-after::after { bottom: -.35rem; }
.question-number { @apply grid h-7 w-7 shrink-0 place-items-center rounded-lg text-[11px] font-black transition; }
.question-tag { @apply inline-flex rounded-md px-1.5 py-0.5 text-[9px] font-black; }
.question-summary { display: -webkit-box; max-height: 2.5rem; overflow: hidden; line-height: 1.25rem; overflow-wrap: anywhere; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
.question-summary :deep(.math-content) { display: inline; }
.question-hover-tooltip { @apply pointer-events-none fixed z-[100] max-h-40 w-[min(340px,calc(100vw-24px))] overflow-hidden rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-[11px] font-semibold leading-5 text-white shadow-2xl; }
.question-hover-tooltip :deep(.math-content) { color: inherit; font-size: inherit; }
@keyframes added-question-flash {
  0%, 100% { box-shadow: 0 0 0 0 rgba(124, 58, 237, 0); }
  50% { border-color: rgb(124 58 237); background-color: rgb(245 243 255); box-shadow: 0 0 0 4px rgba(124, 58, 237, .18); }
}
@media (prefers-reduced-motion: reduce) { .question-item-added { animation: none; border-color: rgb(124 58 237); background-color: rgb(245 243 255); } }
</style>
