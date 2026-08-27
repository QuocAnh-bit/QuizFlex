<template>
  <section class="max-w-6xl mx-auto py-4 space-y-6">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div class="flex items-start gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-purple-500/10 text-purple-500">
          <LayoutDashboard class="h-5 w-5" />
        </div>
        <div>
          <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Admin Dashboard</p>
          <h1 class="text-3xl font-black tracking-[-0.04em] text-[var(--text)]">Tổng quan hệ thống</h1>
          <p class="mt-1 text-sm font-medium text-[var(--muted)]">Theo dõi người dùng, quiz, phòng học, giao dịch và doanh thu thời gian thực.</p>
        </div>
      </div>
      <div class="flex items-center gap-2.5">
        <button class="btn-secondary text-xs px-3.5 py-1.5" type="button" :disabled="isLoading" @click="loadDashboard">
          {{ isLoading ? 'Đang tải...' : '🔄 Cập nhật' }}
        </button>
        <router-link class="btn-primary text-xs px-3.5 py-1.5" to="/admin/reports">Xem báo cáo</router-link>
      </div>
    </div>

    <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-700">
      {{ errorMessage }}
    </div>

    <!-- ACTION CENTER: CẦN XỬ LÝ (MODERATION ALERTS) -->
    <div class="rounded-2xl border border-purple-200 bg-gradient-to-r from-purple-50 via-white to-amber-50/40 p-5 shadow-sm space-y-3">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="flex h-2.5 w-2.5 relative">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#7C3AED] opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#7C3AED]"></span>
          </span>
          <h2 class="text-xs font-black uppercase tracking-wider text-purple-950">
            Trung tâm kiểm duyệt — Cần xử lý ngay
          </h2>
        </div>
        <span class="text-xs text-slate-500 font-medium">Tổng hợp các yêu cầu chờ Admin phê duyệt</span>
      </div>

      <div class="grid gap-3 sm:grid-cols-3">
        <!-- 1. Question Bank Requests -->
        <router-link
          to="/admin/question-bank?tab=pending"
          class="flex items-center justify-between rounded-xl border border-amber-200 bg-white p-4 transition hover:border-amber-400 hover:shadow-xs group cursor-pointer"
        >
          <div class="space-y-0.5">
            <p class="text-xs font-bold text-slate-900 group-hover:text-amber-700">Câu hỏi đóng góp</p>
            <p class="text-[11px] text-slate-400">Chờ duyệt vào Ngân hàng</p>
          </div>
          <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-black text-amber-800">
            {{ actionStats.questionsPending }}
          </span>
        </router-link>

        <!-- 2. Quiz Review Requests -->
        <router-link
          to="/admin/quizzes?tab=pending"
          class="flex items-center justify-between rounded-xl border border-purple-200 bg-white p-4 transition hover:border-[#7C3AED] hover:shadow-xs group cursor-pointer"
        >
          <div class="space-y-0.5">
            <p class="text-xs font-bold text-slate-900 group-hover:text-[#7C3AED]">Quiz công khai</p>
            <p class="text-[11px] text-slate-400">Chờ thẩm định & Diff</p>
          </div>
          <span class="rounded-full bg-purple-100 px-3 py-1 text-sm font-black text-[#7C3AED]">
            {{ actionStats.quizzesPending }}
          </span>
        </router-link>

        <!-- 3. Report Tickets -->
        <router-link
          to="/admin/reports?status=pending"
          class="flex items-center justify-between rounded-xl border border-rose-200 bg-white p-4 transition hover:border-rose-400 hover:shadow-xs group cursor-pointer"
        >
          <div class="space-y-0.5">
            <p class="text-xs font-bold text-slate-900 group-hover:text-rose-700">Báo cáo vi phạm</p>
            <p class="text-[11px] text-slate-400">Phản ánh câu hỏi lỗi</p>
          </div>
          <span class="rounded-full bg-rose-100 px-3 py-1 text-sm font-black text-rose-800">
            {{ actionStats.reportsPending }}
          </span>
        </router-link>
      </div>
    </div>

    <!-- 8 System Stats Cards -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <article
        v-for="stat in systemCards"
        :key="stat.label"
        class="card p-5 card-hover flex flex-col justify-between space-y-2"
      >
        <span class="text-xs font-semibold text-slate-500">{{ stat.label }}</span>
        <strong class="text-2xl font-black text-slate-900">{{ stat.value }}</strong>
        <span class="text-[11px] text-slate-400 font-medium">{{ stat.hint }}</span>
      </article>
    </div>

    <!-- Revenue & Paying Users -->
    <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
      <!-- Revenue Chart Card -->
      <article class="card p-6 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-4">
          <div>
            <h2 class="text-base font-bold text-slate-900">Thống kê doanh thu</h2>
            <p class="text-xs text-slate-500">Biểu đồ dòng tiền theo chu kỳ</p>
          </div>
          <div class="flex rounded-lg border border-slate-200 bg-slate-50 p-1 text-xs font-bold">
            <button
              v-for="mode in chartModes"
              :key="mode.value"
              class="rounded-md px-3 py-1 transition"
              :class="chartMode === mode.value ? 'bg-[#7C3AED] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
              type="button"
              @click="chartMode = mode.value"
            >
              {{ mode.label }}
            </button>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
          <div v-for="item in revenueCards" :key="item.label" class="rounded-xl border border-slate-100 bg-slate-50 p-3 space-y-1">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">{{ item.label }}</span>
            <strong class="text-base font-black text-slate-900 block">{{ item.value }}</strong>
            <span class="text-[10px] text-slate-500 font-medium block">{{ item.hint }}</span>
          </div>
        </div>

        <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-4">
          <div v-if="isLoading" class="grid h-64 w-full place-items-center text-xs text-slate-400">
            Đang tải biểu đồ...
          </div>
          <div v-else-if="chartRows.length === 0" class="grid h-64 w-full place-items-center text-xs font-semibold text-slate-400">
            Chưa có doanh thu thành công để vẽ biểu đồ.
          </div>
          <VueApexCharts 
            v-else 
            class="w-full"
            type="area" 
            height="260" 
            :options="chartOptions" 
            :series="series" 
          />
        </div>
      </article>

      <!-- Paying Users Card -->
      <article class="card p-6 space-y-5">
        <div class="border-b border-slate-100 pb-3">
          <h2 class="text-base font-bold text-slate-900">Người dùng thanh toán</h2>
          <p class="text-xs text-slate-500">Khách hàng đóng góp doanh thu lớn nhất</p>
        </div>

        <div class="grid gap-3 text-xs">
          <div class="rounded-xl border border-slate-100 bg-slate-50 p-3.5 space-y-1">
            <span class="text-slate-400 font-bold uppercase text-[10px]">User nạp nhiều nhất</span>
            <b class="text-slate-900 font-bold text-sm block">{{ entityName(revenue.top_paying_user) }}</b>
            <span class="text-[#7C3AED] font-black text-sm block">{{ formatCurrency(revenue.top_paying_user?.total_paid) }}</span>
          </div>

          <div class="rounded-xl border border-slate-100 bg-slate-50 p-3.5 space-y-1">
            <span class="text-slate-400 font-bold uppercase text-[10px]">User nạp ít nhất</span>
            <b class="text-slate-900 font-bold text-sm block">{{ entityName(revenue.lowest_paying_user) }}</b>
            <span class="text-[#7C3AED] font-black text-sm block">{{ formatCurrency(revenue.lowest_paying_user?.total_paid) }}</span>
          </div>

          <div class="rounded-xl border border-slate-100 bg-slate-50 p-3.5 space-y-2">
            <span class="text-slate-400 font-bold uppercase text-[10px] block">Top khách hàng nổi bật</span>
            <div class="space-y-1.5 divide-y divide-slate-100">
              <div v-for="user in (revenue.revenue_by_user || []).slice(0, 5)" :key="user.user_id" class="flex items-center justify-between pt-1.5 first:pt-0">
                <span class="truncate font-semibold text-slate-800">{{ user.name }}</span>
                <b class="shrink-0 text-[#7C3AED] font-bold">{{ formatCurrency(user.total_paid) }}</b>
              </div>
              <div v-if="!isLoading && !(revenue.revenue_by_user || []).length" class="text-slate-400 text-[11px] pt-1">
                Chưa có giao dịch thành công nào.
              </div>
            </div>
          </div>
        </div>
      </article>
    </div>

    <!-- Quiz Analytics & Quick Admin Links -->
    <div class="grid gap-6 lg:grid-cols-2">
      <!-- Quiz Analytics Card -->
      <article class="card p-6 space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h2 class="text-base font-bold text-slate-900">Thống kê quiz</h2>
            <p class="text-xs text-slate-500">Phân loại và độ tương tác</p>
          </div>
          <router-link class="btn-secondary text-xs" to="/admin/questions">Kho quiz →</router-link>
        </div>

        <div class="grid grid-cols-3 gap-3">
          <div v-for="item in quizTypeCards" :key="item.label" class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-center">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">{{ item.label }}</span>
            <strong class="text-xl font-black text-slate-900 block mt-1">{{ item.value }}</strong>
          </div>
        </div>

        <div class="grid gap-2.5 text-xs">
          <div v-for="row in quizHighlights" :key="row.label" class="rounded-xl border border-slate-100 bg-slate-50 p-3 flex items-center justify-between gap-3">
            <div>
              <span class="text-[10px] font-bold text-slate-400 uppercase block">{{ row.label }}</span>
              <b class="text-slate-900 font-bold block truncate max-w-xs">{{ row.title }}</b>
            </div>
            <span class="text-[#7C3AED] font-bold shrink-0">{{ row.value }}</span>
          </div>
        </div>
      </article>

      <!-- Quick Admin Links -->
      <article class="card p-6 space-y-4">
        <div class="border-b border-slate-100 pb-3">
          <h2 class="text-base font-bold text-slate-900">Luồng quản trị nhanh</h2>
          <p class="text-xs text-slate-500">Truy cập trực tiếp các module quản lý</p>
        </div>

        <div class="grid gap-3">
          <router-link
            v-for="item in quickLinks"
            :key="item.to"
            :to="item.to"
            class="rounded-xl border border-slate-200 p-4 transition hover:border-[#7C3AED] hover:bg-purple-50/30 group"
          >
            <b class="text-xs font-bold text-slate-900 group-hover:text-[#7C3AED] flex items-center justify-between">
              <span>{{ item.title }}</span>
              <span>→</span>
            </b>
            <p class="mt-1 text-[11px] text-slate-500 leading-relaxed">{{ item.desc }}</p>
          </router-link>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { adminBankRequestsApi, adminDashboardApi, quizReviewApi, reportApi } from '@/services/api'
import VueApexCharts from 'vue3-apexcharts'

const dashboard = ref({
  system: {},
  revenue: {},
  quiz: {},
})
const isLoading = ref(false)
const errorMessage = ref('')
const chartMode = ref('day')

const actionStats = reactive({
  questionsPending: 0,
  quizzesPending: 0,
  reportsPending: 0,
})

const chartModes = [
  { label: 'Ngày', value: 'day' },
  { label: 'Tháng', value: 'month' },
  { label: 'Năm', value: 'year' },
]

const system = computed(() => dashboard.value.system || {})
const revenue = computed(() => dashboard.value.revenue || {})
const quiz = computed(() => dashboard.value.quiz || {})

const systemCards = computed(() => [
  { label: 'Tổng người dùng', value: formatNumber(system.value.total_users), hint: 'Tài khoản hoạt động' },
  { label: 'Tổng số Quiz', value: formatNumber(system.value.total_quizzes), hint: 'Bộ đề trên hệ thống' },
  { label: 'Tổng số phòng', value: formatNumber(system.value.total_rooms), hint: `${formatNumber(system.value.total_homework_rooms)} bài tập, ${formatNumber(system.value.total_live_rooms)} live` },
  { label: 'Lượt làm bài', value: formatNumber(system.value.total_attempts), hint: 'Tổng bài nộp' },
  { label: 'Tổng câu hỏi', value: formatNumber(system.value.total_questions), hint: 'Ngân hàng câu hỏi' },
  { label: 'Tài khoản VIP', value: formatNumber(system.value.total_vip_users), hint: 'Plus, Pro, Ultra' },
  { label: 'Giao dịch', value: formatNumber(system.value.total_transactions), hint: 'Đơn thanh toán' },
  { label: 'Tổng doanh thu', value: formatCurrency(system.value.total_revenue), hint: 'Giao dịch thành công' },
])

const revenueCards = computed(() => [
  { label: 'Doanh thu', value: formatCurrency(revenue.value.total_revenue), hint: 'Tổng thành công' },
  { label: 'User thanh toán', value: formatNumber(revenue.value.paid_users_count), hint: 'Khách hàng VIP' },
  { label: 'Thành công', value: formatNumber(revenue.value.successful_transactions), hint: 'Đã hoàn tất' },
  { label: 'Thất bại', value: formatNumber(revenue.value.failed_transactions), hint: 'Lỗi giao dịch' },
  { label: 'Đang xử lý', value: formatNumber(revenue.value.pending_transactions), hint: 'Đang chờ cổng' },
  { label: 'Tỷ lệ thành công', value: successRate.value, hint: 'Hiệu suất cổng' },
])

const successRate = computed(() => {
  const total = Number(system.value.total_transactions || 0)
  if (!total) return '0%'
  return formatPercent(Number(revenue.value.successful_transactions || 0) * 100 / total)
})

const chartRows = computed(() => {
  if (chartMode.value === 'month') return revenue.value.revenue_by_month || []
  if (chartMode.value === 'year') return revenue.value.revenue_by_year || []
  return revenue.value.revenue_by_day || []
})

const series = computed(() => [{
  name: 'Doanh thu',
  data: chartRows.value.map(item => item.revenue)
}])

const chartOptions = computed(() => ({
  chart: {
    type: 'area',
    background: 'transparent',
    toolbar: { show: false },
  },
  colors: ['#7C3AED'], 
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.35,
      opacityTo: 0.05,
      stops: [0, 95, 100]
    }
  },
  dataLabels: { enabled: false },
  stroke: { 
    curve: 'smooth',
    width: 2.5 
  },
  grid: {
    borderColor: '#E2E8F0',
    strokeDashArray: 4,
  },
  xaxis: {
    categories: chartRows.value.map(item => {
      if (chartMode.value === 'day') {
        const parts = item.period.split('-')
        if (parts.length === 3) return `${parts[2]}/${parts[1]}`
      }
      return item.period
    }),
    labels: { style: { colors: '#64748B', fontSize: '11px' } },
    axisBorder: { show: false },
    axisTicks: { show: false }
  },
  yaxis: {
    labels: {
      formatter: (value) => {
        return new Intl.NumberFormat('vi-VN', { 
          notation: "compact", 
          compactDisplay: "short",
          maximumFractionDigits: 1
        }).format(value) + ' đ'
      },
      style: { colors: '#64748B', fontSize: '11px' }
    }
  },
  tooltip: {
    y: {
      formatter: (value) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value)
    }
  }
}))

const quizTypeCards = computed(() => [
  { label: 'Public', value: formatNumber(quiz.value.public_quizzes) },
  { label: 'Private', value: formatNumber(quiz.value.private_quizzes) },
  { label: 'AI sinh đề', value: formatNumber(quiz.value.ai_generated_quizzes) },
])

const quizHighlights = computed(() => [
  {
    label: 'Quiz nhiều lượt làm nhất',
    title: quiz.value.most_attempted_quiz?.title || 'Chưa có dữ liệu',
    value: `${formatNumber(quiz.value.most_attempted_quiz?.attempts_count)} lượt`,
  },
  {
    label: 'Điểm trung bình cao nhất',
    title: quiz.value.highest_average_score_quiz?.title || 'Chưa có dữ liệu',
    value: `${formatPercent(quiz.value.highest_average_score_quiz?.avg_score)}`,
  },
  {
    label: 'Tổng ngân hàng câu hỏi',
    title: 'Question Bank',
    value: `${formatNumber(quiz.value.total_questions)} câu`,
  },
])

const quickLinks = [
  { title: 'Ngân hàng câu hỏi', desc: 'Quản lý, duyệt đóng góp câu hỏi, đối chiếu Revision Diff và xử lý báo cáo.', to: '/admin/question-bank' },
  { title: 'Kho bài Quiz', desc: 'Thẩm định đề thi công khai, so sánh các phiên bản và kiểm soát hiển thị.', to: '/admin/quizzes' },
  { title: 'Báo cáo vi phạm', desc: 'Kiểm duyệt phản ánh câu hỏi lỗi theo nhóm câu hỏi và gửi đính chính.', to: '/admin/reports' },
  { title: 'Quản lý người dùng & Doanh thu', desc: 'Xem danh sách, phân quyền role và theo dõi các giao dịch nạp VIP.', to: '/admin/users' },
]

const loadDashboard = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const [overviewData, bankReqs, quizReqs, reportCount] = await Promise.allSettled([
      adminDashboardApi.overview(),
      adminBankRequestsApi.fetchRequests({ status: 'pending', per_page: 1 }),
      quizReviewApi.fetchAdminReviewRequests({ status: 'pending', per_page: 1 }),
      reportApi.countPending(),
    ])

    if (overviewData.status === 'fulfilled') {
      dashboard.value = overviewData.value
    } else {
      errorMessage.value = overviewData.reason?.message || 'Không tải được dữ liệu dashboard.'
    }

    if (bankReqs.status === 'fulfilled') {
      actionStats.questionsPending = bankReqs.value.stats?.pending || bankReqs.value.total || 0
    }
    if (quizReqs.status === 'fulfilled') {
      actionStats.quizzesPending = quizReqs.value.stats?.pending || quizReqs.value.total || 0
    }
    if (reportCount.status === 'fulfilled') {
      actionStats.reportsPending = reportCount.value.question_pending || reportCount.value.count || 0
    }
  } catch (error) {
    errorMessage.value = error.message || 'Không tải được dữ liệu dashboard.'
  } finally {
    isLoading.value = false
  }
}

const formatNumber = (value) => new Intl.NumberFormat('vi-VN').format(Number(value || 0))

const formatCurrency = (value) => new Intl.NumberFormat('vi-VN', {
  style: 'currency',
  currency: 'VND',
  maximumFractionDigits: 0,
}).format(Number(value || 0))

const formatPercent = (value) => `${Math.round(Number(value || 0))}%`

const entityName = (entity) => {
  if (!entity) return 'Chưa có dữ liệu'
  return entity.name || entity.email || 'Không rõ'
}

onMounted(loadDashboard)
</script>
