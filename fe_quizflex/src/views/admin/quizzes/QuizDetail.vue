```vue
<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'

const route = useRoute()

const quiz = ref(null)
const loading = ref(false)
const averageScore = ref(0)

const fetchQuizDetail = async () => {
  try {
    loading.value = true

    const res = await api.get(
      `/admin/quizzes/${route.params.id}`
    )

    quiz.value = res.data.data.quiz
    averageScore.value =
      res.data.data.average_score
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchQuizDetail()
})
</script>

<template>
  <div class="p-6 bg-white min-h-screen text-black">

    <div
      v-if="loading"
      class="text-center text-lg"
    >
      Đang tải...
    </div>

    <div
      v-if="quiz"
      class="space-y-6"
    >
      <!-- Title -->
      <h1 class="text-3xl font-bold text-black">
        {{ quiz.title }}
      </h1>

      <!-- Quiz Info -->
      <div
        class="bg-white rounded-xl shadow border p-5"
      >
        <h2
          class="font-bold text-xl mb-4 text-black"
        >
          Thông tin Quiz
        </h2>

        <div class="grid grid-cols-2 gap-4 text-black">
          <p>
            <strong>Người tạo:</strong>
            {{ quiz.user?.name }}
          </p>

          <p>
            <strong>Danh mục:</strong>
            {{ quiz.category }}
          </p>

          <p>
            <strong>Độ khó:</strong>
            {{ quiz.difficulty }}
          </p>
        <div>
  <span class="font-semibold">
    Quiz AI:
  </span>

  <span
    :class="
      quiz.is_ai_generated
        ? 'text-green-600'
        : 'text-red-500'
    "
  >
    {{
      quiz.is_ai_generated
        ? 'Có'
        : 'Không'
    }}
  </span>
</div>
          <p>
            <strong>Trạng thái:</strong>

            <span
              class="font-semibold"
              :class="
                quiz.is_public
                  ? 'text-green-600'
                  : 'text-red-600'
              "
            >
              {{
                quiz.is_public
                  ? 'Public'
                  : 'Private'
              }}
            </span>
          </p>

          <p>
            <strong>Số câu hỏi:</strong>
            {{ quiz.questions_count }}
          </p>

          <p>
            <strong>Lượt làm bài:</strong>
            {{ quiz.attempts_count }}
          </p>

          <p>
            <strong>Điểm trung bình:</strong>
            {{ averageScore }}
          </p>
        </div>
      </div>

      <!-- Questions -->
      <div
        class="bg-white rounded-xl shadow border p-5"
      >
        <h2
          class="font-bold text-xl mb-5 text-black"
        >
          Danh sách câu hỏi
        </h2>

        <div
          v-for="(
            question,
            index
          ) in quiz.questions"
          :key="question.id"
          class="border rounded-lg p-4 mb-4 bg-white"
        >
          <h3
            class="font-semibold text-black mb-3"
          >
            Câu {{ index + 1 }}:
            {{ question.content }}
          </h3>

          <div>
            <div
              v-for="answer in question.answers"
              :key="answer.id"
              class="p-3 rounded-lg mb-2 border text-black"
              :class="
                answer.is_correct
                  ? 'bg-green-100 border-green-400'
                  : 'bg-gray-50'
              "
            >
              {{ answer.content }}

              <span
                v-if="
                  answer.is_correct
                "
                class="text-green-700 font-semibold ml-2"
              >
                ✓ Đáp án đúng
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Attempt History -->
      <div
        class="bg-white rounded-xl shadow border p-5"
      >
        <h2
          class="font-bold text-xl mb-4 text-black"
        >
          Lịch sử làm bài
        </h2>

        <div class="overflow-x-auto">
          <table class="w-full border-collapse">
            <thead
              class="bg-gray-100 text-black"
            >
              <tr>
                <th class="p-3 border">
                  Người dùng
                </th>

                <th class="p-3 border">
                  Điểm
                </th>

                <th class="p-3 border">
                  Trạng thái
                </th>

                <th class="p-3 border">
                  Thời gian
                </th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="attempt in quiz.attempts"
                :key="attempt.id"
                class="hover:bg-gray-50"
              >
                <td
                  class="p-3 border text-center text-black"
                >
                  {{
                    attempt.user?.name
                  }}
                </td>

                <td
                  class="p-3 border text-center text-black"
                >
                  {{ attempt.score }}
                </td>

                <td
                  class="p-3 border text-center"
                >
                  <span
                    class="font-medium"
                    :class="
                      attempt.status ===
                      'completed'
                        ? 'text-green-600'
                        : 'text-orange-500'
                    "
                  >
                    {{
                      attempt.status
                    }}
                  </span>
                </td>

                <td
                  class="p-3 border text-center text-black"
                >
                  {{
                    attempt.created_at
                  }}
                </td>
              </tr>

              <tr
                v-if="
                  !quiz.attempts?.length
                "
              >
                <td
                  colspan="4"
                  class="text-center py-5 text-gray-500"
                >
                  Chưa có lịch sử làm bài
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</template>
```
