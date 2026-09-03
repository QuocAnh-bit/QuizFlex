<template>
  <div class="quiz-editor-shell flex h-full min-h-0 flex-col overflow-hidden rounded-xl border border-slate-200 bg-slate-100 text-slate-900 shadow-sm">
    <EditorHeader :title="quiz.title" :save-status="saveStatus" :time-limit-seconds="quiz.time_limit_seconds" :education-level-name="quiz.education_level_name" :grade-name="quiz.grade_name" :subject-name="quiz.subject_name" :topic-name="quiz.topic_name" :cover-panel-open="isCoverPanelOpen" :is-saving="isSaving || isSavingCover" :is-deleting="isDeleting" :save-disabled="isLoading || Boolean(loadError) || isDeleting || isSavingCover" :delete-disabled="isLoading || Boolean(loadError) || isSaving || isSavingCover || !quizId" @update:title="updateQuizTitle" @update:time-limit="updateTimeLimit" @toggle-cover="isCoverPanelOpen = !isCoverPanelOpen" @complete="completeQuiz" @delete="removeQuiz" />
    <QuizCoverPanel v-if="!isLoading && !loadError && !missingQuizContext && isCoverPanelOpen" :selected-cover="quiz.cover" :uploaded-file="pendingCoverFile" @select="selectPresetCover" @upload="selectUploadedCover" @remove="removeCover" @close="isCoverPanelOpen = false" />
    <div v-if="isLoading" class="grid min-h-0 flex-1 place-items-center p-6" role="status" aria-live="polite">
      <div class="text-center"><span class="mx-auto block h-9 w-9 animate-spin rounded-full border-4 border-violet-100 border-t-violet-600"></span><p class="mt-3 text-sm font-black text-slate-700">Đang tải Quiz...</p><p class="mt-1 text-xs text-slate-500">Đang lấy dữ liệu chỉnh sửa từ máy chủ.</p></div>
    </div>
    <div v-else-if="loadError" class="grid min-h-0 flex-1 place-items-center p-6" role="alert">
      <div class="w-full max-w-md rounded-2xl border border-rose-200 bg-white p-7 text-center shadow-sm"><p class="text-base font-black text-slate-900">Không thể tải Quiz</p><p class="mt-2 text-xs leading-5 text-slate-500">{{ loadError }}</p><button type="button" class="mt-5 rounded-xl bg-violet-600 px-4 py-2.5 text-xs font-black text-white shadow-md shadow-violet-200 hover:bg-violet-700" @click="loadQuiz">Thử lại</button></div>
    </div>
    <div v-else-if="missingQuizContext" class="min-h-0 flex-1 overflow-y-auto p-5 sm:p-8">
      <div class="mx-auto max-w-2xl rounded-2xl border border-amber-200 bg-white p-6 shadow-sm"><p class="text-[11px] font-black uppercase tracking-wider text-amber-600">Cần bổ sung thông tin</p><h2 class="mt-2 text-xl font-black text-slate-900">Vui lòng thiết lập Lớp và Môn học trước khi chỉnh sửa Quiz.</h2><p class="mt-1 text-xs leading-5 text-slate-500">Quiz cũ vẫn được giữ nguyên. Sau khi cập nhật metadata, editor sẽ mở lại với đầy đủ câu hỏi hiện có.</p><div class="mt-5"><QuizSetupForm :initial-data="quiz" :is-submitting="isUpdatingContext" :external-error="contextError" submit-label="Lưu và tiếp tục" :show-cancel="false" @submit="saveMissingContext" /></div></div>
    </div>
    <template v-else>
    <div class="flex items-center justify-end gap-3 border-b border-slate-200 bg-white px-4 py-2.5 sm:px-6">
      <span class="text-xs font-bold text-slate-600">Tổng điểm: <b class="text-slate-900">{{ formatPoints(totalQuizPoints) }}<template v-if="totalQuizPoints < pointsBudget"> / {{ formatPoints(pointsBudget) }}</template> điểm</b></span>
      <span class="h-4 w-px bg-slate-200"></span>
      <button type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-black text-violet-700 hover:bg-violet-50" @click="isPointsModalOpen = true">Thiết lập điểm</button>
    </div>
    <div class="compact-question-nav">
      <label for="compact-question-select" class="text-[10px] font-black uppercase tracking-wider text-slate-500">Câu hỏi</label>
      <select id="compact-question-select" :value="activeQuestionId || ''" class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-100" @change="selectQuestion($event.target.value)">
        <option v-if="!quiz.questions.length" value="">Chưa có câu hỏi</option>
        <option v-for="(question, index) in quiz.questions" :key="question.id" :value="question.id">Câu {{ index + 1 }} · {{ question.content || 'Chưa có nội dung' }}</option>
      </select>
      <button type="button" class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-violet-600 text-white shadow-sm" aria-label="Thêm câu hỏi" title="Thêm câu hỏi" @click="openQuestionSource">+</button>
    </div>
    <div v-if="similarSelectionMode" class="flex items-center justify-between gap-3 border-b border-fuchsia-200 bg-fuchsia-50 px-4 py-2"><p class="text-xs font-bold text-fuchsia-800">Chọn câu hỏi mẫu ở cột trái · Đã chọn {{ similarSelectedIds.length }} câu</p><div class="flex gap-2"><button class="rounded-lg border border-fuchsia-200 bg-white px-3 py-2 text-xs font-black text-fuchsia-700" @click="cancelSimilarSelection">Hủy</button><button class="rounded-lg bg-gradient-to-r from-violet-600 to-fuchsia-600 px-3 py-2 text-xs font-black text-white shadow-md shadow-fuchsia-200 transition hover:from-violet-700 hover:to-fuchsia-700 disabled:cursor-not-allowed disabled:from-slate-300 disabled:to-slate-300 disabled:shadow-none" :disabled="!similarSelectedIds.length" @click="openSimilarGenerator">Tạo câu tương tự</button></div></div>
    <div v-if="saveAlert" class="flex flex-wrap items-center gap-3 border-b border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-800" role="alert">
      <div class="min-w-0 flex-1"><p class="font-black">{{ saveAlert.title }}</p><p class="mt-0.5 text-rose-700">{{ saveAlert.message }}</p></div>
      <button v-if="saveAlert.questionId !== null" type="button" class="rounded-lg border border-rose-200 bg-white px-3 py-1.5 font-black text-rose-700 hover:bg-rose-100" @click="goToSaveAlertQuestion">Xem câu lỗi</button>
      <button type="button" class="rounded-lg px-2 py-1 font-black text-rose-500 hover:bg-rose-100" aria-label="Đóng thông báo" @click="saveAlert = null">×</button>
    </div>
    <div class="editor-grid min-h-0 flex-1">
      <QuestionSidebar :questions="quiz.questions" :active-question-id="activeQuestionId" :question-origins="questionOrigins" :invalid-question-ids="invalidQuestionIds" :selection-mode="similarSelectionMode" :selected-question-ids="similarSelectedIds" :highlight-question-ids="newlyAddedQuestionIds" @toggle-question="toggleSimilarQuestion" @select-question="selectQuestion" @reorder-question="reorderQuestion" @add-question="openQuestionSource" />
      <QuestionEditor ref="questionEditor" :question="activeQuestion" :question-number="activeQuestionNumber" :total-questions="quiz.questions.length" :max-points="activeQuestionMaxPoints" :is-bank-locked="isBankQuestion(activeQuestion)" @change-type="changeQuestionType" @update-difficulty="updateDifficulty" @update-points="updatePoints" @add-answer="addAnswer" @remove-answer="removeAnswer" @add-fill-answer="addFillAnswer" @remove-fill-answer="removeFillAnswer" @add-question="openQuestionSource" @duplicate-question="duplicateQuestion" />
      <EditorToolbar :question="activeQuestion" :can-move-up="activeQuestionIndex > 0" :can-move-down="activeQuestionIndex >= 0 && activeQuestionIndex < quiz.questions.length - 1" :ai-review-disabled="aiToolBusy" @generate-ai="openAiGenerator" @review-quiz="openQuizReview" @similar-questions="startSimilarSelection" @add-image="questionEditor?.openImagePicker()" @move-question="moveQuestion" @duplicate-question="duplicateQuestion" @remove-question="removeQuestion" />
    </div>
    <QuestionSourceModal v-if="isQuestionSourceOpen" @choose="handleSourceChoice" @close="isQuestionSourceOpen = false" />
    <QuestionPicker v-if="activePickerSource" :source="activePickerSource" @select="addPickedQuestions" @close="activePickerSource = ''" />
    <AiQuestionGenerator v-if="isAiGeneratorOpen" :quiz-context="quiz" :initial-results="aiGeneratorDraft.results" :initial-selected-ids="aiGeneratorDraft.selectedIds" @state-change="updateAiGeneratorDraft" @select="addAiQuestions" @close="isAiGeneratorOpen = false" />
    <OcrQuestionImporter v-if="isOcrImporterOpen" :quiz-context="quiz" @select="addOcrQuestions" @close="isOcrImporterOpen = false" />
    <AiQuizReview v-if="isQuizReviewOpen" :quiz="cloneData(quiz)" :initial-result="aiQuizReviewDraft.signature === persistedSignature(quiz) ? aiQuizReviewDraft.result : null" @result="storeQuizReview" @select-question="goToReviewedQuestion" @close="isQuizReviewOpen = false" />
    <SimilarQuestionGenerator v-if="isSimilarGeneratorOpen" :quiz="cloneData(quiz)" :source-questions="similarSourceQuestions" :initial-results="similarGeneratorDraft.signature === similarSourceSignature ? similarGeneratorDraft.results : []" :initial-selected-ids="similarGeneratorDraft.signature === similarSourceSignature ? similarGeneratorDraft.selectedIds : []" @state-change="updateSimilarGeneratorDraft" @select="addSimilarQuestions" @close="isSimilarGeneratorOpen = false" />
    <QuizPointsModal v-if="isPointsModalOpen" :question-count="quiz.questions.length" :current-total="pointsBudget || totalQuizPoints" @apply-same="applySamePoints" @distribute="distributeTotalPoints" @close="isPointsModalOpen = false" />
    <EditorConfirmModal v-if="editorDialog" v-bind="editorDialog" @cancel="closeEditorDialog" @confirm="confirmEditorDialog" />
    <Transition name="toast"><div v-if="mockNotice" class="fixed bottom-5 left-1/2 z-50 -translate-x-1/2 rounded-xl bg-slate-900 px-4 py-3 text-xs font-bold text-white shadow-2xl">{{ mockNotice }} — chức năng đang dùng mock UI.</div></Transition>
    </template>
  </div>
</template>
<script setup>
import { useAppLoading } from '@/composables/useAppLoading'
const { beginTask, endTask } = useAppLoading()

import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router'
import EditorHeader from '@/components/quiz-editor/EditorHeader.vue'
import EditorToolbar from '@/components/quiz-editor/EditorToolbar.vue'
import QuizCoverPanel from '@/components/quiz-editor/QuizCoverPanel.vue'
import QuizPointsModal from '@/components/quiz-editor/QuizPointsModal.vue'
import EditorConfirmModal from '@/components/quiz-editor/EditorConfirmModal.vue'
import QuestionEditor from '@/components/quiz-editor/QuestionEditor.vue'
import QuestionPicker from '@/components/quiz-editor/QuestionPicker.vue'
import QuestionSidebar from '@/components/quiz-editor/QuestionSidebar.vue'
import QuestionSourceModal from '@/components/quiz-editor/QuestionSourceModal.vue'
import AiQuestionGenerator from '@/components/quiz-editor/AiQuestionGenerator.vue'
import AiQuizReview from '@/components/quiz-editor/ai/AiQuizReview.vue'
import SimilarQuestionGenerator from '@/components/quiz-editor/ai/SimilarQuestionGenerator.vue'
import QuizSetupForm from '@/components/quiz-create/QuizSetupForm.vue'
import OcrQuestionImporter from '@/components/quiz-editor/ocr/OcrQuestionImporter.vue'
import { deleteQuiz, getQuiz, updateQuiz } from '@/services/quiz-editor/quizEditorApi.js'
import { updateQuizMetadata } from '@/services/quiz-editor/quizDraftApi.js'
import { normalizeQuiz } from '@/services/quiz-editor/quizEditorNormalizer.js'
import { serializeQuiz } from '@/services/quiz-editor/quizEditorSerializer.js'

let sequence = 100
const tempId = () => `temp-${Date.now()}-${sequence++}`
const makeAnswers = (contents = ['Đáp án A', 'Đáp án B', 'Đáp án C', 'Đáp án D']) => contents.map((content, index) => ({ id: tempId(), content, is_correct: index === 0 }))
const makeQuestion = (overrides = {}) => ({ id: tempId(), order: 0, type: 'single_choice', content: '', difficulty: 'medium', points: 10, answers: makeAnswers(), accepted_answers: [{ id: tempId(), content: '' }], ...overrides })

const route = useRoute()
const router = useRouter()
const quiz = ref({ title: '', cover: '', questions: [] })
const questionEditor = ref(null)
const isCoverPanelOpen = ref(false)
const pendingCoverFile = ref(null)
const coverRemoved = ref(false)
const isSavingCover = ref(false)
const isPointsModalOpen = ref(false)
const pointsBudget = ref(0)
const editorDialog = ref(null)
const pendingNavigation = ref('')
let allowNavigation = false

const activeQuestionId = ref(null)
const mockNotice = ref('')
const saveStatus = ref('Đang tải...')
const isLoading = ref(true)
const loadError = ref('')
const isDirty = ref(false)
const isSaving = ref(false)
const isDeleting = ref(false)
const saveError = ref('')
const saveAlert = ref(null)
const originalQuiz = ref(null)
const isHydrating = ref(true)
const editorRevision = ref(0)
const lastSavedRevision = ref(0)
const isQuestionSourceOpen = ref(false)
const activePickerSource = ref('')
const isAiGeneratorOpen = ref(false)
const isOcrImporterOpen = ref(false)
const isQuizReviewOpen = ref(false)
const aiGeneratorDraft = ref({ results: [], selectedIds: [] })
const aiQuizReviewDraft = ref({ signature: '', result: null })
const similarGeneratorDraft = ref({ signature: '', results: [], selectedIds: [] })
const similarSelectionMode = ref(false)
const similarSelectedIds = ref([])
const newlyAddedQuestionIds = ref([])
const isSimilarGeneratorOpen = ref(false)
const aiToolBusy = computed(() => isQuizReviewOpen.value || isSimilarGeneratorOpen.value)
const isUpdatingContext = ref(false)
const contextError = ref('')
const questionOrigins = ref({})
let noticeTimer
let addedHighlightTimer
let autosaveTimer
let queuedManualSave = false
let completeAfterSave = false
const AUTOSAVE_DELAY_MS = 2000
const activeQuestion = computed(() => quiz.value.questions.find((question) => question.id === activeQuestionId.value) || null)
const activeQuestionIndex = computed(() => quiz.value.questions.findIndex((question) => question.id === activeQuestionId.value))
const activeQuestionNumber = computed(() => Math.max(1, activeQuestionIndex.value + 1))
const roundPoints = (value) => Math.round(Number(value) * 100) / 100
const totalQuizPoints = computed(() => roundPoints(quiz.value.questions.reduce((total, question) => total + (Number(question.points) || 0), 0)))
const activeQuestionMaxPoints = computed(() => {
  if (!activeQuestion.value || pointsBudget.value <= 0) return 1000
  return Math.max(0.01, roundPoints(Number(activeQuestion.value.points || 0) + pointsBudget.value - totalQuizPoints.value))
})
const formatPoints = (value) => Number(value || 0).toLocaleString('vi-VN', { maximumFractionDigits: 2 })
const isBankQuestion = (question) => Boolean(question?.origin_question_id || questionOrigins.value[question?.id] === 'bank')
const rebuildQuestionOrigins = (questions) => {
  questionOrigins.value = Object.fromEntries((questions || []).filter((question) => question.origin_question_id).map((question) => [question.id, 'bank']))
}
const questionFingerprint = (question) => {
  const normalize = (value) => String(value || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().toLocaleLowerCase()
  const answers = question?.type === 'fill_in' ? question.accepted_answers || [] : question?.answers || []
  const answerKey = answers
    .map((answer) => `${normalize(answer.content)}::${question?.type === 'fill_in' || answer.is_correct ? '1' : '0'}`)
    .sort()
    .join('||')
  return `${normalize(question?.content)}###${question?.type || 'single_choice'}###${answerKey}`
}
const duplicateQuestionIds = (questions) => {
  const seen = new Map()
  const duplicates = new Set()
  ;(questions || []).forEach((question) => {
    if (!question?.content?.trim()) return
    const fingerprint = questionFingerprint(question)
    if (seen.has(fingerprint)) {
      duplicates.add(question.id)
      duplicates.add(seen.get(fingerprint))
      return
    }
    seen.set(fingerprint, question.id)
  })
  return duplicates
}
const missingQuizContext = computed(() => !quiz.value.grade_id || !quiz.value.subject_id)
const invalidQuestionIds = computed(() => {
  const duplicates = duplicateQuestionIds(quiz.value.questions)
  return new Set(quiz.value.questions.filter((question, index) => duplicates.has(question.id) || getQuestionValidationMessage(question, index)).map((question) => question.id))
})
const similarSourceQuestions = computed(() => quiz.value.questions.filter((question) => similarSelectedIds.value.includes(question.id)).map(cloneData))
const similarSourceSignature = computed(() => JSON.stringify(similarSourceQuestions.value.map((question) => question.id)))

const syncQuestionOrder = () => { quiz.value.questions.forEach((question, index) => { question.order = index + 1 }) }
const quizId = computed(() => route.params.id || route.query.id)
const cloneData = (value) => JSON.parse(JSON.stringify(value))
const clearAutosaveTimer = () => { clearTimeout(autosaveTimer); autosaveTimer = undefined }
const persistedSignature = (value) => JSON.stringify(serializeQuiz(value || {}))
const hasPersistedChanges = () => originalQuiz.value !== null && persistedSignature(quiz.value) !== persistedSignature(originalQuiz.value)
const scheduleAutosave = () => {
  clearAutosaveTimer()
  autosaveTimer = setTimeout(() => { performSave({ source: 'autosave' }) }, AUTOSAVE_DELAY_MS)
}
const markLocalChanged = () => {
  isDirty.value = hasPersistedChanges()
  if (isDirty.value) {
    saveStatus.value = 'Chưa lưu'
    return
  }
  clearAutosaveTimer()
  saveStatus.value = 'Đã lưu'
}
const updateQuizTitle = (title) => { quiz.value.title = title; markLocalChanged() }
const updateTimeLimit = (minutes) => {
  if (!Number.isFinite(minutes)) return
  quiz.value.time_limit_seconds = Math.min(1440, Math.max(1, Math.round(minutes))) * 60
  markLocalChanged()
}
const persistCover = async (payload, fallbackMessage) => {
  if (!quizId.value || isSavingCover.value) return false
  isSavingCover.value = true
  saveStatus.value = 'Đang lưu ảnh bìa...'
  try {
    const updated = normalizeQuiz(await updateQuiz(quizId.value, payload))
    isHydrating.value = true
    quiz.value.cover = updated.cover
    if (originalQuiz.value) originalQuiz.value.cover = updated.cover
    isHydrating.value = false
    // Keep the local blob preview after a successful upload. Replacing it
    // immediately with the remote URL makes the cover flash while the browser
    // downloads the newly stored image. It is cleared when the user chooses a
    // preset, removes/replaces the cover, closes/reloads the editor.
    if (!(payload.cover_file instanceof File)) pendingCoverFile.value = null
    coverRemoved.value = false
    saveStatus.value = isDirty.value ? 'Chưa lưu' : 'Đã lưu ảnh bìa'
    return true
  } catch (error) {
    saveStatus.value = 'Lỗi tải ảnh bìa'
    showSaveAlert({
      title: 'Không thể lưu ảnh bìa',
      message: error?.response?.data?.message || error?.message || fallbackMessage,
    })
    return false
  } finally {
    isSavingCover.value = false
  }
}
const selectPresetCover = async (cover) => {
  pendingCoverFile.value = null
  coverRemoved.value = false
  quiz.value.cover = cover
  await persistCover({ cover }, 'Không thể lưu nền đã chọn.')
}
const selectUploadedCover = async (file) => {
  pendingCoverFile.value = file
  coverRemoved.value = false
  await persistCover({ cover_file: file }, 'Không thể tải ảnh bìa lên máy chủ.')
}
const removeCover = async () => {
  pendingCoverFile.value = null
  coverRemoved.value = true
  quiz.value.cover = ''
  await persistCover({ remove_cover: true }, 'Không thể xóa ảnh bìa.')
}
const selectQuestion = (id) => { activeQuestionId.value = id }
const showSaveAlert = ({ title, message, questionId = null }) => {
  saveAlert.value = { title, message, questionId }
}
const goToSaveAlertQuestion = () => {
  if (saveAlert.value?.questionId !== null) activeQuestionId.value = saveAlert.value.questionId
}
const showValidationAlert = (validationError) => {
  showSaveAlert({
    title: 'Cần hoàn thiện câu hỏi',
    message: validationError.message.replace(/^Câu \d+\s*/i, ''),
    questionId: validationError.questionId ?? null,
  })
}
const showApiSaveAlert = (error) => {
  const rawMessage = String(error?.message || 'Không thể lưu Quiz. Dữ liệu chỉnh sửa vẫn được giữ trên trình duyệt.')
  const questionMatch = rawMessage.match(/Câu\s*(\d+)/i)
  const questionId = questionMatch ? quiz.value.questions[Number(questionMatch[1]) - 1]?.id ?? null : null

  if (rawMessage.includes('nguồn không còn là câu hỏi Ngân hàng')) {
    showSaveAlert({ title: 'Câu hỏi Ngân hàng không còn dùng được', message: 'Câu này không còn ở trạng thái đã duyệt. Hãy xóa câu đó hoặc chọn lại một câu khác từ Ngân hàng.', questionId })
    return
  }
  if (rawMessage.includes('trùng với câu')) {
    showSaveAlert({ title: 'Có câu hỏi trùng', message: rawMessage, questionId })
    return
  }
  if (rawMessage.includes('không thể chỉnh sửa')) {
    showSaveAlert({ title: 'Câu hỏi Ngân hàng đang được khóa', message: 'Hãy dùng “Tạo bản sao để chỉnh sửa” nếu muốn thay đổi nội dung hoặc đáp án.', questionId })
    return
  }
  showSaveAlert({ title: 'Chưa thể lưu Quiz', message: rawMessage, questionId })
}
const loadQuiz = async () => {
    beginTask()
    try {
clearAutosaveTimer()
  isHydrating.value = true
  if (!quizId.value) {
    isLoading.value = false
    isHydrating.value = false
    saveStatus.value = 'Chưa tải dữ liệu'
    loadError.value = 'Thiếu mã Quiz. Hãy mở trang với tham số ?id=...'
    return
  }

  isLoading.value = true
  loadError.value = ''
  try {
    const rawQuiz = await getQuiz(quizId.value)
    const normalizedQuiz = normalizeQuiz(rawQuiz)
    quiz.value = normalizedQuiz
    pointsBudget.value = roundPoints(normalizedQuiz.questions.reduce((total, question) => total + Number(question.points || 0), 0))
    originalQuiz.value = cloneData(normalizedQuiz)
    pendingCoverFile.value = null
    coverRemoved.value = false
    activeQuestionId.value = normalizedQuiz.questions[0]?.id ?? null
    rebuildQuestionOrigins(normalizedQuiz.questions)
    isDirty.value = false
    editorRevision.value = 0
    lastSavedRevision.value = 0
    saveError.value = ''
    saveAlert.value = null
    saveStatus.value = 'Dữ liệu đã tải'
    await consumeInitialSource()
    if (import.meta.env.DEV) console.info('[QuizEditorV2] loaded quiz')
  } catch (error) {
    quiz.value = { title: '', cover: '', questions: [] }
    pointsBudget.value = 0
    pendingCoverFile.value = null
    coverRemoved.value = false
    activeQuestionId.value = null
    saveStatus.value = 'Lỗi tải dữ liệu'
    loadError.value = error?.response?.status === 404
      ? 'Không tìm thấy Quiz hoặc Quiz không còn khả dụng.'
      : 'Vui lòng kiểm tra quyền truy cập hoặc thử lại sau.'
    if (import.meta.env.DEV) console.error('[QuizEditorV2] failed to load quiz', error)
  } finally {
    isLoading.value = false
    isHydrating.value = false
  }
    } finally {
      endTask()
    }
  }
function getQuestionValidationMessage(question, index) {
  const label = `Câu ${index + 1}`
  if (!question.content?.trim()) return `${label} chưa có nội dung.`
  if (!['single_choice', 'multi_choice', 'fill_in', 'true_false'].includes(question.type)) return `${label} có loại câu hỏi không hợp lệ.`
  if (!Number.isFinite(Number(question.points)) || Number(question.points) < 0.01) return `${label} có số điểm không hợp lệ.`

  if (question.type === 'fill_in') {
    const acceptedAnswers = (question.accepted_answers || []).filter((answer) => answer.content?.trim())
    if (acceptedAnswers.length < 2) return `${label} cần ít nhất 2 đáp án được chấp nhận theo API hiện tại.`
    return ''
  }

  const answers = question.answers || []
  if (answers.length < 2 || answers.some((answer) => !answer.content?.trim())) return `${label} cần ít nhất 2 đáp án có nội dung.`
  const correctCount = answers.filter((answer) => answer.is_correct).length
  if (question.type === 'single_choice' && correctCount !== 1) return `${label} phải có đúng 1 đáp án đúng.`
  if (question.type === 'multi_choice' && correctCount < 1) return `${label} phải có ít nhất 1 đáp án đúng.`
  if (question.type === 'true_false' && (answers.length !== 2 || correctCount !== 1)) return `${label} Đúng/Sai phải có 2 lựa chọn và đúng 1 đáp án.`
  return ''
}

const validateQuizForSave = () => {
  if (!quiz.value.title.trim()) return { message: 'Vui lòng nhập tên Quiz trước khi lưu.' }
  if (pointsBudget.value > 0 && totalQuizPoints.value > pointsBudget.value + 0.001) {
    return { message: `Tổng điểm ${formatPoints(totalQuizPoints.value)} vượt quá tổng điểm cho phép ${formatPoints(pointsBudget.value)}.` }
  }

  const duplicateIds = duplicateQuestionIds(quiz.value.questions)
  if (duplicateIds.size) {
    const questionId = quiz.value.questions.find((question) => duplicateIds.has(question.id))?.id
    return { questionId, message: 'Quiz đang có câu hỏi trùng nội dung, loại và đáp án. Hãy xóa hoặc chỉnh sửa một câu trước khi lưu.' }
  }

  for (const [index, question] of quiz.value.questions.entries()) {
    const message = getQuestionValidationMessage(question, index)
    if (message) return { questionId: question.id, message }
  }

  return null
}
const replaceSavedIds = (savingQuiz, savedQuiz) => {
  const idMap = new Map()
  savingQuiz.questions.forEach((question, questionIndex) => {
    const savedQuestion = savedQuiz.questions[questionIndex]
    if (!savedQuestion) return
    if (question.id !== savedQuestion.id) idMap.set(question.id, savedQuestion.id)
    const sourceAnswers = question.type === 'fill_in' ? question.accepted_answers : question.answers
    const savedAnswers = savedQuestion.type === 'fill_in' ? savedQuestion.accepted_answers : savedQuestion.answers
    ;(sourceAnswers || []).forEach((answer, answerIndex) => {
      if (savedAnswers?.[answerIndex] && answer.id !== savedAnswers[answerIndex].id) idMap.set(answer.id, savedAnswers[answerIndex].id)
    })
  })

  if (!idMap.size) return
  isHydrating.value = true
  const nextOrigins = { ...questionOrigins.value }
  quiz.value.questions.forEach((question) => {
    if (idMap.has(question.id)) {
      const previousId = question.id
      question.id = idMap.get(previousId)
      if (nextOrigins[previousId]) {
        nextOrigins[question.id] = nextOrigins[previousId]
        delete nextOrigins[previousId]
      }
    }
    ;(question.answers || []).forEach((answer) => { if (idMap.has(answer.id)) answer.id = idMap.get(answer.id) })
    ;(question.accepted_answers || []).forEach((answer) => { if (idMap.has(answer.id)) answer.id = idMap.get(answer.id) })
  })
  questionOrigins.value = nextOrigins
  if (idMap.has(activeQuestionId.value)) activeQuestionId.value = idMap.get(activeQuestionId.value)
  isHydrating.value = false
}
const performSave = async ({ source }) => {
  const isManual = source === 'manual'
  if (isManual) clearAutosaveTimer()
  if (!isDirty.value || !quizId.value) return
  if (isSaving.value) {
    if (isManual) queuedManualSave = true
    return
  }

  const validationError = validateQuizForSave()
  if (validationError) {
      if (isManual) {
        if (validationError.questionId !== undefined) activeQuestionId.value = validationError.questionId
        saveError.value = validationError.message
        showValidationAlert(validationError)
      saveStatus.value = 'Chưa thể lưu'
    } else {
      saveStatus.value = 'Chưa lưu — dữ liệu chưa hoàn chỉnh'
      if (import.meta.env.DEV) console.info('[QuizEditorV2] autosave deferred:', validationError.message)
    }
    return
  }

    saveError.value = ''
    saveAlert.value = null
  isSaving.value = true
  saveStatus.value = 'Đang lưu...'
  const savingRevision = editorRevision.value
  const savingQuiz = cloneData(quiz.value)
  let updateSucceeded = false
  try {
    const savePayload = serializeQuiz(savingQuiz)
    const updatedQuiz = normalizeQuiz(await updateQuiz(quizId.value, savePayload))
    updateSucceeded = true
    originalQuiz.value = cloneData(updatedQuiz)
    lastSavedRevision.value = savingRevision
    replaceSavedIds(savingQuiz, updatedQuiz)

    if (isManual) {
      const reloadedQuiz = normalizeQuiz(await getQuiz(quizId.value))
      originalQuiz.value = cloneData(reloadedQuiz)
      if (editorRevision.value === savingRevision) {
        const previousActiveId = activeQuestionId.value
        isHydrating.value = true
        quiz.value = reloadedQuiz
        activeQuestionId.value = reloadedQuiz.questions.some((question) => question.id === previousActiveId)
          ? previousActiveId
          : reloadedQuiz.questions[0]?.id ?? null
        rebuildQuestionOrigins(reloadedQuiz.questions)
        isHydrating.value = false
      }
    }
    isDirty.value = editorRevision.value !== lastSavedRevision.value
    saveStatus.value = isDirty.value ? 'Chưa lưu' : 'Đã lưu'
  } catch (error) {
    if (updateSucceeded) {
      isDirty.value = editorRevision.value !== lastSavedRevision.value
      saveStatus.value = isDirty.value ? 'Chưa lưu' : 'Đã lưu, chưa xác minh'
      if (isManual) saveError.value = 'Backend đã nhận dữ liệu nhưng không thể tải lại để xác minh.'
      if (isManual) showSaveAlert({ title: 'Đã lưu nhưng chưa xác minh được', message: 'Dữ liệu có thể đã được lưu. Hãy tải lại trang để kiểm tra lại.' })
    } else {
      isDirty.value = true
      saveStatus.value = source === 'autosave' ? 'Lưu tự động thất bại' : 'Lỗi khi lưu'
      if (isManual) {
        saveError.value = error?.message || 'Không thể lưu Quiz. Dữ liệu chỉnh sửa vẫn được giữ trên trình duyệt.'
        showApiSaveAlert(error)
      }
    }
    if (import.meta.env.DEV) console.error('[QuizEditorV2] failed to save quiz', error)
  } finally {
    isSaving.value = false
    const shouldComplete = completeAfterSave
    completeAfterSave = false
    if (queuedManualSave) {
      queuedManualSave = false
      performSave({ source: 'manual' })
    } else if (isDirty.value && (updateSucceeded || editorRevision.value > savingRevision)) {
      scheduleAutosave()
    }
    if (shouldComplete) completeQuiz()
  }
}
const completeQuiz = async () => {
  clearAutosaveTimer()
  if (isSaving.value) {
    completeAfterSave = true
    saveStatus.value = 'Đang hoàn tất...'
    return
  }
  if (isDirty.value) await performSave({ source: 'manual' })
  if (!isDirty.value && !isSaving.value) await router.push(`/quizzes/${quizId.value}`)
}
const removeQuiz = () => {
  if (!quizId.value || isDeleting.value || isSaving.value) return
  editorDialog.value = {
    action: 'delete-quiz',
    title: 'Xóa bộ Quiz?',
    message: `Quiz “${quiz.value.title || 'Chưa đặt tên'}” sẽ được chuyển vào thùng rác và vẫn có thể khôi phục sau.`,
    confirmLabel: 'Chuyển vào thùng rác',
    cancelLabel: 'Giữ lại',
    danger: true,
  }
}
const executeRemoveQuiz = async () => {
  clearAutosaveTimer()
  isDeleting.value = true
  isHydrating.value = true
  saveError.value = ''
  saveStatus.value = 'Đang xóa quiz...'
  try {
    await deleteQuiz(quizId.value)
    allowNavigation = true
    await router.push('/quizzes')
  } catch (error) {
    isHydrating.value = false
    isDeleting.value = false
    saveStatus.value = isDirty.value ? 'Chưa lưu' : 'Đã lưu'
    saveError.value = error?.response?.data?.message || error?.message || 'Không thể xóa quiz.'
  }
}
const openQuestionSource = () => { isQuestionSourceOpen.value = true }
const openAiGenerator = () => { isAiGeneratorOpen.value = true }
const openQuizReview = () => {
  if (aiQuizReviewDraft.value.signature !== persistedSignature(quiz.value)) aiQuizReviewDraft.value = { signature: '', result: null }
  isQuizReviewOpen.value = true
}
const updateAiGeneratorDraft = (draft) => {
  aiGeneratorDraft.value = {
    results: Array.isArray(draft?.results) ? cloneData(draft.results) : [],
    selectedIds: Array.isArray(draft?.selectedIds) ? [...draft.selectedIds] : [],
  }
}
const storeQuizReview = (result) => { aiQuizReviewDraft.value = { signature: persistedSignature(quiz.value), result: cloneData(result) } }
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
  if (source === 'ai') isAiGeneratorOpen.value = true
  if (source === 'ocr') isOcrImporterOpen.value = true
}
const highlightAddedQuestions = (questions) => {
  clearTimeout(addedHighlightTimer)
  newlyAddedQuestionIds.value = questions.map((question) => question.id)
  addedHighlightTimer = setTimeout(() => { newlyAddedQuestionIds.value = [] }, 2400)
}
const addQuestion = () => { const question = makeQuestion(); quiz.value.questions.push(question); pointsBudget.value = roundPoints(pointsBudget.value + question.points); syncQuestionOrder(); activeQuestionId.value = question.id; markLocalChanged(); highlightAddedQuestions([question]) }
const addPickedQuestions = (selectedQuestions) => {
  if (!Array.isArray(selectedQuestions) || !selectedQuestions.length) return
  const selectedSource = activePickerSource.value
  const normalizedQuestions = selectedQuestions.map((question) => ({
    ...question,
    points: Number(question.points) > 0 ? Number(question.points) : 10,
    difficulty: question.difficulty || 'medium',
    id: tempId(),
    answers: (question.answers || []).map((answer) => ({ ...answer, id: tempId() })),
    accepted_answers: (question.accepted_answers || []).map((answer) => ({ ...answer, id: tempId() })),
    origin_question_id: selectedSource === 'bank' ? Number(question.source_question_id || question.id) : null,
  }))
  const existingFingerprints = new Set(quiz.value.questions.filter((question) => question.content?.trim()).map(questionFingerprint))
  const uniqueQuestions = normalizedQuestions.filter((question) => {
    const fingerprint = questionFingerprint(question)
    if (!question.content?.trim() || existingFingerprints.has(fingerprint)) return false
    existingFingerprints.add(fingerprint)
    return true
  })
  if (!uniqueQuestions.length) {
    editorDialog.value = {
      action: 'notice',
      title: 'Không thể thêm câu hỏi',
      message: 'Các câu đã chọn đều trùng với câu hỏi đang có trong Quiz.',
      confirmLabel: 'Đã hiểu',
      cancelLabel: '',
      danger: false,
    }
    return
  }
  if (uniqueQuestions.length < normalizedQuestions.length) {
    editorDialog.value = {
      action: 'notice',
      title: 'Đã bỏ qua câu hỏi trùng',
      message: `Đã bỏ qua ${normalizedQuestions.length - uniqueQuestions.length} câu trùng trong Quiz. Các câu còn lại vẫn được thêm bình thường.`,
      confirmLabel: 'Đã hiểu',
      cancelLabel: '',
      danger: false,
    }
  }
  const insertAt = activeQuestionIndex.value < 0 ? quiz.value.questions.length : activeQuestionIndex.value + 1
  quiz.value.questions.splice(insertAt, 0, ...uniqueQuestions)
  pointsBudget.value = roundPoints(pointsBudget.value + uniqueQuestions.reduce((total, question) => total + Number(question.points || 0), 0))
  syncQuestionOrder()
  uniqueQuestions.forEach((question) => { questionOrigins.value[question.id] = selectedSource })
  activeQuestionId.value = uniqueQuestions[0].id
  activePickerSource.value = ''
  markLocalChanged()
  highlightAddedQuestions(uniqueQuestions)
}
const consumeInitialSource = async () => {
  const shouldOpenSources = String(route.query.openQuestionSources || '') === '1'
  if (!shouldOpenSources || missingQuizContext.value) return
  if (shouldOpenSources) isQuestionSourceOpen.value = true
  const nextQuery = { ...route.query }
  delete nextQuery.openQuestionSources
  await router.replace({ query: nextQuery })
}
const saveMissingContext = async (form) => {
  if (isUpdatingContext.value) return
  isUpdatingContext.value = true
  contextError.value = ''
  try {
    const updated = normalizeQuiz(await updateQuizMetadata(quizId.value, form))
    quiz.value = updated
    originalQuiz.value = cloneData(updated)
    isDirty.value = false
    saveStatus.value = 'Đã cập nhật thông tin Quiz'
    await consumeInitialSource()
  } catch (error) {
    contextError.value = error?.response?.data?.message || error?.message || 'Không thể cập nhật thông tin Quiz.'
  } finally {
    isUpdatingContext.value = false
  }
}
const addAiQuestions = async (selectedQuestions) => {
  activePickerSource.value = 'ai'
  addPickedQuestions(selectedQuestions)
  aiGeneratorDraft.value = { results: [], selectedIds: [] }
  isAiGeneratorOpen.value = false
  if (isDirty.value) await performSave({ source: 'manual' })
}
const addOcrQuestions = (selectedQuestions) => {
  activePickerSource.value = 'ocr'
  addPickedQuestions(selectedQuestions)
  isOcrImporterOpen.value = false
}
const goToReviewedQuestion = (questionId) => {
  const target = quiz.value.questions.find((question) => String(question.id) === String(questionId))
  if (!target) return
  activeQuestionId.value = target.id
  isQuizReviewOpen.value = false
}
const startSimilarSelection = () => { similarSelectedIds.value = activeQuestion.value ? [activeQuestion.value.id] : []; similarSelectionMode.value = true }
const toggleSimilarQuestion = (questionId) => { similarSelectedIds.value = similarSelectedIds.value.includes(questionId) ? similarSelectedIds.value.filter((id) => id !== questionId) : [...similarSelectedIds.value, questionId] }
const cancelSimilarSelection = () => { similarSelectionMode.value = false; similarSelectedIds.value = [] }
const openSimilarGenerator = () => {
  if (!similarSelectedIds.value.length) return
  if (similarGeneratorDraft.value.signature !== similarSourceSignature.value) similarGeneratorDraft.value = { signature: similarSourceSignature.value, results: [], selectedIds: [] }
  isSimilarGeneratorOpen.value = true
}
const updateSimilarGeneratorDraft = (draft) => {
  similarGeneratorDraft.value = {
    signature: similarSourceSignature.value,
    results: Array.isArray(draft?.results) ? cloneData(draft.results) : [],
    selectedIds: Array.isArray(draft?.selectedIds) ? [...draft.selectedIds] : [],
  }
}
const addSimilarQuestions = async (questions) => {
  await addAiQuestions(questions)
  similarGeneratorDraft.value = { signature: '', results: [], selectedIds: [] }
  isSimilarGeneratorOpen.value = false
  cancelSimilarSelection()
}
const addAnswer = () => { if (!activeQuestion.value) return; activeQuestion.value.answers.push({ id: tempId(), content: '', is_correct: false }); markLocalChanged() }
const removeAnswer = (index) => { if (!activeQuestion.value || activeQuestion.value.answers.length <= 2) return; const removed = activeQuestion.value.answers.splice(index, 1)[0]; if (removed?.is_correct && activeQuestion.value.type === 'single_choice') activeQuestion.value.answers[0].is_correct = true; markLocalChanged() }
const addFillAnswer = () => { activeQuestion.value?.accepted_answers.push({ id: tempId(), content: '' }); markLocalChanged() }
const removeFillAnswer = (index) => { if ((activeQuestion.value?.accepted_answers.length || 0) > 1) activeQuestion.value.accepted_answers.splice(index, 1); markLocalChanged() }
const updatePoints = (points) => {
  if (!activeQuestion.value || !Number.isFinite(points) || points <= 0) return
  const nextPoints = roundPoints(points)
  const nextTotal = roundPoints(totalQuizPoints.value - Number(activeQuestion.value.points || 0) + nextPoints)
  if (pointsBudget.value > 0 && nextTotal > pointsBudget.value + 0.001) {
    showSaveAlert({
      title: 'Điểm vượt quá giới hạn',
      message: `Tổng sau thay đổi sẽ là ${formatPoints(nextTotal)} điểm, vượt quá tổng cho phép ${formatPoints(pointsBudget.value)} điểm. Hãy giảm điểm câu khác hoặc thiết lập lại tổng điểm.`,
      questionId: activeQuestion.value.id,
    })
    return
  }
  activeQuestion.value.points = nextPoints
  saveAlert.value = null
  markLocalChanged()
}
const applySamePoints = (points) => {
  const value = roundPoints(points)
  if (!Number.isFinite(value) || value <= 0) return
  quiz.value.questions.forEach((question) => { question.points = value })
  pointsBudget.value = roundPoints(value * quiz.value.questions.length)
  isPointsModalOpen.value = false
  markLocalChanged()
}
const distributeTotalPoints = (total) => {
  const questions = quiz.value.questions
  const target = roundPoints(total)
  if (!questions.length || !Number.isFinite(target) || target < questions.length * 0.01) return
  const base = Math.floor((target / questions.length) * 100) / 100
  let allocated = 0
  questions.forEach((question, index) => {
    question.points = index === questions.length - 1 ? roundPoints(target - allocated) : base
    allocated = roundPoints(allocated + question.points)
  })
  pointsBudget.value = target
  isPointsModalOpen.value = false
  markLocalChanged()
}
const updateDifficulty = (difficulty) => {
  if (!activeQuestion.value || !['easy', 'medium', 'hard'].includes(difficulty)) return
  activeQuestion.value.difficulty = difficulty
  markLocalChanged()
}
const changeQuestionType = (type) => { if (!activeQuestion.value) return; activeQuestion.value.type = type; if (type === 'true_false') activeQuestion.value.answers = makeAnswers(['Đúng', 'Sai']).slice(0, 2); if (type === 'fill_in' && !activeQuestion.value.accepted_answers.length) activeQuestion.value.accepted_answers = [{ id: tempId(), content: '' }]; if ((type === 'single_choice' || type === 'multi_choice') && activeQuestion.value.answers.length < 2) activeQuestion.value.answers = makeAnswers(); if (type === 'single_choice') activeQuestion.value.answers.forEach((answer, index) => { answer.is_correct = index === 0 }); markLocalChanged() }
const duplicateQuestion = () => {
  if (!activeQuestion.value) return
  const originalId = activeQuestion.value.id
  const clone = JSON.parse(JSON.stringify(activeQuestion.value))
  clone.id = tempId()
  clone.content = `${clone.content} (bản sao)`
  clone.answers = (clone.answers || []).map((answer) => ({ ...answer, id: tempId() }))
  clone.accepted_answers = (clone.accepted_answers || []).map((answer) => ({ ...answer, id: tempId() }))
  quiz.value.questions.splice(activeQuestionIndex.value + 1, 0, clone)
  pointsBudget.value = roundPoints(pointsBudget.value + Number(clone.points || 0))
  syncQuestionOrder()
  // A duplicate is a new local question. It must not inherit the Bank lock or
  // the approved origin of the source question.
  clone.origin_question_id = null
  delete questionOrigins.value[clone.id]
  activeQuestionId.value = clone.id
  markLocalChanged()
  highlightAddedQuestions([clone])
}
const removeQuestion = () => {
  const index = activeQuestionIndex.value
  if (index < 0) return
  editorDialog.value = {
    action: 'delete-question',
    questionId: quiz.value.questions[index].id,
    title: `Xóa câu hỏi ${index + 1}?`,
    message: 'Câu hỏi và các đáp án của câu này sẽ bị loại khỏi Quiz. Thay đổi chỉ được ghi nhận sau khi Quiz được lưu.',
    confirmLabel: 'Xóa câu hỏi',
    cancelLabel: 'Hủy',
    danger: true,
  }
}
const executeRemoveQuestion = (questionId) => {
  const index = quiz.value.questions.findIndex((question) => question.id === questionId)
  if (index < 0) return
  const [removedQuestion] = quiz.value.questions.splice(index, 1)
  pointsBudget.value = Math.max(0, roundPoints(pointsBudget.value - Number(removedQuestion.points || 0)))
  delete questionOrigins.value[removedQuestion.id]
  syncQuestionOrder()
  activeQuestionId.value = quiz.value.questions[index]?.id || quiz.value.questions[index - 1]?.id || null
  markLocalChanged()
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
  markLocalChanged()
}
const reorderQuestion = ({ draggedId, targetId, position }) => {
  const from = quiz.value.questions.findIndex((question) => question.id === draggedId)
  const target = quiz.value.questions.findIndex((question) => question.id === targetId)
  if (from < 0 || target < 0 || from === target) return
  const [question] = quiz.value.questions.splice(from, 1)
  let insertAt = quiz.value.questions.findIndex((item) => item.id === targetId)
  if (position === 'after') insertAt += 1
  quiz.value.questions.splice(insertAt, 0, question)
  syncQuestionOrder()
  activeQuestionId.value = draggedId
  markLocalChanged()
}
const closeEditorDialog = () => {
  editorDialog.value = null
  pendingNavigation.value = ''
}
const confirmEditorDialog = async () => {
  const dialog = editorDialog.value
  editorDialog.value = null
  if (!dialog) return
  if (dialog.action === 'leave') {
    const destination = pendingNavigation.value
    clearAutosaveTimer()
    await performSave({ source: 'manual' })
    if (!isDirty.value && !isSaving.value && destination) {
      pendingNavigation.value = ''
      allowNavigation = true
      await router.push(destination)
    } else {
      pendingNavigation.value = ''
      allowNavigation = false
      if (!saveAlert.value) {
        showSaveAlert({
          title: 'Chưa thể chuyển trang',
          message: 'Quiz chưa được lưu thành công. Vui lòng kiểm tra lại dữ liệu và thử lưu lần nữa.',
        })
      }
    }
    return
  }
  if (dialog.action === 'delete-quiz') {
    await executeRemoveQuiz()
    return
  }
  if (dialog.action === 'delete-question') executeRemoveQuestion(dialog.questionId)
}
const warnBeforeBrowserUnload = (event) => {
  if (!isDirty.value) return
  event.preventDefault()
  event.returnValue = ''
}
onBeforeRouteLeave((to) => {
  if (allowNavigation || !isDirty.value) return true
  pendingNavigation.value = to.fullPath
  editorDialog.value = {
    action: 'leave',
    title: 'Bạn chưa lưu thay đổi',
    message: 'Quiz phải được lưu thành công trước khi chuyển sang trang khác. Nếu dữ liệu chưa hợp lệ hoặc lưu thất bại, bạn sẽ tiếp tục ở lại trình chỉnh sửa.',
    confirmLabel: 'Lưu và chuyển trang',
    cancelLabel: 'Ở lại',
    danger: false,
  }
  return false
})
const showMockNotice = (action) => { mockNotice.value = action; clearTimeout(noticeTimer); noticeTimer = setTimeout(() => { mockNotice.value = '' }, 2200) }
watch(quiz, () => {
  if (isLoading.value || isHydrating.value || loadError.value) return
  editorRevision.value += 1
  markLocalChanged()
  if (isDirty.value) scheduleAutosave()
}, { deep: true, flush: 'sync' })
watch(quizId, loadQuiz)
onMounted(() => { loadQuiz(); window.addEventListener('beforeunload', warnBeforeBrowserUnload) })
onBeforeUnmount(() => { clearAutosaveTimer(); clearTimeout(noticeTimer); clearTimeout(addedHighlightTimer); window.removeEventListener('beforeunload', warnBeforeBrowserUnload) })
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
