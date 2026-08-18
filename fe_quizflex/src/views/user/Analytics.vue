<template>
  <section class="max-w-5xl mx-auto py-4 space-y-6">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Báo cáo năng lực</p>
        <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">Phân tích học tập</h1>
        <p class="mt-1 text-sm text-slate-600">Xem tiến trình điểm số, năng lực theo chủ đề và nhận xét định hướng ôn tập.</p>
      </div>
      <router-link to="/quizzes" class="btn-primary text-xs shrink-0 self-start sm:self-auto">
        Làm thêm quiz →
      </router-link>
    </div>

    <!-- 4 Summary Stats -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <article class="card p-5">
        <span class="text-xs font-semibold text-slate-500">Bài đã hoàn thành</span>
        <b class="mt-2 block text-2xl font-black text-slate-900">{{ summary.totalAttempts }}</b>
      </article>
      <article class="card p-5">
        <span class="text-xs font-semibold text-slate-500">Điểm trung bình</span>
        <b class="mt-2 block text-2xl font-black text-[#7C3AED]">{{ summary.averageScore }}%</b>
      </article>
      <article class="card p-5">
        <span class="text-xs font-semibold text-slate-500">Điểm tốt nhất</span>
        <b class="mt-2 block text-2xl font-black text-emerald-600">{{ summary.bestScore }}%</b>
      </article>
      <article class="card p-5">
        <span class="text-xs font-semibold text-slate-500">Lần làm gần nhất</span>
        <b class="mt-2 block text-xl font-bold text-slate-800">{{ summary.lastAttemptLabel }}</b>
      </article>
    </div>

    <!-- Learning Progress Chart Card -->
    <div class="card p-6 sm:p-8 space-y-6">
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between border-b border-slate-100 pb-4">
        <div>
          <h2 class="text-lg font-bold text-slate-900">Điểm trung bình theo {{ selectedLabel }}</h2>
          <p class="text-xs text-slate-500">Biểu đồ thể hiện xu hướng điểm số theo thời gian</p>
        </div>
        <div class="flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 p-1 text-xs">
          <button
            v-for="option in periodOptions"
            :key="option.value"
            type="button"
            @click="selectedPeriod = option.value"
            class="rounded-md px-3 py-1.5 font-bold transition"
            :class="selectedPeriod === option.value
              ? 'bg-white text-[#7C3AED] shadow-sm'
              : 'text-slate-600 hover:text-slate-900'"
          >
            {{ option.label }}
          </button>
        </div>
      </div>

      <div>
        <AppLoadingState 
          v-if="isLoading" 
          title="Đang tải dữ liệu phân tích..." 
          message="Vui lòng chờ trong giây lát để hệ thống tổng hợp tiến trình học tập."
          icon="📈"
        />
        <AppErrorState 
          v-else-if="errorMessage" 
          title="Không thể tải dữ liệu phân tích"
          :message="errorMessage" 
          @retry="loadAnalytics"
        />
        <div v-else-if="lineSeries.length === 0" class="rounded-xl border border-slate-200 bg-slate-50 p-10 text-center text-xs font-semibold text-slate-500">
          Chưa có kết quả hoàn thành để hiển thị biểu đồ. Hoàn thành ít nhất một quiz để xem tiến trình.
        </div>
        <div v-else class="space-y-6">
          <div class="overflow-x-auto">
            <svg viewBox="0 0 760 280" class="min-w-full">
              <g transform="translate(40,20)">
                <line x1="0" y1="220" x2="680" y2="220" stroke="#e2e8f0" stroke-width="1" />
                <line x1="0" y1="0" x2="0" y2="220" stroke="#e2e8f0" stroke-width="1" />
                <g v-for="row in 5" :key="row">
                  <line x1="0" :y1="44 * (row - 1)" x2="680" :y2="44 * (row - 1)" stroke="#e2e8f0" stroke-width="1" opacity="0.6" stroke-dasharray="3 3" />
                  <text x="-10" :y="44 * (row - 1) + 4" text-anchor="end" fill="#94a3b8" font-size="11" font-weight="600">{{ 100 - (row - 1) * 25 }}%</text>
                </g>
                <path :d="areaPath" fill="rgba(124, 58, 237, 0.08)" />
                <path :d="linePath" fill="none" stroke="#7C3AED" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                <circle
                  v-for="point in lineSeries"
                  :key="point.key"
                  :cx="point.x"
                  :cy="point.y"
                  r="4.5"
                  fill="#ffffff"
                  stroke="#7C3AED"
                  stroke-width="2.5"
                />
                <g v-for="point in lineSeries" :key="point.key + '-label'">
                  <text :x="point.x" y="242" text-anchor="middle" fill="#64748b" font-size="11" font-weight="600">{{ point.label }}</text>
                </g>
              </g>
            </svg>
          </div>

          <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3.5 text-center">
              <span class="text-[11px] font-bold text-slate-400 uppercase">Mức thấp nhất</span>
              <p class="mt-1 text-xl font-bold text-slate-800">{{ summary.minScore }}%</p>
            </div>
            <div class="rounded-xl border border-purple-100 bg-purple-50 p-3.5 text-center">
              <span class="text-[11px] font-bold text-[#7C3AED] uppercase">Mức trung bình</span>
              <p class="mt-1 text-xl font-bold text-[#7C3AED]">{{ summary.averageScore }}%</p>
            </div>
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-3.5 text-center">
              <span class="text-[11px] font-bold text-emerald-700 uppercase">Mức cao nhất</span>
              <p class="mt-1 text-xl font-bold text-emerald-700">{{ summary.bestScore }}%</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick AI / Progress Feedback -->
      <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 space-y-1">
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Nhận xét nhanh</p>
        <p class="text-xs leading-relaxed text-slate-700">{{ quickComment }}</p>
      </div>
    </div>

    <!-- Category Performance Breakdown -->
    <div class="card p-6 sm:p-8 space-y-6">
      <div class="border-b border-slate-100 pb-3">
        <h2 class="text-lg font-bold text-slate-900">Năng lực theo chủ đề</h2>
        <p class="text-xs text-slate-500">Mức độ hiểu bài phân chia theo từng danh mục môn học</p>
      </div>

      <div v-if="categoryStats.length === 0" class="rounded-xl border border-slate-200 bg-slate-50 p-8 text-center text-xs font-semibold text-slate-500">
        Chưa có đủ dữ liệu để phân tích theo chủ đề.
      </div>

      <div v-else class="grid gap-4">
        <div v-for="item in categoryStats" :key="item.name" class="space-y-1.5">
          <div class="flex items-center justify-between text-xs font-bold">
            <span class="text-slate-800">{{ item.name }}</span>
            <span class="text-slate-500">{{ item.average }}% · {{ item.count }} bài</span>
          </div>
          <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100 border border-slate-200">
            <div
              class="h-full rounded-full transition-all duration-500"
              :style="{
                width: item.average + '%',
                background: item.average >= 70 ? '#16A34A' : item.average >= 40 ? '#F59E0B' : '#DC2626',
              }"
            ></div>
          </div>
        </div>
      </div>

      <div v-if="strongestCategory || weakestCategory" class="grid gap-3 sm:grid-cols-2 pt-2">
        <div v-if="strongestCategory" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
          <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800">Chủ đề mạnh nhất</span>
          <p class="mt-1 text-base font-black text-emerald-950">{{ strongestCategory.name }} · {{ strongestCategory.average }}%</p>
        </div>
        <div v-if="weakestCategory" class="rounded-xl border border-red-200 bg-red-50 p-4">
          <span class="text-[11px] font-bold uppercase tracking-wider text-red-800">Cần cải thiện thêm</span>
          <p class="mt-1 text-base font-black text-red-950">{{ weakestCategory.name }} · {{ weakestCategory.average }}%</p>
        </div>
      </div>
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
      return { ...attempt, date }
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
  const width = 680
  const height = 220
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
  const baseY = 220
  const path = points.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x.toFixed(2)} ${point.y.toFixed(2)}`).join(' ')
  return `${path} L ${points[points.length - 1].x.toFixed(2)} ${baseY} L ${points[0].x.toFixed(2)} ${baseY} Z`
})

const categoryStats = computed(() => {
  const groups = {}

  completedAttempts.value.forEach((attempt) => {
    const name = attempt.quiz?.category?.trim() || 'Chưa phân loại'
    if (!groups[name]) {
      groups[name] = { name, totalScore: 0, count: 0 }
    }
    groups[name].totalScore += Number(attempt.score_percent || 0)
    groups[name].count += 1
  })

  return Object.values(groups)
    .map((group) => ({ ...group, average: Math.round(group.totalScore / group.count) }))
    .sort((a, b) => b.average - a.average)
})

const strongestCategory = computed(() => categoryStats.value[0] || null)
const weakestCategory = computed(() => {
  if (categoryStats.value.length < 2) return null
  return categoryStats.value[categoryStats.value.length - 1]
})

const quickComment = computed(() => {
  if (!groupedData.value.length) {
    return 'Hoàn thành ít nhất một quiz để hệ thống đưa ra nhận xét tiến trình.'
  }

  const first = groupedData.value[0].average
  const last = groupedData.value[groupedData.value.length - 1].average

  let comment = ''
  if (last >= first + 8) {
    comment = 'Xu hướng tiến bộ tốt: điểm số đang tăng. Tiếp tục giữ nhịp học và ôn tập đều đặn.'
  } else if (last <= first - 8) {
    comment = 'Điểm số có dấu hiệu giảm. Nên quay lại các chủ đề yếu và làm lại quiz để củng cố.'
  } else {
    comment = 'Điểm số ổn định.'
  }

  if (weakestCategory.value && weakestCategory.value.name !== strongestCategory.value?.name) {
    comment += ` Hãy tập trung ôn thêm chủ đề "${weakestCategory.value.name}" (đang ở mức ${weakestCategory.value.average}%).`
  }

  return comment
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
