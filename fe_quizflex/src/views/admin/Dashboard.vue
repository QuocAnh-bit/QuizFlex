<template>
  <section class="grid gap-6">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">{{ $t('admin_views.Dashboard.badge') }}</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">{{ $t('admin_views.Dashboard.title') }}</h1>
          <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">{{ $t('admin_views.Dashboard.description') }}</p>
        </div>
        <div class="flex flex-wrap gap-3"><router-link class="btn-ghost" to="/admin/questions">{{ $t('admin_views.Dashboard.quiz_bank_button') }}</router-link><router-link class="btn-primary" to="/admin/questions/create">{{ $t('admin_views.Dashboard.create_quiz_button') }}</router-link></div>
      </div>
    </div>

    <div v-if="errorMessage" class="rounded-[2rem] border border-amber-500/30 bg-amber-500/10 p-5 text-sm font-bold text-amber-300">{{ errorMessage }}</div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <article v-for="stat in stats" :key="stat.label" class="group relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl transition duration-300 hover:-translate-y-1 hover:border-[var(--border-strong)] hover:shadow-[0_24px_70px_rgba(0,0,0,0.24)]">
        <div class="absolute -right-12 -top-12 h-32 w-32 rounded-full bg-[var(--primary)]/15 blur-3xl transition group-hover:scale-125"></div>
        <p class="relative z-10 text-sm font-black text-[var(--muted)]">{{ stat.label }}</p>
        <strong class="relative z-10 mt-3 block text-4xl font-black tracking-[-0.06em] text-[var(--text)]">{{ stat.value }}</strong>
        <span class="relative z-10 mt-2 block text-sm font-bold text-[var(--primary)]">{{ stat.hint }}</span>
      </article>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <div class="mb-5 flex items-center justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">{{ $t('admin_views.Dashboard.analytics_badge') }}</p><h2 class="mt-2 text-2xl font-black text-[var(--text)]">{{ $t('admin_views.Dashboard.analytics_title') }}</h2></div><router-link class="btn-ghost" to="/admin/reports">{{ $t('admin_views.Dashboard.report_button') }}</router-link></div>
        <div class="grid gap-4">
          <div v-for="row in reportRows" :key="row.id" class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3"><div><b class="text-[var(--text)]">{{ row.title }}</b><p class="mt-1 text-sm text-[var(--muted)]">{{ $t('admin_views.Dashboard.row_meta', { author: row.author, attempts: row.attempts }) }}</p></div><VisibilityBadge :value="row.visibility" /></div>
            <div class="h-3 overflow-hidden rounded-full bg-[var(--surface)]"><div class="h-full rounded-full bg-gradient-to-r from-[var(--primary)] to-[var(--accent)]" :style="{ width: `${row.avgScore}%` }"></div></div>
            <div class="mt-2 flex justify-between text-xs font-bold text-[var(--muted)]"><span>{{ $t('admin_views.Dashboard.average_score', { score: row.avgScore }) }}</span><span>{{ $t('admin_views.Dashboard.question_count', { count: row.questions }) }}</span></div>
          </div>
          <div v-if="!isLoading && reportRows.length === 0" class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-6 text-sm font-bold text-[var(--muted)]">{{ $t('admin_views.Dashboard.no_report_data') }}</div>
        </div>
      </article>

      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">{{ $t('admin_views.Dashboard.quick_actions_badge') }}</p>
        <h2 class="mt-2 text-2xl font-black text-[var(--text)]">{{ $t('admin_views.Dashboard.quick_actions_title') }}</h2>
        <div class="mt-5 grid gap-3">
          <router-link v-for="item in quickLinks" :key="item.to" :to="item.to" class="group rounded-[1.4rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4 transition duration-300 hover:-translate-y-0.5 hover:border-[var(--border-strong)] hover:bg-[var(--chip-active)]"><b class="text-[var(--text)]">{{ item.title }}</b><p class="mt-1 text-sm leading-6 text-[var(--muted)]">{{ item.desc }}</p></router-link>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import VisibilityBadge from '@/components/common/VisibilityBadge.vue'
import { attemptsApi, currentUserStorage, normalizeQuizCard, quizzesApi, usersApi } from '@/services/api'

const { t } = useI18n()
const quizzes = ref([])
const attempts = ref([])
const users = ref([])
const currentUser = computed(() => currentUserStorage.get())
const isAdmin = computed(() => String(currentUser.value?.role || '').toLowerCase() === 'admin')
const isLoading = ref(false)
const errorMessage = ref('')

const completedAttempts = computed(() => attempts.value.filter((item) => item.status === 'completed'))
const averageScore = computed(() => completedAttempts.value.length ? Math.round(completedAttempts.value.reduce((sum, item) => sum + Number(item.score_percent || 0), 0) / completedAttempts.value.length) : 0)

const stats = computed(() => {
  const items = [
    { label: t('admin_views.Dashboard.stats.total_quizzes.label'), value: quizzes.value.length, hint: t('admin_views.Dashboard.stats.total_quizzes.hint') },
    { label: t('admin_views.Dashboard.stats.attempts.label'), value: attempts.value.length, hint: t('admin_views.Dashboard.stats.attempts.hint') },
    { label: t('admin_views.Dashboard.stats.average_score.label'), value: `${averageScore.value}%`, hint: t('admin_views.Dashboard.stats.average_score.hint') },
  ]

  if (isAdmin.value) {
    items.push({ label: t('admin_views.Dashboard.stats.users.label'), value: users.value.length, hint: t('admin_views.Dashboard.stats.users.hint') })
  }

  return items
})

const reportRows = computed(() => quizzes.value.slice(0, 5))

const quickLinks = computed(() => {
  const links = [
    { title: t('admin_views.Dashboard.quick_links.create_manual.title'), desc: t('admin_views.Dashboard.quick_links.create_manual.desc'), to: '/admin/questions/create' },
    { title: t('admin_views.Dashboard.quick_links.quiz_bank.title'), desc: t('admin_views.Dashboard.quick_links.quiz_bank.desc'), to: '/admin/questions' },
    { title: t('admin_views.Dashboard.quick_links.results.title'), desc: t('admin_views.Dashboard.quick_links.results.desc'), to: '/results' },
  ]

  if (isAdmin.value) {
    links.push({ title: t('admin_views.Dashboard.quick_links.users.title'), desc: t('admin_views.Dashboard.quick_links.users.desc'), to: '/admin/users' })
  }

  return links
})

const loadDashboard = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const [quizData, attemptData] = await Promise.all([
      quizzesApi.list({ per_page: 100 }),
      attemptsApi.list({ per_page: 100 }),
    ])
    quizzes.value = quizData.map(normalizeQuizCard)
    attempts.value = attemptData

    if (isAdmin.value) {
      users.value = await usersApi.list({ per_page: 100 })
    } else {
      users.value = []
    }
  } catch (error) {
    errorMessage.value = t('admin_views.Dashboard.errors.load_failed', { message: error.message })
  } finally {
    isLoading.value = false
  }
}

onMounted(loadDashboard)
</script>
