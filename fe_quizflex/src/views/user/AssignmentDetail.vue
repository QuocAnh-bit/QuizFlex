<template>
  <section class="grid gap-6 py-8 xl:grid-cols-[minmax(0,1fr)_360px]">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl sm:p-8">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>

      <div v-if="isLoading" class="relative z-10 text-sm font-bold text-[var(--muted)]">Đang tải chi tiết homework...</div>
      <div v-if="errorMessage" class="relative z-10 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

      <template v-if="assignment">
        <div class="relative z-10">
          <div class="mb-5 flex flex-wrap items-center gap-2">
            <span class="rounded-full border px-3 py-1 text-xs font-black" :class="statusClass">{{ statusLabel }}</span>
            <span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1 text-xs font-black text-[var(--muted)]">{{ assignment.quiz?.category || 'Quiz' }}</span>
            <span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1 text-xs font-black text-[var(--muted)]">{{ assignment.quiz?.difficulty || 'Vừa' }}</span>
          </div>

          <h1 class="text-4xl font-black leading-tight tracking-[-0.06em] text-[var(--text)] sm:text-5xl">{{ assignment.title }}</h1>
          <p class="mt-5 max-w-3xl text-base font-medium leading-8 text-[var(--muted)]">{{ assignment.description || 'Assignment này chưa có mô tả.' }}</p>

          <div class="mt-8 grid gap-3 sm:grid-cols-3">
            <div class="rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-5">
              <p class="text-sm font-bold text-[var(--muted)]">Số câu</p>
              <b class="mt-2 block text-3xl font-black text-[var(--text)]">{{ assignment.questionCount }}</b>
            </div>
            <div class="rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-5">
              <p class="text-sm font-bold text-[var(--muted)]">Thời lượng</p>
              <b class="mt-2 block text-2xl font-black text-[var(--text)]">{{ assignment.durationLabel }}</b>
            </div>
            <div class="rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-5">
              <p class="text-sm font-bold text-[var(--muted)]">Số lần tối đa</p>
              <b class="mt-2 block text-2xl font-black text-[var(--text)]">{{ assignment.maxAttemptsLabel }}</b>
            </div>
          </div>

          <div class="mt-8 rounded-[2rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-[var(--primary)]">Quiz</p>
            <h2 class="mt-2 text-2xl font-black text-[var(--text)]">{{ assignment.quizTitle }}</h2>
            <p class="mt-2 text-sm leading-6 text-[var(--muted)]">{{ assignment.quizDescription || 'Quiz chưa có mô tả.' }}</p>
          </div>

          <div class="mt-8 flex flex-wrap gap-3">
            <button v-if="canStart" class="btn-primary disabled:cursor-not-allowed disabled:opacity-60" type="button" :disabled="isStarting" @click="startAssignment">
              {{ isStarting ? 'Đang bắt đầu...' : progress?.status === 'in_progress' ? 'Tiếp tục làm' : 'Start Assignment' }}
            </button>
            <router-link v-if="progress?.status === 'submitted' && progress?.result" class="btn-primary" :to="`/rooms/${route.params.roomId}/assignments/${assignment.id}/result`">Xem kết quả</router-link>
            <router-link class="btn-ghost" :to="`/rooms/${route.params.roomId}/homework`">Quay lại homework</router-link>
          </div>
        </div>
      </template>
    </article>

    <aside class="grid content-start gap-5">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Thông tin</p>
        <div class="mt-5 grid gap-3">
          <InfoRow label="Bắt đầu" :value="formatDate(assignment?.starts_at)" />
          <InfoRow label="Deadline" :value="formatDate(assignment?.deadline_at)" />
          <InfoRow label="Show result" :value="assignment?.show_result_mode || 'after_submit'" />
          <InfoRow label="Submission hiện tại" :value="submissionLabel" />
        </div>
      </article>
    </aside>
  </section>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { homeworkAssignmentsApi, homeworkProgressStorage, normalizeAssignment } from '@/services/api'

const InfoRow = defineComponent({
  props: {
    label: { type: String, required: true },
    value: { type: [String, Number], default: '-' },
  },
  setup(props) {
    return () => h('div', { class: 'rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4' }, [
      h('p', { class: 'text-xs font-bold text-[var(--muted)]' }, props.label),
      h('b', { class: 'mt-1 block text-sm text-[var(--text)]' }, props.value || '-'),
    ])
  },
})

const route = useRoute()
const router = useRouter()
const assignment = ref(null)
const progress = ref(null)
const isLoading = ref(false)
const isStarting = ref(false)
const errorMessage = ref('')

const nowMs = () => Date.now()
const dateMs = (value) => (value ? new Date(value).getTime() : null)
const isBeforeStart = computed(() => {
  const startsAt = dateMs(assignment.value?.starts_at)
  return startsAt && startsAt > nowMs()
})
const isPastDeadline = computed(() => {
  const deadlineAt = dateMs(assignment.value?.deadline_at)
  return deadlineAt && deadlineAt < nowMs()
})

const statusLabel = computed(() => {
  if (progress.value?.status === 'submitted') return 'Đã nộp'
  if (progress.value?.status === 'late') return 'Hết hạn'
  if (isPastDeadline.value) return 'Hết hạn'
  if (isBeforeStart.value) return 'Chưa đến giờ'
  if (progress.value?.status === 'in_progress') return 'Đang làm'
  if (assignment.value?.status !== 'published') return 'Chưa mở'
  return 'Chưa bắt đầu'
})

const statusClass = computed(() => {
  if (statusLabel.value === 'Đã nộp') return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300'
  if (statusLabel.value === 'Đang làm') return 'border-amber-500/30 bg-amber-500/10 text-amber-300'
  if (statusLabel.value === 'Hết hạn') return 'border-rose-500/30 bg-rose-500/10 text-rose-300'
  if (statusLabel.value === 'Chưa đến giờ' || statusLabel.value === 'Chưa mở') return 'border-slate-500/30 bg-slate-500/10 text-slate-300'
  return 'border-[var(--border-strong)] bg-[var(--chip-active)] text-[var(--primary)]'
})

const canStart = computed(() => {
  if (!assignment.value) return false
  if (progress.value?.status === 'submitted' || progress.value?.status === 'late') return false
  return assignment.value.status === 'published' && !isBeforeStart.value && !isPastDeadline.value
})

const submissionLabel = computed(() => {
  if (!progress.value) return 'Chưa bắt đầu'
  if (progress.value.status === 'in_progress') return `Đang làm #${progress.value.submission_id || '-'}`
  if (progress.value.status === 'submitted') return 'Đã nộp'
  return progress.value.status
})

const formatDate = (value) => {
  if (!value) return 'Không giới hạn'
  return new Date(value).toLocaleString('vi-VN')
}

const loadAssignment = async () => {
  isLoading.value = true
  errorMessage.value = ''
  progress.value = homeworkProgressStorage.get(route.params.assignmentId)

  try {
    const data = await homeworkAssignmentsApi.get(route.params.roomId, route.params.assignmentId)
    assignment.value = normalizeAssignment(data)
    const submission = await homeworkAssignmentsApi.getMySubmission(route.params.roomId, route.params.assignmentId).catch(() => null)
    if (submission?.id) {
      progress.value = {
        ...(progress.value || {}),
        submission_id: submission.id,
        status: submission.status,
        submitted_at: submission.submitted_at,
        result: submission.result_available === false ? null : submission,
      }
      homeworkProgressStorage.set(route.params.assignmentId, progress.value)
    }
  } catch (error) {
    if (error.status === 401) {
      errorMessage.value = 'Bạn cần đăng nhập để xem assignment.'
      return
    }
    if (error.status === 403) {
      errorMessage.value = 'Bạn không có quyền xem assignment này.'
      return
    }
    errorMessage.value = `Không tải được assignment: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

const startAssignment = async () => {
  if (!assignment.value || !canStart.value) return

  isStarting.value = true
  errorMessage.value = ''

  try {
    const payload = await homeworkAssignmentsApi.start(route.params.roomId, route.params.assignmentId)
    const submission = payload.submission
    if (!submission?.id) {
      errorMessage.value = 'API start assignment không trả về submission id.'
      return
    }

    sessionStorage.setItem(`quizflex_assignment_take_${route.params.assignmentId}`, JSON.stringify(payload))
    homeworkProgressStorage.set(route.params.assignmentId, {
      submission_id: submission.id,
      status: submission.status || 'in_progress',
      attempt_no: submission.attempt_no,
      started_at: submission.started_at,
    })
    router.push(`/rooms/${route.params.roomId}/assignments/${route.params.assignmentId}/do`)
  } catch (error) {
    if (error.status === 403) {
      errorMessage.value = error.message || 'Assignment chưa mở, quá hạn hoặc bạn đã hết số lần làm.'
      return
    }
    errorMessage.value = `Không bắt đầu được assignment: ${error.message}`
  } finally {
    isStarting.value = false
  }
}

onMounted(loadAssignment)
</script>
