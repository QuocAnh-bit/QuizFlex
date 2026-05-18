<template>
  <section class="grid gap-6">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Admin Dashboard</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Tổng quan hệ thống</h1>
          <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Số liệu lấy từ API quiz, lượt làm bài và user. Không còn diễn dữ liệu cho vui nữa.</p>
        </div>
        <div class="flex flex-wrap gap-3"><router-link class="btn-ghost" to="/admin/questions">Kho quiz</router-link><router-link class="btn-primary" to="/admin/questions/create">Tạo quiz</router-link></div>
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
        <div class="mb-5 flex items-center justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Analytics</p><h2 class="mt-2 text-2xl font-black text-[var(--text)]">Hiệu suất quiz</h2></div><router-link class="btn-ghost" to="/admin/reports">Report</router-link></div>
        <div class="grid gap-4">
          <div v-for="row in reportRows" :key="row.id" class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3"><div><b class="text-[var(--text)]">{{ row.title }}</b><p class="mt-1 text-sm text-[var(--muted)]">Owner: {{ row.author }} • {{ row.attempts }} lượt làm</p></div><VisibilityBadge :value="row.visibility" /></div>
            <div class="h-3 overflow-hidden rounded-full bg-[var(--surface)]"><div class="h-full rounded-full bg-gradient-to-r from-[var(--primary)] to-[var(--accent)]" :style="{ width: `${row.avgScore}%` }"></div></div>
            <div class="mt-2 flex justify-between text-xs font-bold text-[var(--muted)]"><span>Điểm TB {{ row.avgScore }}%</span><span>{{ row.questions }} câu</span></div>
          </div>
          <div v-if="!isLoading && reportRows.length === 0" class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-6 text-sm font-bold text-[var(--muted)]">Chưa có quiz để thống kê.</div>
        </div>
      </article>

      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Quick actions</p>
        <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Luồng chính</h2>
        <div class="mt-5 grid gap-3">
          <router-link v-for="item in quickLinks" :key="item.to" :to="item.to" class="group rounded-[1.4rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4 transition duration-300 hover:-translate-y-0.5 hover:border-[var(--border-strong)] hover:bg-[var(--chip-active)]"><b class="text-[var(--text)]">{{ item.title }}</b><p class="mt-1 text-sm leading-6 text-[var(--muted)]">{{ item.desc }}</p></router-link>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import VisibilityBadge from '@/components/common/VisibilityBadge.vue'
import { attemptsApi, normalizeQuizCard, quizzesApi, usersApi } from '@/services/api'

const quizzes = ref([])
const attempts = ref([])
const users = ref([])
const isLoading = ref(false)
const errorMessage = ref('')

const completedAttempts = computed(() => attempts.value.filter((item) => item.status === 'completed'))
const averageScore = computed(() => completedAttempts.value.length ? Math.round(completedAttempts.value.reduce((sum, item) => sum + Number(item.score_percent || 0), 0) / completedAttempts.value.length) : 0)

const stats = computed(() => [
  { label: 'Tổng quiz', value: quizzes.value.length, hint: 'Từ bảng quizzes' },
  { label: 'Lượt làm', value: attempts.value.length, hint: 'Từ bảng quiz_attempts' },
  { label: 'Điểm TB', value: `${averageScore.value}%`, hint: 'Các lượt completed' },
  { label: 'Người dùng', value: users.value.length, hint: 'Từ bảng users' },
])

const reportRows = computed(() => quizzes.value.slice(0, 5))

const quickLinks = [
  { title: 'Tạo quiz thủ công', desc: 'Tạo quiz kèm câu hỏi và đáp án, lưu trực tiếp vào backend.', to: '/admin/questions/create' },
  { title: 'Kho quiz', desc: 'Tìm kiếm, lọc, sửa và xóa mềm quiz.', to: '/admin/questions' },
  { title: 'Kết quả làm bài', desc: 'Xem lượt làm bài đã lưu trong quiz_attempts.', to: '/results' },
  { title: 'Người dùng', desc: 'Tạo, sửa role và quản lý danh sách user.', to: '/admin/users' },
]

const loadDashboard = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const [quizData, attemptData, userData] = await Promise.all([
      quizzesApi.list({ per_page: 100 }),
      attemptsApi.list({ per_page: 100 }),
      usersApi.list({ per_page: 100 }),
    ])
    quizzes.value = quizData.map(normalizeQuizCard)
    attempts.value = attemptData
    users.value = userData
  } catch (error) {
    errorMessage.value = `Không tải được dashboard: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

onMounted(loadDashboard)
</script>
