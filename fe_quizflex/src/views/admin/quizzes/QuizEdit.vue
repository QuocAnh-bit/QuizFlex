<template>
  <section class="max-w-4xl mx-auto py-4 space-y-6">
    <!-- Loading -->
    <div v-if="loading" class="card p-10 text-center text-xs text-slate-400">
      Đang tải thông tin quiz...
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Chỉnh sửa đề thi</p>
          <h1 class="text-3xl font-black tracking-[-0.04em] text-[var(--text)]">Chỉnh sửa Quiz</h1>
          <p class="mt-1 text-sm text-slate-600">Cập nhật thông tin quiz, danh sách câu hỏi và cấu hình đáp án.</p>
        </div>
        <button type="button" class="btn-secondary text-xs px-3.5 py-1.5" @click="goBack">
          ← Quay lại
        </button>
      </div>

      <!-- Main Form Card -->
      <article class="card p-6 sm:p-8 space-y-5">
        <!-- Review Status Banners -->
        <div v-if="form.review_status === 'pending_review'" class="flex items-start gap-3 rounded-2xl border border-amber-500/40 bg-amber-500/10 p-4 text-xs font-bold text-amber-900 shadow-sm">
          <Clock class="mt-0.5 h-4 w-4 shrink-0 text-amber-500" />
          <div class="grid gap-0.5">
            <span class="text-amber-800">Bài Quiz đang trong quá trình Admin xét duyệt công khai</span>
            <span class="text-[11px] font-normal text-amber-700">Nếu bạn chỉnh sửa và lưu nội dung, bài Quiz sẽ trở về bản nháp và bạn cần gửi lại yêu cầu duyệt.</span>
          </div>
        </div>

        <div v-else-if="form.review_status === 'rejected'" class="flex items-start gap-3 rounded-2xl border border-rose-500/40 bg-rose-500/10 p-4 text-xs font-bold text-rose-900 shadow-sm">
          <AlertCircle class="mt-0.5 h-4 w-4 shrink-0 text-rose-500" />
          <div class="grid gap-0.5">
            <span class="text-rose-800">Yêu cầu công khai đã bị Admin từ chối: "{{ form.rejection_reason || 'Nội dung chưa đạt tiêu chuẩn' }}"</span>
            <span class="text-[11px] font-normal text-rose-700">Hãy chỉnh sửa lại các câu hỏi phù hợp và gửi lại yêu cầu duyệt sau khi lưu.</span>
          </div>
        </div>

        <div class="space-y-1">
          <label class="text-xs font-bold text-slate-700 block">Tên Quiz</label>
          <input v-model="form.title" class="field text-xs" placeholder="Nhập tên quiz..." required />
        </div>

        <div class="space-y-1">
          <label class="text-xs font-bold text-slate-700 block">Mô tả</label>
          <textarea v-model="form.description" rows="3" class="field text-xs resize-none" placeholder="Mô tả nội dung quiz..."></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 block">Danh mục</label>
            <input v-model="form.category" class="field text-xs" placeholder="Toán, Lý, Tiếng Anh..." />
          </div>

          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 block">Độ khó</label>
            <select v-model="form.difficulty" class="field text-xs">
              <option value="easy">Dễ</option>
              <option value="medium">Vừa</option>
              <option value="hard">Khó</option>
            </select>
          </div>

          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 block">Hiển thị</label>
            <select v-model="form.visibility" class="field text-xs" :disabled="form.creation_mode === 'manual' && !isAdmin">
              <option v-if="form.creation_mode !== 'manual' || isAdmin" value="public">Public</option>
              <option value="private">Private</option>
              <option value="group">Group</option>
            </select>
            <p v-if="form.creation_mode === 'manual' && !isAdmin" class="text-[10px] text-amber-600 font-medium mt-1">
              Quiz thủ công cần gửi yêu cầu để Admin kiểm duyệt trước khi công khai.
            </p>
          </div>
        </div>

        <!-- Questions Section -->
        <div class="pt-6 border-t border-slate-100 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900">Danh sách câu hỏi ({{ form.questions.length }})</h2>
            <button class="btn-primary text-xs px-3.5 py-1.5" type="button" @click="addQuestion">
              + Thêm câu hỏi
            </button>
          </div>

          <div v-if="hasUnsupportedQuestionTypes" class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs font-bold text-amber-800">
            <TriangleAlert class="mt-0.5 h-4 w-4 shrink-0" />
            <span>Quiz này có câu hỏi dạng "Nhiều đáp án" hoặc "Điền đáp án" — vui lòng dùng Quiz Editor chính để tránh mất dữ liệu khi lưu.</span>
          </div>

          <div class="space-y-4">
            <div
              v-for="(question, qIndex) in form.questions"
              :key="question.id ?? qIndex"
              class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 sm:p-5 space-y-3"
            >
              <div class="flex items-center justify-between border-b border-slate-200/60 pb-2.5">
                <h3 class="text-xs font-bold text-slate-900">Câu hỏi {{ qIndex + 1 }}</h3>
                <button class="text-xs font-bold text-red-600 hover:underline" type="button" @click="removeQuestion(qIndex)">
                  Xóa câu
                </button>
              </div>

              <div class="space-y-1">
                <label class="text-[11px] font-bold text-slate-600 block">Nội dung câu hỏi</label>
                <textarea v-model="question.content" rows="2" class="field text-xs resize-none" placeholder="Nhập câu hỏi..."></textarea>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1">
                  <label class="text-[11px] font-bold text-slate-600 block">Điểm số</label>
                  <input type="number" min="1" class="field text-xs" v-model="question.points" />
                </div>

                <div class="space-y-1">
                  <label class="text-[11px] font-bold text-slate-600 block">Loại câu hỏi</label>
                  <select class="field text-xs" v-model="question.type" :disabled="question.type !== 'single_choice'">
                    <option value="single_choice">Trắc nghiệm một đáp án</option>
                  </select>
                </div>
              </div>

              <!-- Answers -->
              <div class="space-y-2 pt-2 border-t border-slate-200/60">
                <div class="flex items-center justify-between">
                  <span class="text-[11px] font-bold text-slate-700">Các lựa chọn đáp án:</span>
                  <button class="text-xs font-bold text-[#7C3AED] hover:underline" type="button" @click="addAnswer(question)">
                    + Thêm đáp án
                  </button>
                </div>

                <div
                  v-for="(answer, aIndex) in question.answers"
                  :key="answer.id ?? aIndex"
                  class="flex items-center gap-2"
                >
                  <input
                    type="radio"
                    :name="'correct-' + qIndex"
                    :checked="answer.is_correct"
                    class="rounded text-[#7C3AED]"
                    @change="setCorrect(question, answer)"
                  />
                  <input
                    v-model="answer.content"
                    class="field text-xs flex-1"
                    placeholder="Nhập nội dung đáp án..."
                  />
                  <button
                    class="h-8 w-8 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 flex items-center justify-center text-xs"
                    type="button"
                    @click="removeAnswer(question, aIndex)"
                  >
                    ✕
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
          <RouterLink to="/admin/quizzes" class="btn-secondary text-xs px-3.5 py-2">
            Hủy
          </RouterLink>
          <button class="btn-primary text-xs px-5 py-2" type="button" :disabled="saving || hasUnsupportedQuestionTypes" @click="saveQuiz">
            {{ saving ? 'Đang lưu...' : 'Lưu thay đổi' }}
          </button>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import { useAppLoading } from '@/composables/useAppLoading'
const { beginTask, endTask } = useAppLoading()

import { ref, computed, onMounted, inject } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { TriangleAlert, Clock, AlertCircle } from 'lucide-vue-next'
import api, { authApi, currentUserStorage } from '@/services/api'

const route = useRoute()
const router = useRouter()
const loading = ref(false)
const saving = ref(false)
const showToast = inject('showToast')

const currentUser = currentUserStorage.get()
const isAdmin = computed(() => currentUser && String(currentUser.role || '').toLowerCase() === 'admin')

const form = ref({
  title: '',
  description: '',
  category: '',
  difficulty: 'medium',
  creation_mode: 'manual',
  review_status: 'draft',
  rejection_reason: '',
  visibility: 'private',
  questions: []
})

const SUPPORTED_TYPES = ['single_choice']

const hasUnsupportedQuestionTypes = computed(() =>
  form.value.questions.some((q) => !SUPPORTED_TYPES.includes(q.type))
)

const fetchQuiz = async () => {
    beginTask()
    try {
try {
    loading.value = true
    const res = await api.get(`/admin/quizzes/${route.params.id}`)
    const quiz = res.data.data.quiz

    form.value = {
      title: quiz.title || '',
      description: quiz.description || '',
      category: quiz.category || '',
      difficulty: quiz.difficulty || 'medium',
      creation_mode: quiz.creation_mode || 'manual',
      review_status: quiz.review_status || 'draft',
      rejection_reason: quiz.rejection_reason || '',
      visibility: quiz.room_code ? 'group' : (quiz.is_public ? 'public' : 'private'),
      questions: (quiz.questions || []).map(q => ({
        id: q.id,
        content: q.content,
        type: q.type,
        points: q.points,
        order: q.order,
        answers: (q.answers || []).map(a => ({
          id: a.id,
          content: a.content,
          is_correct: a.is_correct,
          order: a.order
        }))
      }))
    }
  } catch (err) {
    console.error(err)
    if (showToast) showToast('Không tải được quiz', 'error')
  } finally {
    loading.value = false
  }
    } finally {
      endTask()
    }
  }

const addQuestion = () => {
  form.value.questions.push({
    id: null,
    content: '',
    type: 'single_choice',
    points: 10,
    order: form.value.questions.length + 1,
    answers: [
      { id: null, content: '', is_correct: true, order: 1 },
      { id: null, content: '', is_correct: false, order: 2 },
      { id: null, content: '', is_correct: false, order: 3 },
      { id: null, content: '', is_correct: false, order: 4 }
    ]
  })
}

const removeQuestion = (index) => {
  form.value.questions.splice(index, 1)
}

const addAnswer = (question) => {
  question.answers.push({
    id: null,
    content: '',
    is_correct: false,
    order: question.answers.length
  })
}

const removeAnswer = (question, index) => {
  if (question.answers.length <= 2) {
    if (showToast) showToast('Câu hỏi phải có ít nhất 2 đáp án', 'error')
    return
  }
  question.answers.splice(index, 1)
}

const setCorrect = (question, answer) => {
  question.answers.forEach(item => {
    item.is_correct = false
  })
  answer.is_correct = true
}

const validateBeforeSave = () => {
  if (!form.value.title.trim()) return 'Bạn chưa nhập tên quiz.'
  if (form.value.questions.length === 0) return 'Quiz cần ít nhất 1 câu hỏi.'

  for (const [index, question] of form.value.questions.entries()) {
    if (!question.content.trim()) return `Câu ${index + 1} chưa có nội dung.`
    if (question.answers.length < 2) return `Câu ${index + 1} cần ít nhất 2 đáp án.`
    if (question.answers.some((answer) => !answer.content.trim())) {
      return `Câu ${index + 1} còn đáp án trống.`
    }
    if (!question.answers.some((answer) => answer.is_correct)) {
      return `Câu ${index + 1} chưa chọn đáp án đúng.`
    }
  }
  return ''
}

const saveQuiz = async () => {
  const validationError = validateBeforeSave()
  if (validationError) {
    if (showToast) showToast(validationError, 'error')
    return
  }

  if (hasUnsupportedQuestionTypes.value) {
    if (showToast) showToast('Quiz này có câu hỏi không hỗ trợ sửa ở đây.', 'error')
    return
  }

  try {
    saving.value = true
    await api.put(`/quizzes/${route.params.id}`, {
      title: form.value.title.trim(),
      description: form.value.description.trim(),
      category: form.value.category.trim(),
      difficulty: form.value.difficulty,
      visibility: form.value.visibility,
      questions: form.value.questions
    })

    if (showToast) showToast('Cập nhật quiz thành công', 'success')
    setTimeout(() => {
      if (route.path.startsWith('/admin')) {
        router.push('/admin/quizzes')
      } else {
        router.push('/dashboard/questions')
      }
    }, 800)
  } catch (err) {
    console.error(err)
    if (showToast) showToast('Cập nhật thất bại', 'error')
  } finally {
    saving.value = false
  }
}

const goBack = () => {
  if (window.history.length > 1) {
    router.back()
  } else if (route.path.startsWith('/admin')) {
    router.push('/admin/quizzes')
  } else {
    router.push('/dashboard/questions')
  }
}

onMounted(fetchQuiz)
</script>
