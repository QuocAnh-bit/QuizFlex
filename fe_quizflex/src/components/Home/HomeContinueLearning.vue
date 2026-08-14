<template>
  <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-[0_4px_16px_rgba(15,23,42,0.06)] space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
      <div class="flex items-center gap-2">
        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#F5F3FF] text-[#7C3AED] text-xs font-bold">
          📖
        </span>
        <h3 class="text-xs font-bold uppercase tracking-wider text-[#64748B]">Tiếp tục học</h3>
      </div>
      <span class="rounded-full bg-[#F5F3FF] px-2.5 py-0.5 text-xs font-bold text-[#7C3AED]">
        {{ activeQuiz?.category || 'Tổng hợp' }}
      </span>
    </div>

    <!-- Quiz Title & Progress Info -->
    <div>
      <h3 class="text-base font-bold text-[#0F172A] line-clamp-1">
        {{ activeQuiz?.title || 'Bộ đề ôn tập tổng hợp' }}
      </h3>
      <div class="mt-2 flex items-center justify-between text-xs font-semibold text-[#64748B]">
        <span>{{ questionCountText }}</span>
        <span class="font-bold text-[#7C3AED]">{{ progressPercent }}% hoàn thành</span>
      </div>
    </div>

    <!-- Progress Bar -->
    <div class="h-2 w-full overflow-hidden rounded-full bg-[#E2E8F0]">
      <div
        class="h-full rounded-full bg-[#7C3AED] transition-all duration-500"
        :style="{ width: `${progressPercent}%` }"
      ></div>
    </div>

    <!-- Footer Action -->
    <div class="flex items-center justify-between pt-1">
      <span class="text-xs text-[#64748B] font-medium">
        Độ khó: <b class="text-[#0F172A]">{{ activeQuiz?.difficulty || 'Vừa' }}</b>
      </span>
      <router-link
        :to="playLink"
        class="inline-flex items-center justify-center rounded-lg bg-[#7C3AED] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#6D28D9] active:scale-[0.98] shadow-sm"
      >
        Tiếp tục làm →
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { attemptsApi, currentUserStorage, normalizeQuizCard, quizzesApi, tokenStorage } from '@/services/api'

const currentUser = ref(currentUserStorage.get())
const recentAttempt = ref(null)
const fallbackQuiz = ref(null)

const isLoggedIn = computed(() => Boolean(currentUser.value && tokenStorage.get()))

const activeQuiz = computed(() => {
  if (recentAttempt.value?.quiz) {
    return normalizeQuizCard(recentAttempt.value.quiz)
  }
  return fallbackQuiz.value || null
})

const progressPercent = computed(() => {
  if (recentAttempt.value) {
    const score = Number(recentAttempt.value.score || 0)
    const total = Number(recentAttempt.value.total_questions || recentAttempt.value.quiz?.questions_count || 20)
    if (total > 0) return Math.min(100, Math.round((score / total) * 100))
  }
  return activeQuiz.value?.avgScore ? Math.round(activeQuiz.value.avgScore) : 68
})

const questionCountText = computed(() => {
  if (recentAttempt.value) {
    const score = Number(recentAttempt.value.score || 0)
    const total = Number(recentAttempt.value.total_questions || recentAttempt.value.quiz?.questions_count || 20)
    return `${score} / ${total} câu hoàn thành`
  }
  const qCount = activeQuiz.value?.questions || 20
  const duration = activeQuiz.value?.duration || '30 phút'
  return `${qCount} câu hỏi · ${duration}`
})

const playLink = computed(() => {
  if (activeQuiz.value?.id) {
    return `/quizzes/${activeQuiz.value.id}`
  }
  return '/quizzes'
})

const loadData = async () => {
  if (isLoggedIn.value) {
    try {
      const attempts = await attemptsApi.list({ per_page: 1 })
      if (Array.isArray(attempts) && attempts.length > 0) {
        recentAttempt.value = attempts[0]
      }
    } catch {
      recentAttempt.value = null
    }
  } else {
    recentAttempt.value = null
  }

  if (!recentAttempt.value) {
    try {
      const quizzes = await quizzesApi.list({ visibility: 'public', per_page: 1 })
      if (Array.isArray(quizzes) && quizzes.length > 0) {
        fallbackQuiz.value = normalizeQuizCard(quizzes[0])
      }
    } catch {
      fallbackQuiz.value = null
    }
  }
}

const syncUser = (event) => {
  currentUser.value = event?.detail ?? currentUserStorage.get()
  loadData()
}

onMounted(() => {
  loadData()
  window.addEventListener('quizflex-user-updated', syncUser)
})

onBeforeUnmount(() => {
  window.removeEventListener('quizflex-user-updated', syncUser)
})
</script>
