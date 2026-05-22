<template>
  <section class="grid gap-6 py-8 xl:grid-cols-[minmax(0,1fr)_360px]">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl sm:p-8">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>

      <div class="relative z-10">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Homework Result</p>
        <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)] sm:text-5xl">{{ assignment?.title || 'Kết quả homework' }}</h1>
        <p class="mt-4 max-w-2xl text-sm leading-7 text-[var(--muted)]">{{ result ? 'Bài làm đã được nộp thành công.' : 'Đã nộp bài thành công. API hiện chưa có endpoint lấy lại kết quả submission sau khi refresh.' }}</p>
      </div>

      <div v-if="errorMessage" class="relative z-10 mt-6 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

      <div class="relative z-10 mt-8 grid gap-3 sm:grid-cols-4">
        <div class="rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-5">
          <p class="text-sm font-bold text-[var(--muted)]">Điểm</p>
          <b class="mt-2 block text-3xl font-black text-[var(--text)]">{{ result?.score ?? '-' }}</b>
        </div>
        <div class="rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-5">
          <p class="text-sm font-bold text-[var(--muted)]">Đúng</p>
          <b class="mt-2 block text-3xl font-black text-emerald-300">{{ result?.correct_count ?? '-' }}</b>
        </div>
        <div class="rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-5">
          <p class="text-sm font-bold text-[var(--muted)]">Sai</p>
          <b class="mt-2 block text-3xl font-black text-rose-300">{{ result?.wrong_count ?? '-' }}</b>
        </div>
        <div class="rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-5">
          <p class="text-sm font-bold text-[var(--muted)]">Trạng thái</p>
          <b class="mt-2 block text-2xl font-black text-[var(--text)]">{{ result?.status || progress?.status || 'submitted' }}</b>
        </div>
      </div>

      <div class="relative z-10 mt-8 flex flex-wrap gap-3">
        <router-link class="btn-primary" :to="`/rooms/${route.params.roomId}/homework`">Về danh sách homework</router-link>
        <router-link class="btn-ghost" :to="`/rooms/${route.params.roomId}/assignments/${route.params.assignmentId}`">Chi tiết assignment</router-link>
      </div>
    </article>

    <aside class="grid content-start gap-5">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Submission</p>
        <div class="mt-5 grid gap-3">
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-bold text-[var(--muted)]">Submission ID</p>
            <b class="mt-1 block text-sm text-[var(--text)]">#{{ result?.id || progress?.submission_id || '-' }}</b>
          </div>
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-bold text-[var(--muted)]">Submitted at</p>
            <b class="mt-1 block text-sm text-[var(--text)]">{{ formatDate(result?.submitted_at || progress?.submitted_at) }}</b>
          </div>
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-bold text-[var(--muted)]">Show result mode</p>
            <b class="mt-1 block text-sm text-[var(--text)]">{{ assignment?.show_result_mode || '-' }}</b>
          </div>
        </div>
      </article>
    </aside>

    <article v-if="canShowAnswers && result?.answers?.length" class="xl:col-span-2 rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Answers</p>
      <div class="mt-5 grid gap-4">
        <div v-for="(answer, index) in result.answers" :key="answer.id" class="rounded-2xl border p-4" :class="answer.is_correct ? 'border-emerald-500/30 bg-emerald-500/10' : 'border-rose-500/30 bg-rose-500/10'">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <p class="text-xs font-black text-[var(--primary)]">Câu {{ index + 1 }} • {{ answer.score ?? 0 }} điểm</p>
              <h3 class="mt-2 text-base font-black leading-7 text-[var(--text)]">{{ answer.question || `Question #${answer.question_id}` }}</h3>
              <p class="mt-2 text-sm font-bold text-[var(--muted)]">Đã chọn: {{ answer.answer || formatSelectedIds(answer.selected_answer_ids) }}</p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-black" :class="answer.is_correct ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300'">{{ answer.is_correct ? 'Đúng' : 'Sai' }}</span>
          </div>
        </div>
      </div>
    </article>

    <article v-else-if="result" class="xl:col-span-2 rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 text-sm font-bold text-[var(--muted)] shadow-[var(--shadow-card)]">
      Kết quả đã được ghi nhận. Chi tiết đáp án chưa hiển thị vì chế độ show result của assignment chưa cho phép hoặc API không trả danh sách đáp án.
    </article>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { homeworkAssignmentsApi, homeworkProgressStorage, normalizeAssignment } from '@/services/api'

const route = useRoute()
const assignment = ref(null)
const result = ref(null)
const progress = ref(null)
const errorMessage = ref('')

const canShowAnswers = computed(() => {
  const mode = assignment.value?.show_result_mode || 'after_submit'
  if (mode === 'manual') return false
  if (mode === 'after_deadline') {
    const deadline = assignment.value?.deadline_at ? new Date(assignment.value.deadline_at).getTime() : 0
    return Boolean(deadline && deadline <= Date.now())
  }
  return ['immediately', 'after_submit'].includes(mode)
})

const formatDate = (value) => {
  if (!value) return 'Chưa có thời gian'
  return new Date(value).toLocaleString('vi-VN')
}

const formatSelectedIds = (ids) => Array.isArray(ids) && ids.length ? ids.join(', ') : 'Không có dữ liệu'

const loadResult = async () => {
  progress.value = homeworkProgressStorage.get(route.params.assignmentId)
  const cachedResult = sessionStorage.getItem(`quizflex_assignment_result_${route.params.assignmentId}`)
  result.value = cachedResult ? JSON.parse(cachedResult) : progress.value?.result || null

  try {
    const data = await homeworkAssignmentsApi.get(route.params.roomId, route.params.assignmentId)
    assignment.value = normalizeAssignment(data)
  } catch (error) {
    errorMessage.value = `Không tải được thông tin assignment: ${error.message}`
  }
}

onMounted(loadResult)
</script>
