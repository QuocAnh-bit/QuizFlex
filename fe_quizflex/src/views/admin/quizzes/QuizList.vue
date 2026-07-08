<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const quizzes = ref([])
const loading = ref(false)
const filters = ref({
  search: '',
  creator: '',
  category: '',
  difficulty: '',
  visibility: '',
  ai_generated: ''
})

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

const fetchQuizzes = async () => {
  try {
    loading.value = true
    const res = await api.get('/admin/quizzes', { params: filters.value })
    quizzes.value = res.data.data.data || []
  } catch (error) {
    console.error('Lỗi lấy quiz:', error)
    showToast('Không tải được danh sách quiz', 'error')
  } finally {
    loading.value = false
  }
}

const deleteQuiz = (id) => {
  showConfirm(
    'Xác nhận xóa',
    'Bạn có chắc chắn muốn xóa mềm bộ quiz này không? Bạn có thể khôi phục lại sau từ thùng rác.',
    async () => {
      try {
        await api.delete(`/admin/quizzes/${id}`)
        showToast('Xóa quiz thành công', 'success')
        fetchQuizzes()
      } catch (error) {
        console.error(error)
        showToast('Xóa thất bại: ' + (error.response?.data?.message || error.message), 'error')
      }
    }
  )
}

onMounted(() => {
  fetchQuizzes()
})
</script>

<template>
  <section class="grid gap-6">
    <!-- Header -->
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Quiz Management</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Quản lý Quiz</h1>
          <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Quản lý tất cả các bộ đề quiz trong hệ thống, xem chi tiết, chỉnh sửa hoặc khôi phục từ thùng rác.</p>
        </div>
        <div class="flex flex-wrap gap-3">
          <RouterLink to="/admin/quizzes-trash" class="rounded-full border border-rose-500/30 bg-rose-500/10 px-4 py-2 text-xs font-black text-rose-400 transition hover:bg-rose-500/20">
            🗑️ Thùng rác
          </RouterLink>
        </div>
      </div>
    </div>

    <!-- Search + Filter -->
    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <div class="flex items-center gap-3 rounded-2xl border border-[var(--border)] bg-[var(--input-bg)] px-4 py-3 transition focus-within:border-[var(--border-strong)]">
          <span class="text-lg">🔍</span>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Tìm theo tên quiz..."
            class="w-full bg-transparent text-sm font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)]"
            @keyup.enter="fetchQuizzes"
          />
        </div>
        <div class="flex items-center gap-3 rounded-2xl border border-[var(--border)] bg-[var(--input-bg)] px-4 py-3 transition focus-within:border-[var(--border-strong)]">
          <span class="text-lg">👤</span>
          <input
            v-model="filters.creator"
            type="text"
            placeholder="Tìm theo người tạo..."
            class="w-full bg-transparent text-sm font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)]"
            @keyup.enter="fetchQuizzes"
          />
        </div>
        <div class="flex items-center gap-3 rounded-2xl border border-[var(--border)] bg-[var(--input-bg)] px-4 py-3 transition focus-within:border-[var(--border-strong)]">
          <span class="text-lg">📁</span>
          <input
            v-model="filters.category"
            type="text"
            placeholder="Tìm theo danh mục..."
            class="w-full bg-transparent text-sm font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)]"
            @keyup.enter="fetchQuizzes"
          />
        </div>
      </div>

      <div class="mt-4 grid gap-4 sm:grid-cols-4 items-center">
        <select v-model="filters.difficulty" class="field" @change="fetchQuizzes">
          <option value="">Mọi độ khó</option>
          <option value="easy">Dễ</option>
          <option value="medium">Vừa</option>
          <option value="hard">Khó</option>
        </select>
        <select v-model="filters.visibility" class="field" @change="fetchQuizzes">
          <option value="">Mọi hiển thị</option>
          <option value="public">🌐 Public</option>
          <option value="private">🔒 Private</option>
        </select>
        <select v-model="filters.ai_generated" class="field" @change="fetchQuizzes">
          <option value="">Mọi nguồn tạo</option>
          <option value="1">🤖 AI Generated</option>
          <option value="0">✍️ Quiz thường</option>
        </select>
        <button @click="fetchQuizzes" class="btn-primary h-full justify-center">
          Tìm kiếm
        </button>
      </div>
    </article>

    <!-- Table -->
    <div class="overflow-x-auto rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <table class="w-full border-collapse text-left text-sm text-[var(--text)]">
        <thead>
          <tr class="border-b border-[var(--border)] bg-[var(--surface-soft)] text-xs font-black uppercase tracking-wider text-[var(--muted)]">
            <th class="p-4">Tên quiz</th>
            <th class="p-4 text-center">Người tạo</th>
            <th class="p-4 text-center">Danh mục</th>
            <th class="p-4 text-center">Độ khó</th>
            <th class="p-4 text-center">Lượt làm</th>
            <th class="p-4 text-center">Điểm TB</th>
            <th class="p-4 text-center">Trạng thái</th>
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
                {{ quiz.difficulty === 'easy' ? 'Dễ' : quiz.difficulty === 'hard' ? 'Khó' : 'Vừa' }}
              </span>
            </td>
            <td class="p-4 text-center font-bold">{{ quiz.attempts_count ?? 0 }}</td>
            <td class="p-4 text-center font-bold text-[var(--primary)]">{{ quiz.avg_score ?? 0 }}%</td>
            <td class="p-4 text-center">
              <span
                class="rounded-full border px-3 py-1 text-xs font-black"
                :class="quiz.is_public ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400' : 'border-rose-500/30 bg-rose-500/10 text-rose-400'"
              >
                {{ quiz.is_public ? '🌐 Public' : '🔒 Private' }}
              </span>
            </td>
            <td class="p-4 text-center space-x-3">
              <RouterLink :to="`/admin/quizzes/${quiz.id}`" class="text-[var(--primary)] hover:underline font-bold">
                Chi tiết
              </RouterLink>
              <RouterLink :to="`/admin/quizzes/${quiz.id}/edit`" class="text-amber-400 hover:underline font-bold">
                Sửa
              </RouterLink>
              <button @click="deleteQuiz(quiz.id)" class="text-rose-400 hover:underline font-bold">
                Xóa
              </button>
            </td>
          </tr>
          <tr v-if="!loading && quizzes.length === 0">
            <td colspan="8" class="text-center py-10 text-[var(--muted)] font-bold">Không có quiz nào trong hệ thống</td>
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