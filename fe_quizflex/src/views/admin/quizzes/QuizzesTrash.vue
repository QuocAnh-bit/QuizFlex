<script setup>
import { ref, onMounted, inject } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/services/api'

const showToast = inject('showToast')
const showConfirm = inject('showConfirm')

const quizzes = ref([])
const loading = ref(false)

const fetchTrash = async () => {
  try {
    loading.value = true
    const res = await api.get('/admin/quizzes/trash')
    quizzes.value = res.data.data || []
  } catch (error) {
    console.error(error)
    if (showToast) showToast('Không tải được thùng rác', 'error')
  } finally {
    loading.value = false
  }
}

const restoreQuiz = (id) => {
  if (showConfirm) {
    showConfirm(
      'Khôi phục Quiz',
      'Bạn có muốn khôi phục lại bộ quiz này về trạng thái hoạt động không?',
      async () => {
        try {
          await api.post(`/admin/quizzes/${id}/restore`)
          if (showToast) showToast('Khôi phục thành công', 'success')
          fetchTrash()
        } catch (error) {
          console.error(error)
          if (showToast) showToast('Khôi phục thất bại', 'error')
        }
      }
    )
  }
}

const forceDeleteQuiz = (id) => {
  if (showConfirm) {
    showConfirm(
      'Xóa vĩnh viễn',
      'Bạn có chắc chắn muốn xóa vĩnh viễn bộ quiz này? Hành động này không thể hoàn tác!',
      async () => {
        try {
          await api.delete(`/admin/quizzes/${id}/force-delete`)
          if (showToast) showToast('Đã xóa vĩnh viễn', 'success')
          fetchTrash()
        } catch (error) {
          console.error(error)
          if (showToast) showToast('Xóa thất bại', 'error')
        }
      }
    )
  }
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
  </section>
</template>
