<template>
  <section class="grid gap-6 py-8 xl:grid-cols-[minmax(0,1fr)_360px]">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl sm:p-8">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div v-if="quiz" class="relative z-10">
        <div class="mb-7 overflow-hidden rounded-[1.8rem] border border-[var(--border)] shadow-[var(--shadow-card)]">
          <div class="relative min-h-[260px]" :style="{ background: quiz.cover }">
            <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-white/10"></div>
            <div class="absolute left-5 top-5 rounded-full bg-black/55 px-3 py-1 text-[10px] font-black uppercase text-white backdrop-blur">{{ quiz.badge }}</div>
            <div class="absolute bottom-5 right-5 grid h-16 w-16 place-items-center rounded-3xl bg-white/90 text-base font-black text-slate-950 shadow-xl">{{ quiz.icon }}</div>
          </div>
        </div>

        <div class="mb-5 flex flex-wrap items-center gap-2">
          <VisibilityBadge :value="quiz.visibility" />
          <span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1 text-xs font-black text-[var(--muted)]">{{ quiz.difficulty }}</span>
          <span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1 text-xs font-black text-[var(--muted)]">{{ quiz.category }}</span>
        </div>
        <h1 class="text-4xl font-black leading-tight tracking-[-0.06em] text-[var(--text)] sm:text-5xl">{{ quiz.title }}</h1>
        <p class="mt-5 max-w-3xl text-base font-medium leading-8 text-[var(--muted)]">{{ quiz.description || 'Quiz này chưa có mô tả.' }}</p>

        <div class="mt-8 grid gap-3 sm:grid-cols-3">
          <div class="rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-5"><p class="text-sm font-bold text-[var(--muted)]">Số câu</p><b class="mt-2 block text-3xl font-black text-[var(--text)]">{{ quiz.questions }}</b></div>
          <div class="rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-5"><p class="text-sm font-bold text-[var(--muted)]">Thời gian</p><b class="mt-2 block text-3xl font-black text-[var(--text)]">{{ quiz.duration }}</b></div>
          <div class="rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-5"><p class="text-sm font-bold text-[var(--muted)]">Lượt làm</p><b class="mt-2 block text-3xl font-black text-[var(--text)]">{{ quiz.attempts }}</b></div>
        </div>

        <div class="mt-8 flex flex-wrap gap-3">
          <router-link class="btn-primary" :to="`/quizzes/${quiz.id}/play`">Bắt đầu làm bài</router-link>
          <router-link class="btn-ghost" :to="`/dashboard/questions/edit/${quiz.id}`">Sửa quiz</router-link>
          <router-link class="btn-ghost" to="/quizzes">Quay lại danh sách</router-link>
        </div>
      </div>

      <div v-if="isLoading" class="relative z-10 text-sm font-bold text-[var(--muted)]">Đang tải chi tiết quiz...</div>
      <div v-if="errorMessage" class="relative z-10 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">{{ errorMessage }}</div>
    </article>

    <aside class="grid content-start gap-5">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Question preview</p>
        <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Danh sách câu hỏi</h2>
        <div class="mt-5 grid gap-3">
          <div v-for="(question, index) in questions" :key="question.id" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-black text-[var(--primary)]">Câu {{ index + 1 }} • {{ question.points }} điểm</p>
            <p class="mt-2 text-sm font-bold leading-6 text-[var(--text)]">{{ question.question }}</p>
          </div>
        </div>
      </article>
    </aside>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import VisibilityBadge from '@/components/common/VisibilityBadge.vue'
import { normalizeQuestion, normalizeQuizCard, quizzesApi } from '@/services/api'

const route = useRoute()
const quiz = ref(null)
const isLoading = ref(false)
const errorMessage = ref('')

const questions = computed(() => (quiz.value?.rawQuestions || []).map(normalizeQuestion))

const loadQuiz = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const data = await quizzesApi.get(route.params.id)
    quiz.value = { ...normalizeQuizCard(data), rawQuestions: data.questions || [] }
  } catch (error) {
    errorMessage.value = `Không tải được chi tiết quiz: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

onMounted(loadQuiz)
</script>
