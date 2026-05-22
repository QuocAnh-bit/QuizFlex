<template>
  <section class="grid gap-6 py-8 xl:grid-cols-[minmax(0,1fr)_360px]">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-soft)] backdrop-blur-2xl sm:p-8">
      <div class="pointer-events-none absolute -left-24 -top-24 h-80 w-80 rounded-full bg-[var(--primary)]/20 blur-3xl"></div>
      <div class="pointer-events-none absolute -bottom-24 right-0 h-80 w-80 rounded-full bg-[var(--accent)]/10 blur-3xl"></div>

      <div class="relative z-10">
        <div v-if="isLoading" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-sm font-bold text-[var(--muted)]">Đang chuẩn bị homework...</div>
        <div v-if="errorMessage" class="mb-6 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">{{ errorMessage }}</div>
        <div v-if="successMessage" class="mb-6 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-300">{{ successMessage }}</div>

        <template v-if="currentQuestion.id">
          <div class="mb-7 flex flex-wrap items-start justify-between gap-4">
            <div>
              <div class="mb-4 flex flex-wrap items-center gap-2">
                <span class="rounded-full border border-[var(--border-strong)] bg-[var(--chip-active)] px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-[var(--primary)]">Câu {{ currentIndex + 1 }} / {{ questions.length }}</span>
                <span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-2 text-xs font-black text-[var(--muted)]">{{ assignment?.quiz?.title || assignment?.title || 'Homework' }}</span>
                <span v-if="isSavingCurrent" class="rounded-full border border-amber-500/30 bg-amber-500/10 px-4 py-2 text-xs font-black text-amber-300">Đang lưu</span>
              </div>
              <h1 class="max-w-4xl text-3xl font-black leading-tight tracking-[-0.055em] text-[var(--text)] sm:text-5xl">{{ currentQuestion.question }}</h1>
              <p class="mt-4 max-w-2xl text-sm font-medium leading-7 text-[var(--muted)]">Chọn đáp án để hệ thống lưu câu trả lời vào homework submission.</p>
            </div>

            <div class="grid gap-3 text-center">
              <div class="relative grid h-24 w-24 place-items-center rounded-full border border-[var(--border)] bg-[var(--surface-soft)] shadow-[var(--shadow-card)]">
                <div class="absolute inset-1 rounded-full" :style="progressRingStyle"></div>
                <div class="relative z-10 grid h-16 w-16 place-items-center rounded-full bg-[var(--surface-strong)]">
                  <span class="text-lg font-black text-[var(--text)]">{{ answeredPercent }}%</span>
                </div>
              </div>
              <div class="text-xs font-black text-[var(--muted)]">Đã trả lời</div>
            </div>
          </div>

          <div class="mb-8 overflow-hidden rounded-full border border-[var(--border)] bg-[var(--surface-soft)] p-1">
            <div class="h-2 rounded-full bg-gradient-to-r from-[var(--primary)] via-[var(--primary-2)] to-[var(--accent)] transition-all duration-500" :style="{ width: `${answeredPercent}%` }"></div>
          </div>

          <div class="grid gap-4">
            <button
              v-for="answer in currentQuestion.answers"
              :key="answer.id"
              type="button"
              class="group relative overflow-hidden rounded-[1.35rem] border p-4 text-left transition duration-300 hover:-translate-y-1 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60"
              :class="getAnswerClass(answer.id)"
              :disabled="isSubmitting || isSavingCurrent"
              @click="toggleAnswer(answer.id)"
            >
              <div v-if="isAnswerSelected(answer.id)" class="absolute inset-0 bg-gradient-to-r from-[var(--primary)]/10 via-[var(--primary-2)]/10 to-[var(--accent)]/10"></div>
              <div class="relative z-10 flex items-center gap-4">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-[var(--primary)] to-[var(--primary-2)] text-sm font-black text-white shadow-[0_14px_30px_rgba(155,44,255,0.24)]">{{ answer.key }}</span>
                <span class="font-bold leading-7 text-[var(--text)]">{{ answer.text }}</span>
                <span v-if="isAnswerSelected(answer.id)" class="ml-auto grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[var(--chip-active)] text-sm font-black text-[var(--primary)]">✓</span>
              </div>
            </button>
          </div>

          <div class="mt-8 flex flex-wrap items-center justify-between gap-3">
            <button class="btn-ghost disabled:cursor-not-allowed disabled:opacity-50" type="button" :disabled="currentIndex === 0" @click="goPrevious">Câu trước</button>
            <div class="flex flex-wrap items-center gap-3">
              <span class="text-sm font-bold text-[var(--muted)]">Đã trả lời: <b class="text-[var(--primary)]">{{ answeredCount }}/{{ questions.length }}</b></span>
              <button class="btn-ghost" type="button" :disabled="isLastQuestion" @click="goNext">Câu tiếp theo</button>
              <button class="btn-primary disabled:cursor-not-allowed disabled:opacity-60" type="button" :disabled="isSubmitting || !submission?.id" @click="submitAssignment(false)">
                {{ isSubmitting ? 'Đang nộp...' : 'Submit' }}
              </button>
            </div>
          </div>
        </template>
      </div>
    </article>

    <aside class="grid content-start gap-5">
      <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <div class="relative z-10">
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Timer</p>
          <h2 class="mt-2 text-2xl font-black tracking-[-0.05em] text-[var(--text)]">{{ assignment?.title || 'Homework' }}</h2>
          <div class="mt-5 rounded-[1.5rem] border border-[var(--border-strong)] bg-[var(--chip-active)] p-5 text-center">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-[var(--muted)]">Còn lại</p>
            <div class="mt-2 text-4xl font-black tracking-[0.08em] text-[var(--text)]">{{ hasTimer ? formatSeconds(timeLeft) : '--:--' }}</div>
          </div>
          <div class="mt-5 grid grid-cols-2 gap-3">
            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
              <p class="text-xs font-bold text-[var(--muted)]">Submission</p>
              <p class="mt-1 text-xl font-black text-[var(--text)]">#{{ submission?.id || '-' }}</p>
            </div>
            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
              <p class="text-xs font-bold text-[var(--muted)]">Attempt</p>
              <p class="mt-1 text-xl font-black text-[var(--text)]">{{ submission?.attempt_no || '-' }}</p>
            </div>
          </div>
        </div>
      </article>

      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Question map</p>
        <div class="mt-4 grid grid-cols-5 gap-2">
          <button v-for="(_, index) in questions" :key="index" type="button" class="grid h-10 place-items-center rounded-2xl border text-xs font-black transition hover:-translate-y-0.5" :class="getQuestionMapClass(index)" @click="goToQuestion(index)">{{ index + 1 }}</button>
        </div>
      </article>
    </aside>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { formatSeconds, homeworkAssignmentsApi, homeworkProgressStorage, normalizeQuestion } from '@/services/api'

const route = useRoute()
const router = useRouter()

const assignment = ref(null)
const submission = ref(null)
const questions = ref([])
const selectedAnswers = ref({})
const currentIndex = ref(0)
const savingQuestionIds = ref({})
const timeLeft = ref(0)
const isLoading = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
let timer = null

const currentQuestion = computed(() => questions.value[currentIndex.value] || { id: 0, question: '', answers: [] })
const isLastQuestion = computed(() => currentIndex.value >= questions.value.length - 1)
const hasTimer = computed(() => Boolean(assignment.value?.duration_minutes))
const answeredCount = computed(() => Object.values(selectedAnswers.value).filter((value) => Array.isArray(value) && value.length > 0).length)
const answeredPercent = computed(() => Math.round((answeredCount.value / Math.max(questions.value.length, 1)) * 100))
const progressRingStyle = computed(() => ({ background: `conic-gradient(var(--accent-2) 0 ${answeredPercent.value}%, var(--surface-soft) ${answeredPercent.value}% 100%)` }))
const isSavingCurrent = computed(() => Boolean(savingQuestionIds.value[currentQuestion.value.id]))

const isMultipleQuestion = (question) => String(question.type || '').includes('multiple')
const selectedForCurrent = () => selectedAnswers.value[currentQuestion.value.id] || []
const isAnswerSelected = (answerId) => selectedForCurrent().includes(answerId)
const getAnswerClass = (answerId) => isAnswerSelected(answerId)
  ? ['border-[var(--border-strong)]', 'bg-[var(--chip-active)]', 'shadow-[0_18px_44px_rgba(155,44,255,0.16)]']
  : ['border-[var(--border)]', 'bg-[var(--surface-soft)]', 'hover:border-[var(--border-strong)]', 'hover:bg-[var(--surface)]']

const getQuestionMapClass = (index) => {
  const questionId = questions.value[index]?.id
  if (index === currentIndex.value) return ['border-[var(--border-strong)]', 'bg-[var(--chip-active)]', 'text-[var(--primary)]']
  return selectedAnswers.value[questionId]?.length
    ? ['border-emerald-500/30', 'bg-emerald-500/10', 'text-emerald-400']
    : ['border-[var(--border)]', 'bg-[var(--surface-soft)]', 'text-[var(--muted)]']
}

const persistProgress = (extra = {}) => {
  if (!submission.value?.id || !assignment.value?.id) return
  homeworkProgressStorage.set(assignment.value.id, {
    submission_id: submission.value.id,
    status: submission.value.status || 'in_progress',
    attempt_no: submission.value.attempt_no,
    started_at: submission.value.started_at,
    answers: selectedAnswers.value,
    ...extra,
  })
}

const toggleAnswer = async (answerId) => {
  if (!currentQuestion.value.id || !submission.value?.id || isSubmitting.value) return

  const question = currentQuestion.value
  const current = selectedAnswers.value[question.id] || []
  const next = isMultipleQuestion(question)
    ? current.includes(answerId) ? current.filter((id) => id !== answerId) : [...current, answerId]
    : [answerId]

  selectedAnswers.value = { ...selectedAnswers.value, [question.id]: next }
  persistProgress()

  if (!next.length) return

  savingQuestionIds.value = { ...savingQuestionIds.value, [question.id]: true }
  errorMessage.value = ''

  try {
    await homeworkAssignmentsApi.answer(submission.value.id, {
      question_id: question.id,
      answer_id: next[0],
      selected_answer_ids: next,
    })
    successMessage.value = 'Đã lưu câu trả lời.'
  } catch (error) {
    errorMessage.value = `Không lưu được câu trả lời: ${error.message}`
  } finally {
    const nextSaving = { ...savingQuestionIds.value }
    delete nextSaving[question.id]
    savingQuestionIds.value = nextSaving
  }
}

const goPrevious = () => {
  if (currentIndex.value > 0) currentIndex.value -= 1
}
const goNext = () => {
  if (!isLastQuestion.value) currentIndex.value += 1
}
const goToQuestion = (index) => {
  currentIndex.value = index
}

const calculateTimeLeft = () => {
  if (!assignment.value?.duration_minutes || !submission.value?.started_at) return 0
  const startedAt = new Date(submission.value.started_at).getTime()
  const endAt = startedAt + Number(assignment.value.duration_minutes) * 60 * 1000
  return Math.max(0, Math.ceil((endAt - Date.now()) / 1000))
}

const startTimer = () => {
  clearInterval(timer)
  if (!hasTimer.value) return

  timeLeft.value = calculateTimeLeft()
  timer = setInterval(async () => {
    timeLeft.value = calculateTimeLeft()
    if (timeLeft.value <= 0) {
      clearInterval(timer)
      await submitAssignment(true)
    }
  }, 1000)
}

const submitAssignment = async (autoSubmit = false) => {
  if (isSubmitting.value || !submission.value?.id) return

  const unanswered = questions.value.length - answeredCount.value
  if (!autoSubmit) {
    const message = unanswered > 0
      ? `Bạn còn ${unanswered} câu chưa trả lời. Vẫn nộp bài?`
      : 'Bạn chắc chắn muốn nộp bài? Sau khi nộp sẽ không sửa được đáp án.'
    if (!window.confirm(message)) return
  }

  isSubmitting.value = true
  errorMessage.value = ''
  clearInterval(timer)

  try {
    const result = await homeworkAssignmentsApi.submit(submission.value.id)
    submission.value = { ...submission.value, ...result, status: result.status || 'submitted' }
    sessionStorage.setItem(`quizflex_assignment_result_${route.params.assignmentId}`, JSON.stringify(result))
    homeworkProgressStorage.set(route.params.assignmentId, {
      submission_id: submission.value.id,
      status: result.status || 'submitted',
      submitted_at: result.submitted_at,
      result,
      answers: selectedAnswers.value,
    })
    router.push(`/rooms/${route.params.roomId}/assignments/${route.params.assignmentId}/result`)
  } catch (error) {
    if (error.status === 403 && autoSubmit) {
      homeworkProgressStorage.set(route.params.assignmentId, {
        submission_id: submission.value.id,
        status: 'late',
        answers: selectedAnswers.value,
      })
    }
    errorMessage.value = autoSubmit ? `Hết giờ nhưng submit không thành công: ${error.message}` : `Không nộp được bài: ${error.message}`
    startTimer()
  } finally {
    isSubmitting.value = false
  }
}

const applyTakingPayload = (payload) => {
  assignment.value = payload.assignment
  submission.value = payload.submission
  questions.value = (payload.assignment?.quiz?.questions || []).map((question) => normalizeQuestion({
    ...question,
    category: payload.assignment?.quiz?.category,
    difficulty: payload.assignment?.quiz?.difficulty,
  }))

  const progress = homeworkProgressStorage.get(route.params.assignmentId)
  selectedAnswers.value = progress?.answers || {}
  persistProgress({ status: submission.value?.status || 'in_progress' })
  startTimer()
}

const loadAssignmentForTaking = async () => {
  isLoading.value = true
  errorMessage.value = ''

  const progress = homeworkProgressStorage.get(route.params.assignmentId)
  if (progress?.status === 'submitted') {
    router.replace(`/rooms/${route.params.roomId}/assignments/${route.params.assignmentId}/result`)
    return
  }

  try {
    const cached = sessionStorage.getItem(`quizflex_assignment_take_${route.params.assignmentId}`)
    if (cached) {
      applyTakingPayload(JSON.parse(cached))
      return
    }

    const payload = await homeworkAssignmentsApi.start(route.params.roomId, route.params.assignmentId)
    sessionStorage.setItem(`quizflex_assignment_take_${route.params.assignmentId}`, JSON.stringify(payload))
    applyTakingPayload(payload)
  } catch (error) {
    if (error.status === 401) {
      errorMessage.value = 'Bạn cần đăng nhập để làm homework.'
      return
    }
    if (error.status === 403) {
      errorMessage.value = error.message || 'Assignment chưa mở, đã quá hạn hoặc bạn đã hết số lần làm.'
      return
    }
    errorMessage.value = `Không tải được assignment: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

onMounted(loadAssignmentForTaking)
onBeforeUnmount(() => clearInterval(timer))
</script>
