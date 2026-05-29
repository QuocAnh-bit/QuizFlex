<template>
  <section class="grid gap-6 py-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <router-link class="btn-ghost" :to="`/homework-rooms/${roomId}`">Quay lại room</router-link>
    </div>

    <div v-if="isLoading" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">Đang tải dữ liệu giao bài...</div>
    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

    <template v-if="!isLoading && room">
      <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
        <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Assignment</p>
            <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Giao quiz</h1>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Chọn quiz và thiết lập thời gian làm bài cho room homework.</p>
          </div>
          <div class="grid gap-2 text-right">
            <span class="text-sm font-black text-[var(--text)]">{{ room.name || 'Room Homework' }}</span>
            <span class="rounded-full bg-[var(--chip-active)] px-4 py-2 text-sm font-black text-[var(--primary)]">{{ room.code || 'NO CODE' }}</span>
          </div>
        </div>
      </article>

      <article v-if="!canManageRoom" class="rounded-[2rem] border border-amber-500/30 bg-amber-500/10 p-6 text-sm font-bold text-amber-200">
        Bạn không có quyền giao bài trong room này.
      </article>

      <article v-else-if="!quizzes.length" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center shadow-[var(--shadow-card)]">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Empty</p>
        <h2 class="mt-2 text-3xl font-black tracking-[-0.05em] text-[var(--text)]">Chưa có quiz để giao</h2>
        <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-[var(--muted)]">Tạo quiz mới hoặc kiểm tra quyền truy cập quiz trước khi giao bài.</p>
        <router-link class="btn-primary mt-6 inline-flex" to="/dashboard/questions/create">Tạo quiz</router-link>
      </article>

      <form v-else class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]" @submit.prevent="submitForm">
        <div class="grid gap-5">
          <label class="grid gap-2">
            <span class="text-sm font-black text-[var(--text)]">Chọn quiz</span>
            <select v-model="form.quiz_id" class="field" @change="syncTitleFromQuiz">
              <option value="">Chọn quiz để giao</option>
              <option v-for="quiz in quizzes" :key="quiz.id" :value="quiz.id">
                {{ quiz.title }} · {{ quiz.questions_count ?? quiz.questions?.length ?? 0 }} câu · {{ quiz.status || 'draft' }}
              </option>
            </select>
          </label>

          <label class="grid gap-2">
            <span class="text-sm font-black text-[var(--text)]">Tiêu đề bài giao</span>
            <input v-model.trim="form.title" class="field" maxlength="255" placeholder="VD: Bài ôn tập cuối tuần" />
          </label>

          <label class="grid gap-2">
            <span class="text-sm font-black text-[var(--text)]">Mô tả</span>
            <textarea v-model.trim="form.description" class="field min-h-28 resize-y" placeholder="Ghi chú cho thành viên trong room"></textarea>
          </label>

          <div class="grid gap-5 md:grid-cols-2">
            <label class="grid gap-2">
              <span class="text-sm font-black text-[var(--text)]">Thời gian bắt đầu</span>
              <input v-model="form.starts_at" class="field" type="datetime-local" />
            </label>

            <label class="grid gap-2">
              <span class="text-sm font-black text-[var(--text)]">Deadline</span>
              <input v-model="form.deadline_at" class="field" type="datetime-local" />
            </label>
          </div>

          <div class="grid gap-5 md:grid-cols-2">
            <label class="grid gap-2">
              <span class="text-sm font-black text-[var(--text)]">Thời lượng làm bài</span>
              <input v-model.number="form.duration_minutes" class="field" min="1" max="1440" type="number" placeholder="Không giới hạn" />
            </label>

            <label class="grid gap-2">
              <span class="text-sm font-black text-[var(--text)]">Số lần làm tối đa</span>
              <input v-model.number="form.max_attempts" class="field" min="1" max="20" type="number" />
            </label>
          </div>

          <div class="grid gap-5 md:grid-cols-2">
            <label class="grid gap-2">
              <span class="text-sm font-black text-[var(--text)]">Hiển thị kết quả</span>
              <select v-model="form.show_result_mode" class="field">
                <option value="immediately">Ngay sau khi nộp</option>
                <option value="after_deadline">Sau deadline</option>
                <option value="manual">Mở thủ công</option>
              </select>
            </label>

            <label class="grid gap-2">
              <span class="text-sm font-black text-[var(--text)]">Trạng thái</span>
              <select v-model="form.status" class="field">
                <option value="published">Đã mở</option>
                <option value="draft">Nháp</option>
                <option value="closed">Đóng</option>
              </select>
            </label>
          </div>
        </div>

        <div v-if="successMessage" class="mt-5 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-300">{{ successMessage }}</div>
        <div v-if="formError" class="mt-5 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">{{ formError }}</div>

        <div class="mt-6 flex flex-wrap justify-end gap-3">
          <router-link class="btn-ghost" :to="`/homework-rooms/${roomId}`">Hủy</router-link>
          <button class="btn-primary" type="submit" :disabled="isSubmitting">{{ isSubmitting ? 'Đang giao...' : 'Giao quiz' }}</button>
        </div>
      </form>
    </template>
  </section>
</template>

<script setup>
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
})

const canManageRoom = computed(() => {
  const role = String(currentUser?.role || '').toLowerCase()
  return role === 'admin' || Number(room.value?.owner_id) === Number(currentUser?.id)
})

const syncTitleFromQuiz = () => {
  if (form.title) return
  const selectedQuiz = quizzes.value.find((quiz) => Number(quiz.id) === Number(form.quiz_id))
  if (selectedQuiz?.title) form.title = selectedQuiz.title
}

const validateForm = () => {
  if (!form.quiz_id) return 'Bạn cần chọn quiz.'
  if (!form.title.trim()) return 'Bạn cần nhập tiêu đề bài giao.'
  if (Number(form.max_attempts) < 1) return 'Số lần làm tối đa phải từ 1 trở lên.'
  if (form.duration_minutes && Number(form.duration_minutes) < 1) return 'Thời lượng làm bài phải lớn hơn 0.'
  if (form.starts_at && form.deadline_at && new Date(form.deadline_at) < new Date(form.starts_at)) {
    return 'Deadline phải sau hoặc bằng thời gian bắt đầu.'
  }
  return ''
}

const buildPayload = () => ({
  quiz_id: Number(form.quiz_id),
  title: form.title.trim(),
  description: form.description || null,
  starts_at: form.starts_at || null,
  deadline_at: form.deadline_at || null,
  duration_minutes: form.duration_minutes ? Number(form.duration_minutes) : null,
  max_attempts: Number(form.max_attempts) || 1,
  show_result_mode: form.show_result_mode,
  status: form.status,
})

const loadData = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const [roomData, quizData] = await Promise.all([
      homeworkApi.getHomeworkRoom(roomId.value),
      quizzesApi.list({ per_page: 100 }),
    ])

    room.value = roomData
    quizzes.value = quizData
  } catch (error) {
    errorMessage.value = `Không tải được dữ liệu giao bài: ${error.message}`
  } finally {
    isLoading.value = false
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
    successMessage.value = 'Giao quiz thành công.'
    window.setTimeout(() => router.push(`/homework-rooms/${roomId.value}`), 700)
  } catch (error) {
    formError.value = error.message?.includes('quyen') || error.message?.includes('quyền')
      ? 'Bạn không có quyền giao bài trong room này.'
      : `Không giao được quiz: ${error.message}`
  } finally {
    isSubmitting.value = false
  }
}

onMounted(loadData)
</script>
