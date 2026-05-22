<template>
  <section class="grid gap-6 py-8">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl sm:p-8">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Room Homework</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)] sm:text-5xl">{{ room?.name || `Room #${route.params.roomId}` }}</h1>
          <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">{{ room?.description || 'Danh sách bài được giao trong room của bạn.' }}</p>
        </div>
        <button class="btn-ghost" type="button" :disabled="isLoading" @click="loadPage">Tải lại</button>
      </div>
    </article>

    <div v-if="isLoading" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">Đang tải homework...</div>
    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

    <div v-if="!isLoading && assignments.length" class="grid gap-4 lg:grid-cols-2">
      <article v-for="assignment in assignments" :key="assignment.id" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <span class="inline-flex rounded-full border px-3 py-1 text-xs font-black" :class="statusClass(assignment)">{{ statusLabel(assignment) }}</span>
            <h2 class="mt-3 text-2xl font-black text-[var(--text)]">{{ assignment.title }}</h2>
            <p class="mt-2 line-clamp-2 text-sm leading-6 text-[var(--muted)]">{{ assignment.description || 'Chưa có mô tả.' }}</p>
          </div>
          <span class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-3 text-sm font-black text-[var(--primary)]">{{ assignment.quizTitle }}</span>
        </div>

        <div class="mt-5 grid gap-3 sm:grid-cols-2">
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-bold text-[var(--muted)]">Bắt đầu</p>
            <b class="mt-1 block text-sm text-[var(--text)]">{{ formatDate(assignment.starts_at) }}</b>
          </div>
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-bold text-[var(--muted)]">Deadline</p>
            <b class="mt-1 block text-sm text-[var(--text)]">{{ formatDate(assignment.deadline_at) }}</b>
          </div>
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-bold text-[var(--muted)]">Thời lượng</p>
            <b class="mt-1 block text-sm text-[var(--text)]">{{ assignment.durationLabel }}</b>
          </div>
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-bold text-[var(--muted)]">Số lần tối đa</p>
            <b class="mt-1 block text-sm text-[var(--text)]">{{ assignment.maxAttemptsLabel }}</b>
          </div>
        </div>

        <div class="mt-5 flex flex-wrap gap-3">
          <router-link class="btn-ghost" :to="`/rooms/${route.params.roomId}/assignments/${assignment.id}`">Xem chi tiết</router-link>
          <router-link v-if="canWork(assignment)" class="btn-primary" :to="`/rooms/${route.params.roomId}/assignments/${assignment.id}/do`">
            {{ progressFor(assignment.id)?.status === 'in_progress' ? 'Tiếp tục làm' : 'Bắt đầu làm' }}
          </router-link>
          <router-link v-if="canViewResult(assignment)" class="btn-ghost" :to="`/rooms/${route.params.roomId}/assignments/${assignment.id}/result`">Xem kết quả</router-link>
        </div>
      </article>
    </div>

    <div v-if="!isLoading && !assignments.length && !errorMessage" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center shadow-[var(--shadow-card)]">
      <h3 class="text-2xl font-black text-[var(--text)]">Chưa có homework</h3>
      <p class="mt-2 text-sm text-[var(--muted)]">Room này chưa có bài được giao hoặc bạn chưa có quyền xem danh sách.</p>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { homeworkAssignmentsApi, homeworkProgressStorage, normalizeAssignment, roomsApi } from '@/services/api'

const route = useRoute()
const room = ref(null)
const assignments = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const progressMap = ref({})

const nowMs = () => Date.now()
const dateMs = (value) => (value ? new Date(value).getTime() : null)
const isBeforeStart = (assignment) => {
  const startsAt = dateMs(assignment.starts_at)
  return startsAt && startsAt > nowMs()
}
const isPastDeadline = (assignment) => {
  const deadlineAt = dateMs(assignment.deadline_at)
  return deadlineAt && deadlineAt < nowMs()
}
const progressFor = (assignmentId) => progressMap.value[String(assignmentId)] || null

const formatDate = (value) => {
  if (!value) return 'Không giới hạn'
  return new Date(value).toLocaleString('vi-VN')
}

const statusLabel = (assignment) => {
  const progress = progressFor(assignment.id)
  if (progress?.status === 'submitted') return 'Đã nộp'
  if (progress?.status === 'late') return 'Hết hạn'
  if (isPastDeadline(assignment)) return 'Hết hạn'
  if (isBeforeStart(assignment)) return 'Chưa đến giờ'
  if (progress?.status === 'in_progress') return 'Đang làm'
  if (assignment.status !== 'published') return 'Chưa mở'
  return 'Chưa bắt đầu'
}

const statusClass = (assignment) => {
  const label = statusLabel(assignment)
  if (label === 'Đã nộp') return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300'
  if (label === 'Đang làm') return 'border-amber-500/30 bg-amber-500/10 text-amber-300'
  if (label === 'Hết hạn') return 'border-rose-500/30 bg-rose-500/10 text-rose-300'
  if (label === 'Chưa đến giờ' || label === 'Chưa mở') return 'border-slate-500/30 bg-slate-500/10 text-slate-300'
  return 'border-[var(--border-strong)] bg-[var(--chip-active)] text-[var(--primary)]'
}

const canWork = (assignment) => {
  const progress = progressFor(assignment.id)
  if (progress?.status === 'submitted' || progress?.status === 'late') return false
  return assignment.status === 'published' && !isBeforeStart(assignment) && !isPastDeadline(assignment)
}

const canViewResult = (assignment) => {
  const progress = progressFor(assignment.id)
  return progress?.status === 'submitted' && Boolean(progress?.result)
}

const loadPage = async () => {
  isLoading.value = true
  errorMessage.value = ''
  progressMap.value = homeworkProgressStorage.getAll()

  try {
    const [roomData, assignmentData] = await Promise.all([
      roomsApi.get(route.params.roomId).catch(() => null),
      homeworkAssignmentsApi.list(route.params.roomId),
    ])
    room.value = roomData
    assignments.value = assignmentData.map(normalizeAssignment)
  } catch (error) {
    if (error.status === 401) {
      errorMessage.value = 'Bạn cần đăng nhập để xem homework trong room.'
      return
    }
    if (error.status === 403) {
      errorMessage.value = 'Bạn không có quyền truy cập room hoặc danh sách homework này.'
      return
    }
    assignments.value = []
    errorMessage.value = `Không tải được homework: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

onMounted(loadPage)
</script>
