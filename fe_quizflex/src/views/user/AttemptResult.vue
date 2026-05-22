<template>
  <section class="grid gap-6 py-8 xl:grid-cols-[minmax(0,1fr)_360px]">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl sm:p-8">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div v-if="attempt" class="relative z-10">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">{{ $t('user_views.AttemptResult.badge') }}</p>
        <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">{{ $t('user_views.AttemptResult.title') }}</h1>
        <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">{{ attempt.quiz_title || attempt.quiz?.title }}</p>

        <div class="mt-8 grid gap-4 sm:grid-cols-4">
          <div class="rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-5"><p class="text-sm font-bold text-[var(--muted)]">{{ $t('user_views.AttemptResult.score_label') }}</p><b class="mt-2 block text-3xl font-black text-[var(--text)]">{{ attempt.score }}/{{ attempt.total_points }}</b></div>
          <div class="rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-5"><p class="text-sm font-bold text-[var(--muted)]">{{ $t('user_views.AttemptResult.percent_label') }}</p><b class="mt-2 block text-3xl font-black text-[var(--text)]">{{ Math.round(attempt.score_percent) }}%</b></div>
          <div class="rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-5"><p class="text-sm font-bold text-[var(--muted)]">{{ $t('user_views.AttemptResult.time_label') }}</p><b class="mt-2 block text-3xl font-black text-[var(--text)]">{{ formatSeconds(attempt.time_spent_seconds) }}</b></div>
          <div class="rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-5"><p class="text-sm font-bold text-[var(--muted)]">{{ $t('user_views.AttemptResult.status_label') }}</p><b class="mt-2 block text-2xl font-black text-[var(--text)]">{{ attempt.status }}</b></div>
        </div>

        <div class="mt-8 grid gap-4">
          <article v-for="(item, index) in attempt.answers_snapshot" :key="item.question_id" class="rounded-[1.5rem] border p-4" :class="item.is_correct ? 'border-emerald-500/30 bg-emerald-500/10' : 'border-rose-500/30 bg-rose-500/10'">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <p class="text-xs font-black uppercase tracking-[0.18em]" :class="item.is_correct ? 'text-emerald-300' : 'text-rose-300'">{{ $t('user_views.AttemptResult.question_number', { index: index + 1 }) }}</p>
                <h2 class="mt-2 text-lg font-black text-[var(--text)]">{{ item.question }}</h2>
              </div>
              <span class="rounded-full px-3 py-1 text-xs font-black" :class="item.is_correct ? 'bg-emerald-500/15 text-emerald-300' : 'bg-rose-500/15 text-rose-300'">{{ $t('user_views.AttemptResult.points_summary', { earned: item.earned_points, points: item.points }) }}</span>
            </div>
            <p class="mt-3 text-sm font-bold text-[var(--muted)]">{{ $t('user_views.AttemptResult.selected_answer', { answer: item.selected_answer_keys?.join(', ') || $t('user_views.AttemptResult.not_selected') }) }}</p>
            <p class="mt-1 text-sm font-bold text-[var(--muted)]">{{ $t('user_views.AttemptResult.correct_answer', { answer: item.correct_answer_keys?.join(', ') }) }}</p>
          </article>
        </div>
      </div>

      <div v-if="isLoading" class="relative z-10 text-sm font-bold text-[var(--muted)]">{{ $t('user_views.AttemptResult.loading') }}</div>
      <div v-if="errorMessage" class="relative z-10 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">{{ errorMessage }}</div>
    </article>

    <aside class="grid content-start gap-5">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">{{ $t('user_views.AttemptResult.actions_badge') }}</p>
        <div class="mt-5 grid gap-3">
          <router-link v-if="attempt" class="btn-primary text-center" :to="`/quizzes/${attempt.quiz_id}/play`">{{ $t('user_views.AttemptResult.retry_button') }}</router-link>
          <router-link class="btn-ghost text-center" to="/results">{{ $t('user_views.AttemptResult.history_button') }}</router-link>
          <router-link class="btn-ghost text-center" to="/quizzes">{{ $t('user_views.AttemptResult.quiz_list_button') }}</router-link>
        </div>
      </article>
    </aside>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { attemptsApi, formatSeconds } from '@/services/api'

const route = useRoute()
const { t } = useI18n()
const attempt = ref(null)
const isLoading = ref(false)
const errorMessage = ref('')

const loadAttempt = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    attempt.value = await attemptsApi.get(route.params.id)
  } catch (error) {
    errorMessage.value = t('user_views.AttemptResult.errors.load_failed', { message: error.message })
  } finally {
    isLoading.value = false
  }
}

onMounted(loadAttempt)
</script>
