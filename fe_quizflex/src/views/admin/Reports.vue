<template>
  <section class="max-w-6xl mx-auto py-4 space-y-6">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Báo cáo & Phân tích</p>
        <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">Hiệu suất đề thi</h1>
        <p class="mt-1 text-sm text-slate-600">Tổng hợp lượt làm bài và điểm số trung bình của các bộ quiz trong hệ thống.</p>
      </div>
      <button class="btn-secondary text-xs px-3.5 py-1.5" type="button" @click="exportCsv">
        ⤓ Xuất CSV
      </button>
    </div>

    <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-700">{{ errorMessage }}</div>

    <div class="grid gap-3">
      <div v-for="row in rows" :key="row.id" class="card p-5 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <b class="text-sm font-bold text-slate-900 block">{{ row.title }}</b>
            <p class="text-xs text-slate-400 mt-0.5">Tác giả: {{ row.author }} • {{ row.attempts }} lượt làm • {{ row.questions }} câu</p>
          </div>
          <span class="rounded-lg bg-purple-50 text-[#7C3AED] px-3 py-1 text-xs font-bold">
            Điểm TB: {{ row.avgScore }}%
          </span>
        </div>
        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
          <div class="h-full rounded-full bg-[#7C3AED] transition-all" :style="{ width: `${row.avgScore}%` }"></div>
        </div>
      </div>
      <div v-if="!isLoading && rows.length === 0" class="card p-10 text-center text-xs text-slate-400">
        Chưa có dữ liệu báo cáo.
      </div>
    </div>
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
