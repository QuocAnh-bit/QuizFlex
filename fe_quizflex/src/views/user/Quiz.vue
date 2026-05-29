<template>
  <section class="grid gap-6 py-8 xl:grid-cols-[minmax(0,1fr)_360px]">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-soft)] backdrop-blur-2xl sm:p-8">
      <div class="pointer-events-none absolute -left-24 -top-24 h-80 w-80 rounded-full bg-[var(--primary)]/20 blur-3xl"></div>
      <div class="pointer-events-none absolute -bottom-24 right-0 h-80 w-80 rounded-full bg-[var(--accent)]/10 blur-3xl"></div>

      <div class="relative z-10">
        <div v-if="isLoading" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-sm font-bold text-[var(--muted)]">Đang chuẩn bị bài thi...</div>
        <div v-if="errorMessage" class="mb-6 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

        <template v-if="currentQuestion.id">
          <div class="mb-7 flex flex-wrap items-center justify-between gap-4">
            <div>
              <div class="mb-4 flex flex-wrap items-center gap-2">
                <span class="rounded-full border border-[var(--border-strong)] bg-[var(--chip-active)] px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-[var(--primary)]">Câu {{ currentIndex + 1 }} / {{ quizQuestions.length || 1 }}</span>
                <span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-2 text-xs font-black text-[var(--muted)]">{{ quizMeta.category || 'Quiz' }}</span>
                <span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-2 text-xs font-black text-[var(--muted)]">{{ quizMeta.difficulty || 'Vừa' }}</span>
              </div>
              <h1 class="max-w-4xl text-3xl font-black leading-tight tracking-[-0.055em] text-[var(--text)] sm:text-5xl">{{ currentQuestion.question }}</h1>
              <p class="mt-4 max-w-2xl text-sm font-medium leading-7 text-[var(--muted)]">Chọn đáp án rồi chuyển câu. Khi hết thời gian, hệ thống tự nộp bài.</p>
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
              :key="answer.key"
              type="button"
              class="group relative overflow-hidden rounded-[1.35rem] border p-4 text-left transition duration-300 hover:-translate-y-1 active:scale-[0.99]"
              :class="getAnswerClass(answer.key)"
              @click="selectedAnswer = answer.key"
            >
              <div v-if="selectedAnswer === answer.key" class="absolute inset-0 bg-gradient-to-r from-[var(--primary)]/10 via-[var(--primary-2)]/10 to-[var(--accent)]/10"></div>
              <div class="relative z-10 flex items-center gap-4">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-[var(--primary)] to-[var(--primary-2)] text-sm font-black text-white shadow-[0_14px_30px_rgba(155,44,255,0.24)]">{{ answer.key }}</span>
                <span class="font-bold leading-7 text-[var(--text)]">{{ answer.text }}</span>
                <span v-if="selectedAnswer === answer.key" class="ml-auto grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[var(--chip-active)] text-sm font-black text-[var(--primary)]">✓</span>
              </div>
            </button>
          </div>

          <div class="mt-8 flex flex-wrap items-center justify-between gap-3">
            <button class="btn-ghost disabled:cursor-not-allowed disabled:opacity-50" type="button" :disabled="currentIndex === 0" @click="goPrevious">Câu trước</button>
            <div class="flex flex-wrap items-center gap-3">
              <span class="text-sm font-bold text-[var(--muted)]">Đã chọn: <b class="text-[var(--primary)]">{{ selectedAnswer || 'Chưa chọn' }}</b></span>
              <button class="btn-primary" type="button" :disabled="isSubmitting" @click="goNext">{{ isSubmitting ? 'Đang nộp...' : isLastQuestion ? 'Nộp bài' : 'Câu tiếp theo' }}</button>
            </div>
          </div>
        </template>
      </div>
    </article>

    <aside class="grid content-start gap-5">
      <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <div class="relative z-10">
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Timer</p>
          <h2 class="mt-2 text-2xl font-black tracking-[-0.05em] text-[var(--text)]">Đếm ngược</h2>
          <div class="mt-5 rounded-[1.5rem] border border-[var(--border-strong)] bg-[var(--chip-active)] p-5 text-center">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-[var(--muted)]">Còn lại</p>
            <div class="mt-2 text-4xl font-black tracking-[0.08em] text-[var(--text)]">{{ formatSeconds(timeLeft) }}</div>
          </div>
          <div class="mt-5 grid grid-cols-2 gap-3">
            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4"><p class="text-xs font-bold text-[var(--muted)]">Bài thi</p><p class="mt-1 text-xl font-black text-[var(--text)]">{{ quizMeta.title || 'Quiz' }}</p></div>
            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4"><p class="text-xs font-bold text-[var(--muted)]">Attempt</p><p class="mt-1 text-xl font-black text-[var(--text)]">#{{ attemptId || '-' }}</p></div>
          </div>
        </div>
      </article>

      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Question map</p>
        <div class="mt-4 grid grid-cols-5 gap-2">
          <button v-for="(_, index) in quizQuestions" :key="index" type="button" class="grid h-10 place-items-center rounded-2xl border text-xs font-black transition hover:-translate-y-0.5" :class="getQuestionMapClass(index)" @click="goToQuestion(index)">{{ index + 1 }}</button>
        </div>
      </article>
    </aside>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { formatSeconds, normalizeQuestion, quizzesApi } from '@/services/api'

const route = useRoute()
const router = useRouter()

const currentIndex = ref(0)
const selectedAnswers = ref({})
const quizQuestions = ref([])
const quizMeta = ref({ title: '', category: '', difficulty: '', timeLimitSeconds: 600 })
const attemptId = ref(null)
const timeLeft = ref(0)
const isLoading = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
let timer = null

const attemptStorageKey = computed(() => `quizflex_practice_attempt_${route.params.id}`)

const currentQuestion = computed(() => quizQuestions.value[currentIndex.value] || { id: 0, question: '', answers: [] })
const selectedAnswer = computed({
  get: () => selectedAnswers.value[currentQuestion.value.id] || '',
  set: (value) => {
    if (!currentQuestion.value.id) return
    selectedAnswers.value = { ...selectedAnswers.value, [currentQuestion.value.id]: value }
  },
})
const progressPercent = computed(() => Math.round(((currentIndex.value + 1) / Math.max(quizQuestions.value.length, 1)) * 100))
const progressRingStyle = computed(() => ({ background: `conic-gradient(var(--accent-2) 0 ${progressPercent.value}%, var(--surface-soft) ${progressPercent.value}% 100%)` }))
const isLastQuestion = computed(() => currentIndex.value === quizQuestions.value.length - 1)

const getAnswerClass = (key) => selectedAnswer.value === key ? ['border-[var(--border-strong)]', 'bg-[var(--chip-active)]', 'shadow-[0_18px_44px_rgba(155,44,255,0.16)]'] : ['border-[var(--border)]', 'bg-[var(--surface-soft)]', 'hover:border-[var(--border-strong)]', 'hover:bg-[var(--surface)]']
const getQuestionMapClass = (i) => {
  const questionId = quizQuestions.value[i]?.id
  return i === currentIndex.value ? ['border-[var(--border-strong)]', 'bg-[var(--chip-active)]', 'text-[var(--primary)]'] : selectedAnswers.value[questionId] ? ['border-emerald-500/30', 'bg-emerald-500/10', 'text-emerald-400'] : ['border-[var(--border)]', 'bg-[var(--surface-soft)]', 'text-[var(--muted)]']
}

const goPrevious = () => { if (currentIndex.value > 0) currentIndex.value -= 1 }
const goToQuestion = (i) => { currentIndex.value = i }
const goNext = async () => {
  if (!isLastQuestion.value) {
    currentIndex.value += 1
    return
  }

  await submitAttempt()
}

const startTimer = () => {
  clearInterval(timer)
  timer = setInterval(async () => {
    timeLeft.value -= 1
    if (timeLeft.value <= 0) {
      timeLeft.value = 0
      clearInterval(timer)
      await submitAttempt(true)
    }
  }, 1000)
}

const submitAttempt = async (autoSubmit = false) => {
  if (isSubmitting.value || !quizQuestions.value.length) return

  isSubmitting.value = true
  errorMessage.value = ''
  clearInterval(timer)

  try {
    const result = await quizzesApi.submitAttempt(route.params.id, {
      attempt_id: attemptId.value,
      answers: selectedAnswers.value,
    })

    const id = result.attempt?.id
    if (id) {
      sessionStorage.removeItem(attemptStorageKey.value)
      router.push(`/results/${id}`)
      return
    }

    errorMessage.value = autoSubmit ? 'Đã hết giờ nhưng không nhận được mã kết quả.' : 'Nộp bài xong nhưng không nhận được mã kết quả.'
  } catch (error) {
    errorMessage.value = `Không nộp được bài: ${error.message}`
    startTimer()
  } finally {
    isSubmitting.value = false
  }
}

const loadQuiz = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const savedAttemptId = Number(sessionStorage.getItem(attemptStorageKey.value) || 0) || undefined
    const data = await quizzesApi.startAttempt(route.params.id, savedAttemptId ? { attempt_id: savedAttemptId } : {})
    const quiz = data.quiz
    attemptId.value = data.attempt?.id
    if (attemptId.value) {
      sessionStorage.setItem(attemptStorageKey.value, String(attemptId.value))
    }
    quizMeta.value = {
      title: quiz.title,
      category: quiz.category,
      difficulty: quiz.difficulty,
      timeLimitSeconds: quiz.time_limit_seconds || 600,
    }
    quizQuestions.value = (quiz.questions || []).map((question) => normalizeQuestion({ ...question, difficulty: quiz.difficulty, category: quiz.category }))
    timeLeft.value = quiz.time_limit_seconds || 600
    startTimer()
  } catch (error) {
    errorMessage.value = `Không tải được bài thi: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

onMounted(loadQuiz)
onBeforeUnmount(() => clearInterval(timer))
</script>
