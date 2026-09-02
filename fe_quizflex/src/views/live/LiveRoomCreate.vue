<template>
  <section class="max-w-2xl mx-auto py-4 space-y-6">
    <div class="flex items-center justify-between">
      <router-link class="btn-secondary text-xs" to="/live-rooms">← Quay lại</router-link>
      <router-link class="btn-secondary text-xs" to="/live-rooms/join">Tham gia bằng mã</router-link>
    </div>

    <!-- Header -->
    <div class="card p-6 sm:p-8 space-y-2">
      <span class="rounded-full bg-amber-50 border border-amber-200 px-3 py-0.5 text-xs font-bold text-amber-700">
        Khởi tạo phòng
      </span>
      <h1 class="text-2xl font-black text-slate-900 sm:text-3xl pt-1">Tạo phòng thi đấu trực tiếp</h1>
      <p class="text-xs text-slate-600">Chọn bộ câu hỏi, hệ thống sẽ cấp mã phòng để bạn trình chiếu cho người chơi tham gia.</p>
    </div>

    <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-700">
      {{ errorMessage }}
    </div>
    <div v-if="successMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-bold text-emerald-700">
      {{ successMessage }}
    </div>

    <form class="card p-6 sm:p-8 space-y-5" @submit.prevent="handleCreate">
      <div class="space-y-4">
        <label class="grid gap-1.5 text-xs font-bold text-slate-700">
          Chọn quiz <span class="text-red-00"></span>
          <select v-model="form.quiz_id" class="field text-xs" required>
            <option value="">-- Chọn bộ quiz muốn thi đấu --</option>
            <option v-for="quiz in quizzes" :key="quiz.id" :value="quiz.id">
              {{ quiz.title || `Quiz #${quiz.id}` }} ({{ quiz.questions_count ?? quiz.questions?.length ?? 0 }} câu)
            </option>
          </select>
        </label>

        <label class="grid gap-1.5 text-xs font-bold text-slate-700">
          Tên phòng thi đấu (Tùy chọn)
          <input v-model.trim="form.title" class="field text-xs" placeholder="Để trống để dùng tên quiz làm tên phòng" />
        </label>
      </div>

      <div class="flex items-center justify-between pt-4 border-t border-slate-100">
        <span class="text-xs text-slate-500 font-medium">
          {{ quizzes.length ? `${quizzes.length} quiz khả dụng trong kho` : 'Chưa có quiz nào' }}
        </span>
        <button class="btn-primary text-xs px-5 py-2" type="submit" :disabled="isLoading || isSubmitting || !quizzes.length">
          {{ isSubmitting ? 'Đang tạo...' : 'Tạo phòng thi đấu ngay →' }}
        </button>
      </div>
    </form>
  </section>
</template>

<script setup>
import { useAppLoading } from '@/composables/useAppLoading'
const { beginTask, endTask } = useAppLoading()

import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { liveRoomApi, quizzesApi } from '@/services/api'

const router = useRouter()
const quizzes = ref([])
const isLoading = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const form = reactive({
  quiz_id: '',
  title: '',
})

const loadQuizzes = async () => {
    beginTask()
    try {
isLoading.value = true
  errorMessage.value = ''

  try {
    quizzes.value = await quizzesApi.list({ per_page: 100 })
  } catch (error) {
    errorMessage.value = `Không tải được danh sách quiz: ${error.message}`
  } finally {
    isLoading.value = false
  }
    } finally {
      endTask()
    }
  }

const handleCreate = async () => {
  if (!form.quiz_id) {
    errorMessage.value = 'Vui lòng chọn quiz.'
    return
  }

  isSubmitting.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const payload = {
      quiz_id: Number(form.quiz_id),
      ...(form.title ? { title: form.title } : {}),
    }
    const liveRoom = await liveRoomApi.createLiveRoom(payload)
    successMessage.value = `Tạo live room thành công. Mã phòng: ${liveRoom.code || '-'}`
    router.push(`/live-rooms/${liveRoom.id}/host`)
  } catch (error) {
    errorMessage.value = error.message || 'Không tạo được live room.'
  } finally {
    isSubmitting.value = false
  }
}

onMounted(loadQuizzes)
</script>
