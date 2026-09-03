<template>
  <section class="mx-auto w-full max-w-5xl min-w-0 pb-24">
    <div v-if="isLoading" class="rounded-2xl border border-slate-200 bg-white p-12 text-center text-sm font-semibold text-slate-500">Đang tải câu hỏi #{{ questionId }}...</div>
    <div v-else-if="fetchError" class="rounded-2xl border border-rose-200 bg-rose-50 p-6 text-sm text-rose-700">{{ fetchError }}<button class="ml-3 font-black underline" @click="goBack">Quay lại</button></div>
    <template v-else>
      <header class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <button type="button" class="grid h-10 w-10 place-items-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:text-violet-700" @click="goBack"><ArrowLeft class="h-4 w-4" /></button>
          <div><p class="text-[11px] font-black uppercase tracking-[.14em] text-violet-600">Kho câu hỏi cá nhân</p><h1 class="text-xl font-black text-slate-900">Chỉnh sửa câu hỏi #{{ questionId }}</h1></div>
        </div>
        <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-black text-white shadow-md shadow-violet-200 hover:bg-violet-700 disabled:opacity-50" :disabled="isSubmitting" @click="saveQuestion"><Save class="h-4 w-4" />{{ isSubmitting ? 'Đang lưu...' : 'Lưu thay đổi' }}</button>
      </header>

      <div class="mb-4 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
        <label class="field">Cấp học<select v-model="question.education_level_id" @change="question.grade_id = ''"><option value="">Không chọn</option><option v-for="item in levels" :key="item.id" :value="item.id">{{ item.name }}</option></select></label>
        <label class="field">Khối lớp<select v-model="question.grade_id"><option value="">Không chọn</option><option v-for="item in grades" :key="item.id" :value="item.id">{{ item.name }}</option></select></label>
        <label class="field">Bộ môn <span class="text-rose-500">*</span><select id="edit-question-subject-select" v-model="question.subject_id"><option value="">Chọn bộ môn</option><option v-for="item in subjects" :key="item.id" :value="item.id">{{ item.name }}</option></select></label>
        <label class="field">Chủ đề<input v-model="question.topic_name" placeholder="Ví dụ: Hàm số" /></label>
      </div>

      <StandaloneQuestionEditor :question="question" @change-type="changeType" @add-answer="addAnswer" @remove-answer="removeAnswer" @add-fill-answer="addFillAnswer" @remove-fill-answer="removeFillAnswer" />
    </template>
  </section>
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, Save } from 'lucide-vue-next'
import StandaloneQuestionEditor from '@/components/question/StandaloneQuestionEditor.vue'
import { formatApiErrorMessage, myQuestionsApi, taxonomyApi } from '@/services/api'

const route = useRoute(), router = useRouter(), showToast = inject('showToast')
const questionId = computed(() => Number(route.params.id))
const isLoading = ref(true), isSubmitting = ref(false), fetchError = ref('')
const levels = ref([]), allSubjects = ref([])
let localId = 0
const nextId = () => `edit-local-${++localId}`
const question = reactive({ content: '', image_url: null, type: 'single_choice', difficulty: 'medium', education_level_id: '', grade_id: '', subject_id: '', topic_name: '', answers: [], accepted_answers: [] })
const grades = computed(() => question.education_level_id ? (levels.value.find(x => x.id === Number(question.education_level_id))?.grades || []) : levels.value.flatMap(x => x.grades || []))
const subjects = computed(() => question.grade_id ? (grades.value.find(x => x.id === Number(question.grade_id))?.subjects || allSubjects.value) : allSubjects.value)
const goBack = () => router.push(`/dashboard/my-questions?question_id=${questionId.value}`)
const defaultAnswers = () => ['A', 'B', 'C', 'D'].map((key, index) => ({ id: nextId(), key, content: '', is_correct: index === 0 }))
const changeType = (type) => {
  question.type = type
  if (type === 'true_false') question.answers = [{ id: nextId(), content: 'Đúng', is_correct: true }, { id: nextId(), content: 'Sai', is_correct: false }]
  else if (type === 'fill_in' && !question.accepted_answers.length) question.accepted_answers = [{ id: nextId(), content: '' }]
  else if (!['true_false', 'fill_in'].includes(type) && question.answers.length < 2) question.answers = defaultAnswers()
  if (type === 'single_choice') question.answers.forEach((answer, index) => { answer.is_correct = index === Math.max(0, question.answers.findIndex(x => x.is_correct)) })
}
const addAnswer = () => question.answers.push({ id: nextId(), key: String.fromCharCode(65 + question.answers.length), content: '', is_correct: false })
const removeAnswer = (index) => { if (question.answers.length > 2) question.answers.splice(index, 1) }
const addFillAnswer = () => question.accepted_answers.push({ id: nextId(), content: '' })
const removeFillAnswer = (index) => { if (question.accepted_answers.length > 1) question.accepted_answers.splice(index, 1) }
const load = async () => {
  try {
    const [tree, bank] = await Promise.all([taxonomyApi.tree(), myQuestionsApi.fetchBank({ question_id: questionId.value })])
    const taxonomy = tree?.education_levels ? tree : (tree?.data ?? tree)
    levels.value = taxonomy?.education_levels || taxonomy?.educationLevels || []
    allSubjects.value = taxonomy?.subjects || []
    const source = bank.items.find(item => item.id === questionId.value) || bank.items[0]
    if (!source) throw new Error(`Không tìm thấy câu hỏi #${questionId.value}.`)
    Object.assign(question, source, {
      content: source.content || source.text || '',
      type: source.type === 'fill_blank' ? 'fill_in' : (source.type || 'single_choice'),
      difficulty: source.difficulty || 'medium',
      education_level_id: source.education_level_id || '', grade_id: source.grade_id || '', subject_id: source.subject_id || '', topic_name: source.topic_name || '',
      answers: (source.answers || []).map((x, i) => ({ ...x, id: x.id || nextId(), key: x.key || String.fromCharCode(65 + i), content: x.content || x.text || '', is_correct: Boolean(x.is_correct) })),
    })
    question.accepted_answers = question.type === 'fill_in' ? (question.answers.length ? question.answers.map(x => ({ ...x, id: x.id || nextId() })) : [{ id: nextId(), content: '' }]) : []
    if (!question.answers.length && question.type !== 'fill_in') changeType(question.type)
  } catch (error) { fetchError.value = error.message || 'Không thể tải câu hỏi.' } finally { isLoading.value = false }
}
const validate = () => {
  if (!question.content.trim()) return 'Vui lòng nhập nội dung câu hỏi.'
  if (!question.subject_id) return 'Vui lòng chọn Bộ môn.'
  const answers = question.type === 'fill_in' ? question.accepted_answers : question.answers
  if (!answers.length || answers.some(x => !x.content?.trim())) return 'Vui lòng nhập đầy đủ nội dung đáp án.'
  const correct = answers.filter(x => x.is_correct).length
  if (question.type === 'single_choice' && correct !== 1) return 'Câu một đáp án cần đúng chính xác một lựa chọn.'
  if (question.type === 'multi_choice' && correct < 2) return 'Câu nhiều đáp án cần ít nhất hai lựa chọn đúng.'
  return ''
}
const saveQuestion = async () => {
  const error = validate()
  if (error) return showToast?.(error, 'error')
  isSubmitting.value = true
  try {
    const sourceAnswers = question.type === 'fill_in' ? question.accepted_answers.map(x => ({ ...x, is_correct: true })) : question.answers
    await myQuestionsApi.update(questionId.value, {
      content: question.content.trim(), image_url: question.image_url || null, type: question.type === 'fill_in' ? 'fill_blank' : question.type, difficulty: question.difficulty,
      education_level_id: question.education_level_id || null, grade_id: question.grade_id || null, subject_id: question.subject_id || null, topic_name: question.topic_name?.trim() || null,
      answers: sourceAnswers.map((x, i) => ({ ...(Number.isInteger(Number(x.id)) ? { id: Number(x.id) } : {}), content: x.content.trim(), key: x.key || String.fromCharCode(65 + i), is_correct: Boolean(x.is_correct) })),
    })
    showToast?.('Đã lưu thay đổi câu hỏi.', 'success')
    router.push(`/dashboard/my-questions?question_id=${questionId.value}&updated=1`)
  } catch (err) { showToast?.(formatApiErrorMessage(err, 'Cập nhật thất bại.'), 'error') } finally { isSubmitting.value = false }
}
onMounted(load)
</script>

<style scoped>
.field { @apply grid gap-1.5 text-xs font-bold text-slate-600; }
.field select, .field input { @apply min-w-0 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-100; }
</style>
