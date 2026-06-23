<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/services/api'

const quizzes = ref([])
const loading = ref(false)

const confirmModal = ref({
  isOpen: false,
  title: '',
  message: '',
  action: null
})

const toast = ref({
  isOpen: false,
  message: '',
  type: 'success'
})

const showToast = (message, type = 'success') => {
  toast.value.message = message
  toast.value.type = type
  toast.value.isOpen = true
  setTimeout(() => {
    toast.value.isOpen = false
  }, 3000)
}

const showConfirm = (title, message, action) => {
  confirmModal.value.title = title
  confirmModal.value.message = message
  confirmModal.value.action = action
  confirmModal.value.isOpen = true
}

const triggerConfirmAction = async () => {
  confirmModal.value.isOpen = false
  if (confirmModal.value.action) {
    await confirmModal.value.action()
  }
}

const fetchTrash = async () => {
  try {
    loading.value = true
    const res = await api.get('/admin/quizzes/trash')
    quizzes.value = res.data.data.data || []
  } catch (error) {
    console.error(error)
    showToast('Không tải được thùng rác', 'error')
  } finally {
    loading.value = false
  }
}

const restoreQuiz = (id) => {
  showConfirm(
    'Khôi phục Quiz',
    'Bạn có muốn khôi phục lại bộ quiz này về trạng thái hoạt động không?',
    async () => {
      try {
        await api.post(`/admin/quizzes/${id}/restore`)
        showToast('Khôi phục thành công', 'success')
        fetchTrash()
      } catch (error) {
        console.error(error)
        showToast('Khôi phục thất bại', 'error')
      }
    }
  )
}

const forceDeleteQuiz = (id) => {
  showConfirm(
    'Xóa vĩnh viễn',
    'Bạn có chắc chắn muốn xóa vĩnh viễn bộ quiz này? Hành động này không thể hoàn tác!',
    async () => {
      try {
        await api.delete(`/admin/quizzes/${id}/force-delete`)
        showToast('Đã xóa vĩnh viễn', 'success')
        fetchTrash()
      } catch (error) {
        console.error(error)
        showToast('Xóa thất bại', 'error')
      }
    }
  )
}

onMounted(() => {
  fetchTrash()
})
</script>

<template>
  <section class="grid gap-6">
    <!-- Header -->
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-rose-500/10 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-rose-400">Trash Bin</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Thùng rác Quiz</h1>
          <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Danh sách các bộ đề quiz đã bị xóa mềm. Bạn có thể khôi phục lại hoặc xóa vĩnh viễn khỏi cơ sở dữ liệu.</p>
        </div>
        <div class="flex flex-wrap gap-3">
          <RouterLink to="/admin/quizzes" class="btn-ghost">
            ← Quay lại
          </RouterLink>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <table class="w-full border-collapse text-left text-sm text-[var(--text)]">
        <thead>
          <tr class="border-b border-[var(--border)] bg-[var(--surface-soft)] text-xs font-black uppercase tracking-wider text-[var(--muted)]">
            <th class="p-4">Tên quiz</th>
            <th class="p-4 text-center">Người tạo</th>
            <th class="p-4 text-center">Danh mục</th>
            <th class="p-4 text-center">Độ khó</th>
            <th class="p-4 text-center">Thao tác</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[var(--border)]">
          <tr v-for="quiz in quizzes" :key="quiz.id" class="transition hover:bg-[var(--surface-soft)]">
            <td class="p-4 font-bold">{{ quiz.title }}</td>
            <td class="p-4 text-center text-[var(--muted)]">{{ quiz.user?.name || quiz.author || 'Chưa có' }}</td>
            <td class="p-4 text-center">
              <span class="rounded-full bg-[var(--surface-soft)] px-3 py-1 text-xs font-bold">
                {{ quiz.category || 'Chưa có' }}
              </span>
            </td>
            <td class="p-4 text-center">
              <span
                class="rounded-full px-3 py-1 text-xs font-bold border"
                :class="quiz.difficulty === 'easy' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : quiz.difficulty === 'hard' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : 'bg-amber-500/10 text-amber-400 border-amber-500/20'"
              >
                {{ quiz.difficulty_label || quiz.difficulty || 'Vừa' }}
              </span>
            </td>
            <td class="p-4 text-center space-x-3">
              <button @click="restoreQuiz(quiz.id)" class="text-emerald-400 hover:underline font-bold">
                Khôi phục
              </button>
              <button @click="forceDeleteQuiz(quiz.id)" class="text-rose-400 hover:underline font-bold">
                Xóa vĩnh viễn
              </button>
            </td>
          </tr>
          <tr v-if="!loading && quizzes.length === 0">
            <td colspan="5" class="text-center py-10 text-[var(--muted)] font-bold">Thùng rác trống</td>
          </tr>
        </tbody>
      </table>
      <div v-if="loading" class="text-center py-10 text-[var(--muted)] font-bold">Đang tải dữ liệu...</div>
    </div>

    <!-- Custom Confirm Modal -->
    <transition name="fade">
      <div v-if="confirmModal.isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
        <div class="w-full max-w-md rounded-[2.5rem] border border-[var(--border-strong)] bg-[var(--surface)] p-6 shadow-[0_24px_70px_rgba(0,0,0,0.5)] backdrop-blur-2xl transition-all scale-100">
          <div class="flex items-center gap-3 text-rose-400 mb-4">
            <span class="text-2xl">⚠️</span>
            <h3 class="text-xl font-black text-[var(--text)]">{{ confirmModal.title }}</h3>
          </div>
          <p class="text-sm leading-6 text-[var(--muted)] mb-6">{{ confirmModal.message }}</p>
          <div class="flex justify-end gap-3">
            <button @click="confirmModal.isOpen = false" class="btn-ghost px-5 py-2">
              Hủy
            </button>
            <button @click="triggerConfirmAction" class="rounded-full bg-rose-500 hover:bg-rose-600 px-5 py-2 text-xs font-black text-white transition hover:-translate-y-0.5">
              Xác nhận
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Custom Toast Message -->
    <transition name="slide-up">
      <div v-if="toast.isOpen" class="fixed bottom-6 right-6 z-50 flex items-center gap-3 rounded-2xl border px-4 py-3 shadow-lg backdrop-blur-xl transition-all"
           :class="toast.type === 'success' ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400 shadow-[0_12px_40px_rgba(16,185,129,0.1)]' : 'border-rose-500/30 bg-rose-500/10 text-rose-400 shadow-[0_12px_40px_rgba(244,63,94,0.1)]'">
        <span class="text-base">{{ toast.type === 'success' ? '✅' : '❌' }}</span>
        <span class="text-sm font-bold">{{ toast.message }}</span>
      </div>
    </transition>
  </section>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(20px);
}
</style>
