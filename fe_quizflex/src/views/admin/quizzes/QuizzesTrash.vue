<template>
  <section class="max-w-6xl mx-auto py-4 space-y-6">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-red-600">Thùng rác</p>
        <h1 class="text-3xl font-black tracking-[-0.04em] text-[var(--text)]">Thùng rác Quiz</h1>
        <p class="mt-1 text-sm text-slate-600">Các bộ đề đã bị xóa tạm thời. Bạn có thể khôi phục lại hoặc xóa vĩnh viễn.</p>
      </div>
      <RouterLink to="/admin/quizzes" class="btn-secondary text-xs px-3.5 py-1.5">
        ← Quay lại danh sách
      </RouterLink>
    </div>

    <!-- Table Card -->
    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50 text-slate-400 font-bold uppercase text-[10px]">
              <th class="py-3 px-4">Tên quiz</th>
              <th class="py-3 px-4">Người tạo</th>
              <th class="py-3 px-4 text-center">Danh mục</th>
              <th class="py-3 px-4 text-center">Độ khó</th>
              <th class="py-3 px-4 text-right">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-for="quiz in quizzes" :key="quiz.id" class="hover:bg-slate-50">
              <td class="py-3.5 px-4 font-bold text-slate-900 max-w-xs truncate">{{ quiz.title }}</td>
              <td class="py-3.5 px-4 text-slate-500">{{ quiz.user?.name || quiz.author || '-' }}</td>
              <td class="py-3.5 px-4 text-center">
                <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">
                  {{ quiz.category || 'Mặc định' }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-center">
                <span
                  class="rounded px-2 py-0.5 text-[10px] font-bold"
                  :class="quiz.difficulty === 'easy' ? 'bg-emerald-50 text-emerald-700' : quiz.difficulty === 'hard' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700'"
                >
                  {{ quiz.difficulty_label || quiz.difficulty || 'Vừa' }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="restoreQuiz(quiz.id)" class="text-emerald-700 hover:underline font-bold text-xs">
                    Khôi phục
                  </button>
                  <button @click="forceDeleteQuiz(quiz.id)" class="text-red-600 hover:underline font-bold text-xs">
                    Xóa vĩnh viễn
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!loading && quizzes.length === 0">
              <td colspan="5" class="text-center py-10 text-slate-400 text-xs">Thùng rác trống.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="loading" class="text-center py-8 text-xs text-slate-400">Đang tải thùng rác...</div>
    </div>
  </section>
</template>

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
