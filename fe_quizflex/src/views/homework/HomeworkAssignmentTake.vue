<template>
  <section class="grid gap-6 py-8 xl:grid-cols-[minmax(0,1fr)_360px]">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-soft)] backdrop-blur-2xl sm:p-8">
      <div class="pointer-events-none absolute -left-24 -top-24 h-80 w-80 rounded-full bg-[var(--primary)]/20 blur-3xl"></div>
      <div class="pointer-events-none absolute -bottom-24 right-0 h-80 w-80 rounded-full bg-[var(--accent)]/10 blur-3xl"></div>

      <div class="relative z-10">
        <div v-if="isLoading" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-sm font-bold text-[var(--muted)]">Đang chuẩn bị bài homework...</div>
        <div v-if="errorMessage" class="mb-6 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

        <template v-if="result">
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Submitted</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Đã nộp bài</h1>
          <p class="mt-3 text-sm leading-7 text-[var(--muted)]">{{ submitMessage }}</p>

          <div class="mt-8 grid gap-4 sm:grid-cols-4">
            <div class="rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-5">
              <p class="text-sm font-bold text-[var(--muted)]">Điểm</p>
              <b class="mt-2 block text-3xl font-black text-[var(--text)]">{{ result.score ?? 0 }}/{{ result.total_points ?? 0 }}</b>
            </div>
            <div class="rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-5">
              <p class="text-sm font-bold text-[var(--muted)]">Tỷ lệ</p>
              <b class="mt-2 block text-3xl font-black text-[var(--text)]">{{ Math.round(result.score_percent ?? 0) }}%</b>
            </div>
            <div class="rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-5">
              <p class="text-sm font-bold text-[var(--muted)]">Số câu đúng</p>
              <b class="mt-2 block text-3xl font-black text-[var(--text)]">{{ result.correct_count ?? '-' }}/{{ result.total_questions ?? '-' }}</b>
            </div>
            <div class="rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-5">
              <p class="text-sm font-bold text-[var(--muted)]">Trạng thái</p>
              <div class="mt-3"><StatusBadge :value="result.attempt?.status || 'completed'" /></div>
            </div>
          </div>

          <router-link class="btn-primary mt-8 inline-flex" :to="`/homework-rooms/${roomId}`">Quay lại room</router-link>
        </template>

        <template v-else-if="currentQuestion.id">
          <div class="mb-7 flex flex-wrap items-center justify-between gap-4">
            <div>
              <div class="mb-4 flex flex-wrap items-center gap-2">
                <span class="rounded-full border border-[var(--border-strong)] bg-[var(--chip-active)] px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-[var(--primary)]">Câu {{ currentIndex + 1 }} / {{ questions.length || 1 }}</span>
                <span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-2 text-xs font-black text-[var(--muted)]">{{ assignment?.title || quizMeta.title || 'Homework' }}</span>
              </div>
              <h1 class="max-w-4xl text-3xl font-black leading-tight tracking-[-0.055em] text-[var(--text)] sm:text-5xl">{{ currentQuestion.question }}</h1>
              <p class="mt-4 max-w-2xl text-sm font-medium leading-7 text-[var(--muted)]">Chọn đáp án, chuyển câu và nộp bài khi hoàn tất.</p>
            </div>

            <div class="grid gap-3 text-center">
              <div class="relative grid h-24 w-24 place-items-center rounded-full border border-[var(--border)] bg-[var(--surface-soft)] shadow-[var(--shadow-card)]">
                <div class="absolute inset-1 rounded-full" :style="progressRingStyle"></div>
                <div class="relative z-10 grid h-16 w-16 place-items-center rounded-full bg-[var(--surface-strong)]">
                  <span class="text-lg font-black text-[var(--text)]">{{ progressPercent }}%</span>
                </div>
              </div>
              <div class="text-xs font-black text-[var(--muted)]">Tiến độ</div>
            </div>
          </div>

          <div class="mb-8 overflow-hidden rounded-full border border-[var(--border)] bg-[var(--surface-soft)] p-1">
            <div class="h-2 rounded-full bg-gradient-to-r from-[var(--primary)] via-[var(--primary-2)] to-[var(--accent)] transition-all duration-500" :style="{ width: `${progressPercent}%` }"></div>
          </div>

          <div class="grid gap-4">
            <button
              v-for="answer in currentQuestion.answers"
              :key="answer.id"
              type="button"
              class="group relative overflow-hidden rounded-[1.35rem] border p-4 text-left transition duration-300 hover:-translate-y-1 active:scale-[0.99]"
              :class="getAnswerClass(answer)"
              @click="toggleAnswer(answer)"
            >
              <div v-if="isAnswerSelected(answer)" class="absolute inset-0 bg-gradient-to-r from-[var(--primary)]/10 via-[var(--primary-2)]/10 to-[var(--accent)]/10"></div>
              <div class="relative z-10 flex items-center gap-4">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-[var(--primary)] to-[var(--primary-2)] text-sm font-black text-white shadow-[0_14px_30px_rgba(155,44,255,0.24)]">{{ answer.key }}</span>
                <span class="font-bold leading-7 text-[var(--text)]">{{ answer.text }}</span>
                <span v-if="isAnswerSelected(answer)" class="ml-auto grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[var(--chip-active)] text-sm font-black text-[var(--primary)]">✓</span>
              </div>
            </button>
          </div>

          <div class="mt-8 flex flex-wrap items-center justify-between gap-3">
            <button class="btn-ghost disabled:cursor-not-allowed disabled:opacity-50" type="button" :disabled="currentIndex === 0" @click="goPrevious">Câu trước</button>
            <div class="flex flex-wrap items-center gap-3">
              <span class="text-sm font-bold text-[var(--muted)]">Đã chọn: <b class="text-[var(--primary)]">{{ selectedLabel || 'Chưa chọn' }}</b></span>
              <button class="btn-ghost" type="button" :disabled="isSubmitting" @click="goNext">{{ isLastQuestion ? 'Câu cuối' : 'Câu tiếp theo' }}</button>
              <button class="btn-primary" type="button" :disabled="isSubmitting" @click="submitAttempt">{{ isSubmitting ? 'Đang nộp...' : 'Nộp bài' }}</button>
            </div>
          </div>
        </template>
      </div>
    </article>

    <aside class="grid content-start gap-5">
      <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <div class="relative z-10">
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Homework</p>
          <h2 class="mt-2 text-2xl font-black tracking-[-0.05em] text-[var(--text)]">{{ assignment?.title || quizMeta.title || 'Bài được giao' }}</h2>
          <div class="mt-5 grid gap-3">
            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
              <p class="text-xs font-bold text-[var(--muted)]">Attempt</p>
              <p class="mt-1 text-xl font-black text-[var(--text)]">#{{ attemptId || '-' }}</p>
            </div>
            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
              <p class="text-xs font-bold text-[var(--muted)]">Còn lại</p>
              <p class="mt-1 text-xl font-black text-[var(--text)]">{{ timeLeft === null ? 'Không giới hạn' : formatSeconds(timeLeft) }}</p>
            </div>
            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
              <p class="text-xs font-bold text-[var(--muted)]">Số lần làm</p>
              <p class="mt-1 text-xl font-black text-[var(--text)]">{{ attempt?.attempt_number || '-' }}/{{ assignment?.max_attempts || '-' }}</p>
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

      <router-link class="btn-ghost text-center" :to="`/homework-rooms/${roomId}`">Quay lại room</router-link>
    </aside>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { formatSeconds, homeworkApi, normalizeQuestion } from '@/services/api'

const route = useRoute()
const roomId = computed(() => route.params.roomId)
const assignmentId = computed(() => route.params.assignmentId)

const currentIndex = ref(0)
const selectedAnswers = ref({})
const questions = ref([])
const quizMeta = ref({ title: '', timeLimitSeconds: null })
const assignment = ref(null)
const attempt = ref(null)
const attemptId = ref(null)
const timeLeft = ref(null)
const isLoading = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const result = ref(null)
const submitMessage = ref('')
let timer = null

const attemptStorageKey = computed(() => `quizflex_homework_attempt_${assignmentId.value}`)
const currentQuestion = computed(() => questions.value[currentIndex.value] || { id: 0, question: '', answers: [], type: 'single_choice' })
const isLastQuestion = computed(() => currentIndex.value === questions.value.length - 1)
const answeredCount = computed(() => questions.value.filter((question) => {
  const selected = selectedAnswers.value[question.id]
  return Array.isArray(selected) ? selected.length > 0 : Boolean(selected)
}).length)
const progressPercent = computed(() => Math.round((answeredCount.value / Math.max(questions.value.length, 1)) * 100))
const progressRingStyle = computed(() => ({ background: `conic-gradient(var(--accent-2) 0 ${progressPercent.value}%, var(--surface-soft) ${progressPercent.value}% 100%)` }))
const selectedLabel = computed(() => {
  const selected = selectedAnswers.value[currentQuestion.value.id]
  if (Array.isArray(selected)) {
    return currentQuestion.value.answers.filter((answer) => selected.includes(answer.id)).map((answer) => answer.key).join(', ')
  }
  return currentQuestion.value.answers.find((answer) => answer.id === selected)?.key || ''
})

const getSelectedValue = (question = currentQuestion.value) => selectedAnswers.value[question.id]
const isMultiChoice = (question = currentQuestion.value) => question.type === 'multiple_choice'

const isAnswerSelected = (answer) => {
  const selected = getSelectedValue()
  return Array.isArray(selected) ? selected.includes(answer.id) : selected === answer.id
}

const getAnswerClass = (answer) => isAnswerSelected(answer)
  ? ['border-[var(--border-strong)]', 'bg-[var(--chip-active)]', 'shadow-[0_18px_44px_rgba(155,44,255,0.16)]']
  : ['border-[var(--border)]', 'bg-[var(--surface-soft)]', 'hover:border-[var(--border-strong)]', 'hover:bg-[var(--surface)]']

const getQuestionMapClass = (index) => {
  const questionId = questions.value[index]?.id
  const selected = selectedAnswers.value[questionId]
  const answered = Array.isArray(selected) ? selected.length > 0 : Boolean(selected)
  return index === currentIndex.value
    ? ['border-[var(--border-strong)]', 'bg-[var(--chip-active)]', 'text-[var(--primary)]']
    : answered
      ? ['border-emerald-500/30', 'bg-emerald-500/10', 'text-emerald-400']
      : ['border-[var(--border)]', 'bg-[var(--surface-soft)]', 'text-[var(--muted)]']
}

const toggleAnswer = (answer) => {
  const question = currentQuestion.value
  if (!question.id) return

  if (isMultiChoice(question)) {
    const selected = Array.isArray(selectedAnswers.value[question.id]) ? selectedAnswers.value[question.id] : []
    selectedAnswers.value = {
      ...selectedAnswers.value,
      [question.id]: selected.includes(answer.id) ? selected.filter((id) => id !== answer.id) : [...selected, answer.id],
    }
    return
  }

  selectedAnswers.value = { ...selectedAnswers.value, [question.id]: answer.id }
}

const goPrevious = () => { if (currentIndex.value > 0) currentIndex.value -= 1 }
const goNext = () => { if (!isLastQuestion.value) currentIndex.value += 1 }
const goToQuestion = (index) => { currentIndex.value = index }

const startTimer = () => {
  clearInterval(timer)
  if (timeLeft.value === null) return

  timer = setInterval(async () => {
    timeLeft.value -= 1
    if (timeLeft.value <= 0) {
      timeLeft.value = 0
      clearInterval(timer)
      await submitAttempt(true)
    }
  }, 1000)
}

const resolveTimeLeft = (payload) => {
  const durationSeconds = payload.assignment?.duration_minutes ? Number(payload.assignment.duration_minutes) * 60 : null
  if (!durationSeconds || !payload.attempt?.started_at) return null

  const startedAt = new Date(payload.attempt.started_at).getTime()
  const elapsed = Math.floor((Date.now() - startedAt) / 1000)
  return Math.max(durationSeconds - elapsed, 0)
}

const resolveHomeworkError = (error) => {
  const message = error?.message || ''
  if (message.includes('Chủ room không thể làm bài') || message.includes('Chu room khong the lam bai')) {
    return 'Chủ room không thể làm bài trong room của mình.'
  }
  return message
}

const submitAttempt = async (autoSubmit = false) => {
  if (isSubmitting.value || result.value || !attemptId.value) return

  const unanswered = questions.value.length - answeredCount.value
  const message = autoSubmit
    ? ''
    : unanswered > 0
      ? `Bạn vẫn còn ${unanswered} câu chưa trả lời. Bạn có chắc muốn nộp bài không?`
      : 'Bạn có chắc muốn nộp bài không?'

  if (message && !window.confirm(message)) return

  isSubmitting.value = true
  errorMessage.value = ''
  clearInterval(timer)

  try {
    const payload = { answers: selectedAnswers.value }
    const data = await homeworkApi.submitRoomAssignmentAttempt(assignmentId.value, attemptId.value, payload)
    result.value = data
    submitMessage.value = data.message || 'Bài homework đã được nộp.'
    sessionStorage.removeItem(attemptStorageKey.value)
  } catch (error) {
    errorMessage.value = `Không nộp được bài: ${resolveHomeworkError(error)}`
    startTimer()
  } finally {
    isSubmitting.value = false
  }
}

const loadAttempt = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const savedAttemptId = Number(sessionStorage.getItem(attemptStorageKey.value) || 0) || undefined
    const data = await homeworkApi.startRoomAssignmentAttempt(assignmentId.value, savedAttemptId ? { attempt_id: savedAttemptId } : {})

    attempt.value = data.attempt
    assignment.value = data.assignment
    attemptId.value = data.attempt?.id
    if (attemptId.value) sessionStorage.setItem(attemptStorageKey.value, String(attemptId.value))

    const quiz = data.quiz || {}
    quizMeta.value = {
      title: quiz.title,
      timeLimitSeconds: quiz.time_limit_seconds || null,
    }
    questions.value = (quiz.questions || []).map((question) => normalizeQuestion(question))
    timeLeft.value = resolveTimeLeft(data)
    startTimer()
  } catch (error) {
    errorMessage.value = `Không tải được bài homework: ${resolveHomeworkError(error)}`
  } finally {
    isLoading.value = false
  }
}

onMounted(loadAttempt)
onBeforeUnmount(() => clearInterval(timer))
</script>
