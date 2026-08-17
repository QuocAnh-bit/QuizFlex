<template>
  <section class="grid gap-6">
    <!-- Top Breadcrumb & Actions Header Bar -->
    <div class="space-y-4">
      <div class="flex items-center gap-2 text-xs font-bold text-[var(--muted)]">
        <router-link to="/admin" class="hover:text-[var(--primary)] transition">Dashboard</router-link>
        <span>&rsaquo;</span>
        <router-link to="/admin/question-bank" class="hover:text-[var(--primary)] transition">Question Bank</router-link>
        <span>&rsaquo;</span>
        <span class="text-[var(--text)]">{{ isEditMode ? `Chỉnh sửa câu hỏi #${questionId}` : 'Tạo câu hỏi mới' }}</span>
      </div>

      <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
        <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-center">
          <div class="flex items-center gap-4">
            <button
              type="button"
              class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] text-lg text-[var(--text)] transition hover:bg-[var(--primary)] hover:text-white"
              title="Quay lại"
              @click="goBack"
            >
              ←
            </button>
            <div>
              <h1 class="text-3xl font-black tracking-[-0.05em] text-[var(--text)]">
                {{ isEditMode ? `Chỉnh sửa câu hỏi #${questionId}` : 'Tạo câu hỏi mới' }}
              </h1>
              <p class="mt-1 text-xs sm:text-sm text-[var(--muted)]">
                Soạn thảo nội dung câu hỏi, đính kèm hình ảnh, thiết lập đáp án đúng và phân loại thuộc tính môn học.
              </p>
            </div>
          </div>

          <!-- Sticky Top Action Buttons -->
          <div class="flex flex-wrap items-center gap-3">
            <button
              type="button"
              class="btn-ghost flex items-center gap-2 px-5 py-2.5 text-xs font-bold"
              @click="goBack"
            >
              <span>✕</span>
              <span>Hủy bỏ</span>
            </button>

            <button
              type="button"
              class="btn-primary flex items-center gap-2 px-6 py-2.5 text-xs font-black shadow-xl"
              :disabled="isSubmitting"
              @click="saveQuestion"
            >
              <span>💾</span>
              <span>{{ isSubmitting ? 'Đang lưu...' : (isEditMode ? 'Lưu thay đổi' : 'Tạo câu hỏi') }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-12 text-center text-sm font-bold text-[var(--muted)] shadow-[var(--shadow-card)]">
      <div class="mx-auto mb-3 h-10 w-10 animate-spin rounded-full border-4 border-[var(--primary)] border-t-transparent"></div>
      Đang nạp dữ liệu câu hỏi...
    </div>

    <!-- Main 2-Column Form Layout -->
    <form v-else @submit.prevent="saveQuestion" class="grid gap-6 lg:grid-cols-12">
      <!-- LEFT COLUMN (65% width - Editor & Answers) -->
      <div class="space-y-6 lg:col-span-8">
        <!-- 1. Question Content Form Card -->
        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 sm:p-8 shadow-[var(--shadow-card)] backdrop-blur-2xl space-y-4">
          <div class="flex items-center justify-between border-b border-[var(--border)] pb-3">
            <label class="text-xs font-black uppercase tracking-wider text-[var(--primary)]">
              📝 Nội dung câu hỏi *
            </label>
            <span class="text-xs text-[var(--muted)]">Hỗ trợ định dạng văn bản & xuống dòng</span>
          </div>

          <textarea
            v-model="form.content"
            rows="5"
            required
            class="field w-full text-base font-semibold leading-relaxed"
            placeholder="Nhập câu hỏi trắc nghiệm ở đây..."
          ></textarea>

          <!-- Optional Image URL Input & Live Preview -->
          <div class="space-y-2 pt-2 border-t border-[var(--border)]">
            <label class="block text-xs font-bold text-[var(--muted)]">
              🖼️ Đường dẫn hình ảnh minh họa (URL)
            </label>
            <input
              v-model="form.image_url"
              type="url"
              class="field w-full text-xs font-semibold"
              placeholder="https://example.com/image.png"
            />

            <!-- Live Image Thumbnail Preview -->
            <div v-if="form.image_url" class="mt-3 overflow-hidden rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] max-w-md">
              <p class="p-2 text-[10px] font-bold text-[var(--muted)] border-b border-[var(--border)]">Xem trước hình ảnh:</p>
              <img :src="form.image_url" alt="Preview Image" class="max-h-60 w-full object-contain p-2" @error="onImageError" />
            </div>
          </div>
        </article>

        <!-- 2. Answers Options Editor Card -->
        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 sm:p-8 shadow-[var(--shadow-card)] backdrop-blur-2xl space-y-4">
          <div class="flex items-center justify-between border-b border-[var(--border)] pb-3">
            <label class="text-xs font-black uppercase tracking-wider text-[var(--primary)]">
              🎯 Danh sách phương án đáp án *
            </label>
            <button
              type="button"
              class="text-xs font-black text-[var(--primary)] hover:underline flex items-center gap-1"
              @click="addAnswerRow"
            >
              <span>+ Thêm phương án</span>
            </button>
          </div>

          <div class="space-y-3">
            <div
              v-for="(ans, index) in form.answers"
              :key="index"
              class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 transition focus-within:border-[var(--border-strong)]"
            >
              <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-black/30 text-xs font-black text-[var(--text)] border border-[var(--border)]">
                {{ String.fromCharCode(65 + index) }}
              </span>

              <input
                v-model="ans.content"
                type="text"
                required
                :placeholder="`Nội dung phương án ${String.fromCharCode(65 + index)}...`"
                class="field flex-1 text-sm font-semibold"
              />

              <!-- Correct Answer Checkbox -->
              <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-emerald-400 shrink-0 bg-emerald-500/10 border border-emerald-500/30 px-3.5 py-2.5 rounded-xl transition hover:bg-emerald-500/20">
                <input
                  type="checkbox"
                  v-model="ans.is_correct"
                  class="h-4 w-4 accent-emerald-500 cursor-pointer"
                />
                <span>{{ ans.is_correct ? '✓ Đáp án đúng' : 'Đáp án sai' }}</span>
              </label>

              <!-- Remove Answer Option Row -->
              <button
                type="button"
                class="text-rose-400 hover:text-rose-300 text-sm font-black p-2 shrink-0 self-end sm:self-center"
                title="Xóa phương án này"
                v-if="form.answers.length > 2"
                @click="removeAnswerRow(index)"
              >
                ✕
              </button>
            </div>
          </div>
        </article>
      </div>

      <!-- RIGHT COLUMN (35% width - Classification & Config) -->
      <div class="space-y-6 lg:col-span-4">
        <!-- 1. Categorization & Metadata Panel Card -->
        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl space-y-4">
          <h3 class="text-xs font-black uppercase tracking-wider text-[var(--primary)] border-b border-[var(--border)] pb-3">
            ⚙️ Phân loại & Thuộc tính
          </h3>

          <!-- Subject Select -->
          <div>
            <label class="block text-xs font-bold text-[var(--muted)] mb-1.5">Môn học</label>
            <select v-model="form.subject_id" class="field text-xs font-bold">
              <option :value="null">-- Chọn môn học --</option>
              <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>

          <!-- Grade Select -->
          <div>
            <label class="block text-xs font-bold text-[var(--muted)] mb-1.5">Khối lớp</label>
            <select v-model="form.grade_id" class="field text-xs font-bold">
              <option :value="null">-- Chọn khối lớp --</option>
              <option v-for="g in grades" :key="g.id" :value="g.id">{{ g.name }}</option>
            </select>
          </div>

          <!-- Topic Input -->
          <div>
            <label class="block text-xs font-bold text-[var(--muted)] mb-1.5">Chủ đề bài học</label>
            <input
              v-model="form.topic_name"
              type="text"
              class="field text-xs font-bold"
              placeholder="VD: Hàm số, Văn học dân gian..."
            />
          </div>

          <!-- Difficulty Select -->
          <div>
            <label class="block text-xs font-bold text-[var(--muted)] mb-1.5">Độ khó câu hỏi</label>
            <select v-model="form.difficulty" class="field text-xs font-bold">
              <option value="easy">Dễ (Nhận biết)</option>
              <option value="medium">Vừa (Thông hiểu)</option>
              <option value="hard">Khó (Vận dụng)</option>
            </select>
          </div>

          <!-- Points Input -->
          <div>
            <label class="block text-xs font-bold text-[var(--muted)] mb-1.5">Điểm số (Points)</label>
            <input
              v-model.number="form.points"
              type="number"
              min="1"
              max="100"
              class="field text-xs font-bold"
            />
          </div>
        </article>

        <!-- 2. Visibility Status Config Card -->
        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl space-y-4">
          <h3 class="text-xs font-black uppercase tracking-wider text-[var(--primary)] border-b border-[var(--border)] pb-3">
            🌐 Quyền hiển thị câu hỏi
          </h3>

          <div class="flex items-center justify-between gap-3 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <div>
              <p class="text-xs font-bold text-[var(--text)]">Công khai ngân hàng chung</p>
              <p class="text-[10px] text-[var(--muted)]">Mọi người dùng có thể tìm & dùng câu này</p>
            </div>
            <input
              type="checkbox"
              v-model="form.is_public"
              class="h-6 w-6 accent-[var(--primary)] cursor-pointer"
            />
          </div>
        </article>

        <!-- 3. Bottom Action Buttons Panel Card -->
        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl space-y-3">
          <button
            type="submit"
            class="btn-primary w-full justify-center text-xs font-black shadow-xl py-3"
            :disabled="isSubmitting"
          >
            💾 {{ isSubmitting ? 'Đang lưu...' : (isEditMode ? 'Lưu thay đổi câu hỏi' : 'Tạo mới câu hỏi') }}
          </button>

          <button
            type="button"
            class="btn-ghost w-full justify-center text-xs font-bold text-[var(--muted)] py-2.5"
            @click="goBack"
          >
            ← Hủy bỏ & Quay lại
          </button>
        </article>
      </div>
    </form>
  </section>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { adminQuestionsApi, taxonomyApi } from '@/services/api'

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
    if (showToast) showToast(`Lưu thất bại: ${err.message}`, 'error')
  } finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  fetchTaxonomies()
  loadQuestionDetail()
})
</script>
