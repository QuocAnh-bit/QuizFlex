<template>
  <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_4px_16px_rgba(15,23,42,0.05)]">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
      <div class="flex items-center gap-2.5">
        <!-- Book icon -->
        <span
          class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#F5F3FF] text-[#7C3AED]"
        >
          <svg
            class="h-4 w-4"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v17H6.5A2.5 2.5 0 0 0 4 22V5.5Z" />
            <path d="M4 5.5V22" />
            <path d="M8 7h8" />
            <path d="M8 11h8" />
          </svg>
        </span>

        <h3 class="text-sm font-bold tracking-wide text-[#334155]">
          TIẾP TỤC HỌC
        </h3>
      </div>

      <!-- AI Generated -->
      <span
        class="rounded-full bg-[#F5F3FF] px-3 py-1 text-xs font-bold text-[#7C3AED]"
      >
        AI Generated
      </span>
    </div>

    <!-- Quiz -->
    <div class="pt-5">
      <h3
        class="line-clamp-1 text-lg font-bold text-[#0F172A]"
      >
        {{ activeQuiz?.title || 'Bộ đề ôn tập tổng hợp' }}
      </h3>

      <div
        class="mt-3 flex items-center justify-between text-sm font-semibold"
      >
        <span class="text-[#64748B]">
          {{ questionCountText }}
        </span>

        <span class="font-bold text-[#7C3AED]">
          {{ progressPercent }}% hoàn thành
        </span>
      </div>
    </div>

    <!-- Progress -->
    <div class="mt-5 h-2.5 w-full overflow-hidden rounded-full bg-[#E2E8F0]">
      <div
        class="h-full rounded-full bg-[#7C3AED] transition-all duration-500"
        :style="{ width: `${progressPercent}%` }"
      ></div>
    </div>

    <!-- Footer -->
    <div class="mt-5 flex items-center justify-between gap-3">
      <span class="text-sm font-medium text-[#64748B]">
        Độ khó:
        <b class="text-[#0F172A]">
          {{ activeQuiz?.difficulty || 'Vừa' }}
        </b>
      </span>

      <router-link
        :to="playLink"
        class="inline-flex items-center justify-center rounded-xl bg-[#7C3AED] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#6D28D9] active:scale-[0.98]"
      >
        Tiếp tục làm
        <span class="ml-1.5 text-base">→</span>
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import {
  attemptsApi,
  currentUserStorage,
  normalizeQuizCard,
  quizzesApi,
  tokenStorage,
} from '@/services/api'

const currentUser = ref(currentUserStorage.get())
const recentAttempt = ref(null)
const fallbackQuiz = ref(null)

const isLoggedIn = computed(() =>
  Boolean(currentUser.value && tokenStorage.get())
)

const activeQuiz = computed(() => {
  if (recentAttempt.value?.quiz) {
    return normalizeQuizCard(recentAttempt.value.quiz)
  }

  return fallbackQuiz.value || null
})

const progressPercent = computed(() => {
  if (recentAttempt.value) {
    const score = Number(recentAttempt.value.score || 0)

    const total = Number(
      recentAttempt.value.total_questions ||
      recentAttempt.value.quiz?.questions_count ||
      20
    )

    if (total > 0) {
      return Math.min(
        100,
        Math.round((score / total) * 100)
      )
    }
  }

  return activeQuiz.value?.avgScore
    ? Math.round(activeQuiz.value.avgScore)
    : 68
})

const questionCountText = computed(() => {
  if (recentAttempt.value) {
    const score = Number(recentAttempt.value.score || 0)

    const total = Number(
      recentAttempt.value.total_questions ||
      recentAttempt.value.quiz?.questions_count ||
      20
    )

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
      const attempts = await attemptsApi.list({
        per_page: 1,
      })

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
      const quizzes = await quizzesApi.list({
        visibility: 'public',
        per_page: 1,
      })

      if (Array.isArray(quizzes) && quizzes.length > 0) {
        fallbackQuiz.value = normalizeQuizCard(quizzes[0])
      }
    } catch {
      fallbackQuiz.value = null
    }
  }
}

const syncUser = (event) => {
  currentUser.value =
    event?.detail ?? currentUserStorage.get()

  loadData()
}

onMounted(() => {
  loadData()

  window.addEventListener(
    'quizflex-user-updated',
    syncUser
  )
})

onBeforeUnmount(() => {
  window.removeEventListener(
    'quizflex-user-updated',
    syncUser
  )
})
</script>