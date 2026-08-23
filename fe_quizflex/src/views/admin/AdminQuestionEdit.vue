<template>
  <section class="space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-slate-500">
      <router-link to="/admin" class="hover:text-[#7C3AED] transition-colors">
        Dashboard
      </router-link>
      <ChevronRight class="h-4 w-4" />
      <router-link to="/admin/question-bank" class="hover:text-[#7C3AED] transition-colors">
        Ngân hàng câu hỏi
      </router-link>
      <ChevronRight class="h-4 w-4" />
      <span class="font-medium text-slate-900">
        {{ isEditMode ? `Chỉnh sửa #${questionId}` : 'Tạo câu hỏi mới' }}
      </span>
    </div>

    <!-- Header -->
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex items-start gap-4">
          <button
            type="button"
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 cursor-pointer"
            title="Quay lại"
            @click="goBack"
          >
            <ArrowLeft class="h-5 w-5" />
          </button>

          <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
              {{ isEditMode ? `Chỉnh sửa câu hỏi #${questionId}` : 'Tạo câu hỏi mới' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">
              Soạn thảo nội dung, đính kèm hình ảnh, thiết lập đáp án đúng và phân loại thuộc tính.
            </p>
          </div>
        </div>

        <!-- Top actions -->
        <div class="flex flex-wrap items-center gap-3">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 cursor-pointer"
            @click="goBack"
          >
            <X class="h-4 w-4" />
            Hủy bỏ
          </button>

          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-[#7C3AED] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#6D28D9] disabled:opacity-60 cursor-pointer"
            :disabled="isSubmitting"
            @click="saveQuestion"
          >
            <Save class="h-4 w-4" />
            {{ isSubmitting ? 'Đang lưu...' : (isEditMode ? 'Lưu thay đổi' : 'Tạo câu hỏi') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div
      v-if="isLoading"
      class="rounded-2xl border border-slate-200 bg-white py-16 text-center shadow-sm"
    >
      <div class="mx-auto mb-3 h-8 w-8 animate-spin rounded-full border-4 border-[#7C3AED] border-t-transparent"></div>
      <p class="text-sm font-medium text-slate-500">Đang nạp dữ liệu câu hỏi...</p>
    </div>

    <!-- Form -->
    <form v-else @submit.prevent="saveQuestion" class="grid gap-6 lg:grid-cols-12">
      <!-- LEFT COLUMN -->
      <div class="space-y-6 lg:col-span-8">
        <!-- Question Content -->
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 space-y-5">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <label class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
              <FileText class="h-4 w-4" />
              Nội dung câu hỏi *
            </label>
            <span class="text-xs text-slate-500">Hỗ trợ xuống dòng</span>
          </div>

          <textarea
            v-model="form.content"
            rows="5"
            required
            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-base font-medium leading-relaxed text-slate-900 placeholder:text-slate-400 focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
            placeholder="Nhập câu hỏi trắc nghiệm ở đây..."
          ></textarea>

          <!-- Image URL -->
          <div class="space-y-2 border-t border-slate-100 pt-4">
            <label class="flex items-center gap-2 text-xs font-medium text-slate-500">
              <ImageIcon class="h-4 w-4" />
              Đường dẫn hình ảnh minh họa (URL)
            </label>
            <input
              v-model="form.image_url"
              type="url"
              class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
              placeholder="https://example.com/image.png"
            />

            <div
              v-if="form.image_url"
              class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-slate-50 max-w-md"
            >
              <p class="border-b border-slate-100 px-3 py-1.5 text-xs font-medium text-slate-500">
                Xem trước hình ảnh
              </p>
              <img
                :src="form.image_url"
                alt="Preview"
                class="max-h-60 w-full object-contain p-3"
                @error="onImageError"
              />
            </div>
          </div>
        </article>

        <!-- Answers Editor -->
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 space-y-5">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <label class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
              <ListChecks class="h-4 w-4" />
              Phương án đáp án *
            </label>
            <button
              type="button"
              class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-800 hover:underline cursor-pointer"
              @click="addAnswerRow"
            >
              <Plus class="h-4 w-4" />
              Thêm phương án
            </button>
          </div>

          <div class="space-y-3">
            <div
              v-for="(ans, index) in form.answers"
              :key="index"
              class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 transition focus-within:border-slate-300 sm:flex-row sm:items-center"
            >
              <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700">
                {{ String.fromCharCode(65 + index) }}
              </span>

              <input
                v-model="ans.content"
                type="text"
                required
                :placeholder="`Nội dung phương án ${String.fromCharCode(65 + index)}...`"
                class="flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
              />

              <label
                class="flex cursor-pointer items-center gap-2 rounded-lg border px-3.5 py-2.5 text-xs font-medium transition shrink-0"
                :class="ans.is_correct
                  ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                  : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
              >
                <input
                  type="checkbox"
                  v-model="ans.is_correct"
                  class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                />
                <span>{{ ans.is_correct ? 'Đáp án đúng' : 'Đáp án sai' }}</span>
              </label>

              <button
                v-if="form.answers.length > 2"
                type="button"
                class="self-end rounded-lg p-2 text-rose-500 transition hover:bg-rose-50 sm:self-center cursor-pointer"
                title="Xóa phương án này"
                @click="removeAnswerRow(index)"
              >
                <X class="h-4 w-4" />
              </button>
            </div>
          </div>
        </article>
      </div>

      <!-- RIGHT COLUMN -->
      <div class="space-y-6 lg:col-span-4">
        <!-- Classification -->
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
          <h3 class="flex items-center gap-2 border-b border-slate-100 pb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
            <Settings class="h-4 w-4" />
            Phân loại & Thuộc tính
          </h3>

          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-500">Môn học</label>
            <select
              v-model="form.subject_id"
              class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
            >
              <option :value="null">-- Chọn môn học --</option>
              <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>

          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-500">Khối lớp</label>
            <select
              v-model="form.grade_id"
              class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
            >
              <option :value="null">-- Chọn khối lớp --</option>
              <option v-for="g in grades" :key="g.id" :value="g.id">{{ g.name }}</option>
            </select>
          </div>

          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-500">Chủ đề bài học</label>
            <input
              v-model="form.topic_name"
              type="text"
              class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
              placeholder="VD: Hàm số, Văn học dân gian..."
            />
          </div>

          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-500">Độ khó</label>
            <select
              v-model="form.difficulty"
              class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
            >
              <option value="easy">Dễ (Nhận biết)</option>
              <option value="medium">Vừa (Thông hiểu)</option>
              <option value="hard">Khó (Vận dụng)</option>
            </select>
          </div>

          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-500">Điểm số</label>
            <input
              v-model.number="form.points"
              type="number"
              min="1"
              max="100"
              class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
            />
          </div>
        </article>

        <!-- Visibility -->
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
          <h3 class="flex items-center gap-2 border-b border-slate-100 pb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
            <Globe class="h-4 w-4" />
            Quyền hiển thị
          </h3>

          <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:bg-slate-100">
            <div>
              <p class="text-sm font-medium text-slate-900">Công khai ngân hàng chung</p>
              <p class="text-xs text-slate-500">Mọi người dùng có thể tìm & sử dụng câu này</p>
            </div>
            <input
              type="checkbox"
              v-model="form.is_public"
              class="h-5 w-5 rounded border-slate-300 text-[#7C3AED] focus:ring-[#7C3AED]"
            />
          </label>
        </article>

        <!-- Bottom actions -->
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-3">
          <button
            type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#7C3AED] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#6D28D9] disabled:opacity-60"
            :disabled="isSubmitting"
          >
            <Save class="h-4 w-4" />
            {{ isSubmitting ? 'Đang lưu...' : (isEditMode ? 'Lưu thay đổi câu hỏi' : 'Tạo mới câu hỏi') }}
          </button>

          <button
            type="button"
            class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
            @click="goBack"
          >
            <ArrowLeft class="h-4 w-4" />
            Hủy bỏ & Quay lại
          </button>
        </article>
      </div>
    </form>
  </section>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ChevronRight,
  ArrowLeft,
  X,
  Save,
  FileText,
  Image as ImageIcon,
  ListChecks,
  Plus,
  Settings,
  Globe
} from 'lucide-vue-next'
import { adminQuestionsApi, formatApiErrorMessage, taxonomyApi } from '@/services/api'

const route = useRoute()
const router = useRouter()
const showToast = inject('showToast')

const questionId = computed(() => route.params.id)
const isEditMode = computed(() => Boolean(questionId.value))

const isLoading = ref(false)
const isSubmitting = ref(false)

const subjects = ref([])
const grades = ref([])

const form = reactive({
  content: '',
  image_url: '',
  difficulty: 'medium',
  points: 10,
  subject_id: null,
  grade_id: null,
  topic_name: '',
  is_public: true,
  answers: [
    { content: '', is_correct: true },
    { content: '', is_correct: false },
    { content: '', is_correct: false },
    { content: '', is_correct: false }
  ]
})

const fetchTaxonomies = async () => {
  try {
    const tree = await taxonomyApi.tree()
    if (tree) {
      if (Array.isArray(tree.subjects)) subjects.value = tree.subjects
      if (Array.isArray(tree.education_levels)) {
        const grList = []
        tree.education_levels.forEach(el => {
          if (Array.isArray(el.grades)) {
            el.grades.forEach(g => {
              if (!grList.some(existing => existing.id === g.id)) grList.push(g)
            })
          }
        })
        grades.value = grList
      }
    }
  } catch (e) {
    console.error('Lỗi khi tải danh mục môn/lớp:', e)
  }
}

const loadQuestionDetail = async () => {
  if (!isEditMode.value) return
  isLoading.value = true
  try {
    const data = await adminQuestionsApi.get(questionId.value)
    if (data) {
      form.content = data.content || ''
      form.image_url = data.image_url || ''
      form.difficulty = data.difficulty || 'medium'
      form.points = data.points || 10
      form.subject_id = data.subject_id || null
      form.grade_id = data.grade_id || null
      form.topic_name = data.topic_name || ''
      form.is_public = Boolean(data.is_public)

      if (Array.isArray(data.answers) && data.answers.length > 0) {
        form.answers = data.answers.map(a => ({
          id: a.id,
          content: a.content || a.text || '',
          is_correct: Boolean(a.is_correct)
        }))
      }
    }
  } catch (err) {
    if (showToast) showToast(`Không tải được câu hỏi: ${err.message}`, 'error')
  } finally {
    isLoading.value = false
  }
}

const addAnswerRow = () => {
  form.answers.push({ content: '', is_correct: false })
}

const removeAnswerRow = (index) => {
  if (form.answers.length > 2) {
    form.answers.splice(index, 1)
  }
}

const onImageError = () => {
  if (showToast) showToast('Không thể tải hình ảnh từ URL đã nhập.', 'warning')
}

const goBack = () => {
  if (isEditMode.value) {
    router.push(`/admin/questions/${questionId.value}`)
  } else {
    router.push('/admin/question-bank')
  }
}

const saveQuestion = async () => {
  if (!form.content.trim()) {
    if (showToast) showToast('Vui lòng nhập nội dung câu hỏi!', 'error')
    return
  }

  for (let i = 0; i < form.answers.length; i++) {
    if (!form.answers[i].content.trim()) {
      if (showToast) {
        showToast(`Vui lòng nhập nội dung cho Phương án ${String.fromCharCode(65 + i)}!`, 'error')
      }
      return
    }
  }

  const hasCorrectAnswer = form.answers.some(a => a.is_correct)
  if (!hasCorrectAnswer) {
    if (showToast) showToast('Vui lòng đánh dấu ít nhất 1 đáp án đúng!', 'warning')
    return
  }

  isSubmitting.value = true
  try {
    if (isEditMode.value) {
      await adminQuestionsApi.update(questionId.value, form)
      if (showToast) showToast('Cập nhật câu hỏi thành công!', 'success')
      router.push(`/admin/questions/${questionId.value}`)
    } else {
      const created = await adminQuestionsApi.createQuestion(form)
      if (showToast) showToast('Tạo câu hỏi mới thành công!', 'success')
      if (created && created.id) {
        router.push(`/admin/questions/${created.id}`)
      } else {
        router.push('/admin/question-bank')
      }
    }
  } catch (err) {
    const msg = formatApiErrorMessage(err, 'Lưu thất bại. Vui lòng kiểm tra lại.')
    if (showToast) showToast(msg, 'error')
  } finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  fetchTaxonomies()
  loadQuestionDetail()
})
</script>