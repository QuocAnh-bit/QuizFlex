<template>
  <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/50">
    <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 px-4 py-3 sm:px-5">
      <select :value="question.type" class="compact-control" aria-label="Loại câu hỏi" @change="$emit('change-type', $event.target.value)">
        <option value="single_choice">Một đáp án</option>
        <option value="multi_choice">Nhiều đáp án</option>
        <option value="fill_in">Điền đáp án</option>
        <option value="true_false">Đúng / Sai</option>
      </select>
      <select v-model="question.difficulty" class="compact-control" aria-label="Mức độ câu hỏi">
        <option value="easy">Dễ</option><option value="medium">Vừa</option><option value="hard">Khó</option>
      </select>
      <span class="ml-auto text-[10px] font-semibold text-slate-400">{{ question.content.length }} ký tự</span>
    </div>

    <section class="border-b border-slate-100 p-4 sm:p-5">
      <div class="flex items-center justify-between gap-4">
        <p class="text-[11px] font-black uppercase tracking-[.14em] text-violet-600">Nội dung câu hỏi</p>
        <button type="button" class="icon-button" title="Thêm hình ảnh" @click="imageInput?.click()"><ImagePlus class="h-4 w-4" /></button>
      </div>
      <input ref="imageInput" class="hidden" type="file" accept="image/png,image/jpeg,image/webp,image/gif" @change="handleImageChange" />
      <MixedContentEditor ref="contentEditor" v-model="question.content" class="mt-2" placeholder="Nhập nội dung câu hỏi..." @edit-formula="openFormulaEditor('question', null, $event)" />
      <div v-if="question.image_url" class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
        <img :src="question.image_url" alt="Ảnh minh họa câu hỏi" class="mx-auto max-h-72 max-w-full object-contain p-2" />
        <div class="flex justify-end gap-2 border-t border-slate-200 bg-white p-2">
          <button type="button" class="image-action" @click="imageInput?.click()">Đổi ảnh</button>
          <button type="button" class="image-action text-rose-600" @click="question.image_url = null">Xóa ảnh</button>
        </div>
      </div>
      <p v-if="imageError" class="mt-2 text-xs font-semibold text-rose-600">{{ imageError }}</p>
      <button type="button" class="mt-2 inline-flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-bold text-slate-500 hover:bg-violet-50 hover:text-violet-600" @click="openFormulaEditor('question')"><Sigma class="h-4 w-4" /> Chèn công thức LaTeX</button>
    </section>

    <section id="edit-answers-section" class="bg-slate-50/60 p-4 sm:p-5">
      <div class="mb-3"><p class="text-[11px] font-black uppercase tracking-[.14em] text-slate-600">Thiết lập đáp án</p><p class="mt-0.5 text-xs text-slate-400">Nội dung và công thức được hiển thị trực tiếp khi chỉnh sửa.</p></div>
      <component :is="activeEditor" :question="question" @add-answer="$emit('add-answer')" @remove-answer="$emit('remove-answer', $event)" @add-fill-answer="$emit('add-fill-answer')" @remove-fill-answer="$emit('remove-fill-answer', $event)" @edit-formula="openFormulaEditor($event.type, $event.target, $event.formula)" />
    </section>
  </article>
  <MathFormulaEditor v-model="formulaDraft" :open="formulaEditorOpen" :editing="Boolean(formulaTarget?.formula)" @close="closeFormulaEditor" @confirm="insertFormula" />
</template>

<script setup>
import { computed, nextTick, ref } from 'vue'
import { ImagePlus, Sigma } from 'lucide-vue-next'
import MixedContentEditor from '@/components/quiz-editor/MixedContentEditor.vue'
import MathFormulaEditor from '@/components/quiz-editor/MathFormulaEditor.vue'
import FillInEditor from '@/components/quiz-editor/question-types/FillInEditor.vue'
import MultiChoiceEditor from '@/components/quiz-editor/question-types/MultiChoiceEditor.vue'
import SingleChoiceEditor from '@/components/quiz-editor/question-types/SingleChoiceEditor.vue'
import TrueFalseEditor from '@/components/quiz-editor/question-types/TrueFalseEditor.vue'

const props = defineProps({ question: { type: Object, required: true } })
defineEmits(['change-type', 'add-answer', 'remove-answer', 'add-fill-answer', 'remove-fill-answer'])
const editors = { single_choice: SingleChoiceEditor, multi_choice: MultiChoiceEditor, fill_in: FillInEditor, true_false: TrueFalseEditor }
const activeEditor = computed(() => editors[props.question.type] || SingleChoiceEditor)
const contentEditor = ref(null)
const imageInput = ref(null)
const imageError = ref('')
const formulaEditorOpen = ref(false)
const formulaDraft = ref('')
const formulaTarget = ref(null)
const openFormulaEditor = (type, target = null, formula = null) => {
  formulaTarget.value = { type, target, formula, insertAt: type === 'question' && !formula ? contentEditor.value?.getCursorOffset() : null }
  formulaDraft.value = formula?.latex || ''
  formulaEditorOpen.value = true
}
const closeFormulaEditor = () => { formulaEditorOpen.value = false; formulaTarget.value = null }
const wrapFormula = (latex, raw = '') => raw.startsWith('$$') ? `$$${latex}$$` : raw.startsWith('\\[') ? `\\[${latex}\\]` : raw.startsWith('\\(') ? `\\(${latex}\\)` : `$${latex}$`
const insertFormula = async (latex) => {
  const current = formulaTarget.value
  if (!current) return
  const target = current.type === 'question' ? props.question : current.target
  const content = target.content || ''
  if (current.formula) target.content = `${content.slice(0, current.formula.start)}${wrapFormula(latex, current.formula.raw)}${content.slice(current.formula.end)}`
  else {
    const position = current.type === 'question' ? Math.min(current.insertAt ?? content.length, content.length) : content.length
    target.content = `${content.slice(0, position)}${content && current.type !== 'question' ? ' ' : ''}${wrapFormula(latex)}${content.slice(position)}`
  }
  closeFormulaEditor()
  await nextTick()
  if (current.type === 'question') contentEditor.value?.focus()
}
const handleImageChange = (event) => {
  const file = event.target.files?.[0]
  event.target.value = ''
  if (!file) return
  if (!['image/png', 'image/jpeg', 'image/webp', 'image/gif'].includes(file.type) || file.size > 3 * 1024 * 1024) {
    imageError.value = 'Chỉ hỗ trợ PNG, JPG, WEBP, GIF tối đa 3MB.'
    return
  }
  const reader = new FileReader()
  reader.onload = () => { props.question.image_url = reader.result; imageError.value = '' }
  reader.onerror = () => { imageError.value = 'Không đọc được ảnh đã chọn.' }
  reader.readAsDataURL(file)
}
</script>

<style scoped>
.compact-control { @apply rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 outline-none shadow-sm focus:border-violet-400 focus:ring-2 focus:ring-violet-100; }
.icon-button { @apply grid h-8 w-8 place-items-center rounded-lg border border-slate-200 text-slate-400 hover:bg-violet-50 hover:text-violet-600; }
.image-action { @apply rounded-lg px-3 py-1.5 text-xs font-black text-violet-700 hover:bg-violet-50; }
</style>
