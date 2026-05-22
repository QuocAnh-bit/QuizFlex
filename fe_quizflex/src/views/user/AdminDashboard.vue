<template>
  <section class="grid gap-6 py-8">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl sm:p-8">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Admin Dashboard</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)] sm:text-5xl">Quản trị hệ thống QuizFlex</h1>
          <p class="mt-3 max-w-3xl text-sm leading-7 text-[var(--muted)]">Theo dõi toàn hệ thống, quản lý người dùng, gói VIP, nội dung quiz và các cấu hình vận hành.</p>
        </div>
        <div class="flex flex-wrap gap-3">
          <button class="btn-primary opacity-70" type="button" disabled>Gửi thông báo</button>
          <router-link class="btn-ghost" to="/admin/users">Quản lý người dùng</router-link>
          <router-link class="btn-ghost" to="/admin/payments">Quản lý gói VIP</router-link>
        </div>
      </div>
    </article>

    <div v-if="isLoading" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">Đang tải số liệu quản trị...</div>
    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
      <article v-for="stat in stats" :key="stat.label" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
        <p class="text-xs font-black uppercase tracking-[0.16em] text-[var(--muted)]">{{ stat.label }}</p>
        <b class="mt-2 block text-3xl font-black text-[var(--text)]">{{ stat.value }}</b>
        <span class="mt-2 block text-xs font-bold text-[var(--primary)]">{{ stat.hint }}</span>
      </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_minmax(360px,0.75fr)]">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">User Management</p>
            <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Quản lý người dùng</h2>
            <p class="mt-2 text-sm leading-6 text-[var(--muted)]">Dùng API users hiện có để xem danh sách và điều hướng sang màn quản lý chi tiết.</p>
          </div>
          <router-link class="btn-primary" to="/admin/users">Mở trang Users</router-link>
        </div>

        <div class="mt-5 grid gap-3 lg:grid-cols-[1fr_160px_160px]">
          <input v-model="userSearch" class="field" placeholder="Tìm theo tên hoặc email..." />
          <select v-model="roleFilter" class="field">
            <option value="all">Tất cả role</option>
            <option value="user">Normal</option>
            <option value="vip">VIP</option>
            <option value="admin">Admin</option>
          </select>
          <select class="field" disabled>
            <option>Trạng thái TODO</option>
          </select>
        </div>

        <div class="mt-5 overflow-hidden rounded-[1.5rem] border border-[var(--border)]">
          <div class="grid grid-cols-[minmax(0,1fr)_110px_110px_150px] gap-3 border-b border-[var(--border)] bg-[var(--surface-soft)] px-4 py-3 text-xs font-black uppercase tracking-[0.14em] text-[var(--muted)]">
            <span>User</span>
            <span>Role</span>
            <span>Status</span>
            <span>Action</span>
          </div>
          <div v-for="user in filteredUsers.slice(0, 6)" :key="user.id" class="grid grid-cols-[minmax(0,1fr)_110px_110px_150px] items-center gap-3 border-b border-[var(--border)] px-4 py-4 last:border-b-0">
            <div>
              <b class="block text-sm text-[var(--text)]">{{ user.name || `User #${user.id}` }}</b>
              <span class="mt-1 block text-xs font-bold text-[var(--muted)]">{{ user.email || 'Không có email' }}</span>
            </div>
            <span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1 text-xs font-black text-[var(--primary)]">{{ user.role || 'user' }}</span>
            <span class="text-xs font-bold text-[var(--muted)]">{{ user.status || 'active' }}</span>
            <div class="flex gap-2">
              <router-link class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-2 text-xs font-black text-[var(--text)]" to="/admin/users">Xem</router-link>
              <button class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-2 text-xs font-black text-[var(--muted)] opacity-60" type="button" disabled>Khóa TODO</button>
            </div>
          </div>
          <div v-if="filteredUsers.length === 0" class="p-6 text-center text-sm font-bold text-[var(--muted)]">Chưa có dữ liệu user phù hợp.</div>
        </div>
      </article>

      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">VIP Packages</p>
            <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Quản lý gói VIP</h2>
          </div>
          <button class="btn-ghost opacity-70" type="button" disabled>Tạo gói TODO</button>
        </div>

        <div class="mt-5 grid gap-3">
          <article v-for="plan in vipPlans" :key="plan.id" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <h3 class="text-lg font-black text-[var(--text)]">{{ plan.name }}</h3>
                <p class="mt-1 text-sm text-[var(--muted)]">{{ plan.period }} • {{ plan.users }} user đang dùng</p>
              </div>
              <span class="rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--primary)]">{{ plan.price }}</span>
            </div>
            <p class="mt-3 text-sm leading-6 text-[var(--muted)]">{{ plan.features }}</p>
            <div class="mt-4 flex gap-2">
              <button class="rounded-full border border-[var(--border)] bg-[var(--surface)] px-3 py-2 text-xs font-black text-[var(--muted)] opacity-60" type="button" disabled>Sửa TODO</button>
              <button class="rounded-full border border-[var(--border)] bg-[var(--surface)] px-3 py-2 text-xs font-black text-[var(--muted)] opacity-60" type="button" disabled>Ẩn/hiện TODO</button>
            </div>
          </article>
        </div>
      </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-3">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] xl:col-span-2">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">System Statistics</p>
        <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Thống kê hệ thống</h2>
        <p class="mt-2 text-sm leading-6 text-[var(--muted)]">TODO: connect admin stats API. Hiện dùng dữ liệu tổng hợp từ API users/quizzes/quiz-attempts/rooms đang có.</p>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <div class="mb-3 flex items-center justify-between"><b class="text-sm text-[var(--text)]">Tỷ lệ role</b><span class="text-xs font-black text-[var(--primary)]">{{ users.length }} users</span></div>
            <div class="grid gap-3">
              <ProgressRow label="Normal" :value="rolePercent('user')" />
              <ProgressRow label="VIP" :value="rolePercent('vip')" />
              <ProgressRow label="Admin" :value="rolePercent('admin')" />
            </div>
          </div>

          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <div class="mb-3 flex items-center justify-between"><b class="text-sm text-[var(--text)]">Practice / Homework / Live</b><span class="text-xs font-black text-[var(--primary)]">Mix</span></div>
            <div class="grid gap-3">
              <ProgressRow label="Practice" :value="practicePercent" />
              <ProgressRow label="Homework" :value="homeworkPercent" />
              <ProgressRow label="Live" :value="0" />
            </div>
          </div>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <b class="text-sm text-[var(--text)]">Top quiz được làm nhiều</b>
            <div class="mt-3 grid gap-2">
              <div v-for="quiz in topQuizzes" :key="quiz.id" class="flex items-center justify-between gap-3 text-sm">
                <span class="font-bold text-[var(--muted)]">{{ quiz.title }}</span>
                <b class="text-[var(--primary)]">{{ quiz.attempts || 0 }}</b>
              </div>
              <p v-if="topQuizzes.length === 0" class="text-sm font-bold text-[var(--muted)]">Chưa có dữ liệu quiz.</p>
            </div>
          </div>
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <b class="text-sm text-[var(--text)]">Top room hoạt động</b>
            <div class="mt-3 grid gap-2">
              <div v-for="room in topRooms" :key="room.id" class="flex items-center justify-between gap-3 text-sm">
                <span class="font-bold text-[var(--muted)]">{{ room.name || `Room #${room.id}` }}</span>
                <b class="text-[var(--primary)]">{{ room.assignments_count ?? 0 }}</b>
              </div>
              <p v-if="topRooms.length === 0" class="text-sm font-bold text-[var(--muted)]">Chưa có dữ liệu room.</p>
            </div>
          </div>
        </div>
      </article>

      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Notifications</p>
        <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Gửi thông báo</h2>
        <p class="mt-2 text-sm leading-6 text-[var(--muted)]">TODO: connect notification API.</p>

        <div class="mt-5 grid gap-3">
          <input class="field" placeholder="Tiêu đề thông báo" disabled />
          <textarea class="field min-h-28" placeholder="Nội dung thông báo" disabled></textarea>
          <select class="field" disabled>
            <option>Tất cả user</option>
            <option>User thường</option>
            <option>User VIP</option>
            <option>Teacher/VIP</option>
          </select>
          <button class="btn-primary opacity-70" type="button" disabled>Chưa có API gửi thông báo</button>
        </div>
      </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Recent Activity</p>
        <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Hoạt động gần đây</h2>
        <div class="mt-5 grid gap-3">
          <div v-for="activity in recentActivities" :key="activity.id" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <b class="text-sm text-[var(--text)]">{{ activity.title }}</b>
                <p class="mt-1 text-xs font-bold text-[var(--muted)]">{{ activity.description }}</p>
              </div>
              <span class="text-xs font-black text-[var(--primary)]">{{ activity.type }}</span>
            </div>
          </div>
          <div v-if="recentActivities.length === 0" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-sm font-bold text-[var(--muted)]">TODO: connect activity feed API.</div>
        </div>
      </article>

      <aside class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Admin Navigation</p>
        <div class="mt-5 grid gap-3">
          <router-link v-for="item in adminLinks" :key="item.to" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 text-sm font-black text-[var(--text)] transition hover:border-[var(--border-strong)] hover:bg-[var(--chip-active)]" :to="item.to">{{ item.label }}</router-link>
        </div>
      </aside>
    </section>
  </section>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, ref } from 'vue'
import { attemptsApi, normalizeQuizCard, normalizeUser, quizzesApi, roomsApi, usersApi } from '@/services/api'

// TODO: connect admin stats API when backend exposes aggregated metrics.
// TODO: connect VIP package API when backend exposes package CRUD.
// TODO: connect notification API when backend supports admin announcements.
// TODO: connect recent activity API when backend exposes audit/activity feed.

const ProgressRow = defineComponent({
  props: {
    label: { type: String, required: true },
    value: { type: Number, default: 0 },
  },
  setup(props) {
    return () => h('div', [
      h('div', { class: 'mb-1 flex items-center justify-between text-xs font-black text-[var(--muted)]' }, [
        h('span', props.label),
        h('span', `${Math.round(props.value)}%`),
      ]),
      h('div', { class: 'h-2 overflow-hidden rounded-full bg-[var(--surface)]' }, [
        h('div', {
          class: 'h-full rounded-full bg-gradient-to-r from-[var(--primary)] to-[var(--accent)]',
          style: { width: `${Math.min(100, Math.max(0, props.value))}%` },
        }),
      ]),
    ])
  },
})

const users = ref([])
const quizzes = ref([])
const attempts = ref([])
const rooms = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const userSearch = ref('')
const roleFilter = ref('all')

const vipPlans = computed(() => [
  {
    id: 'free',
    name: 'Free',
    price: '0đ',
    period: 'Cơ bản',
    users: users.value.filter((user) => user.role === 'user').length,
    features: 'Practice quiz public, join room, làm homework được giao.',
  },
  {
    id: 'vip',
    name: 'VIP',
    price: '99.000đ',
    period: 'Theo tháng',
    users: users.value.filter((user) => user.role === 'vip').length,
    features: 'Tạo room, giao homework, mở live quiz, AI/OCR quota cao hơn.',
  },
])

const filteredUsers = computed(() => {
  const keyword = userSearch.value.trim().toLowerCase()
  return users.value.filter((user) => {
    const matchesRole = roleFilter.value === 'all' || user.role === roleFilter.value
    const matchesKeyword = !keyword || [user.name, user.email].join(' ').toLowerCase().includes(keyword)
    return matchesRole && matchesKeyword
  })
})

const totalHomework = computed(() => rooms.value.reduce((sum, room) => sum + Number(room.assignments_count || 0), 0))
const totalLiveSessions = computed(() => 0)
const vipRevenue = computed(() => users.value.filter((user) => user.role === 'vip').length * 99000)

const stats = computed(() => [
  { label: 'Tổng người dùng', value: users.value.length, hint: 'users API' },
  { label: 'User thường', value: users.value.filter((user) => user.role === 'user').length, hint: 'role=user' },
  { label: 'User VIP', value: users.value.filter((user) => user.role === 'vip').length, hint: 'role=vip' },
  { label: 'Tổng số Room', value: rooms.value.length, hint: 'rooms API' },
  { label: 'Tổng số Quiz', value: quizzes.value.length, hint: 'quizzes API' },
  { label: 'Lượt làm bài', value: attempts.value.length, hint: 'quiz_attempts' },
  { label: 'Homework đã giao', value: totalHomework.value, hint: 'assignments_count' },
  { label: 'Live session', value: totalLiveSessions.value, hint: 'TODO API' },
  { label: 'Doanh thu VIP', value: `${vipRevenue.value.toLocaleString('vi-VN')}đ`, hint: 'ước tính UI' },
  { label: 'User mới 7 ngày', value: recentUsersCount.value, hint: 'created_at nếu có' },
])

const recentUsersCount = computed(() => {
  const since = Date.now() - 7 * 24 * 60 * 60 * 1000
  return users.value.filter((user) => user.created_at && new Date(user.created_at).getTime() >= since).length
})

const rolePercent = (role) => {
  if (!users.value.length) return 0
  return (users.value.filter((user) => user.role === role).length / users.value.length) * 100
}

const practicePercent = computed(() => {
  const total = attempts.value.length + totalHomework.value + totalLiveSessions.value
  return total ? (attempts.value.length / total) * 100 : 0
})
const homeworkPercent = computed(() => {
  const total = attempts.value.length + totalHomework.value + totalLiveSessions.value
  return total ? (totalHomework.value / total) * 100 : 0
})

const topQuizzes = computed(() => [...quizzes.value].sort((a, b) => Number(b.attempts || 0) - Number(a.attempts || 0)).slice(0, 5))
const topRooms = computed(() => [...rooms.value].sort((a, b) => Number(b.assignments_count || 0) - Number(a.assignments_count || 0)).slice(0, 5))

const recentActivities = computed(() => [
  ...users.value.slice(0, 3).map((user) => ({
    id: `user-${user.id}`,
    type: 'user',
    title: 'User mới / cập nhật',
    description: `${user.name || user.email || `User #${user.id}`} • ${user.role || 'user'}`,
  })),
  ...rooms.value.slice(0, 2).map((room) => ({
    id: `room-${room.id}`,
    type: 'room',
    title: 'Room mới / hoạt động',
    description: `${room.name || `Room #${room.id}`} • ${room.assignments_count ?? 0} homework`,
  })),
  ...quizzes.value.slice(0, 2).map((quiz) => ({
    id: `quiz-${quiz.id}`,
    type: 'quiz',
    title: 'Quiz public/content',
    description: `${quiz.title} • ${quiz.questions || 0} câu`,
  })),
].slice(0, 7))

const adminLinks = [
  { label: 'Tổng quan', to: '/admin' },
  { label: 'Người dùng', to: '/admin/users' },
  { label: 'Gói VIP', to: '/admin/payments' },
  { label: 'Thống kê', to: '/admin/reports' },
  { label: 'Nội dung / Quiz', to: '/admin/questions' },
  { label: 'Cài đặt hệ thống', to: '/admin/settings' },
]

const loadAdminDashboard = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const [userData, quizData, attemptData, roomData] = await Promise.all([
      usersApi.list({ per_page: 100 }).catch(() => []),
      quizzesApi.list({ per_page: 100 }).catch(() => []),
      attemptsApi.list({ per_page: 100 }).catch(() => []),
      roomsApi.list().catch(() => []),
    ])
    users.value = userData.map(normalizeUser)
    quizzes.value = quizData.map(normalizeQuizCard)
    attempts.value = attemptData
    rooms.value = roomData
  } catch (error) {
    errorMessage.value = `Không tải được admin dashboard: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

onMounted(loadAdminDashboard)
</script>
