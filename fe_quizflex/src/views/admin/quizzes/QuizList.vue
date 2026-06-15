<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const quizzes = ref([])
const loading = ref(false)

const filters = ref({
  search: '',
  creator: '',
  difficulty: '',
  visibility: ''
})

const fetchQuizzes = async () => {
  try {
    loading.value = true
    const res = await api.get('/admin/quizzes', { params: filters.value })
    quizzes.value = res.data.data.data || []
  } catch (error) {
    console.error('Lỗi lấy quiz:', error)
  } finally {
    loading.value = false
  }
}

const deleteQuiz = async (id) => {
  if (!confirm('Bạn có chắc muốn xóa quiz này?')) return
  try {
    await api.delete(`/admin/quizzes/${id}`)
    alert('Xóa quiz thành công')
    fetchQuizzes()
  } catch (error) {
    console.error(error)
    alert('Xóa thất bại')
  }
}

onMounted(() => {
  fetchQuizzes()
})
</script>

<template>
  <div class="p-6 bg-white min-h-screen text-black">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-black">Quản lý Quiz</h1>
      <RouterLink to="/admin/quizzes-trash" class="bg-red-500 text-white px-4 py-2 rounded-lg">
        Thùng rác
      </RouterLink>
    </div>

    <!-- Search + Filter -->
    <div class="grid grid-cols-4 gap-4 mb-5">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Tìm quiz..."
        class="border p-2 rounded-lg text-black bg-white"
      />
      <input
        v-model="filters.creator"
        type="text"
        placeholder="Người tạo..."
        class="border p-2 rounded-lg text-black bg-white"
      />
      <select v-model="filters.difficulty" class="border p-2 rounded-lg text-black bg-white">
        <option value="">Độ khó</option>
        <option value="easy">Dễ</option>
        <option value="medium">Vừa</option>
        <option value="hard">Khó</option>
      </select>
      <select v-model="filters.visibility" class="border p-2 rounded-lg text-black bg-white">
        <option value="">Hiển thị</option>
        <option value="public">Public</option>
        <option value="private">Private</option>
      </select>
    </div>

    <button @click="fetchQuizzes" class="bg-blue-600 text-white px-4 py-2 rounded-lg mb-5">
      Tìm kiếm
    </button>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow border overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-100 text-black">
          <tr>
            <th class="p-4 text-left">Tên quiz</th>
            <th class="text-center">Người tạo</th>
            <th class="text-center">Danh mục</th>
            <th class="text-center">Độ khó</th>
            <th class="text-center">Lượt làm</th>
            <th class="text-center">Điểm TB</th>
            <th class="text-center">Trạng thái</th>
            <th class="text-center">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="quiz in quizzes" :key="quiz.id" class="border-b hover:bg-gray-50">
            <td class="p-4 text-black">{{ quiz.title }}</td>
            <td class="text-center text-black">{{ quiz.user?.name || quiz.author || 'Chưa có' }}</td>
            <td class="text-center text-black">{{ quiz.category || 'Chưa có' }}</td>
            <td class="text-center text-black">
              {{ quiz.difficulty === 'easy' ? 'Dễ' : quiz.difficulty === 'hard' ? 'Khó' : 'Vừa' }}
            </td>
            <td class="text-center text-black">{{ quiz.attempts_count ?? 0 }}</td>
            <td class="text-center text-black">{{ quiz.avg_score ?? 0 }}</td>
            <td class="text-center">
              <span
                class="px-3 py-1 rounded-full text-white"
                :class="quiz.is_public ? 'bg-green-500' : 'bg-red-500'"
              >
                {{ quiz.is_public ? 'Public' : 'Private' }}
              </span>
            </td>
            <td class="text-center space-x-3 p-3">
              <RouterLink :to="`/admin/quizzes/${quiz.id}`" class="text-blue-600 font-medium">
                Chi tiết
              </RouterLink>
              <RouterLink :to="`/admin/quizzes/${quiz.id}/edit`" class="text-yellow-600 font-medium">
                Sửa
              </RouterLink>
              <button @click="deleteQuiz(quiz.id)" class="text-red-600 font-medium">
                Xóa
              </button>
            </td>
          </tr>
          <tr v-if="!loading && quizzes.length === 0">
            <td colspan="8" class="text-center py-8 text-gray-500">Không có quiz nào</td>
          </tr>
        </tbody>
      </table>
      <div v-if="loading" class="text-center py-5 text-black">Đang tải dữ liệu...</div>
    </div>
  </div>
</template>