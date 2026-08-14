<template>
  <section class="max-w-6xl mx-auto py-4 space-y-6">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">
          {{ isUserWorkspace ? 'Kho quiz cá nhân' : 'Ngân hàng đề thi' }}
        </p>
        <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">
          {{ isUserWorkspace ? 'Kho quiz của tôi' : 'Kho quiz' }}
        </h1>
        <p class="mt-1 text-sm text-slate-600 max-w-2xl">
          {{ isUserWorkspace 
            ? 'Quản lý, chỉnh sửa hoặc kiểm tra lượt làm bài các bộ quiz do bạn tạo.' 
            : 'Tìm kiếm, lọc danh mục, độ khó và trạng thái hiển thị của các quiz trên toàn hệ thống.' 
          }}
        </p>

        <div v-if="isUserWorkspace" class="mt-4 flex gap-2.5">
          <button
            type="button"
            class="rounded-lg border border-red-200 bg-red-50 px-3.5 py-1.5 text-xs font-bold text-red-700 hover:bg-red-100 transition"
            @click="loadTrash"
          >
            🗑 Thùng rác
          </button>
          <button
            v-if="showTrash"
            type="button"
            class="btn-secondary text-xs px-3.5 py-1.5"
            @click="loadQuizzes"
          >
            ← Quay lại
          </button>
        </div>
      </div>
    </div>

    <!-- Search & Filter Controls -->
    <article class="card p-5 space-y-3">
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_160px_160px_160px]">
        <input 
          v-model="search" 
          class="field text-xs" 
          placeholder="Tìm theo tên quiz, tác giả, thẻ tag..." 
          @keyup.enter="loadQuizzes" 
        />
        <select v-model="difficultyFilter" class="field text-xs" @change="loadQuizzes">
          <option value="">Tất cả độ khó</option>
          <option value="easy">Dễ</option>
          <option value="medium">Vừa</option>
          <option value="hard">Khó</option>
        </select>
        <select v-model="tagFilter" class="field text-xs">
          <option value="all">Tất cả tag</option>
          <option v-for="tag in tags" :key="tag" :value="tag">{{ tag }}</option>
        </select>
        <select v-model="visibilityFilter" class="field text-xs" @change="loadQuizzes">
          <option value="all">Tất cả hiển thị</option>
          <option value="public">Public</option>
          <option value="private">Private</option>
          <option value="group">Group</option>
        </select>
      </div>

      <div class="flex flex-wrap gap-2 pt-1">
        <button 
          v-for="item in visibilityChips" 
          :key="item.value" 
          type="button" 
          class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1 text-xs font-bold transition" 
          :class="visibilityFilter === item.value ? 'bg-[#7C3AED] text-white border-[#7C3AED]' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'" 
          @click="setVisibility(item.value)"
        >
          <component v-if="item.icon" :is="item.icon" class="h-3.5 w-3.5" />
          <span>{{ item.label }}</span>
        </button>
        <button 
          type="button" 
          class="flex items-center gap-1.5 rounded-lg border px-3 py-1 text-xs font-bold transition" 
          :class="tagFilter === 'AI' ? 'bg-[#7C3AED] text-white border-[#7C3AED]' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'" 
          @click="tagFilter = tagFilter === 'AI' ? 'all' : 'AI'"
        >
          <Bot class="h-3.5 w-3.5" />
          <span>AI Generated</span>
        </button>
      </div>
    </article>

    <!-- Feedback Alerts -->
    <div v-if="isLoading" class="card p-10 text-center text-xs text-slate-400">Đang tải danh sách quiz...</div>
    <div v-if="errorMessage" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs font-bold text-amber-800">{{ errorMessage }}</div>

    <!-- Quizzes Grid -->
    <div v-if="!isLoading && filteredQuizzes.length > 0" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <article 
        v-for="quiz in filteredQuizzes" 
        :key="quiz.id" 
        class="card card-hover overflow-hidden flex flex-col justify-between"
      >
        <div class="p-5 space-y-3">
          <div class="flex items-center justify-between">
            <span
              v-if="isUserWorkspace && !quiz.is_public"
              class="rounded bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800"
            >
              Đã bị ẩn
            </span>
            <VisibilityBadge v-else :value="quiz.visibility" />

            <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 uppercase">
              {{ quiz.difficulty }}
            </span>
          </div>

          <div>
            <h3 class="font-bold text-sm text-slate-900 line-clamp-1 hover:text-[#7C3AED] transition">
              {{ quiz.title }}
            </h3>
            <p class="text-xs text-slate-400 mt-1 line-clamp-1">
              {{ quiz.category }} • by {{ quiz.author }}
            </p>
          </div>

          <div class="grid grid-cols-3 gap-2 text-center text-xs pt-1">
            <div class="rounded-lg bg-slate-50 p-2 border border-slate-100">
              <b class="text-slate-900 font-bold block">{{ quiz.questions }}</b>
              <span class="text-[10px] text-slate-400 block">Câu</span>
            </div>
            <div class="rounded-lg bg-slate-50 p-2 border border-slate-100">
              <b class="text-slate-900 font-bold block">{{ quiz.attempts }}</b>
              <span class="text-[10px] text-slate-400 block">Lượt làm</span>
            </div>
            <div class="rounded-lg bg-slate-50 p-2 border border-slate-100">
              <b class="text-[#7C3AED] font-bold block">{{ quiz.avgScore }}%</b>
              <span class="text-[10px] text-slate-400 block">Điểm TB</span>
            </div>
          </div>
        </div>

        <!-- Card Footer Actions -->
        <div class="p-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50/50">
          <router-link
            class="btn-secondary text-xs px-3 py-1.5"
            :to="`/quizzes/${quiz.id}`"
          >
            Xem
          </router-link>
          
          <router-link
            class="btn-primary text-xs px-3 py-1.5"
            :to="`${questionBase}/edit/${quiz.id}`"
          >
            Sửa
          </router-link>

          <button
            v-if="isUserWorkspace && !showTrash"
            class="rounded-lg border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 text-xs px-3 py-1.5 font-bold"
            type="button"
            @click="deleteQuiz(quiz.id)"
          >
            Xóa
          </button>

          <button
            v-if="isUserWorkspace && showTrash"
            class="rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-xs px-3 py-1.5 font-bold"
            type="button"
            @click="restoreQuiz(quiz.id)"
          >
            Khôi phục
          </button>

          <button
            v-if="isUserWorkspace && showTrash"
            class="rounded-lg border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 text-xs px-3 py-1.5 font-bold"
            type="button"
            @click="forceDeleteQuiz(quiz.id)"
          >
            Xóa vĩnh viễn
          </button>

          <button
            v-if="!isUserWorkspace"
            class="rounded-lg border border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100 text-xs px-3 py-1.5 font-bold"
            type="button"
            @click="toggleVisibility(quiz)"
          >
            {{ quiz.is_public ? 'Ẩn' : 'Hiện lại' }}
          </button>
        </div>
      </article>
    </div>

    <!-- Empty State -->
    <div v-if="!isLoading && filteredQuizzes.length === 0" class="card p-10 text-center text-xs text-slate-400 space-y-2">
      <div class="text-3xl">🔎</div>
      <h3 class="font-bold text-slate-900 text-base">Không tìm thấy quiz nào</h3>
      <p class="text-slate-500">Hãy thử đổi từ khóa tìm kiếm hoặc điều chỉnh bộ lọc.</p>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, inject } from 'vue'
import { useRoute } from 'vue-router'
import { Bot, Globe, Lock, Users } from 'lucide-vue-next'
import VisibilityBadge from '@/components/common/VisibilityBadge.vue'
import { normalizeQuizCard, quizzesApi } from '@/services/api'

const showConfirm = inject('showConfirm')
const showToast = inject('showToast')

const route = useRoute()
const questionBase = computed(() => route.path.startsWith('/dashboard') ? '/dashboard/questions' : '/admin/questions')
const search = ref('')
const difficultyFilter = ref('')
const tagFilter = ref('all')
const visibilityFilter = ref('all')
const isUserWorkspace = computed(() => route.path.startsWith('/dashboard'))
const quizzes = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const showTrash = ref(false)

const visibilityChips = [
  { value: 'all', label: 'Tất cả', icon: null },
  { value: 'public', label: 'Public', icon: Globe },
  { value: 'private', label: 'Private', icon: Lock },
  { value: 'group', label: 'Group', icon: Users },
]

const tags = computed(() => [...new Set(quizzes.value.map((quiz) => quiz.tag).filter(Boolean))])

const filteredQuizzes = computed(() => {
  let list = quizzes.value
  if (tagFilter.value !== 'all') {
    list = list.filter(q => q.tag === tagFilter.value || (tagFilter.value === 'AI' && q.is_ai_generated))
  }
  return list
})

const setVisibility = async (value) => {
  visibilityFilter.value = value
  await loadQuizzes()
}

const loadQuizzes = async () => {
  showTrash.value = false
  isLoading.value = true
  errorMessage.value = ''

  try {
    const data = await quizzesApi.list({
      search: search.value || undefined,
      difficulty: difficultyFilter.value || undefined,
      visibility: visibilityFilter.value === 'all' ? undefined : visibilityFilter.value,
      owner: isUserWorkspace.value ? 'me' : undefined,
      per_page: 100
    })
    quizzes.value = data.map(normalizeQuizCard)
  } catch (error) {
    errorMessage.value = `Không tải được danh sách: ${error.message}`
    quizzes.value = []
  } finally {
    isLoading.value = false
  }
}

const loadTrash = async () => {
  showTrash.value = true
  isLoading.value = true
  errorMessage.value = ''

  try {
    const data = await quizzesApi.trash({
      search: search.value || undefined,
      per_page: 100
    })
    quizzes.value = data.map(normalizeQuizCard)
  } catch (error) {
    errorMessage.value = `Không tải được thùng rác: ${error.message}`
    quizzes.value = []
  } finally {
    isLoading.value = false
  }
}

const deleteQuiz = async (id) => {
  const message = 'Chuyển Quiz này vào thùng rác?'
  if (showConfirm) {
    showConfirm('Chuyển vào thùng rác', message, async () => {
      await executeDeleteQuiz(id)
    })
  } else {
    if (!window.confirm(message)) return
    await executeDeleteQuiz(id)
  }
}

const executeDeleteQuiz = async (id) => {
  try {
    await quizzesApi.remove(id)
    quizzes.value = quizzes.value.filter((quiz) => quiz.id !== id)
    if (showToast) showToast('Đã chuyển Quiz vào thùng rác.', 'success')
  } catch (error) {
    errorMessage.value = `Xóa thất bại: ${error.message}`
    if (showToast) showToast(errorMessage.value, 'error')
  }
}

const restoreQuiz = async (id) => {
  try {
    await quizzesApi.restore(id)
    quizzes.value = quizzes.value.filter((quiz) => quiz.id !== id)
    if (showToast) showToast('Đã khôi phục Quiz thành công.', 'success')
  } catch (error) {
    if (showToast) showToast(`Khôi phục thất bại: ${error.message}`, 'error')
  }
}

const forceDeleteQuiz = async (id) => {
  if (!window.confirm('Xóa vĩnh viễn quiz này? Không thể hoàn tác!')) return
  try {
    await quizzesApi.forceDelete(id)
    quizzes.value = quizzes.value.filter((quiz) => quiz.id !== id)
    if (showToast) showToast('Đã xóa vĩnh viễn Quiz.', 'success')
  } catch (error) {
    if (showToast) showToast(`Xóa thất bại: ${error.message}`, 'error')
  }
}

const toggleVisibility = async (quiz) => {
  try {
    const updated = await quizzesApi.update(quiz.id, { is_public: !quiz.is_public })
    quiz.is_public = updated.is_public
    if (showToast) showToast('Đã cập nhật trạng thái hiển thị.', 'success')
  } catch (error) {
    if (showToast) showToast(`Cập nhật thất bại: ${error.message}`, 'error')
  }
}

onMounted(loadQuizzes)
</script>
