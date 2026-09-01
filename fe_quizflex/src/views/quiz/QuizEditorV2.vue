<template>
  <div class="quiz-editor-shell flex h-[calc(100dvh-64px)] min-h-[520px] flex-col overflow-hidden bg-slate-100 text-slate-900">
    <EditorHeader v-model:title="quiz.title" :save-status="saveStatus" @preview="showMockNotice('Xem trước')" @complete="showMockNotice('Hoàn tất quiz')" />
    <div class="compact-question-nav">
      <label for="compact-question-select" class="text-[10px] font-black uppercase tracking-wider text-slate-500">Câu hỏi</label>
      <select id="compact-question-select" :value="activeQuestionId || ''" class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-100" @change="selectQuestion($event.target.value)">
        <option v-if="!quiz.questions.length" value="">Chưa có câu hỏi</option>
        <option v-for="(question, index) in quiz.questions" :key="question.id" :value="question.id">Câu {{ index + 1 }} · {{ question.content || 'Chưa có nội dung' }}</option>
      </select>
      <button type="button" class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-violet-600 text-white shadow-sm" aria-label="Thêm câu hỏi" title="Thêm câu hỏi" @click="openQuestionSource">+</button>
    </div>
    <div class="editor-grid min-h-0 flex-1">
      <QuestionSidebar :questions="quiz.questions" :active-question-id="activeQuestionId" :question-origins="questionOrigins" @select-question="selectQuestion" @add-question="openQuestionSource" />
      <QuestionEditor :question="activeQuestion" :question-number="activeQuestionNumber" :total-questions="quiz.questions.length" @change-type="changeQuestionType" @update-difficulty="updateDifficulty" @update-points="updatePoints" @add-answer="addAnswer" @remove-answer="removeAnswer" @add-fill-answer="addFillAnswer" @remove-fill-answer="removeFillAnswer" @add-question="openQuestionSource" />
      <EditorToolbar :question="activeQuestion" :can-move-up="activeQuestionIndex > 0" :can-move-down="activeQuestionIndex >= 0 && activeQuestionIndex < quiz.questions.length - 1" @move-question="moveQuestion" @duplicate-question="duplicateQuestion" @remove-question="removeQuestion" />
    </div>
    <QuestionSourceModal v-if="isQuestionSourceOpen" @choose="handleSourceChoice" @close="isQuestionSourceOpen = false" />
    <QuestionPicker v-if="activePickerSource" :source="activePickerSource" @select="addPickedQuestions" @close="activePickerSource = ''" />
    <Transition name="toast"><div v-if="mockNotice" class="fixed bottom-5 left-1/2 z-50 -translate-x-1/2 rounded-xl bg-slate-900 px-4 py-3 text-xs font-bold text-white shadow-2xl">{{ mockNotice }} — chức năng đang dùng mock UI.</div></Transition>
  </div>
</template>
<script setup>
import { computed, ref } from 'vue'
import EditorHeader from '@/components/quiz-editor/EditorHeader.vue'
import EditorToolbar from '@/components/quiz-editor/EditorToolbar.vue'
import QuestionEditor from '@/components/quiz-editor/QuestionEditor.vue'
import QuestionPicker from '@/components/quiz-editor/QuestionPicker.vue'
import QuestionSidebar from '@/components/quiz-editor/QuestionSidebar.vue'
import QuestionSourceModal from '@/components/quiz-editor/QuestionSourceModal.vue'

let sequence = 100
const TOTAL_QUIZ_POINTS = 10
const tempId = () => `temp-${Date.now()}-${sequence++}`
const makeAnswers = (contents = ['Đáp án A', 'Đáp án B', 'Đáp án C', 'Đáp án D']) => contents.map((content, index) => ({ id: tempId(), content, is_correct: index === 0 }))
const makeQuestion = (overrides = {}) => ({ id: tempId(), order: 0, type: 'single_choice', content: '', difficulty: 'medium', points: 1, answers: makeAnswers(), accepted_answers: [{ id: tempId(), content: '' }], ...overrides })

const quiz = ref({ title: 'Kiến thức tổng hợp — QuizFlex', questions: [
  makeQuestion({ content: 'Thủ đô của Việt Nam là thành phố nào?', answers: makeAnswers(['Hà Nội', 'Huế', 'Đà Nẵng', 'TP. Hồ Chí Minh']) }),
  makeQuestion({ type: 'multi_choice', content: 'Đâu là các framework hoặc thư viện JavaScript?', points: 2, answers: makeAnswers(['Vue', 'React', 'Laravel', 'Svelte']).map((answer, index) => ({ ...answer, is_correct: index !== 2 })) }),
  makeQuestion({ type: 'fill_in', content: 'Điền tên framework frontend đang được QuizFlex sử dụng.', accepted_answers: [{ id: tempId(), content: 'Vue' }, { id: tempId(), content: 'Vue.js' }] }),
  makeQuestion({ type: 'true_false', content: 'Vue 3 hỗ trợ Composition API.', answers: makeAnswers(['Đúng', 'Sai']).slice(0, 2) }),
] })

const activeQuestionId = ref(quiz.value.questions[0].id)
const mockNotice = ref('')
const saveStatus = ref('Đã lưu bản nháp')
const isQuestionSourceOpen = ref(false)
const activePickerSource = ref('')
const questionOrigins = ref({})
const manualPointQuestionIds = new Set()
let noticeTimer
const activeQuestion = computed(() => quiz.value.questions.find((question) => question.id === activeQuestionId.value) || null)
const activeQuestionIndex = computed(() => quiz.value.questions.findIndex((question) => question.id === activeQuestionId.value))
const activeQuestionNumber = computed(() => Math.max(1, activeQuestionIndex.value + 1))

const syncQuestionOrder = () => { quiz.value.questions.forEach((question, index) => { question.order = index + 1 }) }
const markMockSaved = () => { saveStatus.value = 'Đã lưu bản nháp' }
const selectQuestion = (id) => { activeQuestionId.value = id }
const openQuestionSource = () => { isQuestionSourceOpen.value = true }
const handleSourceChoice = (source) => {
  isQuestionSourceOpen.value = false
  if (source === 'new') {
    addQuestion()
    return
  }
  if (source === 'personal' || source === 'bank') {
    activePickerSource.value = source
    return
  }
  if (source === 'ai') showMockNotice('Tạo câu hỏi bằng AI')
}
const roundPoints = (value) => Math.round(value * 100) / 100
const reallocatePoints = (forceAll = false) => {
  const questions = quiz.value.questions
  if (!questions.length) return
  if (forceAll) manualPointQuestionIds.clear()

  const automaticQuestions = questions.filter((question) => !manualPointQuestionIds.has(question.id))
  const manualTotal = questions.reduce((total, question) => manualPointQuestionIds.has(question.id) ? total + (parseFloat(question.points) || 0) : total, 0)
  if (!automaticQuestions.length) return

  const remainingPoints = Math.max(0, roundPoints(TOTAL_QUIZ_POINTS - manualTotal))
  const basePoints = roundPoints(remainingPoints / automaticQuestions.length)
  let allocatedPoints = 0

  automaticQuestions.forEach((question, index) => {
    const isLastQuestion = index === automaticQuestions.length - 1
    question.points = isLastQuestion ? roundPoints(remainingPoints - allocatedPoints) : basePoints
    allocatedPoints = roundPoints(allocatedPoints + question.points)
  })
}
const addQuestion = () => { const question = makeQuestion(); quiz.value.questions.push(question); syncQuestionOrder(); activeQuestionId.value = question.id; reallocatePoints(false); markMockSaved() }
const addPickedQuestions = (selectedQuestions) => {
  if (!Array.isArray(selectedQuestions) || !selectedQuestions.length) return
  const selectedSource = activePickerSource.value
  const normalizedQuestions = selectedQuestions.map((question) => ({
    ...question,
    difficulty: question.difficulty || 'medium',
    id: tempId(),
    answers: (question.answers || []).map((answer) => ({ ...answer, id: tempId() })),
    accepted_answers: (question.accepted_answers || []).map((answer) => ({ ...answer, id: tempId() })),
  }))
  const insertAt = activeQuestionIndex.value < 0 ? quiz.value.questions.length : activeQuestionIndex.value + 1
  quiz.value.questions.splice(insertAt, 0, ...normalizedQuestions)
  syncQuestionOrder()
  normalizedQuestions.forEach((question) => { questionOrigins.value[question.id] = selectedSource })
  activeQuestionId.value = normalizedQuestions[0].id
  activePickerSource.value = ''
  reallocatePoints(false)
  markMockSaved()
}
const addAnswer = () => { if (!activeQuestion.value) return; activeQuestion.value.answers.push({ id: tempId(), content: '', is_correct: false }); markMockSaved() }
const removeAnswer = (index) => { if (!activeQuestion.value || activeQuestion.value.answers.length <= 2) return; const removed = activeQuestion.value.answers.splice(index, 1)[0]; if (removed?.is_correct && activeQuestion.value.type === 'single_choice') activeQuestion.value.answers[0].is_correct = true; markMockSaved() }
const addFillAnswer = () => { activeQuestion.value?.accepted_answers.push({ id: tempId(), content: '' }); markMockSaved() }
const removeFillAnswer = (index) => { if ((activeQuestion.value?.accepted_answers.length || 0) > 1) activeQuestion.value.accepted_answers.splice(index, 1); markMockSaved() }
const updatePoints = (points) => {
  if (!activeQuestion.value || !Number.isFinite(points)) return
  if (quiz.value.questions.length === 1) {
    activeQuestion.value.points = TOTAL_QUIZ_POINTS
    markMockSaved()
    return
  }

  if (!manualPointQuestionIds.has(activeQuestion.value.id) && manualPointQuestionIds.size >= quiz.value.questions.length - 1) {
    const previousManualId = [...manualPointQuestionIds].find((id) => id !== activeQuestion.value.id)
    if (previousManualId) manualPointQuestionIds.delete(previousManualId)
  }

  manualPointQuestionIds.add(activeQuestion.value.id)
  const automaticCount = quiz.value.questions.length - manualPointQuestionIds.size
  const otherManualTotal = quiz.value.questions.reduce((total, question) => {
    return manualPointQuestionIds.has(question.id) && question.id !== activeQuestion.value.id
      ? total + (parseFloat(question.points) || 0)
      : total
  }, 0)
  const maximumPoint = Math.max(0.01, roundPoints(TOTAL_QUIZ_POINTS - otherManualTotal - automaticCount * 0.01))
  activeQuestion.value.points = roundPoints(Math.min(maximumPoint, Math.max(0.01, points)))
  reallocatePoints(false)
  markMockSaved()
}
const updateDifficulty = (difficulty) => {
  if (!activeQuestion.value || !['easy', 'medium', 'hard'].includes(difficulty)) return
  activeQuestion.value.difficulty = difficulty
  markMockSaved()
}
const changeQuestionType = (type) => { if (!activeQuestion.value) return; activeQuestion.value.type = type; if (type === 'true_false') activeQuestion.value.answers = makeAnswers(['Đúng', 'Sai']).slice(0, 2); if (type === 'fill_in' && !activeQuestion.value.accepted_answers.length) activeQuestion.value.accepted_answers = [{ id: tempId(), content: '' }]; if ((type === 'single_choice' || type === 'multi_choice') && activeQuestion.value.answers.length < 2) activeQuestion.value.answers = makeAnswers(); if (type === 'single_choice') activeQuestion.value.answers.forEach((answer, index) => { answer.is_correct = index === 0 }); markMockSaved() }
const duplicateQuestion = () => {
  if (!activeQuestion.value) return
  const originalId = activeQuestion.value.id
  const clone = JSON.parse(JSON.stringify(activeQuestion.value))
  clone.id = tempId()
  clone.content = `${clone.content} (bản sao)`
  clone.answers = (clone.answers || []).map((answer) => ({ ...answer, id: tempId() }))
  clone.accepted_answers = (clone.accepted_answers || []).map((answer) => ({ ...answer, id: tempId() }))
  quiz.value.questions.splice(activeQuestionIndex.value + 1, 0, clone)
  syncQuestionOrder()
  if (questionOrigins.value[originalId]) questionOrigins.value[clone.id] = questionOrigins.value[originalId]
  activeQuestionId.value = clone.id
  reallocatePoints(false)
  markMockSaved()
}
const removeQuestion = () => {
  const index = activeQuestionIndex.value
  if (index < 0 || !window.confirm('Bạn có chắc muốn xóa câu hỏi này?')) return
  const [removedQuestion] = quiz.value.questions.splice(index, 1)
  manualPointQuestionIds.delete(removedQuestion.id)
  delete questionOrigins.value[removedQuestion.id]
  syncQuestionOrder()
  activeQuestionId.value = quiz.value.questions[index]?.id || quiz.value.questions[index - 1]?.id || null
  reallocatePoints(false)
  markMockSaved()
}
const moveQuestion = (direction) => {
  const from = activeQuestionIndex.value
  const to = from + direction
  if (from < 0 || to < 0 || to >= quiz.value.questions.length) return
  const currentId = activeQuestionId.value
  const [question] = quiz.value.questions.splice(from, 1)
  quiz.value.questions.splice(to, 0, question)
  syncQuestionOrder()
  activeQuestionId.value = currentId
  markMockSaved()
}
const showMockNotice = (action) => { mockNotice.value = action; clearTimeout(noticeTimer); noticeTimer = setTimeout(() => { mockNotice.value = '' }, 2200) }
syncQuestionOrder()
reallocatePoints(true)
</script>
<style scoped>
.editor-grid { display: grid; grid-template-columns: clamp(260px, 18vw, 280px) minmax(0, 1fr) 64px; overflow: hidden; }
.compact-question-nav { display: none; }
:global(body:has(.quiz-editor-shell)), :global(.app-shell:has(.quiz-editor-shell)) { max-width: 100vw; overflow-x: hidden; }
.toast-enter-active, .toast-leave-active { transition: all .2s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translate(-50%, 10px); }
@media (max-width: 1366px) { .editor-grid { grid-template-columns: 230px minmax(0, 1fr) 60px; } }
@media (max-width: 1023px) {
  .quiz-editor-shell { padding-bottom: 58px; }
  .compact-question-nav { display: flex; align-items: center; gap: .625rem; border-bottom: 1px solid rgb(226 232 240); background: white; padding: .625rem .875rem; }
  .editor-grid { grid-template-columns: minmax(0, 1fr); overflow: visible; }
  .editor-grid > :first-child { display: none; }
  .editor-grid > :last-child { position: fixed; right: 0; bottom: 0; left: 0; z-index: 40; min-height: 58px; border-left: 0; border-top: 1px solid rgb(226 232 240); }
}
@media (max-width: 639px) { .quiz-editor-shell { min-height: 0; } .compact-question-nav { padding-inline: .75rem; } }
</style>
