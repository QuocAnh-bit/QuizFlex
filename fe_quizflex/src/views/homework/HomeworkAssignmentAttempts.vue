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
        <div class="hidden grid-cols-[minmax(220px,1.4fr)_110px_110px_120px_150px_150px_120px] gap-3 border-b border-[var(--border)] bg-[var(--surface-soft)] px-4 py-3 text-xs font-black uppercase tracking-[0.16em] text-[var(--muted)] lg:grid">
          <span>Người làm</span>
          <span>Trạng thái</span>
          <span>Điểm</span>
          <span>Số câu đúng</span>
          <span>Bắt đầu</span>
          <span>Nộp bài</span>
          <span>Thao tác</span>
        </div>

        <article
          v-for="attempt in attempts"
          :key="attempt.id"
          class="grid gap-3 border-b border-[var(--border)] px-4 py-4 last:border-b-0 lg:grid-cols-[minmax(220px,1.4fr)_110px_110px_120px_150px_150px_120px] lg:items-center"
        >
          <div>
            <h3 class="font-black text-[var(--text)]">{{ attempt.user?.name || `User #${attempt.user_id}` }}</h3>
            <p class="mt-1 text-xs font-bold text-[var(--muted)]">{{ attempt.user?.email || 'Chưa có email' }}</p>
          </div>
          <StatusBadge :value="attempt.status || '-'" />
          <p class="text-sm font-black text-[var(--text)]">{{ formatScore(attempt) }}</p>
          <p class="text-sm font-black text-[var(--text)]">{{ formatCorrectCount(attempt) }}</p>
          <p class="text-sm font-bold text-[var(--muted)]">{{ formatDateTime(attempt.started_at) }}</p>
          <p class="text-sm font-bold text-[var(--muted)]">{{ formatDateTime(attempt.submitted_at || attempt.finished_at) }}</p>
          <div class="flex items-center gap-2">
            <button
              class="btn-ghost px-3 py-1.5 text-xs flex items-center gap-1"
              :class="attempt.evaluation ? 'text-emerald-400 hover:bg-emerald-500/10' : 'text-[var(--primary)] hover:bg-[var(--primary)]/10'"
              type="button"
              @click="openEvaluation(attempt)"
            >
              <span>Đánh giá</span>
              <span v-if="attempt.evaluation" title="Đã có nhận xét">💬</span>
            </button>
            <button
              v-if="isHost && !isBanned"
              class="btn-ghost px-3 py-1.5 text-xs text-rose-400 hover:bg-rose-500/10 flex items-center gap-1 disabled:opacity-50"
              type="button"
              :disabled="isResetting === attempt.id"
              @click="resetAttempt(attempt)"
            >
              {{ isResetting === attempt.id ? '...' : 'Reset' }}
            </button>
          </div>
        </article>
      </div>

      <div v-else class="mt-5 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-10 text-center">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Empty</p>
        <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-[var(--text)]">Chưa có bài nộp</h3>
        <p class="mt-3 text-sm leading-7 text-[var(--muted)]">Khi thành viên bắt đầu hoặc nộp bài, dữ liệu sẽ xuất hiện tại đây.</p>
      </div>
    </article>

    <!-- Modal Đánh giá bài làm -->
    <div v-if="selectedAttempt" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-opacity">
      <div class="relative w-full max-w-lg rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <div class="flex items-center justify-between pb-4 border-b border-[var(--border)]">
          <h3 class="text-xl font-black text-[var(--text)]">Đánh giá bài làm</h3>
          <button @click="closeEvaluation" class="text-[var(--muted)] hover:text-[var(--text)] text-2xl font-bold">&times;</button>
        </div>

        <div class="mt-5 space-y-6 max-h-[70vh] overflow-y-auto pr-1">
          <div class="space-y-2 rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 text-sm leading-relaxed">
            <p class="font-bold text-[var(--text)]">Học viên: <span class="font-black text-[var(--primary)]">{{ selectedAttempt.user?.name || `User #${selectedAttempt.user_id}` }}</span></p>
            <p class="text-[var(--muted)]">Email: <span class="font-bold text-[var(--text)]">{{ selectedAttempt.user?.email || '-' }}</span></p>
            <p class="text-[var(--muted)]">Điểm số: <span class="font-black text-[var(--text)]">{{ formatScore(selectedAttempt) }} ({{ formatCorrectCount(selectedAttempt) }} câu đúng)</span></p>
            <p class="text-[var(--muted)]">Thời gian nộp: <span class="font-bold text-[var(--text)]">{{ formatDateTime(selectedAttempt.submitted_at || selectedAttempt.finished_at) }}</span></p>
          </div>

          <div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)] mb-3">Nhận xét bài làm</p>
            
            <div v-if="isLoadingEvaluation" class="py-4 text-center text-xs font-bold text-[var(--muted)]">
              Đang tải nhận xét...
            </div>

            <div v-else>
              <div v-if="isHost && !isBanned" class="space-y-4">
                <div>
                  <label class="block text-xs font-bold text-[var(--muted)] mb-1 uppercase">Nhận xét của giáo viên</label>
                  <textarea 
                    v-model="evaluationForm.comment"
                    class="field min-h-24 w-full resize-y text-sm"
                    maxlength="1000"
                    placeholder="Nhập nhận xét cho bài làm này (Ví dụ: Trình bày tốt, làm bài nghiêm túc...)"
                  ></textarea>
                </div>
              </div>

              <div v-else class="space-y-3 rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
                <div>
                  <p class="text-[10px] font-bold text-[var(--muted)] uppercase">Nhận xét</p>
                  <p class="mt-1 text-sm font-bold leading-relaxed text-[var(--text)] italic">
                    "{{ evaluationData?.comment || 'Chưa có nhận xét nào cho bài làm này.' }}"
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-[var(--border)]">
          <button @click="closeEvaluation" class="btn-ghost" type="button">Đóng</button>
          <button 
            v-if="isHost && !isLoadingEvaluation && !isBanned" 
            @click="saveEvaluation" 
            class="btn-primary" 
            type="button"
            :disabled="isSavingEvaluation"
          >
            {{ isSavingEvaluation ? 'Đang lưu...' : (evaluationData ? 'Cập nhật nhận xét' : 'Lưu nhận xét') }}
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { currentUserStorage, homeworkApi } from '@/services/api'

const route = useRoute()
const roomId = computed(() => route.params.roomId)
const assignmentId = computed(() => route.params.assignmentId)

const room = ref(null)
const attempts = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const isResetting = ref(null)
const MAX_EVALUATION_COMMENT_LENGTH = 1000

const currentUser = currentUserStorage.get()
const canManageRoom = computed(() => currentUser?.role === 'admin' || Number(room.value?.host_id) === Number(currentUser?.id))
const isHost = computed(() => Number(room.value?.host_id) === Number(currentUser?.id))
const isBanned = computed(() => room.value?.status === 'banned')

// Evaluation modal state
const selectedAttempt = ref(null)
const isLoadingEvaluation = ref(false)
const isSavingEvaluation = ref(false)
const evaluationData = ref(null)
const evaluationForm = ref({
  comment: '',
})

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
    const [roomData, attemptsData] = await Promise.all([
      homeworkApi.getHomeworkRoom(roomId.value),
      homeworkApi.getRoomAssignmentAttempts(assignmentId.value)
    ])
    room.value = roomData
    attempts.value = attemptsData
  } catch (error) {
    errorMessage.value = error.message || 'Bạn không có quyền xem danh sách bài nộp.'
  } finally {
    isLoading.value = false
  }
}

const openEvaluation = async (attempt) => {
  selectedAttempt.value = attempt
  isLoadingEvaluation.value = true
  evaluationData.value = null
  evaluationForm.value.comment = ''

  try {
    const data = await homeworkApi.getSubmissionEvaluation(roomId.value, attempt.id)
    evaluationData.value = data
    if (data) {
      evaluationForm.value.comment = data.comment || ''
    }
  } catch (error) {
    console.error('Không tải được nhận xét:', error)
  } finally {
    isLoadingEvaluation.value = false
  }
}

const closeEvaluation = () => {
  selectedAttempt.value = null
}

const saveEvaluation = async () => {
  if (!selectedAttempt.value) return

  const trimmedComment = evaluationForm.value.comment.trim()
  if (trimmedComment.length > MAX_EVALUATION_COMMENT_LENGTH) {
    alert(`Nhận xét tối đa ${MAX_EVALUATION_COMMENT_LENGTH} ký tự.`)
    return
  }

  isSavingEvaluation.value = true
  try {
    const data = await homeworkApi.saveSubmissionEvaluation(roomId.value, selectedAttempt.value.id, {
      comment: trimmedComment,
    })
    evaluationData.value = data

    const idx = attempts.value.findIndex(a => a.id === selectedAttempt.value.id)
    if (idx !== -1) attempts.value[idx].evaluation = data

    alert('Đã lưu nhận xét bài nộp thành công.')
  } catch (error) {
    alert(`Không lưu được nhận xét: ${error.message}`)
  } finally {
    isSavingEvaluation.value = false
  }
}

const resetAttempt = async (attempt) => {
  if (!confirm('Xác nhận xóa kết quả làm bài của học viên này? Thao tác này không thể hoàn tác.')) return

  isResetting.value = attempt.id
  try {
    const res = await homeworkApi.resetRoomAssignmentAttempt(assignmentId.value, attempt.id)
    alert(res.message || 'Đã reset lượt làm bài thành công. Học viên có thể làm lại từ đầu.')
    await loadAttempts()
  } catch (error) {
    alert(error.message || 'Không reset được lượt làm bài.')
  } finally {
    isResetting.value = null
  }
}

onMounted(loadAttempts)
</script>
