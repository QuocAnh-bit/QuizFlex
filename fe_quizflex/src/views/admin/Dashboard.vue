<template>
  <section class="grid gap-6">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Admin Dashboard</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Tổng quan hệ thống</h1>
          <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">
            Theo dõi người dùng, quiz, room, lượt làm bài, câu hỏi, VIP, giao dịch và doanh thu từ dữ liệu backend.
          </p>
        </div>
        <div class="flex flex-wrap gap-3">
          <button class="btn-ghost" type="button" :disabled="isLoading" @click="loadDashboard">
            {{ isLoading ? 'Đang tải...' : 'Tải lại' }}
          </button>
          <router-link class="btn-primary" to="/admin/reports">Xem report</router-link>
        </div>
      </div>
    </div>

    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">
      {{ errorMessage }}
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <article
        v-for="stat in systemCards"
        :key="stat.label"
        class="group relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl transition duration-300 hover:-translate-y-1 hover:border-[var(--border-strong)]"
      >
        <div class="absolute -right-12 -top-12 h-32 w-32 rounded-full bg-[var(--primary)]/15 blur-3xl transition group-hover:scale-125"></div>
        <p class="relative z-10 text-sm font-black text-[var(--muted)]">{{ stat.label }}</p>
        <strong class="relative z-10 mt-3 block text-4xl font-black tracking-[-0.06em] text-[var(--text)]">{{ stat.value }}</strong>
        <span class="relative z-10 mt-2 block text-sm font-bold text-[var(--primary)]">{{ stat.hint }}</span>
      </article>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Revenue</p>
            <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Thống kê doanh thu</h2>
          </div>
          <div class="flex rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-1">
            <button
              v-for="mode in chartModes"
              :key="mode.value"
              class="rounded-xl px-4 py-2 text-xs font-black transition"
              :class="chartMode === mode.value ? 'bg-[var(--chip-active)] text-[var(--primary)]' : 'text-[var(--muted)] hover:text-[var(--text)]'"
              type="button"
              @click="chartMode = mode.value"
            >
              {{ mode.label }}
            </button>
          </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-3">
          <div v-for="item in revenueCards" :key="item.label" class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--muted)]">{{ item.label }}</p>
            <strong class="mt-2 block text-2xl font-black text-[var(--text)]">{{ item.value }}</strong>
            <span class="mt-1 block text-xs font-bold text-[var(--primary)]">{{ item.hint }}</span>
          </div>
        </div>

        <div class="mt-6 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4">
          <div class="flex h-72 items-end gap-2 overflow-x-auto pb-2">
            <div
              v-for="item in chartRows"
              :key="item.period"
              class="flex min-w-[58px] flex-1 flex-col items-center justify-end gap-2"
            >
              <div class="flex h-52 w-full items-end rounded-xl bg-[var(--surface)] px-2 py-2">
                <div
                  class="w-full rounded-lg bg-gradient-to-t from-[var(--primary)] to-[var(--accent)] transition-all"
                  :style="{ height: `${barHeight(item.revenue)}%` }"
                  :title="`${item.period}: ${formatCurrency(item.revenue)}`"
                ></div>
              </div>
              <span class="max-w-[70px] truncate text-center text-[11px] font-bold text-[var(--muted)]">{{ item.period }}</span>
            </div>
            <div v-if="!isLoading && chartRows.length === 0" class="grid h-52 w-full place-items-center text-sm font-bold text-[var(--muted)]">
              Chưa có doanh thu thành công để vẽ biểu đồ.
            </div>
          </div>
        </div>
      </article>

      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Payment users</p>
        <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Người dùng thanh toán</h2>
        <div class="mt-5 grid gap-4">
          <div class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-sm font-black text-[var(--muted)]">User nạp nhiều nhất</p>
            <b class="mt-2 block text-xl text-[var(--text)]">{{ entityName(revenue.top_paying_user) }}</b>
            <span class="mt-1 block text-sm font-bold text-[var(--primary)]">{{ formatCurrency(revenue.top_paying_user?.total_paid) }}</span>
          </div>
          <div class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-sm font-black text-[var(--muted)]">User nạp ít nhất</p>
            <b class="mt-2 block text-xl text-[var(--text)]">{{ entityName(revenue.lowest_paying_user) }}</b>
            <span class="mt-1 block text-sm font-bold text-[var(--primary)]">{{ formatCurrency(revenue.lowest_paying_user?.total_paid) }}</span>
          </div>
          <div class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-sm font-black text-[var(--muted)]">Top 10 user đã thanh toán</p>
            <div class="mt-3 grid gap-3">
              <div v-for="user in revenue.revenue_by_user || []" :key="user.user_id" class="flex items-center justify-between gap-3 text-sm">
                <span class="min-w-0 truncate font-bold text-[var(--text)]">{{ user.name }}</span>
                <b class="shrink-0 text-[var(--primary)]">{{ formatCurrency(user.total_paid) }}</b>
              </div>
              <div v-if="!isLoading && !(revenue.revenue_by_user || []).length" class="text-sm font-bold text-[var(--muted)]">
                Chưa có user thanh toán thành công.
              </div>
            </div>
          </div>
        </div>
      </article>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <div class="mb-5 flex items-center justify-between gap-4">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Quiz analytics</p>
            <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Thống kê quiz</h2>
          </div>
          <router-link class="btn-ghost" to="/admin/questions">Kho quiz</router-link>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
          <div v-for="item in quizTypeCards" :key="item.label" class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--muted)]">{{ item.label }}</p>
            <strong class="mt-2 block text-3xl font-black text-[var(--text)]">{{ item.value }}</strong>
          </div>
        </div>

        <div class="mt-5 grid gap-4">
          <div v-for="row in quizHighlights" :key="row.label" class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--muted)]">{{ row.label }}</p>
            <b class="mt-2 block text-lg text-[var(--text)]">{{ row.title }}</b>
            <span class="mt-1 block text-sm font-bold text-[var(--primary)]">{{ row.value }}</span>
          </div>
        </div>
      </article>

      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Quick actions</p>
        <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Luồng quản trị</h2>
        <div class="mt-5 grid gap-3">
          <router-link
            v-for="item in quickLinks"
            :key="item.to"
            :to="item.to"
            class="group rounded-[1.4rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4 transition duration-300 hover:-translate-y-0.5 hover:border-[var(--border-strong)] hover:bg-[var(--chip-active)]"
          >
            <b class="text-[var(--text)]">{{ item.title }}</b>
            <p class="mt-1 text-sm leading-6 text-[var(--muted)]">{{ item.desc }}</p>
          </router-link>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { adminDashboardApi } from '@/services/api'

const dashboard = ref({
  system: {},
  revenue: {},
  quiz: {},
})
const isLoading = ref(false)
const errorMessage = ref('')
const chartMode = ref('day')

const chartModes = [
  { label: 'Ngày', value: 'day' },
  { label: 'Tháng', value: 'month' },
  { label: 'Năm', value: 'year' },
]

const system = computed(() => dashboard.value.system || {})
const revenue = computed(() => dashboard.value.revenue || {})
const quiz = computed(() => dashboard.value.quiz || {})

const systemCards = computed(() => [
  { label: 'Tổng user', value: formatNumber(system.value.total_users), hint: 'Bảng users' },
  { label: 'Tổng quiz', value: formatNumber(system.value.total_quizzes), hint: 'Bảng quizzes' },
  { label: 'Tổng room', value: formatNumber(system.value.total_rooms), hint: `${formatNumber(system.value.total_homework_rooms)} homework, ${formatNumber(system.value.total_live_rooms)} live` },
  { label: 'Lượt làm bài', value: formatNumber(system.value.total_attempts), hint: 'Bảng quiz_attempts' },
  { label: 'Tổng câu hỏi', value: formatNumber(system.value.total_questions), hint: 'Bảng questions' },
  { label: 'User VIP', value: formatNumber(system.value.total_vip_users), hint: 'Role VIP hoặc còn hạn VIP' },
  { label: 'Giao dịch', value: formatNumber(system.value.total_transactions), hint: 'Bảng payments' },
  { label: 'Doanh thu', value: formatCurrency(system.value.total_revenue), hint: 'Giao dịch thành công' },
])

const revenueCards = computed(() => [
  { label: 'Doanh thu', value: formatCurrency(revenue.value.total_revenue), hint: 'Tổng giao dịch success' },
  { label: 'User đã thanh toán', value: formatNumber(revenue.value.paid_users_count), hint: 'Distinct user_id' },
  { label: 'Thành công', value: formatNumber(revenue.value.successful_transactions), hint: 'Payment success' },
  { label: 'Thất bại', value: formatNumber(revenue.value.failed_transactions), hint: 'Payment failed' },
  { label: 'Đang chờ', value: formatNumber(revenue.value.pending_transactions), hint: 'Payment pending' },
  { label: 'Tỉ lệ thành công', value: successRate.value, hint: 'Success / tổng giao dịch' },
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

const maxChartRevenue = computed(() => Math.max(...chartRows.value.map((item) => Number(item.revenue || 0)), 0))

const quizTypeCards = computed(() => [
  { label: 'Public', value: formatNumber(quiz.value.public_quizzes) },
  { label: 'Private', value: formatNumber(quiz.value.private_quizzes) },
  { label: 'AI generated', value: formatNumber(quiz.value.ai_generated_quizzes) },
])

const quizHighlights = computed(() => [
  {
    label: 'User tạo nhiều quiz nhất',
    title: entityName(quiz.value.top_quiz_creator),
    value: `${formatNumber(quiz.value.top_quiz_creator?.quizzes_count)} quiz`,
  },
  {
    label: 'User tạo ít quiz nhất',
    title: entityName(quiz.value.lowest_quiz_creator),
    value: `${formatNumber(quiz.value.lowest_quiz_creator?.quizzes_count)} quiz`,
  },
  {
    label: 'Quiz nhiều lượt làm nhất',
    title: quiz.value.most_attempted_quiz?.title || 'Chưa có dữ liệu',
    value: `${formatNumber(quiz.value.most_attempted_quiz?.attempts_count)} lượt làm`,
  },
  {
    label: 'Điểm trung bình cao nhất',
    title: quiz.value.highest_average_score_quiz?.title || 'Chưa có dữ liệu',
    value: `${formatPercent(quiz.value.highest_average_score_quiz?.avg_score)} từ ${formatNumber(quiz.value.highest_average_score_quiz?.attempts_count)} lượt`,
  },
  {
    label: 'Điểm trung bình thấp nhất',
    title: quiz.value.lowest_average_score_quiz?.title || 'Chưa có dữ liệu',
    value: `${formatPercent(quiz.value.lowest_average_score_quiz?.avg_score)} từ ${formatNumber(quiz.value.lowest_average_score_quiz?.attempts_count)} lượt`,
  },
  {
    label: 'Tổng câu hỏi hệ thống',
    title: 'Question bank',
    value: `${formatNumber(quiz.value.total_questions)} câu hỏi`,
  },
])

const quickLinks = [
  { title: 'Quản lý user', desc: 'Tạo user, đổi role, kiểm tra số quiz và lượt làm bài.', to: '/admin/users' },
  { title: 'Kho quiz', desc: 'Tìm kiếm, lọc, sửa và xóa mềm quiz trong hệ thống.', to: '/admin/questions' },
  { title: 'Báo cáo quiz', desc: 'Xem hiệu suất quiz và xuất CSV khi cần.', to: '/admin/reports' },
  { title: 'Thanh toán', desc: 'Kiểm tra lịch sử giao dịch và các gói VIP.', to: '/admin/payments' },
]

const loadDashboard = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    dashboard.value = await adminDashboardApi.overview()
  } catch (error) {
    errorMessage.value = error.message || 'Không tải được dữ liệu dashboard.'
  } finally {
    isLoading.value = false
  }
}

const barHeight = (value) => {
  const max = maxChartRevenue.value
  if (!max) return 0
  return Math.max(6, Math.round(Number(value || 0) * 100 / max))
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
