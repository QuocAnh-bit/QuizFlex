<template>
  <main class="min-w-0 overflow-y-auto bg-slate-100/80 p-3 sm:p-4 lg:p-5">
    <section v-if="question" class="mx-auto w-full max-w-5xl">
      <div class="question-meta mb-2.5 flex flex-wrap items-center justify-between gap-3 px-1">
        <div class="flex min-w-0 flex-wrap items-center gap-2">
          <GripVertical class="h-4 w-4 text-slate-400" />
          <span class="text-xs font-black text-slate-600">Câu {{ questionNumber }} / {{ totalQuestions }}</span>
          <select :value="question.type" class="compact-control min-w-[132px]" aria-label="Loại câu hỏi" @change="$emit('change-type', $event.target.value)">
            <option value="single_choice">Một đáp án</option>
            <option value="multi_choice">Nhiều đáp án</option>
            <option value="fill_in">Điền đáp án</option>
            <option value="true_false">Đúng / Sai</option>
          </select>
          <select :value="question.difficulty || 'medium'" class="compact-control min-w-[104px]" aria-label="Mức độ câu hỏi" @change="$emit('update-difficulty', $event.target.value)">
            <option value="easy">Dễ</option>
            <option value="medium">Vừa</option>
            <option value="hard">Khó</option>
          </select>
        </div>
        <label class="compact-points">
          <input :value="question.points" type="number" min="0.01" max="10" step="any" class="w-12 bg-transparent text-right text-xs font-black text-violet-700 outline-none" aria-label="Điểm câu hỏi" @input="$emit('update-points', Number($event.target.value))" />
          <span>điểm</span>
        </label>
      </div>

      <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/50">
        <div class="border-b border-slate-100 p-4 sm:p-5">
          <div class="flex items-center justify-between gap-4"><p class="text-[11px] font-black uppercase tracking-[0.14em] text-violet-600">Nội dung câu hỏi</p><button type="button" class="grid h-8 w-8 place-items-center rounded-lg border border-slate-200 text-slate-400 hover:bg-violet-50 hover:text-violet-600" title="Thêm hình ảnh" @click="openImagePicker"><ImagePlus class="h-4 w-4" /></button></div>
          <input ref="imageInput" class="hidden" type="file" accept="image/png,image/jpeg,image/webp,image/gif" @change="handleImageChange" />
          <MixedContentEditor ref="contentEditor" v-model="question.content" class="mt-2" placeholder="Nhập nội dung câu hỏi..." @edit-formula="openFormulaEditor('question', null, $event)" />
          <div v-if="question.image_url" class="question-image-preview">
            <img :src="question.image_url" alt="Ảnh minh họa câu hỏi" />
            <div class="question-image-actions">
              <button type="button" @click="openImagePicker">Đổi ảnh</button>
              <button type="button" class="text-rose-600 hover:bg-rose-50" @click="removeImage">Xóa ảnh</button>
            </div>
          </div>
          <p v-if="imageError" class="mt-2 text-xs font-semibold text-rose-600">{{ imageError }}</p>
          <div class="mt-2 flex items-center gap-1 border-t border-slate-100 pt-2 text-slate-400"><button type="button" class="format-button font-serif font-black">B</button><button type="button" class="format-button italic">I</button><button type="button" class="inline-flex h-8 items-center gap-1.5 rounded-lg px-2 text-xs font-bold transition hover:bg-violet-50 hover:text-violet-600" @click="openFormulaEditor('question')"><Sigma class="h-4 w-4" /> Công thức</button><span class="ml-auto text-[10px] font-semibold">{{ question.content.length }} ký tự</span></div>
        </div>

        <div class="bg-slate-50/60 p-4 sm:p-5">
          <div class="mb-3 flex items-center justify-between"><div><p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-600">Thiết lập đáp án</p><p class="mt-0.5 text-xs text-slate-400">Đánh dấu đáp án đúng trước khi hoàn tất.</p></div><CheckCircle2 class="h-5 w-5 text-emerald-500" /></div>
          <component :is="activeEditor" :question="question" @add-answer="$emit('add-answer')" @remove-answer="$emit('remove-answer', $event)" @add-fill-answer="$emit('add-fill-answer')" @remove-fill-answer="$emit('remove-fill-answer', $event)" @edit-formula="openFormulaEditor($event.type, $event.target, $event.formula)" />
        </div>
      </article>

      <button type="button" class="mx-auto mt-4 flex items-center gap-2 rounded-xl border border-dashed border-slate-300 bg-white px-5 py-2.5 text-xs font-black text-slate-600 shadow-sm transition hover:border-violet-300 hover:text-violet-700" @click="$emit('add-question')"><Plus class="h-4 w-4" /> Thêm câu hỏi phía dưới</button>
    </section>
    <section v-else class="grid h-full min-h-[360px] place-items-center">
      <div class="max-w-sm rounded-2xl border border-dashed border-slate-300 bg-white px-8 py-10 text-center shadow-sm">
        <div class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-violet-100 text-violet-600"><FileQuestion class="h-6 w-6" /></div>
        <h2 class="mt-4 text-base font-black text-slate-800">Quiz chưa có câu hỏi</h2>
        <p class="mt-1.5 text-xs leading-5 text-slate-500">Thêm một câu hỏi mới hoặc chọn câu hỏi từ nguồn có sẵn để bắt đầu.</p>
        <button type="button" class="mx-auto mt-5 flex items-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-xs font-black text-white shadow-md shadow-violet-200 transition hover:bg-violet-700" @click="$emit('add-question')"><Plus class="h-4 w-4" /> Thêm câu hỏi</button>
      </div>
    </section>
    <MathFormulaEditor v-model="formulaDraft" :open="formulaEditorOpen" :editing="Boolean(formulaTarget?.formula)" @close="closeFormulaEditor" @confirm="insertFormula" />
  </main>
</template>
<script setup>
import { computed, nextTick, ref } from 'vue'
import { CheckCircle2, FileQuestion, GripVertical, ImagePlus, Plus, Sigma } from 'lucide-vue-next'
import MathFormulaEditor from './MathFormulaEditor.vue'
import MixedContentEditor from './MixedContentEditor.vue'
import FillInEditor from './question-types/FillInEditor.vue'
import MultiChoiceEditor from './question-types/MultiChoiceEditor.vue'
import SingleChoiceEditor from './question-types/SingleChoiceEditor.vue'
import TrueFalseEditor from './question-types/TrueFalseEditor.vue'
const props = defineProps({ question: { type: Object, default: null }, questionNumber: { type: Number, default: 1 }, totalQuestions: { type: Number, default: 0 } })
defineEmits(['change-type', 'update-difficulty', 'update-points', 'add-answer', 'remove-answer', 'add-fill-answer', 'remove-fill-answer', 'add-question'])
const editors = { single_choice: SingleChoiceEditor, multi_choice: MultiChoiceEditor, fill_in: FillInEditor, true_false: TrueFalseEditor }
const activeEditor = computed(() => editors[props.question?.type] || SingleChoiceEditor)
const contentEditor = ref(null)
const imageInput = ref(null)
const imageError = ref('')
const formulaEditorOpen = ref(false)
const formulaDraft = ref('')
const formulaTarget = ref(null)
const openImagePicker = () => {
  imageError.value = ''
  imageInput.value?.click()
}
const fileToDataUrl = (file) => new Promise((resolve, reject) => {
  const reader = new FileReader()
  reader.onload = () => resolve(reader.result)
  reader.onerror = () => reject(new Error('Không đọc được ảnh đã chọn.'))
  reader.readAsDataURL(file)
})
const handleImageChange = async (event) => {
  const file = event.target.files?.[0]
  event.target.value = ''
  if (!file || !props.question) return
  if (!['image/png', 'image/jpeg', 'image/webp', 'image/gif'].includes(file.type)) {
    imageError.value = 'Chỉ hỗ trợ ảnh PNG, JPG, WEBP hoặc GIF.'
    return
  }
  if (file.size > 3 * 1024 * 1024) {
    imageError.value = 'Ảnh câu hỏi không được vượt quá 3MB.'
    return
  }
  try {
    props.question.image_url = await fileToDataUrl(file)
    imageError.value = ''
  } catch (error) {
    imageError.value = error.message || 'Không đọc được ảnh đã chọn.'
  }
}
const removeImage = () => {
  if (!props.question) return
  props.question.image_url = null
  imageError.value = ''
}
defineExpose({ openImagePicker })
const openFormulaEditor = (type, target = null, formula = null) => {
  formulaTarget.value = { type, target, formula, insertAt: type === 'question' && !formula ? contentEditor.value?.getCursorOffset() : null }
  formulaDraft.value = formula?.latex || ''
  formulaEditorOpen.value = true
}
const closeFormulaEditor = () => { formulaEditorOpen.value = false; formulaTarget.value = null }
const wrapFormula = (latex, raw = '') => raw.startsWith('$$') ? `$$${latex}$$` : raw.startsWith('\\[') ? `\\[${latex}\\]` : raw.startsWith('\\(') ? `\\(${latex}\\)` : `$${latex}$`
const insertFormula = async (latex) => {
  const currentTarget = formulaTarget.value
  if (!currentTarget) return
  if (currentTarget.type === 'question') {
    const content = props.question.content || ''
    if (currentTarget.formula) {
      const { start, end, raw } = currentTarget.formula
      props.question.content = `${content.slice(0, start)}${wrapFormula(latex, raw)}${content.slice(end)}`
    } else {
      const position = Math.min(currentTarget.insertAt ?? content.length, content.length)
      props.question.content = `${content.slice(0, position)}${wrapFormula(latex)}${content.slice(position)}`
    }
    closeFormulaEditor()
    await nextTick()
    contentEditor.value?.focus()
    return
  }
  const targetContent = currentTarget.target.content || ''
  if (currentTarget.formula) {
    const { start, end, raw } = currentTarget.formula
    currentTarget.target.content = `${targetContent.slice(0, start)}${wrapFormula(latex, raw)}${targetContent.slice(end)}`
  } else {
    currentTarget.target.content = `${targetContent}${targetContent ? ' ' : ''}${wrapFormula(latex)}`
  }
  closeFormulaEditor()
}
</script>
<style scoped>
.format-button { @apply grid h-8 w-8 place-items-center rounded-lg text-sm transition hover:bg-slate-100 hover:text-violet-600; }
.compact-control { @apply rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 outline-none shadow-sm transition focus:border-violet-400 focus:ring-2 focus:ring-violet-100; }
.compact-points { @apply inline-flex items-center gap-1 rounded-xl bg-violet-100 px-3 py-2 text-[10px] font-black text-violet-700; }
.question-image-preview { @apply mt-3 overflow-hidden rounded-xl border border-slate-200 bg-slate-50; }
.question-image-preview img { @apply mx-auto max-h-72 w-auto max-w-full object-contain p-2; }
.question-image-actions { @apply flex justify-end gap-2 border-t border-slate-200 bg-white p-2; }
.question-image-actions button { @apply rounded-lg px-3 py-1.5 text-xs font-black text-violet-700 transition hover:bg-violet-50; }
@media (max-width: 639px) {
  main { padding: .75rem; }
  .question-meta { gap: .5rem; }
  .compact-control { min-width: 0 !important; padding: .5rem .625rem; }
  .compact-points { margin-left: auto; padding: .5rem .625rem; }
  article > div { padding: .875rem !important; }
}
</style>
