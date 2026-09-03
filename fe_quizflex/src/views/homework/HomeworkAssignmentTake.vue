<template>
  <section class="py-4">
    <!-- 1. LOADING STATE -->
    <AppLoadingState 
      v-if="isLoading" 
      title="Đang chuẩn bị bài homework..." 
      message="Vui lòng chờ trong giây lát để hệ thống mở lượt làm bài và danh sách câu hỏi."
      icon="📝"
    />

    <!-- 2. ERROR STATE -->
    <AppErrorState 
      v-else-if="errorMessage" 
      title="Không thể tải bài homework"
      :message="errorMessage" 
      @retry="loadAttempt"
    >
      <template #actions>
        <router-link class="btn-ghost text-xs" :to="`/homework-rooms/${roomId}`">Quay lại phòng</router-link>
      </template>
    </AppErrorState>

    <!-- 3. LOADED STATE -->
    <div v-else class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
      <!-- Main Content -->
      <article class="card p-6 sm:p-8 space-y-6">
        <!-- Results Screen -->
        <template v-if="result">
          <div class="space-y-2 border-b border-slate-100 pb-4">
            <span
              v-if="isResultExpired"
              class="rounded-full bg-red-50 border border-red-200 px-3 py-0.5 text-xs font-bold text-red-700"
            >
              ⏱ Đã hết giờ làm bài
            </span>
            <span v-else class="rounded-full bg-emerald-50 border border-emerald-200 px-3 py-0.5 text-xs font-bold text-emerald-700">
              ✓ Đã hoàn thành
            </span>
            <h1 class="text-2xl font-black text-slate-900 sm:text-3xl pt-1">
              {{ isResultExpired ? 'Đã hết giờ làm bài' : 'Đã nộp bài thành công' }}
            </h1>
            <p class="text-xs text-slate-600">{{ submitMessage }}</p>
          </div>

          <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-purple-200 bg-purple-50/60 p-4 text-center">
              <span class="text-xs font-semibold text-slate-500">Điểm số (Thang 10)</span>
              <b class="mt-1 block text-2xl font-black text-[#7C3AED]">
                {{ formatDisplayScore(result) }} <span class="text-xs font-bold text-slate-500">/ 10</span>
              </b>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-center">
              <span class="text-xs font-semibold text-slate-500">Số câu đúng</span>
              <b class="mt-1 block text-2xl font-black text-emerald-700">
                {{ result.correct_count ?? result.result?.correct_count ?? '-' }}/{{ result.total_questions ?? result.result?.total_questions ?? questions.length }} câu
              </b>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-center">
              <span class="text-xs font-semibold text-slate-500">Độ chính xác</span>
              <b class="mt-1 block text-2xl font-black text-slate-900">{{ Math.round(result.accuracy_percentage ?? result.score_percent ?? 0) }}%</b>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-center">
              <span class="text-xs font-semibold text-slate-500">Trạng thái</span>
              <div class="mt-2"><StatusBadge :value="result.attempt?.status || (isResultExpired ? 'expired' : 'completed')" /></div>
            </div>
          </div>

          <div class="pt-4 border-t border-slate-100">
            <router-link class="btn-primary text-xs px-5 py-2.5" :to="`/homework-rooms/${roomId}`">Quay lại phòng học</router-link>
          </div>
        </template>

        <!-- Taking Questions -->
        <template v-else-if="currentQuestion.id">
          <!-- Question Header & Progress -->
          <div class="space-y-4 border-b border-slate-100 pb-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div class="flex items-center gap-2">
                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                  Câu {{ currentIndex + 1 }} / {{ questions.length || 1 }}
                </span>
                <span
                  v-if="currentQuestion.type === 'multi_choice'"
                  class="rounded-full border border-purple-200 bg-purple-100/70 px-3 py-1 text-xs font-bold text-purple-800 uppercase tracking-wide"
                >
                  Nhiều đáp án đúng
                </span>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 truncate max-w-xs">
                  {{ assignment?.title || quizMeta.title || 'Homework' }}
                </span>
              </div>
              <span class="text-xs font-bold text-slate-500">
                Tiến độ: <b class="text-[#7C3AED]">{{ progressPercent }}%</b>
              </span>
            </div>

            <!-- Progress Bar -->
            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100 border border-slate-200">
              <div
                class="h-full rounded-full bg-[#7C3AED] transition-all duration-300"
                :style="{ width: `${progressPercent}%` }"
              ></div>
            </div>

            <h1 class="text-xl font-bold leading-relaxed text-slate-900 sm:text-2xl pt-2">
              <MathText :content="currentQuestion.question || currentQuestion.content || ''" />
            </h1>

            <!-- Optional Question Image -->
            <div v-if="currentQuestion.image_url" class="py-2.5 flex justify-center">
              <QuestionImage
                :src="currentQuestion.image_url"
                size="normal"
                allow-zoom
              />
            </div>
          </div>

          <!-- Answers List -->
          <div class="grid gap-3 pt-2">
            <button
              v-for="answer in currentQuestion.answers"
              :key="answer.id"
              type="button"
              class="flex items-center gap-3.5 rounded-xl border p-4 text-left transition duration-150 active:scale-[0.99] cursor-pointer"
              :class="isAnswerSelected(answer)
                ? 'border-[#7C3AED] bg-purple-50 text-slate-900 shadow-sm'
                : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50'"
              @click="toggleAnswer(answer)"
            >
              <span
                class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-xs font-black transition"
                :class="isAnswerSelected(answer) ? 'bg-[#7C3AED] text-white' : 'bg-slate-100 text-slate-700'"
              >
                {{ answer.key }}
              </span>
              <span class="font-medium text-sm leading-relaxed flex-1">
                <MathText :content="answer.text || answer.content || ''" compact />
              </span>
              <span
                v-if="isAnswerSelected(answer)"
                class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-[#7C3AED] text-xs font-bold text-white"
              >
                ✓
              </span>
            </button>
          </div>

          <!-- Controls -->
          <div class="flex flex-wrap items-center justify-between gap-3 pt-6 border-t border-slate-100">
            <button
              class="btn-secondary text-xs disabled:opacity-40"
              type="button"
              :disabled="currentIndex === 0"
              @click="goPrevious"
            >
              ← Câu trước
            </button>

            <div class="flex items-center gap-2.5">
              <button
                v-if="!isLastQuestion"
                class="btn-secondary text-xs px-4"
                type="button"
                :disabled="isSubmitting"
                @click="goNext"
              >
                Câu tiếp theo →
              </button>
              <button
                class="btn-primary text-xs px-5"
                type="button"
                :disabled="isSubmitting"
                @click="submitAttempt(false)"
              >
                {{ isSubmitting ? 'Đang nộp...' : 'Nộp bài tập' }}
              </button>
            </div>
          </div>
        </template>
      </article>

      <!-- Sidebar: Timer & Question Map -->
      <aside class="grid content-start gap-5">
        <!-- Timer Card -->
        <article class="card p-5 space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Thời gian làm bài</span>
            <div class="flex items-center gap-2">
              <span class="text-lg">⏱️</span>
            </div>
          </div>

          <div
            class="rounded-xl border p-4 text-center transition"
            :class="hasTimeLimit && timeLeft <= 60 ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-slate-200 bg-slate-50 text-slate-900'"
          >
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Thời gian còn lại</p>
            <div class="mt-1 text-3xl font-black tracking-wider">
              {{ hasTimeLimit ? formatSeconds(timeLeft) : 'Không giới hạn' }}
            </div>
          </div>
        </article>

        <!-- Question Map Card -->
        <article class="card p-5 space-y-3">
          <div class="flex items-center justify-between border-b border-slate-100 pb-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Danh sách câu hỏi</h3>
            <span class="text-xs font-semibold text-slate-500">{{ answeredQuestionsCount }}/{{ questions.length }} đã chọn</span>
          </div>

          <div class="grid grid-cols-5 gap-1.5 pt-1">
            <button
              v-for="(_, index) in questions"
              :key="index"
              type="button"
              class="grid h-9 place-items-center rounded-lg border text-xs font-bold transition active:scale-95"
              :class="getQuestionMapClass(index)"
              @click="goToQuestion(index)"
            >
              {{ index + 1 }}
            </button>
          </div>
        </article>
      </aside>
    </div>
  </section>
</template>

<script setup>
import { useAppLoading } from '@/composables/useAppLoading'
const { beginTask, endTask } = useAppLoading()

import { computed, onBeforeUnmount, onMounted, onUnmounted, ref } from 'vue'
import { onBeforeRouteLeave, useRoute } from 'vue-router'
import AppLoadingState from '@/components/common/AppLoadingState.vue'
import AppErrorState from '@/components/common/AppErrorState.vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import QuestionImage from '@/components/question/QuestionImage.vue'
import MathText from '@/components/MathText.vue'
import { formatSeconds, homeworkApi, normalizeQuestion } from '@/services/api'

const route = useRoute()
const roomId = computed(() => route.params.roomId)
const assignmentId = computed(() => route.params.assignmentId)

const assignment = ref(null)
const attemptId = ref(null)
const questions = ref([])
const quizMeta = ref({ title: '' })
const selectedAnswers = ref({})
const currentIndex = ref(0)
const isLoading = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const submitMessage = ref('')
const result = ref(null)

const timeLeft = ref(0)
const hasTimeLimit = ref(true)
let timer = null

const isResultExpired = computed(() => {
  const status = result.value?.attempt?.status || result.value?.data?.attempt?.status || result.value?.status
  return status === 'expired'
})

const currentQuestion = computed(() => questions.value[currentIndex.value] || { id: 0, question: '', answers: [] })
const isLastQuestion = computed(() => currentIndex.value === questions.value.length - 1)
const progressPercent = computed(() => Math.round(((currentIndex.value + 1) / Math.max(questions.value.length, 1)) * 100))

const answeredQuestionsCount = computed(() => {
  return Object.values(selectedAnswers.value).filter((v) =>
    Array.isArray(v) ? v.length > 0 : Boolean(v),
  ).length
})

const isAnswerSelected = (answer) => {
  const current = selectedAnswers.value[currentQuestion.value.id]
  if (Array.isArray(current)) return current.includes(answer.id)
  return current === answer.id
}

const formatDisplayScore = (res) => {
  if (!res) return '0'
  let raw = 0
  if (res.scaled_score_10 !== undefined && res.scaled_score_10 !== null) {
    raw = Number(res.scaled_score_10)
  } else {
    const tot = Number(res.total_points) || 0
    const sc = Number(res.score) || 0
    raw = tot > 0 ? (sc / tot) * 10 : 0
  }
  return Number.isInteger(raw) ? raw.toString() : parseFloat(raw.toFixed(2)).toString()
}

const toggleAnswer = (answer) => {
  if (!currentQuestion.value.id) return
  const qId = currentQuestion.value.id
  const isMulti = currentQuestion.value.type === 'multi_choice'
  if (isMulti) {
    const current = Array.isArray(selectedAnswers.value[qId])
      ? [...selectedAnswers.value[qId]]
      : selectedAnswers.value[qId]
        ? [selectedAnswers.value[qId]]
        : []
    const index = current.indexOf(answer.id)
    if (index > -1) {
      current.splice(index, 1)
    } else {
      current.push(answer.id)
    }
    selectedAnswers.value = {
      ...selectedAnswers.value,
      [qId]: current,
    }
  } else {
    selectedAnswers.value = {
      ...selectedAnswers.value,
      [qId]: answer.id,
    }
  }
}

const getQuestionMapClass = (i) => {
  const questionId = questions.value[i]?.id
  if (i === currentIndex.value) {
    return ['border-[#7C3AED]', 'bg-[#7C3AED]', 'text-white', 'shadow-sm']
  }
  const ans = selectedAnswers.value[questionId]
  const hasAnswered = Array.isArray(ans) ? ans.length > 0 : Boolean(ans)
  if (hasAnswered) {
    return ['border-emerald-200', 'bg-emerald-50', 'text-emerald-700']
  }
  return ['border-slate-200', 'bg-slate-50', 'text-slate-600', 'hover:bg-slate-100']
}

const goPrevious = () => {
  if (currentIndex.value > 0) currentIndex.value -= 1
}

const goNext = () => {
  if (!isLastQuestion.value) currentIndex.value += 1
}

const goToQuestion = (i) => {
  currentIndex.value = i
}

const buildAnswerPayload = () => {
  const payload = {}
  Object.entries(selectedAnswers.value).forEach(([questionId, answerVal]) => {
    payload[questionId] = answerVal
  })
  return payload
}

const startTimer = () => {
  if (!hasTimeLimit.value) return
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

const loadAttempt = async () => {
  beginTask()
  try {
    isLoading.value = true
    errorMessage.value = ''

    try {
      const data = await homeworkApi.startAssignmentAttempt(roomId.value, assignmentId.value)
      assignment.value = data.assignment
      attemptId.value = data.attempt?.id
      quizMeta.value = {
        title: data.quiz?.title || data.assignment?.quiz?.title || data.assignment?.title || 'Homework',
      }
      const rawQuestions = data.quiz?.questions || data.assignment?.quiz?.questions || []
      questions.value = rawQuestions.map(normalizeQuestion)

      // Calculate time limit
      let totalLimit = 0
      if (data.assignment?.duration_minutes) {
        totalLimit = Number(data.assignment.duration_minutes) * 60
      } else if (data.quiz?.time_limit_seconds) {
        totalLimit = Number(data.quiz.time_limit_seconds)
      }

      if (totalLimit > 0) {
        hasTimeLimit.value = true
        if (data.attempt?.started_at) {
          const startedAtMs = new Date(data.attempt.started_at).getTime()
          const nowMs = Date.now()
          const elapsedSeconds = Math.max(0, Math.floor((nowMs - startedAtMs) / 1000))
          timeLeft.value = Math.max(0, totalLimit - elapsedSeconds)
        } else {
          timeLeft.value = totalLimit
        }
      } else if (data.assignment?.deadline_at) {
        hasTimeLimit.value = true
        const deadlineMs = new Date(data.assignment.deadline_at).getTime()
        const nowMs = Date.now()
        timeLeft.value = Math.max(0, Math.floor((deadlineMs - nowMs) / 1000))
      } else {
        hasTimeLimit.value = false
        timeLeft.value = 0
      }

      // Check against deadline_at if exists
      if (hasTimeLimit.value && data.assignment?.deadline_at) {
        const deadlineMs = new Date(data.assignment.deadline_at).getTime()
        const nowMs = Date.now()
        const secondsToDeadline = Math.max(0, Math.floor((deadlineMs - nowMs) / 1000))
        if (secondsToDeadline < timeLeft.value) {
          timeLeft.value = secondsToDeadline
        }
      }

      if (hasTimeLimit.value && timeLeft.value <= 0) {
        timeLeft.value = 0
        await submitAttempt(true)
        return
      }

      if (hasTimeLimit.value) {
        startTimer()
      }
    } catch (error) {
      errorMessage.value = `Không tải được bài tập: ${error.message}`
    } finally {
      isLoading.value = false
    }
  } finally {
    endTask()
  }
}

const submitAttempt = async (autoSubmit = false) => {
  if (isSubmitting.value) return
  if (!autoSubmit && !questions.value.length) return
  isSubmitting.value = true
  errorMessage.value = ''
  clearInterval(timer)

  try {
    const res = await homeworkApi.submitAssignmentAttempt(roomId.value, assignmentId.value, {
      attempt_id: attemptId.value,
      answers: buildAnswerPayload(),
    })
    result.value = res.data || res
    const isExpired = result.value?.attempt?.status === 'expired' || result.value?.status === 'expired' || autoSubmit
    submitMessage.value = res.message || (isExpired ? 'Đã hết giờ làm bài.' : 'Nộp bài tập thành công.')
  } catch (error) {
    if (autoSubmit) {
      result.value = {
        attempt: { status: 'expired' },
        score: 0,
        total_points: questions.value.length,
        correct_count: 0,
        total_questions: questions.value.length,
        accuracy_percentage: 0,
        score_percent: 0,
      }
      submitMessage.value = 'Đã hết giờ làm bài.'
    } else {
      errorMessage.value = `Không nộp được bài: ${error.message}`
      if (hasTimeLimit.value && timeLeft.value > 0) {
        startTimer()
      }
    }
  } finally {
    isSubmitting.value = false
  }
}

onMounted(async () => {
  await loadAttempt()
})

onBeforeRouteLeave(() => {
  clearInterval(timer)
})

onBeforeUnmount(() => {
  clearInterval(timer)
})

onUnmounted(() => {
  clearInterval(timer)
})
</script>
