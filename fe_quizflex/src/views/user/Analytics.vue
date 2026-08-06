<template>
  <section class="grid gap-6 py-8">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Analytics Dashboard</p>
        <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Phân tích Năng lực</h1>
        <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Xem tiến trình điểm số, năng lực theo chủ đề và nhận xét nhanh để định hướng ôn luyện.</p>
      </div>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
        <p class="text-sm font-bold text-[var(--muted)]">Bài đã hoàn thành</p>
        <b class="mt-2 block text-3xl font-black text-[var(--text)]">{{ summary.totalAttempts }}</b>
      </article>
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
        <p class="text-sm font-bold text-[var(--muted)]">Điểm trung bình</p>
        <b class="mt-2 block text-3xl font-black text-[var(--text)]">{{ summary.averageScore }}%</b>
      </article>
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
        <p class="text-sm font-bold text-[var(--muted)]">Điểm tốt nhất</p>
        <b class="mt-2 block text-3xl font-black text-[var(--text)]">{{ summary.bestScore }}%</b>
      </article>
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
        <p class="text-sm font-bold text-[var(--muted)]">Dữ liệu cập nhật</p>
        <b class="mt-2 block text-3xl font-black text-[var(--text)]">{{ summary.lastAttemptLabel }}</b>
      </article>
    </div>

    <div class="grid gap-4">
      <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">5.1 Biểu đồ tiến trình học tập</p>
            <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Điểm trung bình theo {{ selectedLabel }}</h2>
          </div>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="option in periodOptions"
              :key="option.value"
              type="button"
              @click="selectedPeriod = option.value"
              :class="[
                'rounded-full border px-4 py-2 text-sm font-bold transition duration-200',
                selectedPeriod === option.value
                  ? 'border-[var(--primary)] bg-[var(--primary)]/10 text-[var(--text)]'
                  : 'border-[var(--border)] bg-[var(--surface-soft)] text-[var(--muted)] hover:border-[var(--border-strong)] hover:bg-[var(--surface)]',
              ]"
            >
              {{ option.label }}
            </button>
          </div>
        </div>

        <div class="mt-6">
          <AppLoadingState 
            v-if="isLoading" 
            title="Đang tải dữ liệu phân tích..." 
            message="Vui lòng chờ trong giây lát để hệ thống tổng hợp tiến trình học tập của bạn."
            icon="📈"
          />
          <AppErrorState 
            v-else-if="errorMessage" 
            title="Không thể tải dữ liệu phân tích"
            :message="errorMessage" 
            @retry="loadAnalytics"
          />
          <div v-else-if="lineSeries.length === 0" class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-10 text-center text-sm font-bold text-[var(--muted)]">Chưa có kết quả hoàn thành để hiển thị biểu đồ. Hoàn thành ít nhất một quiz để xem tiến trình.</div>
          <div v-else class="overflow-hidden rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5">
            <div class="overflow-x-auto">
              <svg viewBox="0 0 760 320" class="min-w-full">
                <defs>
                  <linearGradient id="analyticsStroke" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="#8b5cf6" />
                    <stop offset="100%" stop-color="#ec4899" />
                  </linearGradient>
                </defs>
                <g transform="translate(40,20)">
                  <line x1="0" y1="250" x2="660" y2="250" stroke="var(--border)" stroke-width="1" />
                  <line x1="0" y1="0" x2="0" y2="250" stroke="var(--border)" stroke-width="1" />
                  <g v-for="row in 5" :key="row">
                    <line x1="0" :y1="50 * (row - 1)" x2="660" :y2="50 * (row - 1)" stroke="var(--border)" stroke-width="1" opacity="0.15" />
                    <text x="-10" :y="50 * (row - 1) + 4" text-anchor="end" fill="var(--muted)" font-size="11">{{ 100 - (row - 1) * 25 }}%</text>
                  </g>
                  <path :d="linePath" fill="none" stroke="url(#analyticsStroke)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                  <path :d="areaPath" fill="rgba(139,92,246,0.16)" />
                  <circle
                    v-for="point in lineSeries"
                    :key="point.key"
                    :cx="point.x"
                    :cy="point.y"
                    r="5"
                    fill="white"
                    stroke="url(#analyticsStroke)"
                    stroke-width="3"
                  />
                  <g v-for="point in lineSeries" :key="point.key + '-label'">
                    <text :x="point.x" y="270" text-anchor="middle" fill="var(--muted)" font-size="11">{{ point.label }}</text>
                  </g>
                </g>
              </svg>
            </div>

            <div class="mt-6 grid gap-3 sm:grid-cols-3">
              <div class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)] p-4">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--muted)]">Mức thấp nhất</p>
                <p class="mt-3 text-2xl font-black text-[var(--text)]">{{ summary.minScore }}%</p>
              </div>
              <div class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)] p-4">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--muted)]">Mức trung bình</p>
                <p class="mt-3 text-2xl font-black text-[var(--text)]">{{ summary.averageScore }}%</p>
              </div>
              <div class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)] p-4">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--muted)]">Mức cao nhất</p>
                <p class="mt-3 text-2xl font-black text-[var(--text)]">{{ summary.bestScore }}%</p>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-6 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)] p-5">
          <p class="text-sm font-bold text-[var(--muted)]">Nhận xét nhanh</p>
          <p class="mt-3 text-sm leading-7 text-[var(--text)]">{{ quickComment }}</p>
        </div>
      </div>

      <!-- 5.2 removed: Radar skill chart intentionally omitted per user request -->
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import AppLoadingState from '@/components/common/AppLoadingState.vue'
import AppErrorState from '@/components/common/AppErrorState.vue'
import { attemptsApi } from '@/services/api'

const attempts = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const selectedPeriod = ref('day')

const periodOptions = [
  { label: 'Ngày', value: 'day' },
  { label: 'Tuần', value: 'week' },
  { label: 'Tháng', value: 'month' },
]

const completedAttempts = computed(() => attempts.value.filter((attempt) => attempt.status === 'completed' && (attempt.finished_at || attempt.started_at) && attempt.score_percent !== undefined && attempt.score_percent !== null))

const summary = computed(() => {
  const sorted = [...completedAttempts.value].sort((a, b) => new Date(a.finished_at || a.started_at) - new Date(b.finished_at || b.started_at))
  const average = sorted.length ? Math.round(sorted.reduce((sum, attempt) => sum + Number(attempt.score_percent || 0), 0) / sorted.length) : 0
  const best = sorted.length ? Math.max(...sorted.map((attempt) => Math.round(Number(attempt.score_percent || 0)))) : 0
  const min = sorted.length ? Math.min(...sorted.map((attempt) => Math.round(Number(attempt.score_percent || 0)))) : 0
  const lastAttempt = sorted.length ? new Date(sorted[sorted.length - 1].finished_at || sorted[sorted.length - 1].started_at) : null

  return {
    totalAttempts: sorted.length,
    averageScore: average,
    bestScore: best,
    minScore: min,
    lastAttemptLabel: lastAttempt ? lastAttempt.toLocaleDateString('vi-VN') : 'Chưa có',
  }
})

const periodLabelMap = {
  day: 'ngày',
  week: 'tuần',
  month: 'tháng',
}

const selectedLabel = computed(() => periodLabelMap[selectedPeriod.value] || 'kỳ')

const groupedData = computed(() => {
  const values = [...completedAttempts.value]
    .map((attempt) => {
      const date = new Date(attempt.finished_at || attempt.started_at)
      if (Number.isNaN(date.getTime())) return null
      return {
        ...attempt,
        date,
      }
    })
    .filter(Boolean)

  const groups = values.reduce((acc, attempt) => {
    const key = getPeriodKey(attempt.date, selectedPeriod.value)
    if (!key) return acc

    if (!acc[key]) {
      acc[key] = {
        key,
        label: getPeriodLabel(attempt.date, selectedPeriod.value),
        totalScore: 0,
        count: 0,
        average: 0,
      }
    }

    acc[key].totalScore += Number(attempt.score_percent || 0)
    acc[key].count += 1
    return acc
  }, {})

  return Object.values(groups)
    .map((group) => ({
      ...group,
      average: Math.round(group.count ? group.totalScore / group.count : 0),
    }))
    .sort((a, b) => a.key.localeCompare(b.key))
})

const lineSeries = computed(() => {
  const items = groupedData.value
  if (!items.length) return []
  const width = 660
  const height = 250
  const xStep = items.length > 1 ? width / (items.length - 1) : width / 2

  return items.map((item, index) => ({
    key: item.key,
    label: item.label,
    average: item.average,
    x: index * xStep,
    y: height - (item.average / 100) * height,
  }))
})

const linePath = computed(() => {
  if (!lineSeries.value.length) return ''
  return lineSeries.value.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x.toFixed(2)} ${point.y.toFixed(2)}`).join(' ')
})

const areaPath = computed(() => {
  if (!lineSeries.value.length) return ''
  const points = lineSeries.value
  const baseY = 250
  const path = points.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x.toFixed(2)} ${point.y.toFixed(2)}`).join(' ')
  return `${path} L ${points[points.length - 1].x.toFixed(2)} ${baseY} L ${points[0].x.toFixed(2)} ${baseY} Z`
})

// Radar (5.2) removed per user request - related computed properties omitted

const quickComment = computed(() => {
  if (!groupedData.value.length) {
    return 'Hoàn thành ít nhất một quiz để hệ thống đưa ra nhận xét tiến trình.'
  }

  const first = groupedData.value[0].average
  const last = groupedData.value[groupedData.value.length - 1].average
  if (last >= first + 8) {
    return 'Xu hướng tiến bộ tốt: điểm số đang tăng. Tiếp tục giữ nhịp học và ôn tập đều đặn.'
  }

  if (last <= first - 8) {
    return 'Điểm số có dấu hiệu giảm. Nên quay lại các chủ đề yếu và làm lại quiz để củng cố.'
  }

  return 'Điểm số ổn định. Hãy tập trung vào các chủ đề có điểm trung bình thấp hơn để cân bằng năng lực.'
})

const loadAnalytics = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    attempts.value = await attemptsApi.list({ status: 'completed', per_page: 100 })
  } catch (error) {
    errorMessage.value = `Không tải được dữ liệu phân tích: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

const getPeriodKey = (date, period) => {
  if (!(date instanceof Date) || Number.isNaN(date.getTime())) return null

  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  if (period === 'day') {
    return `${year}-${month}-${day}`
  }

  if (period === 'week') {
    const week = getIsoWeek(date)
    return `${year}-W${String(week).padStart(2, '0')}`
  }

  return `${year}-${month}`
}

const getPeriodLabel = (date, period) => {
  if (!(date instanceof Date) || Number.isNaN(date.getTime())) return ''

  if (period === 'day') {
    return date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' })
  }

  if (period === 'week') {
    const week = getIsoWeek(date)
    return `Tuần ${week}`
  }

  return `Tháng ${date.toLocaleDateString('vi-VN', { month: 'numeric', year: 'numeric' })}`
}

const getIsoWeek = (date) => {
  const temp = new Date(date.valueOf())
  temp.setHours(0, 0, 0, 0)
  temp.setDate(temp.getDate() + 4 - (temp.getDay() || 7))
  const yearStart = new Date(temp.getFullYear(), 0, 1)
  const weekNo = Math.ceil((((temp - yearStart) / 86400000) + 1) / 7)
  return weekNo
}

onMounted(loadAnalytics)
</script>
