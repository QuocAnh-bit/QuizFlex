<template>
  <section class="max-w-6xl mx-auto py-4 space-y-6">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Kho đề thi</p>
        <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">Quản lý Quiz</h1>
        <p class="mt-1 text-sm text-slate-600">Quản lý tất cả bộ đề trắc nghiệm trong hệ thống, xem phân tích và hiệu suất.</p>
      </div>
      <div class="flex items-center gap-2.5">
        <RouterLink to="/admin/quizzes-trash" class="rounded-lg border border-red-200 bg-red-50 px-3.5 py-1.5 text-xs font-bold text-red-700 hover:bg-red-100 transition">
          🗑️ Thùng rác
        </RouterLink>
      </div>
    </div>

    <!-- Search & Filter Card -->
    <article class="card p-5 space-y-3">
      <div class="grid gap-3 sm:grid-cols-3">
        <input
          v-model="filters.search"
          type="text"
          placeholder="🔍 Tìm theo tên quiz..."
          class="field text-xs"
          @keyup.enter="fetchQuizzes"
        />
        <input
          v-model="filters.creator"
          type="text"
          placeholder="👤 Tìm theo người tạo..."
          class="field text-xs"
          @keyup.enter="fetchQuizzes"
        />
        <input
          v-model="filters.category"
          type="text"
          placeholder="📁 Tìm theo danh mục..."
          class="field text-xs"
          @keyup.enter="fetchQuizzes"
        />
      </div>

      <div class="grid gap-3 sm:grid-cols-4 items-center">
        <select v-model="filters.difficulty" class="field text-xs" @change="fetchQuizzes">
          <option value="">Mọi độ khó</option>
          <option value="easy">Dễ</option>
          <option value="medium">Vừa</option>
          <option value="hard">Khó</option>
        </select>
        <select v-model="filters.visibility" class="field text-xs" @change="fetchQuizzes">
          <option value="">Mọi hiển thị</option>
          <option value="public">🌐 Công khai (Public)</option>
          <option value="private">🔒 Riêng tư (Private)</option>
        </select>
        <select v-model="filters.ai_generated" class="field text-xs" @change="fetchQuizzes">
          <option value="">Mọi nguồn tạo</option>
          <option value="1">🤖 AI sinh đề</option>
          <option value="0">✍️ Thủ công</option>
        </select>
        <button @click="fetchQuizzes" class="btn-primary text-xs py-2.5">
          Tìm kiếm
        </button>
      </div>
    </article>

    <!-- Table Card -->
    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50 text-slate-400 font-bold uppercase text-[10px]">
              <th class="py-3 px-4">Tên Quiz</th>
              <th class="py-3 px-4">Tác giả</th>
              <th class="py-3 px-4 text-center">Danh mục</th>
              <th class="py-3 px-4 text-center">Độ khó</th>
              <th class="py-3 px-4 text-center">Lượt làm</th>
              <th class="py-3 px-4 text-center">Điểm TB</th>
              <th class="py-3 px-4 text-center">Trạng thái</th>
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
                  {{ quiz.difficulty === 'easy' ? 'Dễ' : quiz.difficulty === 'hard' ? 'Khó' : 'Vừa' }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-center font-bold text-slate-800">{{ quiz.attempts_count ?? 0 }}</td>
              <td class="py-3.5 px-4 text-center font-bold text-[#7C3AED]">{{ quiz.avg_score ?? 0 }}%</td>
              <td class="py-3.5 px-4 text-center">
                <span
                  class="rounded px-2 py-0.5 text-[10px] font-bold"
                  :class="quiz.is_public ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'"
                >
                  {{ quiz.is_public ? 'Public' : 'Private' }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <RouterLink :to="`/admin/quizzes/${quiz.id}`" class="text-[#7C3AED] hover:underline font-bold text-xs">
                    Chi tiết
                  </RouterLink>
                  <RouterLink :to="`/admin/quizzes/${quiz.id}/edit`" class="text-amber-600 hover:underline font-bold text-xs">
                    Sửa
                  </RouterLink>
                  <button @click="deleteQuiz(quiz.id)" class="text-red-600 hover:underline font-bold text-xs">
                    Xóa
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!loading && quizzes.length === 0">
              <td colspan="8" class="text-center py-10 text-slate-400 text-xs">Không có quiz nào phù hợp.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="loading" class="text-center py-8 text-xs text-slate-400">Đang tải danh sách quiz...</div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue'
import api from '@/services/api'

const showToast = inject('showToast')
const showConfirm = inject('showConfirm')

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

const fetchQuizzes = async () => {
  try {
    loading.value = true
    const res = await api.get('/admin/quizzes', { params: filters.value })
    quizzes.value = res.data.data.data || []
  } catch (error) {
    console.error('Lỗi lấy quiz:', error)
    if (showToast) showToast('Không tải được danh sách quiz', 'error')
  } finally {
    loading.value = false
  }
}

const deleteQuiz = (id) => {
  if (showConfirm) {
    showConfirm(
      'Xác nhận xóa',
      'Bạn có chắc chắn muốn xóa mềm bộ quiz này không? Bạn có thể khôi phục lại sau từ thùng rác.',
      async () => {
        try {
          await api.delete(`/admin/quizzes/${id}`)
          if (showToast) showToast('Xóa quiz thành công', 'success')
          fetchQuizzes()
        } catch (error) {
          console.error(error)
          if (showToast) showToast('Xóa thất bại: ' + (error.response?.data?.message || error.message), 'error')
        }
      }
    )
  }
}

onMounted(() => {
  fetchQuizzes()
})
</script>