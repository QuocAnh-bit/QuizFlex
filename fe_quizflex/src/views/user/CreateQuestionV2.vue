<template>
  <main class="min-h-[calc(100dvh-64px)] bg-slate-100 px-3 py-4 sm:px-5 lg:px-7">
    <div class="mx-auto w-full max-w-6xl">
      <header class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:px-5">
        <div class="min-w-0"><p class="text-[10px] font-black uppercase tracking-[.16em] text-violet-600">Kho câu hỏi cá nhân</p><h1 class="mt-1 text-lg font-black text-slate-900">Tạo câu hỏi mới</h1><p class="mt-0.5 text-xs text-slate-500">Soạn bằng cùng editor với QuizEditorV2. Câu hỏi sẽ được lưu riêng tư trong kho của bạn.</p></div>
        <button type="button" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-black text-slate-700 hover:bg-slate-50" @click="router.push('/dashboard/my-questions')">Quay lại kho</button>
      </header>

      <section class="mb-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="mb-3 flex items-center justify-between gap-3"><div><p class="text-[11px] font-black uppercase tracking-[.14em] text-violet-600">Thông tin phân loại</p><p class="mt-0.5 text-xs text-slate-500">Dùng chung lớp, môn, chủ đề và mức độ với Quiz Editor.</p></div><span v-if="taxonomyLoading" class="text-[11px] font-bold text-slate-400">Đang tải...</span></div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <label class="field-label">Cấp học<select v-model="metadata.education_level_id" class="field" :disabled="taxonomyLoading" @change="changeLevel"><option value="">Chọn cấp học</option><option v-for="level in levels" :key="level.id" :value="level.id">{{ level.name }}</option></select></label>
          <label class="field-label">Lớp<select v-model="metadata.grade_id" class="field" :disabled="!metadata.education_level_id" @change="changeGrade"><option value="">Chọn lớp</option><option v-for="grade in grades" :key="grade.id" :value="grade.id">{{ grade.name }}</option></select></label>
          <label class="field-label">Môn học <span class="text-rose-500">*</span><select v-model="metadata.subject_id" class="field" :disabled="!metadata.grade_id"><option value="">Chọn môn học</option><option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option></select></label>
          <label class="field-label">Chủ đề<input v-model="metadata.topic_name" class="field" maxlength="255" placeholder="Ví dụ: Hàm số bậc nhất" /></label>
        </div>
      </section>

      <p v-if="errorMessage" class="mb-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-bold text-rose-700">{{ errorMessage }}</p>
      <QuestionEditor :question="question" :question-number="1" :total-questions="1" :manual-points="true" :show-add-question="false" @change-type="changeType" @update-difficulty="question.difficulty = $event" @update-points="question.points = $event" @add-answer="addAnswer" @remove-answer="removeAnswer" @add-fill-answer="addFillAnswer" @remove-fill-answer="removeFillAnswer" />

      <footer class="mt-4 flex flex-wrap justify-end gap-2 pb-5"><button type="button" class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-xs font-black text-slate-700" :disabled="saving" @click="router.push('/dashboard/my-questions')">Hủy</button><button type="button" class="rounded-xl border border-violet-200 bg-violet-50 px-5 py-3 text-xs font-black text-violet-700 disabled:opacity-50" :disabled="saving" @click="save(true)">Lưu và tạo tiếp</button><button type="button" class="rounded-xl bg-violet-600 px-5 py-3 text-xs font-black text-white shadow-lg shadow-violet-200 disabled:opacity-50" :disabled="saving" @click="save(false)">{{ saving ? 'Đang lưu...' : 'Lưu vào kho cá nhân' }}</button></footer>
    </div>
  </main>
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import QuestionEditor from '@/components/quiz-editor/QuestionEditor.vue'
import { formatApiErrorMessage, myQuestionsApi, taxonomyApi } from '@/services/api.js'
import { normalizeQuestion } from '@/services/quiz-editor/quizEditorNormalizer.js'

const router = useRouter()
const showToast = inject('showToast', null)
const levels = ref([])
const taxonomyLoading = ref(true)
const saving = ref(false)
const errorMessage = ref('')
const metadata = reactive({ education_level_id: '', grade_id: '', subject_id: '', topic_name: '' })
const tempId = () => `personal-question-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`
const makeAnswers = (values = ['', '', '', '']) => values.map((content, index) => ({ id: `${tempId()}-${index}`, content, is_correct: index === 0, order: index }))
const createQuestion = () => normalizeQuestion({ id: tempId(), type: 'single_choice', content: '', difficulty: 'medium', points: 1, answers: makeAnswers() })
const question = reactive(createQuestion())
const grades = computed(() => levels.value.find((level) => Number(level.id) === Number(metadata.education_level_id))?.grades || [])
const subjects = computed(() => grades.value.find((grade) => Number(grade.id) === Number(metadata.grade_id))?.subjects || [])
const changeLevel = () => { metadata.grade_id = ''; metadata.subject_id = '' }
const changeGrade = () => { metadata.subject_id = '' }
const addAnswer = () => question.answers.push({ id: tempId(), content: '', is_correct: false, order: question.answers.length })
const removeAnswer = (index) => { if (question.answers.length <= 2) return; question.answers.splice(index, 1); if (question.type === 'single_choice' && !question.answers.some((answer) => answer.is_correct)) question.answers[0].is_correct = true }
const addFillAnswer = () => question.accepted_answers.push({ id: tempId(), content: '' })
const removeFillAnswer = (index) => { if (question.accepted_answers.length > 1) question.accepted_answers.splice(index, 1) }
const changeType = (type) => {
  question.type = type
  if (type === 'true_false') question.answers = makeAnswers(['Đúng', 'Sai']).slice(0, 2)
  if (type === 'fill_in' && !question.accepted_answers.length) question.accepted_answers = [{ id: tempId(), content: '' }]
  if ((type === 'single_choice' || type === 'multi_choice') && question.answers.length < 2) question.answers = makeAnswers()
  if (type === 'single_choice') question.answers.forEach((answer, index) => { answer.is_correct = index === 0 })
}
const validate = () => {
  if (!question.content.trim()) return 'Vui lòng nhập nội dung câu hỏi.'
  if (!metadata.subject_id) return 'Vui lòng chọn môn học.'
  if (question.type === 'fill_in') return question.accepted_answers.some((answer) => answer.content.trim()) ? '' : 'Vui lòng nhập ít nhất một đáp án chấp nhận được.'
  if (question.answers.length < 2 || question.answers.some((answer) => !answer.content.trim())) return 'Vui lòng nhập ít nhất hai đáp án đầy đủ.'
  const correctCount = question.answers.filter((answer) => answer.is_correct).length
  if (question.type === 'single_choice' || question.type === 'true_false') return correctCount === 1 ? '' : 'Vui lòng chọn đúng một đáp án.'
  return correctCount >= 2 ? '' : 'Câu hỏi nhiều đáp án cần chọn ít nhất hai đáp án đúng.'
}
const resetQuestion = () => Object.assign(question, createQuestion())
const save = async (createAnother) => {
  errorMessage.value = validate()
  if (errorMessage.value) { showToast?.(errorMessage.value, 'error'); return }
  saving.value = true
  try {
    const isFillIn = question.type === 'fill_in'
    const answers = isFillIn
      ? question.accepted_answers.filter((answer) => answer.content.trim()).map((answer, index) => ({ content: answer.content.trim(), key: String.fromCharCode(65 + index), is_correct: true }))
      : question.answers.map((answer, index) => ({ content: answer.content.trim(), key: String.fromCharCode(65 + index), is_correct: Boolean(answer.is_correct) }))
    await myQuestionsApi.createQuestion({ content: question.content.trim(), type: isFillIn ? 'fill_blank' : question.type, difficulty: question.difficulty, points: Math.max(1, Math.round(Number(question.points) || 1)), education_level_id: metadata.education_level_id || null, grade_id: metadata.grade_id || null, subject_id: metadata.subject_id, topic_name: metadata.topic_name.trim() || null, is_public: false, answers })
    showToast?.('Đã lưu câu hỏi vào kho cá nhân.', 'success')
    if (createAnother) resetQuestion()
    else router.push('/dashboard/my-questions')
  } catch (error) { errorMessage.value = formatApiErrorMessage(error, 'Không thể lưu câu hỏi.'); showToast?.(errorMessage.value, 'error') } finally { saving.value = false }
}
onMounted(async () => { try { const data = await taxonomyApi.tree(); levels.value = data?.education_levels || data?.educationLevels || [] } catch (error) { errorMessage.value = 'Không tải được dữ liệu cấp học, lớp và môn học.' } finally { taxonomyLoading.value = false } })
</script>

<style scoped>
.field-label { @apply grid gap-1.5 text-xs font-black text-slate-700; }
.field { @apply w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-100 disabled:bg-slate-100; }
</style>
