```vue
<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/services/api'

const quizzes = ref([])
const loading = ref(false)

const fetchTrash = async () => {
  try {
    loading.value = true

    const res = await api.get(
      '/admin/quizzes/trash'
    )

    quizzes.value =
      res.data.data.data || []
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

const restoreQuiz = async (id) => {
  const confirmRestore = confirm(
    'Khôi phục quiz này?'
  )

  if (!confirmRestore) return

  try {
    await api.post(
      `/admin/quizzes/${id}/restore`
    )

    alert(
      'Khôi phục thành công'
    )

    fetchTrash()
  } catch (error) {
    console.error(error)

    alert(
      'Khôi phục thất bại'
    )
  }
}

const forceDeleteQuiz = async (
  id
) => {
  const confirmDelete = confirm(
    'Xóa vĩnh viễn quiz này? Không thể hoàn tác!'
  )

  if (!confirmDelete) return

  try {
    await api.delete(
      `/admin/quizzes/${id}/force-delete`
    )

    alert(
      'Đã xóa vĩnh viễn'
    )

    fetchTrash()
  } catch (error) {
    console.error(error)

    alert(
      'Xóa thất bại'
    )
  }
}

onMounted(() => {
  fetchTrash()
})
</script>

<template>
  <div
    class="p-6 bg-white min-h-screen text-black"
  >
    <!-- Header -->
    <div
      class="flex justify-between items-center mb-6"
    >
      <div>
        <h1
          class="text-2xl font-bold"
        >
          Thùng rác Quiz
        </h1>

        <p class="text-gray-500">
          Danh sách quiz đã xóa mềm
        </p>
      </div>

      <RouterLink
        to="/admin/quizzes"
        class="bg-blue-600 text-white px-4 py-2 rounded-lg"
      >
        Quay lại
      </RouterLink>
    </div>

    <!-- Table -->
    <div
      class="bg-white rounded-xl border shadow overflow-hidden"
    >
      <table class="w-full">
        <thead
          class="bg-gray-100"
        >
          <tr>
            <th
              class="p-4 text-left"
            >
              Tên quiz
            </th>

            <th
              class="text-center"
            >
              Người tạo
            </th>

            <th
              class="text-center"
            >
              Danh mục
            </th>

            <th
              class="text-center"
            >
              Độ khó
            </th>

            <th
              class="text-center"
            >
              Thao tác
            </th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="quiz in quizzes"
            :key="quiz.id"
            class="border-b hover:bg-gray-50"
          >
            <!-- Title -->
            <td class="p-4">
              {{ quiz.title }}
            </td>

            <!-- Author -->
            <td
              class="text-center"
            >
              {{
                quiz.user?.name ||
                quiz.author ||
                'Chưa có'
              }}
            </td>

            <!-- Category -->
            <td
              class="text-center"
            >
              {{
                quiz.category
              }}
            </td>

            <!-- Difficulty -->
            <td
              class="text-center"
            >
              {{
                quiz.difficulty_label ||
                quiz.difficulty
              }}
            </td>

            <!-- Actions -->
            <td
              class="text-center space-x-3"
            >
              <button
                @click="
                  restoreQuiz(
                    quiz.id
                  )
                "
                class="text-green-600 font-medium"
              >
                Khôi phục
              </button>

              <button
                @click="
                  forceDeleteQuiz(
                    quiz.id
                  )
                "
                class="text-red-600 font-medium"
              >
                Xóa vĩnh viễn
              </button>
            </td>
          </tr>

          <tr
            v-if="
              !loading &&
              quizzes.length === 0
            "
          >
            <td
              colspan="5"
              class="text-center py-8 text-gray-500"
            >
              Không có quiz nào trong thùng rác
            </td>
          </tr>
        </tbody>
      </table>

      <div
        v-if="loading"
        class="text-center py-5"
      >
        Đang tải dữ liệu...
      </div>
    </div>
  </div>
</template>
```
