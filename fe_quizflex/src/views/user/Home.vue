<template>
  <GuestHome v-if="isGuest" />
  <AdminDashboard v-else-if="isAdmin" />

  <section v-else class="grid gap-6 py-8">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl sm:p-8">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">QuizFlex Dashboard</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)] sm:text-5xl">{{ dashboardTitle }}</h1>
          <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Practice dùng quiz public và lưu vào quiz_attempts. Homework và Live luôn đi qua Room.</p>
        </div>
        <div class="flex flex-wrap gap-3">
          <router-link class="btn-primary" to="/quizzes">Vào thư viện đề</router-link>
          <router-link class="btn-ghost" to="/rooms">My Rooms</router-link>
        </div>
      </div>
    </article>

    <div v-if="isLoading" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">Đang tải dashboard...</div>
    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

    <template v-if="!isLoading">
      <section v-if="isVip" class="grid gap-6">
        <div class="grid gap-4 lg:grid-cols-3">
          <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">My Rooms</p>
            <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Quản lý room</h2>
            <p class="mt-3 text-sm leading-6 text-[var(--muted)]">Tạo room, mời thành viên và quản lý homework trong từng room.</p>
            <div class="mt-5 flex flex-wrap gap-3">
              <router-link class="btn-primary" to="/rooms">Tạo Room</router-link>
              <router-link class="btn-ghost" to="/rooms">Danh sách Room</router-link>
            </div>
          </article>

          <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Homework Management</p>
            <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Giao bài trong Room</h2>
            <p class="mt-3 text-sm leading-6 text-[var(--muted)]">Chọn một room để giao quiz làm homework, đặt deadline và số lần làm.</p>
            <router-link class="btn-primary mt-5" :to="firstRoomLink">Giao bài</router-link>
          </article>

          <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Live Quiz</p>
            <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Realtime session</h2>
            <p class="mt-3 text-sm leading-6 text-[var(--muted)]">Live UI đã đặt trong Room. Realtime/API live session đang để trạng thái TODO.</p>
            <router-link class="btn-ghost mt-5" :to="firstRoomLink">Tạo Live Quiz</router-link>
          </article>
        </div>
      </section>

      <section v-else class="grid gap-4 lg:grid-cols-3">
        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Practice</p>
          <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Luyện tập đề public</h2>
          <p class="mt-3 text-sm leading-6 text-[var(--muted)]">Chọn quiz public, làm bài tự do, nộp bài và xem kết quả practice.</p>
          <router-link class="btn-primary mt-5" to="/quizzes">Vào thư viện đề</router-link>
        </article>

        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Room được mời</p>
          <h2 class="mt-2 text-2xl font-black text-[var(--text)]">My Rooms</h2>
          <p class="mt-3 text-sm leading-6 text-[var(--muted)]">Vào room được mời để xem homework và live quiz nếu giáo viên mở.</p>
          <div class="mt-5 flex flex-wrap gap-3">
            <router-link class="btn-primary" to="/rooms">Xem Room</router-link>
            <router-link class="btn-ghost" to="/join-room">Join room</router-link>
          </div>
        </article>

        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">VIP Actions</p>
          <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Tạo Room và Live</h2>
          <p class="mt-3 text-sm leading-6 text-[var(--muted)]">Tài khoản thường có thể xem các action này, khi bấm sẽ chuyển đến trang nâng cấp.</p>
          <div class="mt-5 grid gap-3">
            <button class="btn-primary w-full" type="button" @click="requireUpgrade">Tạo Room</button>
            <button class="btn-ghost w-full" type="button" @click="requireUpgrade">Giao Homework</button>
            <button class="btn-ghost w-full" type="button" @click="requireUpgrade">Mở Live Quiz</button>
          </div>
        </article>
      </section>

      <section class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_420px]">
        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
          <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">{{ isVip ? 'Room Management' : 'My Rooms' }}</p>
              <h2 class="mt-2 text-2xl font-black text-[var(--text)]">{{ isVip ? 'Room đang quản lý/tham gia' : 'Room được mời/tham gia' }}</h2>
            </div>
            <router-link class="btn-ghost" to="/rooms">Xem tất cả</router-link>
          </div>

          <div class="mt-5 grid gap-3">
            <router-link v-for="room in rooms.slice(0, 4)" :key="room.id" :to="`/rooms/${room.id}`" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 transition hover:border-[var(--border-strong)] hover:bg-[var(--chip-active)]">
              <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <h3 class="text-lg font-black text-[var(--text)]">{{ room.name || `Room #${room.id}` }}</h3>
                  <p class="mt-1 text-sm text-[var(--muted)]">{{ room.assignments_count ?? 0 }} homework • {{ room.members_count ?? 0 }} thành viên</p>
                </div>
                <span class="rounded-full border border-[var(--border)] bg-[var(--surface)] px-3 py-1 text-xs font-black text-[var(--primary)]">{{ room.code }}</span>
              </div>
            </router-link>
            <div v-if="rooms.length === 0" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-sm font-bold text-[var(--muted)]">Chưa có room. Join room bằng mã hoặc tạo room nếu là VIP.</div>
          </div>
        </article>

        <aside class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Practice</p>
          <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Quiz public nổi bật</h2>
          <div class="mt-5 grid gap-3">
            <router-link v-for="quiz in publicQuizzes.slice(0, 4)" :key="quiz.id" :to="`/quizzes/${quiz.id}`" class="flex items-center gap-3 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-sm font-bold text-[var(--muted)] transition hover:border-[var(--border-strong)] hover:text-[var(--text)]">
              <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-[var(--primary)] to-[var(--primary-2)] text-xs font-black text-white">{{ quiz.icon }}</span>
              <span>{{ quiz.title }}</span>
            </router-link>
            <div v-if="publicQuizzes.length === 0" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-sm font-bold text-[var(--muted)]">Chưa có quiz public.</div>
          </div>
        </aside>
      </section>

      <section class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">{{ isVip ? 'Homework Management' : 'Homework' }}</p>
            <h2 class="mt-2 text-2xl font-black text-[var(--text)]">{{ isVip ? 'Bài đã giao gần đây' : 'Bài được giao gần đây' }}</h2>
          </div>
          <router-link class="btn-ghost" :to="firstRoomLink">Vào Room</router-link>
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-2">
          <router-link v-for="assignment in recentAssignments" :key="`${assignment.room_id}-${assignment.id}`" :to="`/rooms/${assignment.room_id}/assignments/${assignment.id}`" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 transition hover:border-[var(--border-strong)] hover:bg-[var(--chip-active)]">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-black" :class="statusClass(assignment)">{{ statusLabel(assignment) }}</span>
                <h3 class="mt-3 text-xl font-black text-[var(--text)]">{{ assignment.title }}</h3>
                <p class="mt-1 text-sm text-[var(--muted)]">{{ assignment.quizTitle }} • {{ assignment.durationLabel }}</p>
              </div>
              <span class="text-xs font-black text-[var(--muted)]">{{ formatDate(assignment.deadline_at) }}</span>
            </div>
          </router-link>
          <div v-if="recentAssignments.length === 0" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-sm font-bold text-[var(--muted)]">Chưa có homework gần đây.</div>
        </div>
      </section>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import AdminDashboard from '@/views/user/AdminDashboard.vue'
import GuestHome from '@/views/user/GuestHome.vue'
import {
  currentUserStorage,
  homeworkAssignmentsApi,
  homeworkProgressStorage,
  normalizeAssignment,
  normalizeQuizCard,
  quizzesApi,
  roomsApi,
} from '@/services/api'

const router = useRouter()
const rooms = ref([])
const publicQuizzes = ref([])
const recentAssignments = ref([])
const progressMap = ref({})
const isLoading = ref(false)
const errorMessage = ref('')

const currentUser = computed(() => currentUserStorage.get() || {})
const isGuest = computed(() => !currentUser.value?.id)
const userRole = computed(() => String(currentUser.value.role || 'user').toLowerCase())
const isAdmin = computed(() => userRole.value === 'admin')
const isVip = computed(() => userRole.value === 'vip')
const dashboardTitle = computed(() => isVip.value ? 'Quản lý Room, Homework và Live Quiz' : 'Practice và Homework của tôi')
const firstRoomLink = computed(() => rooms.value[0]?.id ? `/rooms/${rooms.value[0].id}` : '/rooms')

const requireUpgrade = () => {
  window.alert('Tạo Room, Giao Homework và Live Quiz là tính năng VIP. Vui lòng nâng cấp để sử dụng.')
  router.push('/upgrade')
}

const dateMs = (value) => (value ? new Date(value).getTime() : null)
const progressFor = (assignmentId) => progressMap.value[String(assignmentId)] || null

const statusLabel = (assignment) => {
  const progress = progressFor(assignment.id)
  if (progress?.status === 'submitted') return 'Đã nộp'
  if (progress?.status === 'in_progress') return 'Đang làm'
  if (assignment.deadline_at && dateMs(assignment.deadline_at) < Date.now()) return 'Quá hạn'
  return 'Chưa làm'
}

const statusClass = (assignment) => {
  const label = statusLabel(assignment)
  if (label === 'Đã nộp') return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300'
  if (label === 'Đang làm') return 'border-amber-500/30 bg-amber-500/10 text-amber-300'
  if (label === 'Quá hạn') return 'border-rose-500/30 bg-rose-500/10 text-rose-300'
  return 'border-[var(--border-strong)] bg-[var(--chip-active)] text-[var(--primary)]'
}

const formatDate = (value) => {
  if (!value) return 'Không deadline'
  return new Date(value).toLocaleDateString('vi-VN')
}

const loadRecentAssignments = async (roomList) => {
  const assignmentGroups = await Promise.all(
    roomList.slice(0, 6).map(async (room) => {
      try {
        const data = await homeworkAssignmentsApi.list(room.id)
        return data.map((assignment) => normalizeAssignment({ ...assignment, room_id: room.id }))
      } catch {
        return []
      }
    }),
  )
  recentAssignments.value = assignmentGroups.flat().slice(0, 6)
}

const loadDashboard = async () => {
  isLoading.value = true
  errorMessage.value = ''
  progressMap.value = homeworkProgressStorage.getAll()

  try {
    const [roomData, quizData] = await Promise.all([
      roomsApi.list().catch(() => []),
      quizzesApi.list({ visibility: 'public', per_page: 12 }).catch(() => []),
    ])
    rooms.value = roomData
    publicQuizzes.value = quizData.map(normalizeQuizCard)
    await loadRecentAssignments(roomData)
  } catch (error) {
    errorMessage.value = `Không tải được dashboard: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  if (!isGuest.value && !isAdmin.value) loadDashboard()
})
</script>
