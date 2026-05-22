<template>
  <section class="grid gap-6 py-8">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">{{ $t('user_views.Results.badge') }}</p>
        <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">{{ $t('user_views.Results.title') }}</h1>
        <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">{{ $t('user_views.Results.description') }}</p>
      </div>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
      <article v-for="stat in stats" :key="stat.label" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
        <p class="text-sm font-bold text-[var(--muted)]">{{ stat.label }}</p>
        <b class="mt-2 block text-3xl font-black text-[var(--text)]">{{ stat.value }}</b>
      </article>
    </div>

    <div v-if="isLoading" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">{{ $t('user_views.Results.loading') }}</div>
    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

    <article class="grid gap-4">
      <router-link v-for="item in attempts" :key="item.id" :to="`/results/${item.id}`" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)] backdrop-blur-2xl transition hover:-translate-y-1 hover:border-[var(--border-strong)]">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h2 class="text-xl font-black text-[var(--text)]">{{ item.quiz_title || item.quiz?.title }}</h2>
            <p class="mt-1 text-sm text-[var(--muted)]">{{ formatDate(item.finished_at || item.started_at) }} • {{ formatSeconds(item.time_spent_seconds) }} • {{ item.status }}</p>
          </div>
          <VisibilityBadge :value="item.quiz?.visibility || 'public'" />
        </div>
        <div class="mt-4 h-3 overflow-hidden rounded-full bg-[var(--surface-soft)]">
          <div class="h-full rounded-full bg-gradient-to-r from-[var(--primary)] to-[var(--accent)]" :style="{ width: `${item.score_percent}%` }"></div>
        </div>
        <div class="mt-2 text-right text-sm font-black text-[var(--primary)]">{{ $t('user_views.Results.score_summary', { score: item.score, total: item.total_points, percent: Math.round(item.score_percent) }) }}</div>
      </router-link>
    </article>

    <div v-if="!isLoading && attempts.length === 0" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center shadow-[var(--shadow-card)]">
      <h3 class="text-2xl font-black text-[var(--text)]">{{ $t('user_views.Results.empty_title') }}</h3>
      <p class="mt-2 text-sm text-[var(--muted)]">{{ $t('user_views.Results.empty_description') }}</p>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import VisibilityBadge from '@/components/common/VisibilityBadge.vue'
import { attemptsApi, formatSeconds } from '@/services/api'

const { t } = useI18n()
const attempts = ref([])
const isLoading = ref(false)
const errorMessage = ref('')

const stats = computed(() => {
  const completed = attempts.value.filter((item) => item.status === 'completed')
  const average = completed.length ? Math.round(completed.reduce((sum, item) => sum + Number(item.score_percent || 0), 0) / completed.length) : 0
  const best = completed.length ? Math.max(...completed.map((item) => Math.round(Number(item.score_percent || 0)))) : 0

  return [
    { label: t('user_views.Results.stats.completed_quizzes'), value: attempts.value.length },
    { label: t('user_views.Results.stats.average_score'), value: `${average}%` },
    { label: t('user_views.Results.stats.best_score'), value: `${best}%` },
    { label: t('user_views.Results.stats.completed'), value: completed.length },
  ]
})

const formatDate = (value) => {
  if (!value) return t('user_views.Results.no_time')
  return new Date(value).toLocaleString('vi-VN')
}

const loadAttempts = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    attempts.value = await attemptsApi.list({ status: 'completed' })
  } catch (error) {
    errorMessage.value = t('user_views.Results.errors.load_failed', { message: error.message })
  } finally {
    isLoading.value = false
  }
}

onMounted(loadAttempts)
</script>
