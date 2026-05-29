<template>
  <section class="grid gap-6 py-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <router-link class="btn-ghost" :to="`/homework-rooms/${roomId}`">Quay lại room</router-link>
      <button class="btn-ghost" type="button" :disabled="isLoading" @click="loadAttempts">
        {{ isLoading ? 'Đang tải...' : 'Tải lại' }}
      </button>
    </div>

    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Homework Results</p>
        <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Bài nộp của thành viên</h1>
        <p class="mt-3 max-w-3xl text-sm leading-7 text-[var(--muted)]">
          Theo dõi trạng thái, điểm và thời gian nộp bài của các thành viên trong assignment này.
        </p>
      </div>
    </article>

    <div v-if="isLoading" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">
      Đang tải danh sách bài nộp...
    </div>

    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">
      {{ errorMessage }}
    </div>

    <article v-if="!isLoading && !errorMessage" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Attempts</p>
          <h2 class="mt-1 text-2xl font-black tracking-[-0.04em] text-[var(--text)]">Danh sách bài nộp</h2>
        </div>
        <span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1 text-xs font-black text-[var(--muted)]">
          {{ attempts.length }}
        </span>
      </div>

      <div v-if="attempts.length" class="mt-5 overflow-hidden rounded-[1.5rem] border border-[var(--border)]">
        <div class="hidden grid-cols-[minmax(220px,1.4fr)_120px_140px_130px_160px_160px] gap-3 border-b border-[var(--border)] bg-[var(--surface-soft)] px-4 py-3 text-xs font-black uppercase tracking-[0.16em] text-[var(--muted)] lg:grid">
          <span>Người làm</span>
          <span>Trạng thái</span>
          <span>Điểm</span>
          <span>Số câu đúng</span>
          <span>Bắt đầu</span>
          <span>Nộp bài</span>
        </div>

        <article
          v-for="attempt in attempts"
          :key="attempt.id"
          class="grid gap-3 border-b border-[var(--border)] px-4 py-4 last:border-b-0 lg:grid-cols-[minmax(220px,1.4fr)_120px_140px_130px_160px_160px] lg:items-center"
        >
          <div>
            <h3 class="font-black text-[var(--text)]">{{ attempt.user?.name || `User #${attempt.user_id}` }}</h3>
            <p class="mt-1 text-xs font-bold text-[var(--muted)]">{{ attempt.user?.email || 'Chưa có email' }}</p>
          </div>
          <span class="w-fit rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--primary)]">{{ attempt.status || '-' }}</span>
          <p class="text-sm font-black text-[var(--text)]">{{ formatScore(attempt) }}</p>
          <p class="text-sm font-black text-[var(--text)]">{{ formatCorrectCount(attempt) }}</p>
          <p class="text-sm font-bold text-[var(--muted)]">{{ formatDateTime(attempt.started_at) }}</p>
          <p class="text-sm font-bold text-[var(--muted)]">{{ formatDateTime(attempt.submitted_at || attempt.finished_at) }}</p>
        </article>
      </div>

      <div v-else class="mt-5 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-10 text-center">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Empty</p>
        <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-[var(--text)]">Chưa có bài nộp</h3>
        <p class="mt-3 text-sm leading-7 text-[var(--muted)]">Khi thành viên bắt đầu hoặc nộp bài, dữ liệu sẽ xuất hiện tại đây.</p>
      </div>
    </article>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { homeworkApi } from '@/services/api'

const route = useRoute()
const roomId = computed(() => route.params.roomId)
const assignmentId = computed(() => route.params.assignmentId)

const attempts = ref([])
const isLoading = ref(false)
const errorMessage = ref('')

const formatDateTime = (value) => {
  if (!value) return '-'
  return new Date(value).toLocaleString('vi-VN')
}

const formatScore = (attempt) => {
  if (attempt.score === null || attempt.score === undefined) return '-'
  if (attempt.total_points === null || attempt.total_points === undefined) return String(attempt.score)
  return `${attempt.score}/${attempt.total_points}`
}

const formatCorrectCount = (attempt) => {
  if (attempt.correct_count === null || attempt.correct_count === undefined) return '-'
  if (attempt.total_questions === null || attempt.total_questions === undefined) return String(attempt.correct_count)
  return `${attempt.correct_count}/${attempt.total_questions}`
}

const loadAttempts = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    attempts.value = await homeworkApi.getRoomAssignmentAttempts(assignmentId.value)
  } catch (error) {
    errorMessage.value = error.message || 'Bạn không có quyền xem danh sách bài nộp.'
  } finally {
    isLoading.value = false
  }
}

onMounted(loadAttempts)
</script>
