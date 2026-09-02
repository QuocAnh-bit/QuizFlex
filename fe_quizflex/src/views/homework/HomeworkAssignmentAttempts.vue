<template>
  <section class="max-w-5xl mx-auto py-4 space-y-6">
    <!-- 1. LOADING STATE -->
    <AppLoadingState 
      v-if="isLoading" 
      title="Đang tải danh sách bài nộp..." 
      message="Vui lòng chờ trong giây lát để hệ thống tải thông tin lượt làm bài của các thành viên."
      icon="📊"
    />

    <!-- 2. ERROR STATE -->
    <AppErrorState 
      v-else-if="errorMessage" 
      title="Không thể tải danh sách bài nộp"
      :message="errorMessage" 
      @retry="loadAttempts"
    >
      <template #actions>
        <router-link class="btn-ghost text-xs" :to="`/homework-rooms/${roomId}`">Quay lại phòng</router-link>
      </template>
    </AppErrorState>

    <!-- 3. LOADED STATE -->
    <template v-else>
      <div class="flex flex-wrap items-center justify-between gap-3">
        <router-link class="btn-secondary text-xs" :to="`/homework-rooms/${roomId}`">← Quay lại phòng học</router-link>
        <button class="btn-secondary text-xs px-3.5 py-1.5" type="button" :disabled="isLoading" @click="loadAttempts">
          🔄 Tải lại
        </button>
      </div>

      <!-- Header -->
      <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Báo cáo kết quả</p>
          <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">Bài nộp của thành viên</h1>
          <p class="mt-1 text-sm text-slate-600">Theo dõi trạng thái, điểm số và gửi nhận xét đánh giá cho từng học viên.</p>
        </div>
        <span class="rounded-full bg-blue-50 border border-blue-200 px-3.5 py-1 text-xs font-bold text-blue-700">
          Tổng cộng: {{ attempts.length }} bài nộp
        </span>
      </div>

      <!-- Attempts Table Card -->
      <article class="card overflow-hidden">
        <div v-if="attempts.length" class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="border-b border-slate-100 bg-slate-50 text-slate-400 font-bold uppercase text-[10px]">
                <th class="py-3.5 px-4">Học viên</th>
                <th class="py-3.5 px-4">Trạng thái</th>
                <th class="py-3.5 px-4">Điểm số</th>
                <th class="py-3.5 px-4">Số câu đúng</th>
                <th class="py-3.5 px-4">Bắt đầu</th>
                <th class="py-3.5 px-4">Nộp bài</th>
                <th class="py-3.5 px-4 text-right">Thao tác</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr v-for="attempt in attempts" :key="attempt.id" class="hover:bg-slate-50">
                <td class="py-3.5 px-4">
                  <span class="font-bold text-slate-900 block">{{ attempt.user?.name || `User #${attempt.user_id}` }}</span>
                  <span class="text-[11px] text-slate-400 block">{{ attempt.user?.email || '-' }}</span>
                </td>
                <td class="py-3.5 px-4">
                  <StatusBadge :value="attempt.status || '-'" />
                </td>
                <td class="py-3.5 px-4 font-bold text-[#7C3AED]">{{ formatScore(attempt) }}</td>
                <td class="py-3.5 px-4 text-slate-700">{{ formatCorrectCount(attempt) }}</td>
                <td class="py-3.5 px-4 text-slate-500">{{ formatDateTime(attempt.started_at) }}</td>
                <td class="py-3.5 px-4 text-slate-500">{{ formatDateTime(attempt.submitted_at || attempt.finished_at) }}</td>
                <td class="py-3.5 px-4 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button
                      class="rounded px-2.5 py-1 font-bold text-xs transition"
                      :class="attempt.evaluation ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-purple-50 text-[#7C3AED] hover:bg-purple-100'"
                      type="button"
                      @click="openEvaluation(attempt)"
                    >
                      <span>{{ attempt.evaluation ? '💬 Đã nhận xét' : 'Đánh giá' }}</span>
                    </button>
                    <button
                      v-if="isHost && !isBanned"
                      class="rounded bg-red-50 px-2 py-1 text-xs font-bold text-red-600 hover:bg-red-100 transition disabled:opacity-50"
                      type="button"
                      :disabled="isResetting === attempt.id"
                      @click="resetAttempt(attempt)"
                      title="Cho phép học viên làm lại từ đầu"
                    >
                      {{ isResetting === attempt.id ? '...' : 'Reset' }}
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="p-10 text-center text-slate-500">
          <span class="text-3xl block mb-1">📝</span>
          <h3 class="text-base font-bold text-slate-800">Chưa có bài nộp nào</h3>
          <p class="mt-1 text-xs">Khi thành viên bắt đầu hoặc nộp bài, kết quả sẽ hiển thị tại đây.</p>
        </div>
      </article>
    </template>

    <!-- Modal Đánh giá bài làm -->
    <div v-if="selectedAttempt" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
      <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <h3 class="text-base font-bold text-slate-900">Đánh giá & Nhận xét bài làm</h3>
          <button @click="closeEvaluation" class="text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
        </div>

        <div class="space-y-4 text-xs">
          <div class="rounded-xl border border-slate-100 bg-slate-50 p-3.5 space-y-1.5">
            <p class="font-bold text-slate-900">Học viên: <b class="text-[#7C3AED]">{{ selectedAttempt.user?.name || `User #${selectedAttempt.user_id}` }}</b></p>
            <p class="text-slate-500">Email: {{ selectedAttempt.user?.email || '-' }}</p>
            <p class="text-slate-700 font-bold">Điểm số: {{ formatScore(selectedAttempt) }} ({{ formatCorrectCount(selectedAttempt) }} câu đúng)</p>
            <p class="text-slate-500">Nộp lúc: {{ formatDateTime(selectedAttempt.submitted_at || selectedAttempt.finished_at) }}</p>
          </div>

          <div>
            <span class="text-xs font-bold text-slate-700 block mb-1">Nhận xét của giáo viên / chủ phòng</span>
            <div v-if="isLoadingEvaluation" class="py-4 text-center text-slate-400 font-semibold">
              Đang tải nhận xét...
            </div>
            <div v-else>
              <textarea 
                v-if="isHost && !isBanned"
                v-model="evaluationForm.comment"
                class="field text-xs min-h-24 resize-y"
                maxlength="1000"
                placeholder="Nhập nhận xét chi tiết (Ví dụ: Hoàn thành bài tốt, cần xem lại phần câu hỏi 5...)"
              ></textarea>
              <div v-else class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-slate-700 italic">
                "{{ evaluationData?.comment || 'Chưa có nhận xét nào cho bài làm này.' }}"
              </div>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
          <button @click="closeEvaluation" class="btn-secondary text-xs" type="button">Đóng</button>
          <button 
            v-if="isHost && !isLoadingEvaluation && !isBanned" 
            @click="saveEvaluation" 
            class="btn-primary text-xs px-4 py-2" 
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
import { useAppLoading } from '@/composables/useAppLoading'
const { beginTask, endTask } = useAppLoading()

import { computed, onMounted, ref, watch, inject } from 'vue'
import { useRoute } from 'vue-router'
import AppLoadingState from '@/components/common/AppLoadingState.vue'
import AppErrorState from '@/components/common/AppErrorState.vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { currentUserStorage, homeworkApi } from '@/services/api'

const showConfirm = inject('showConfirm')
const showToast = inject('showToast')

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
const isHost = computed(() => Number(room.value?.host_id) === Number(currentUser?.id))
const isBanned = computed(() => room.value?.status === 'banned')

const selectedAttempt = ref(null)
const isLoadingEvaluation = ref(false)
const isSavingEvaluation = ref(false)
const evaluationData = ref(null)
const evaluationForm = ref({ comment: '' })

const formatDateTime = (value) => {
  if (!value) return '-'
  return new Date(value).toLocaleString('vi-VN')
}

const formatScore = (attempt) => {
  if (attempt.score === null || attempt.score === undefined) return '-'
  let raw = 0
  if (attempt.scaled_score_10 !== undefined && attempt.scaled_score_10 !== null) {
    raw = Number(attempt.scaled_score_10)
  } else {
    const tot = Number(attempt.total_points) || 0
    const sc = Number(attempt.score) || 0
    raw = tot > 0 ? (sc / tot) * 10 : 0
  }
  const formatted = Number.isInteger(raw) ? raw.toString() : parseFloat(raw.toFixed(2)).toString()
  return `${formatted} / 10`
}

const formatCorrectCount = (attempt) => {
  if (attempt.correct_count === null || attempt.correct_count === undefined) return '-'
  if (attempt.total_questions === null || attempt.total_questions === undefined) return String(attempt.correct_count)
  return `${attempt.correct_count}/${attempt.total_questions}`
}

const loadAttempts = async () => {
    beginTask()
    try {
isLoading.value = true
  errorMessage.value = ''

  try {
    const [roomData, attemptsData] = await Promise.all([
      homeworkApi.getHomeworkRoom(roomId.value),
      homeworkApi.getRoomAssignmentAttempts(assignmentId.value)
    ])
    room.value = roomData
    attempts.value = attemptsData

    if (route.query.attempt_id) {
      const attempt = attemptsData.find(a => Number(a.id) === Number(route.query.attempt_id))
      if (attempt) {
        openEvaluation(attempt)
      }
    }
  } catch (error) {
    errorMessage.value = error.message || 'Bạn không có quyền xem danh sách bài nộp.'
  } finally {
    isLoading.value = false
  }
    } finally {
      endTask()
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

    if (showToast) {
      showToast('Đã lưu nhận xét bài nộp thành công.', 'success')
    }
  } catch (error) {
    if (showToast) {
      showToast(`Không lưu được nhận xét: ${error.message}`, 'error')
    }
  } finally {
    isSavingEvaluation.value = false
  }
}

const resetAttempt = async (attempt) => {
  const message = 'Xác nhận xóa kết quả làm bài của học viên này? Thao tác này không thể hoàn tác.'
  
  if (showConfirm) {
    showConfirm(
      'Xóa kết quả làm bài',
      message,
      async () => {
        await executeResetAttempt(attempt)
      }
    )
  } else {
    if (!confirm(message)) return
    await executeResetAttempt(attempt)
  }
}

const executeResetAttempt = async (attempt) => {
  isResetting.value = attempt.id
  try {
    const res = await homeworkApi.resetRoomAssignmentAttempt(assignmentId.value, attempt.id)
    if (showToast) {
      showToast(res.message || 'Đã reset lượt làm bài thành công. Học viên có thể làm lại từ đầu.', 'success')
    }
    await loadAttempts()
  } catch (error) {
    if (showToast) {
      showToast(error.message || 'Không reset được lượt làm bài.', 'error')
    }
  } finally {
    isResetting.value = null
  }
}

watch(
  () => route.query.attempt_id,
  (newAttemptId) => {
    if (newAttemptId && attempts.value.length) {
      const attempt = attempts.value.find(a => Number(a.id) === Number(newAttemptId))
      if (attempt) {
        openEvaluation(attempt)
      }
    }
  }
)

onMounted(loadAttempts)
</script>
