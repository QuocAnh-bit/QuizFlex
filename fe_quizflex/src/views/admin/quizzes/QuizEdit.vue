<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const saving = ref(false)

const form = ref({
  title: '',
  description: '',
  category: '',
  difficulty: 'medium',
  visibility: 'public',
})

const fetchQuiz = async () => {
  try {
    loading.value = true
    const res = await api.get(`/admin/quizzes/${route.params.id}`)
    const quiz = res.data.data.quiz
    form.value = {
      title: quiz.title || '',
      description: quiz.description || '',
      category: quiz.category || '',
      difficulty: quiz.difficulty || 'medium',
      visibility: quiz.is_public ? 'public' : 'private',
    }
  } catch (error) {
    console.error(error)
    alert('Không tải được quiz')
  } finally {
    loading.value = false
  }
}

const saveQuiz = async () => {
  try {
    saving.value = true
    await api.put(`/quizzes/${route.params.id}`, {
      title: form.value.title,
      description: form.value.description,
      category: form.value.category,
      difficulty: form.value.difficulty,
      visibility: form.value.visibility,
    })
    alert('Cập nhật quiz thành công')
    router.push('/admin/quizzes')
  } catch (error) {
    console.error(error)
    alert('Cập nhật thất bại')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchQuiz()
})
</script>

<template>
  <div class="p-6 bg-white min-h-screen text-black">
    <div v-if="loading" class="text-center text-lg">Đang tải...</div>

    <div v-else class="max-w-2xl">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-black">Sửa Quiz</h1>
        <RouterLink to="/admin/quizzes" class="text-gray-500 hover:text-gray-700">
          ← Quay lại
        </RouterLink>
      </div>

      <!-- Form -->
      <div class="bg-white rounded-xl shadow border p-6 space-y-5">
        <!-- Tên quiz -->
        <div>
          <label class="block font-semibold mb-1 text-black">Tên quiz</label>
          <input
            v-model="form.title"
            type="text"
            class="w-full border rounded-lg p-2 text-black bg-white"
            placeholder="Nhập tên quiz..."
          />
        </div>

        <!-- Mô tả -->
        <div>
          <label class="block font-semibold mb-1 text-black">Mô tả</label>
          <textarea
            v-model="form.description"
            rows="3"
            class="w-full border rounded-lg p-2 text-black bg-white"
            placeholder="Nhập mô tả..."
          />
        </div>

        <!-- Danh mục -->
        <div>
          <label class="block font-semibold mb-1 text-black">Danh mục</label>
          <input
            v-model="form.category"
            type="text"
            class="w-full border rounded-lg p-2 text-black bg-white"
            placeholder="Nhập danh mục..."
          />
        </div>

        <!-- Độ khó -->
        <div>
          <label class="block font-semibold mb-1 text-black">Độ khó</label>
          <select v-model="form.difficulty" class="w-full border rounded-lg p-2 text-black bg-white">
            <option value="easy">Dễ</option>
            <option value="medium">Vừa</option>
            <option value="hard">Khó</option>
          </select>
        </div>

        <!-- Trạng thái -->
        <div>
          <label class="block font-semibold mb-1 text-black">Trạng thái</label>
          <select v-model="form.visibility" class="w-full border rounded-lg p-2 text-black bg-white">
            <option value="public">Public</option>
            <option value="private">Private</option>
            <option value="group">Group</option>
          </select>
        </div>

        <!-- Buttons -->
        <div class="flex gap-3 pt-2">
          <button
            @click="saveQuiz"
            :disabled="saving"
            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            {{ saving ? 'Đang lưu...' : 'Lưu thay đổi' }}
          </button>
          <RouterLink
            to="/admin/quizzes"
            class="bg-gray-200 text-black px-6 py-2 rounded-lg hover:bg-gray-300"
          >
            Hủy
          </RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>