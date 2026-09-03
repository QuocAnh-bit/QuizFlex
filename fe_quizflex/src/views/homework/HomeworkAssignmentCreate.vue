<template>
  <section class="max-w-3xl mx-auto py-4 space-y-6">
    <div class="flex items-center justify-between">
      <router-link class="btn-secondary text-xs" :to="`/homework-rooms/${roomId}`">← Quay lại phòng học</router-link>
    </div>

    <div v-if="isLoading" class="card p-10 text-center text-xs font-semibold text-slate-500">
      Đang tải dữ liệu giao bài...
    </div>
    <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-700">
      {{ errorMessage }}
    </div>

    <template v-if="!isLoading && room">
      <!-- Header -->
      <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Giao bài tập</p>
          <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">Giao quiz vào phòng</h1>
          <p class="mt-1 text-sm text-slate-600">Chọn bộ câu hỏi và thiết lập thời hạn làm bài cho các thành viên.</p>
        </div>
        <div class="text-right">
          <span class="text-xs font-bold text-slate-900 block">{{ room.name || 'Phòng học' }}</span>
          <span class="font-mono text-xs font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200 mt-1 inline-block">
            {{ room.code || 'NO CODE' }}
          </span>
        </div>
      </div>

      <article v-if="!canManageRoom" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs font-bold text-amber-800">
        Bạn không có quyền giao bài trong phòng này.
      </article>

      <article v-else-if="!quizzes.length" class="card p-10 text-center text-slate-500 space-y-3">
        <span class="text-3xl block">📦</span>
        <h2 class="text-lg font-bold text-slate-800">Chưa có quiz để giao</h2>
        <p class="text-xs max-w-sm mx-auto">Hãy tạo ít nhất một bộ quiz trong tài khoản của bạn trước khi giao bài.</p>
        <router-link class="btn-primary text-xs" to="/dashboard/questions/create">Tạo quiz mới →</router-link>
      </article>

      <form v-else class="card p-6 sm:p-8 space-y-5" @submit.prevent="submitForm">
        <div class="space-y-4">
          <!-- Quiz Selector -->
          <label class="grid gap-1.5 text-xs font-bold text-slate-700">
            Chọn quiz từ kho <span class="text-red-500">*</span>
            <select v-model="form.quiz_id" class="field text-xs" @change="syncTitleFromQuiz" required>
              <option value="">-- Chọn bộ quiz muốn giao --</option>
              <option v-for="quiz in quizzes" :key="quiz.id" :value="quiz.id">
                {{ quiz.title }} ({{ quiz.questions_count ?? quiz.questions?.length ?? 0 }} câu)
              </option>
            </select>
          </label>

          <!-- Title -->
          <label class="grid gap-1.5 text-xs font-bold text-slate-700">
            Tiêu đề bài tập <span class="text-red-500">*</span>
            <input v-model.trim="form.title" class="field text-xs" maxlength="255" placeholder="VD: Bài kiểm tra 15 phút - Chương 1" required />
          </label>

          <!-- Description -->
          <label class="grid gap-1.5 text-xs font-bold text-slate-700">
            Ghi chú / Hướng dẫn
            <textarea v-model.trim="form.description" class="field text-xs min-h-24 resize-y" maxlength="2000" placeholder="Ghi chú thêm cho thành viên trong phòng..."></textarea>
          </label>

          <!-- Timers & Shuffling -->
          <div class="grid gap-4 sm:grid-cols-2">
            <label class="grid gap-1.5 text-xs font-bold text-slate-700">
              Thời gian bắt đầu
              <input v-model="form.starts_at" class="field text-xs" type="datetime-local" />
            </label>

            <label class="grid gap-1.5 text-xs font-bold text-slate-700">
              Hạn chót nộp bài (Deadline)
              <input v-model="form.deadline_at" class="field text-xs" type="datetime-local" />
            </label>
          </div>

          <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 space-y-2">
            <span class="text-xs font-bold text-slate-700 block mb-1">Cấu hình xáo trộn</span>
            <div class="flex flex-wrap gap-4 text-xs font-semibold text-slate-700">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" v-model="form.shuffle_questions" class="rounded text-[#7C3AED]" />
                <span>Xáo trộn thứ tự câu hỏi</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" v-model="form.shuffle_answers" class="rounded text-[#7C3AED]" />
                <span>Xáo trộn thứ tự đáp án</span>
              </label>
            </div>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <label class="grid gap-1.5 text-xs font-bold text-slate-700">
              Thời lượng làm bài (phút)
              <input v-model.number="form.duration_minutes" class="field text-xs" min="1" max="1440" type="number" placeholder="Để trống = Thời gian mặc định của quiz" />
            </label>

            <label class="grid gap-1.5 text-xs font-bold text-slate-700">
              Số lần làm tối đa
              <input v-model.number="form.max_attempts" class="field text-xs" min="1" max="20" type="number" required />
            </label>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <label class="grid gap-1.5 text-xs font-bold text-slate-700">
              Chế độ hiển thị kết quả
              <select v-model="form.show_result_mode" class="field text-xs">
                <option value="immediately">Hiển thị ngay sau khi nộp</option>
                <option value="after_deadline">Chỉ hiển thị sau deadline</option>
                <option value="manual">Chủ phòng mở thủ công</option>
              </select>
            </label>

            <label class="grid gap-1.5 text-xs font-bold text-slate-700">
              Trạng thái bài giao
              <select v-model="form.status" class="field text-xs">
                <option value="published">Đã mở (Published)</option>
                <option value="draft">Bản nháp (Draft)</option>
                <option value="closed">Đã đóng (Closed)</option>
              </select>
            </label>
          </div>
        </div>

        <div v-if="successMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs font-bold text-emerald-700">{{ successMessage }}</div>
        <div v-if="formError" class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs font-bold text-red-700">{{ formError }}</div>

        <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
          <router-link class="btn-secondary text-xs px-4 py-2" :to="`/homework-rooms/${roomId}`">Hủy</router-link>
          <button class="btn-primary text-xs px-5 py-2" type="submit" :disabled="isSubmitting">{{ isSubmitting ? 'Đang giao...' : 'Giao bài tập' }}</button>
        </div>
      </form>
    </template>
  </section>
</template>

<script setup>
import { useAppLoading } from '@/composables/useAppLoading'
const { beginTask, endTask } = useAppLoading()

import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { currentUserStorage, homeworkApi, quizzesApi } from '@/services/api'

const route = useRoute()
const router = useRouter()
const roomId = computed(() => route.params.roomId)
const currentUser = currentUserStorage.get()

const room = ref(null)
const quizzes = ref([])
const isLoading = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const formError = ref('')
const successMessage = ref('')

const form = reactive({
  quiz_id: '',
  title: '',
  description: '',
  starts_at: '',
  deadline_at: '',
  duration_minutes: '',
  max_attempts: 1,
  show_result_mode: 'immediately',
  status: 'published',
  shuffle_questions: false,
  shuffle_answers: false,
})

const canManageRoom = computed(() => {
  const role = String(currentUser?.role || '').toLowerCase()
  return role === 'admin' || Number(room.value?.host_id) === Number(currentUser?.id)
})

const syncTitleFromQuiz = () => {
  if (form.title) return
  const selectedQuiz = quizzes.value.find((quiz) => Number(quiz.id) === Number(form.quiz_id))
  if (selectedQuiz?.title) form.title = selectedQuiz.title
}

const validateForm = () => {
  if (!form.quiz_id) return 'Bạn cần chọn quiz.'
  if (!form.title.trim()) return 'Bạn cần nhập tiêu đề bài giao.'

  const maxAttempts = Number(form.max_attempts)
  if (!Number.isFinite(maxAttempts) || maxAttempts < 1 || maxAttempts > 20) {
    return 'Số lần làm tối đa phải từ 1 đến 20.'
  }

  if (form.duration_minutes) {
    const duration = Number(form.duration_minutes)
    if (!Number.isFinite(duration) || duration < 1 || duration > 1440) {
      return 'Thời lượng làm bài phải từ 1 đến 1440 phút.'
    }
  }

  const description = (form.description || '').trim()
  if (description.length > 2000) return 'Mô tả tối đa 2000 ký tự.'

  if (form.starts_at && form.deadline_at && new Date(form.deadline_at) < new Date(form.starts_at)) {
    return 'Deadline phải sau hoặc bằng thời gian bắt đầu.'
  }
  return ''
}

const buildPayload = () => ({
  quiz_id: Number(form.quiz_id),
  title: form.title.trim(),
  description: form.description ? form.description.trim() : null,
  starts_at: form.starts_at || null,
  deadline_at: form.deadline_at || null,
  duration_minutes: form.duration_minutes ? Number(form.duration_minutes) : null,
  max_attempts: Number(form.max_attempts) || 1,
  show_result_mode: form.show_result_mode,
  status: form.status,
  shuffle_questions: form.shuffle_questions,
  shuffle_answers: form.shuffle_answers,
})

const loadData = async () => {
    beginTask()
    try {
isLoading.value = true
  errorMessage.value = ''

  try {
    const [roomData, quizData] = await Promise.all([
      homeworkApi.getHomeworkRoom(roomId.value),
      quizzesApi.list({ mine: 1, per_page: 100 }),
    ])

    room.value = roomData
    quizzes.value = quizData
  } catch (error) {
    errorMessage.value = `Không tải được dữ liệu giao bài: ${error.message}`
  } finally {
    isLoading.value = false
  }
    } finally {
      endTask()
    }
  }

const submitForm = async () => {
  formError.value = ''
  successMessage.value = ''

  const validationError = validateForm()
  if (validationError) {
    formError.value = validationError
    return
  }

  isSubmitting.value = true

  try {
    await homeworkApi.createRoomAssignment(roomId.value, buildPayload())
    successMessage.value = 'Giao quiz thành công!'
    window.setTimeout(() => router.push(`/homework-rooms/${roomId.value}`), 600)
  } catch (error) {
    formError.value = error.message?.includes('quyen') || error.message?.includes('quyền')
      ? 'Bạn không có quyền giao bài trong phòng này.'
      : `Không giao được quiz: ${error.message}`
  } finally {
    isSubmitting.value = false
  }
}

onMounted(loadData)
</script>
