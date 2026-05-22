<template>
  <section class="grid gap-6 py-8">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">{{ $t('user_views.QuizList.badge') }}</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">{{ $t('user_views.QuizList.title') }}</h1>
          <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">{{ $t('user_views.QuizList.description') }}</p>
        </div>
        <router-link class="btn-primary" to="/dashboard/questions/create">{{ $t('user_views.QuizList.create_quiz_button') }}</router-link>
      </div>
    </div>

    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <div class="grid gap-4 xl:grid-cols-[1fr_auto_auto_auto] xl:items-center">
        <input v-model="filters.search" class="field" :placeholder="$t('user_views.QuizList.search_placeholder')" @keyup.enter="loadQuizzes" />
        <select v-model="filters.visibility" class="field xl:w-48" @change="loadQuizzes">
          <option value="all">{{ $t('user_views.QuizList.visibility_all') }}</option>
          <option value="public">{{ $t('user_views.QuizList.visibility_public') }}</option>
          <option value="private">{{ $t('user_views.QuizList.visibility_private') }}</option>
          <option value="group">{{ $t('user_views.QuizList.visibility_group') }}</option>
        </select>
        <select v-model="filters.difficulty" class="field xl:w-44" @change="loadQuizzes">
          <option value="">{{ $t('user_views.QuizList.difficulty_all') }}</option>
          <option value="easy">{{ $t('user_views.QuizList.difficulty_easy') }}</option>
          <option value="medium">{{ $t('user_views.QuizList.difficulty_medium') }}</option>
          <option value="hard">{{ $t('user_views.QuizList.difficulty_hard') }}</option>
        </select>
        <button class="btn-ghost" type="button" @click="loadQuizzes">{{ $t('user_views.QuizList.search_button') }}</button>
      </div>
    </article>

    <div v-if="isLoading" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">{{ $t('user_views.QuizList.loading') }}</div>
    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <article v-for="quiz in quizzes" :key="quiz.id" class="group overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] shadow-[var(--shadow-card)] transition duration-300 hover:-translate-y-2 hover:border-[var(--border-strong)]">
        <router-link :to="`/quizzes/${quiz.id}`" class="block">
          <div class="relative h-36" :style="{ background: quiz.cover }">
            <div class="absolute inset-0 bg-gradient-to-t from-black/45 via-black/5 to-white/10"></div>
            <div class="absolute left-4 top-4 rounded-full bg-black/55 px-3 py-1 text-[10px] font-black text-white backdrop-blur">{{ quiz.badge }}</div>
            <div class="absolute bottom-4 right-4 grid h-12 w-12 place-items-center rounded-2xl bg-white/90 text-sm font-black text-slate-900 shadow-xl">{{ quiz.icon }}</div>
          </div>
          <div class="p-5">
            <div class="mb-3 flex flex-wrap items-center gap-2">
              <VisibilityBadge :value="quiz.visibility" />
              <span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1 text-xs font-black text-[var(--muted)]">{{ quiz.difficulty }}</span>
            </div>
            <h2 class="text-xl font-black tracking-[-0.04em] text-[var(--text)] transition group-hover:text-[var(--primary)]">{{ quiz.title }}</h2>
            <p class="mt-2 line-clamp-2 text-sm leading-6 text-[var(--muted)]">{{ quiz.description || $t('user_views.QuizList.no_description') }}</p>
            <div class="mt-5 grid grid-cols-3 gap-2">
              <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-center"><b class="block text-[var(--text)]">{{ quiz.questions }}</b><span class="text-[10px] font-bold text-[var(--muted)]">{{ $t('user_views.QuizList.question_label') }}</span></div>
              <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-center"><b class="block text-[var(--text)]">{{ quiz.duration }}</b><span class="text-[10px] font-bold text-[var(--muted)]">{{ $t('user_views.QuizList.duration_label') }}</span></div>
              <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-center"><b class="block text-[var(--text)]">{{ quiz.avgScore }}%</b><span class="text-[10px] font-bold text-[var(--muted)]">{{ $t('user_views.QuizList.average_label') }}</span></div>
            </div>
          </div>
        </router-link>
      </article>
    </div>

    <div v-if="!isLoading && quizzes.length === 0" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center shadow-[var(--shadow-card)]">
      <h3 class="text-2xl font-black text-[var(--text)]">{{ $t('user_views.QuizList.empty_title') }}</h3>
      <p class="mt-2 text-sm text-[var(--muted)]">{{ $t('user_views.QuizList.empty_description') }}</p>
    </div>
  </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import VisibilityBadge from '@/components/common/VisibilityBadge.vue'
import { normalizeQuizCard, quizzesApi } from '@/services/api'

const { t } = useI18n()
const quizzes = ref([])
const isLoading = ref(false)
const errorMessage = ref('')

const filters = reactive({
  search: '',
  visibility: 'all',
  difficulty: '',
})

const loadQuizzes = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const params = {
      search: filters.search || undefined,
      visibility: filters.visibility === 'all' ? undefined : filters.visibility,
      difficulty: filters.difficulty || undefined,
    }
    const data = await quizzesApi.list(params)
    quizzes.value = data.map(normalizeQuizCard)
  } catch (error) {
    errorMessage.value = t('user_views.QuizList.errors.load_failed', { message: error.message })
  } finally {
    isLoading.value = false
  }
}

onMounted(loadQuizzes)
</script>
