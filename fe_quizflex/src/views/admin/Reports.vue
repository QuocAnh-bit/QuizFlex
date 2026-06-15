<template>
  <section class="grid gap-6">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end"><div><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Reports</p><h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Báo cáo & analytics</h1><p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Tổng hợp hiệu suất quiz từ API quizzes và quiz_attempts.</p></div><div class="flex gap-3"><button class="btn-ghost" type="button" @click="exportCsv">Export CSV</button></div></div>
    </div>

    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

    <article class="grid gap-4">
      <div v-for="row in rows" :key="row.id" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]"><div class="flex flex-wrap items-center justify-between gap-4"><div><b class="text-xl text-[var(--text)]">{{ row.title }}</b><p class="mt-1 text-sm text-[var(--muted)]">{{ row.author }} • {{ row.attempts }} lượt làm • {{ row.questions }} câu</p></div><span class="rounded-full bg-[var(--chip-active)] px-4 py-2 text-sm font-black text-[var(--primary)]">{{ row.avgScore }}%</span></div><div class="mt-4 h-3 overflow-hidden rounded-full bg-[var(--surface-soft)]"><div class="h-full rounded-full bg-gradient-to-r from-[var(--primary)] to-[var(--accent)]" :style="{ width: `${row.avgScore}%` }"></div></div></div>
      <div v-if="!isLoading && rows.length === 0" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">Chưa có dữ liệu báo cáo.</div>
    </article>
  </section>
</template>
<script setup>
import { computed, onMounted, ref } from 'vue'
import { normalizeQuizCard, quizzesApi } from '@/services/api'

const quizzes = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const rows = computed(() => quizzes.value)

const loadReports = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const data = await quizzesApi.list({ per_page: 100 })
    quizzes.value = data.map(normalizeQuizCard)
  } catch (error) {
    errorMessage.value = `Không tải được báo cáo: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

const exportCsv = () => {
  const header = ['id', 'title', 'author', 'questions', 'attempts', 'avg_score']
  const body = rows.value.map((row) => [row.id, row.title, row.author, row.questions, row.attempts, row.avgScore])
  const csv = [header, ...body].map((line) => line.map((cell) => `"${String(cell).replaceAll('"', '""')}"`).join(',')).join('\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = 'quizflex-report.csv'
  link.click()
  URL.revokeObjectURL(url)
}

onMounted(loadReports)
</script>
