<template>
  <section class="grid gap-6 py-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <router-link class="btn-ghost" to="/live-rooms">Quay lại Live Room</router-link>
      <router-link class="btn-ghost" to="/live-rooms/join">Tham gia bằng mã</router-link>
    </div>

    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)]">
      <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Create Live</p>
      <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Tạo live room</h1>
      <p class="mt-3 max-w-3xl text-sm leading-7 text-[var(--muted)]">Chọn quiz, tạo mã live room và chuyển sang màn host monitor.</p>
    </article>

    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">{{ errorMessage }}</div>
    <div v-if="successMessage" class="rounded-[2rem] border border-emerald-500/30 bg-emerald-500/10 p-5 text-sm font-bold text-emerald-300">{{ successMessage }}</div>

    <form class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]" @submit.prevent="handleCreate">
      <div class="grid gap-5">
        <label class="grid gap-2">
          <span class="text-sm font-black text-[var(--text)]">Quiz</span>
          <select v-model="form.quiz_id" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-3 text-sm font-bold text-[var(--text)] outline-none focus:border-[var(--border-strong)]">
            <option value="">Chọn quiz</option>
            <option v-for="quiz in quizzes" :key="quiz.id" :value="quiz.id">{{ quiz.title || `Quiz #${quiz.id}` }}</option>
          </select>
        </label>

        <label class="grid gap-2">
          <span class="text-sm font-black text-[var(--text)]">Tên live room</span>
          <input v-model.trim="form.title" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-3 text-sm font-bold text-[var(--text)] outline-none focus:border-[var(--border-strong)]" placeholder="Có thể để trống để dùng tên quiz" />
        </label>
      </div>

      <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm font-bold text-[var(--muted)]">{{ quizzes.length ? `${quizzes.length} quiz có thể chọn` : 'Chưa có quiz để tạo live room' }}</p>
        <button class="btn-primary disabled:cursor-not-allowed disabled:opacity-50" type="submit" :disabled="isLoading || isSubmitting || !quizzes.length">
          {{ isSubmitting ? 'Đang tạo...' : 'Tạo live room' }}
        </button>
      </div>
    </form>
  </section>
</template>

<script setup>
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
  isLoading.value = true
  errorMessage.value = ''

  try {
    quizzes.value = await quizzesApi.list({ per_page: 100 })
  } catch (error) {
    errorMessage.value = `Không tải được danh sách quiz: ${error.message}`
  } finally {
    isLoading.value = false
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
