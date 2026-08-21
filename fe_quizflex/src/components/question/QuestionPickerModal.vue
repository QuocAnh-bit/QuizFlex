<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-3 sm:p-6 backdrop-blur-md animate-fade-in"
    @click.self="handleCancel"
  >
    <div
      class="relative flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] shadow-2xl backdrop-blur-2xl"
    >
      <!-- HEADER -->
      <div class="border-b border-[var(--border)] p-5 sm:px-6 sm:py-5 bg-[var(--surface-soft)]/50">
        <div class="flex items-start justify-between gap-4">
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <span class="inline-block h-2.5 w-2.5 rounded-full bg-[var(--primary)] shadow-[0_0_10px_var(--primary)]"></span>
              <h3 class="text-xl font-black tracking-[-0.03em] text-[var(--text)]">
                Chọn câu hỏi cho bộ đề
              </h3>
            </div>
            <p class="mt-1 text-xs text-[var(--muted)]">
              Chọn câu hỏi từ Kho cá nhân của bạn hoặc Ngân hàng câu hỏi công khai để đưa vào đề thi.
            </p>
          </div>

          <button
            type="button"
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-[var(--border)] text-[var(--muted)] hover:bg-[var(--chip-active)] hover:text-[var(--text)] transition cursor-pointer"
            title="Đóng modal"
            @click="handleCancel"
          >
            <X :size="16" />
          </button>
        </div>

        <!-- SOURCE TABS -->
        <div class="mt-4 flex flex-wrap items-center gap-2">
          <button
            type="button"
            class="flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition duration-200 cursor-pointer"
            :class="activeTab === 'my_bank'
              ? 'bg-[var(--primary)] text-white shadow-md shadow-[var(--primary)]/20'
              : 'border border-[var(--border)] bg-[var(--surface)] text-[var(--muted)] hover:text-[var(--text)]'"
            @click="switchTab('my_bank')"
          >
            <User :size="14" />
            <span>Kho câu hỏi của tôi</span>
            <span
              v-if="myBankSelectedCount > 0"
              class="ml-1 rounded-full px-1.5 py-0.5 text-[10px] font-black"
              :class="activeTab === 'my_bank' ? 'bg-white/25 text-white' : 'bg-[var(--primary)]/15 text-[var(--primary)]'"
            >
              {{ myBankSelectedCount }}
            </span>
          </button>

          <button
            type="button"
            class="flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition duration-200 cursor-pointer"
            :class="activeTab === 'public_bank'
              ? 'bg-[var(--primary)] text-white shadow-md shadow-[var(--primary)]/20'
              : 'border border-[var(--border)] bg-[var(--surface)] text-[var(--muted)] hover:text-[var(--text)]'"
            @click="switchTab('public_bank')"
          >
            <Globe :size="14" />
            <span>Ngân hàng câu hỏi</span>
            <span
              v-if="publicBankSelectedCount > 0"
              class="ml-1 rounded-full px-1.5 py-0.5 text-[10px] font-black"
              :class="activeTab === 'public_bank' ? 'bg-white/25 text-white' : 'bg-[var(--primary)]/15 text-[var(--primary)]'"
            >
              {{ publicBankSelectedCount }}
            </span>
          </button>

          <!-- Overall Counter Summary Pill -->
          <div class="ml-auto text-xs font-bold text-[var(--muted)] hidden sm:flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-lg border border-[var(--border)] bg-[var(--surface)] px-2.5 py-1 text-[11px]">
              <span class="text-emerald-400">👤 Kho: {{ myBankSelectedCount }}</span>
              <span class="text-[var(--border)]">•</span>
              <span class="text-sky-400">🌐 Ngân hàng: {{ publicBankSelectedCount }}</span>
            </span>
          </div>
        </div>
      </div>

      <!-- FILTER BAR -->
      <div class="border-b border-[var(--border)] p-4 sm:px-6 bg-[var(--surface)] grid gap-3">
        <!-- Row 1: Taxonomy dropdowns -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
          <select
            v-model="filters.education_level_id"
            class="field !py-2 text-xs"
            @change="onLevelChange"
          >
            <option value="">Tất cả Cấp học</option>
            <option v-for="level in taxonomyLevels" :key="level.id" :value="level.id">{{ level.name }}</option>
          </select>

          <select
            v-model="filters.grade_id"
            class="field !py-2 text-xs"
            @change="onGradeSubjectChange"
          >
            <option value="">Tất cả Khối lớp</option>
            <option v-for="grade in availableGrades" :key="grade.id" :value="grade.id">{{ grade.name }}</option>
          </select>

          <select
            v-model="filters.subject_id"
            class="field !py-2 text-xs"
            @change="onGradeSubjectChange"
          >
            <option value="">Tất cả Bộ môn</option>
            <option v-for="subject in availableSubjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
          </select>

          <select
            v-model="filters.difficulty"
            class="field !py-2 text-xs"
            @change="applyFilter"
          >
            <option value="">Tất cả Độ khó</option>
            <option value="easy">Dễ (Nhận biết)</option>
            <option value="medium">Vừa (Thông hiểu)</option>
            <option value="hard">Khó (Vận dụng)</option>
          </select>
        </div>

        <!-- Row 2: Search, Topic & Actions -->
        <div class="grid sm:grid-cols-[1fr_220px_auto_auto] gap-2.5">
          <div class="relative">
            <Search :size="14" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[var(--muted)]" />
            <input
              v-model="filters.search"
              type="text"
              class="field w-full !py-2 !pl-8 text-xs font-medium"
              placeholder="Tìm kiếm nội dung câu hỏi..."
              @keyup.enter="applyFilter"
            />
          </div>

          <select
            v-model="filters.topic_name"
            class="field !py-2 text-xs"
            @change="applyFilter"
          >
            <option value="">Tất cả Chủ đề</option>
            <option v-for="top in topicsList" :key="top.topic_name" :value="top.topic_name">
              {{ top.topic_name }} ({{ top.total_questions }} câu)
            </option>
          </select>

          <button
            type="button"
            class="btn-primary inline-flex items-center justify-center gap-1.5 !py-2 !px-3.5 text-xs cursor-pointer shrink-0"
            @click="applyFilter"
          >
            <SearchCheck :size="13" />
            <span>Lọc</span>
          </button>

          <button
            v-if="hasActiveFilters"
            type="button"
            class="btn-ghost inline-flex items-center justify-center gap-1 !py-2 text-xs font-bold text-rose-400 hover:bg-rose-500/10 cursor-pointer shrink-0"
            @click="resetFilters"
          >
            <RotateCcw :size="12" />
            <span>Đặt lại</span>
          </button>
        </div>
      </div>

      <!-- SELECTION & STATS SUB-BAR -->
      <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--border)] px-5 py-2.5 bg-[var(--surface-soft)]/30 text-xs font-bold text-[var(--muted)]">
        <span>
          Tìm thấy <strong class="text-[var(--text)]">{{ pagination.total }}</strong> câu hỏi
          <span v-if="pagination.last_page > 1" class="ml-1 text-[11px] font-normal">
            (Trang {{ pagination.current_page }}/{{ pagination.last_page }})
          </span>
        </span>

        <div class="flex items-center gap-3">
          <button
            v-if="questions.length > 0"
            type="button"
            class="text-[var(--primary)] hover:underline font-bold cursor-pointer text-xs"
            @click="toggleSelectAllCurrentPage"
          >
            {{ isCurrentPageAllSelected ? '✕ Bỏ chọn trang này' : '✓ Chọn tất cả trang này' }}
          </button>
        </div>
      </div>

      <!-- QUESTION LIST BODY -->
      <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-3 min-h-[260px] max-h-[48vh]">
        <!-- Loading State -->
        <div v-if="isLoading" class="flex flex-col items-center justify-center gap-3 py-16 text-xs font-bold text-[var(--muted)]">
          <div class="h-7 w-7 animate-spin rounded-full border-2 border-[var(--primary)] border-t-transparent"></div>
          <span>Đang tải danh sách câu hỏi...</span>
        </div>

        <!-- Empty State -->
        <div v-else-if="questions.length === 0" class="flex flex-col items-center justify-center py-16 text-center text-xs text-[var(--muted)]">
          <span class="text-3xl mb-2">🔍</span>
          <p class="font-bold text-sm text-[var(--text)]">Không tìm thấy câu hỏi phù hợp</p>
          <p class="mt-1">Hãy thử điều chỉnh lại bộ lọc hoặc từ khóa tìm kiếm.</p>
        </div>

        <!-- Questions Cards -->
        <template v-else>
          <div
            v-for="q in questions"
            :key="q.id"
            class="group flex cursor-pointer items-start gap-3.5 rounded-2xl border p-4 transition-all duration-200 select-none"
            :class="localSelectedIds.includes(q.id)
              ? 'border-[var(--primary)] bg-[var(--primary)]/10 shadow-sm'
              : 'border-[var(--border)] bg-[var(--surface-soft)] hover:border-[var(--border-strong)] hover:bg-[var(--chip-active)]'"
            @click="toggleSelectQuestion(q)"
          >
            <!-- Checkbox Box -->
            <div
              class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-lg border transition-all duration-150"
              :class="localSelectedIds.includes(q.id)
                ? 'border-[var(--primary)] bg-[var(--primary)] text-white shadow-sm'
                : 'border-slate-300 dark:border-slate-600 bg-[var(--surface)] group-hover:border-[var(--primary)]'"
            >
              <Check v-if="localSelectedIds.includes(q.id)" :size="13" :stroke-width="3" />
            </div>

            <!-- Question Info -->
            <div class="min-w-0 flex-1">
              <div class="mb-1.5 flex flex-wrap items-center gap-2">
                <span class="font-mono text-xs font-bold text-[var(--primary)]">#{{ q.id }}</span>

                <!-- Difficulty Badge -->
                <span
                  class="rounded-full px-2 py-0.5 text-[10px] font-black"
                  :class="getDifficultyClass(q.difficulty)"
                >
                  {{ getDifficultyLabel(q.difficulty) }}
                </span>

                <!-- Source Badge -->
                <span
                  class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                  :class="activeTab === 'my_bank' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-sky-500/10 text-sky-400 border border-sky-500/20'"
                >
                  {{ activeTab === 'my_bank' ? '👤 Kho của tôi' : '🌐 Ngân hàng' }}
                </span>

                <!-- Subject / Grade -->
                <span v-if="q.subject_name || q.grade_name" class="text-[11px] font-medium text-[var(--muted)] truncate max-w-[200px]">
                  {{ [q.subject_name, q.grade_name].filter(Boolean).join(' • ') }}
                </span>

                <span v-if="q.topic_name" class="text-[10px] font-semibold text-[var(--muted)] bg-[var(--surface)] px-1.5 py-0.5 rounded border border-[var(--border)]">
                  {{ q.topic_name }}
                </span>

                <!-- Button Xem / Ẩn đáp án -->
                <button
                  type="button"
                  class="ml-auto inline-flex items-center gap-1 rounded-lg border border-[var(--border)] bg-[var(--surface)] px-2.5 py-0.5 text-[11px] font-bold text-[var(--text)] hover:bg-[var(--chip-active)] transition cursor-pointer"
                  title="Xem đáp án của câu hỏi này"
                  @click.stop="toggleRevealAnswer(q.id)"
                >
                  <Loader2 v-if="loadingAnswerId === q.id" :size="11" class="animate-spin text-[var(--primary)]" />
                  <EyeOff v-else-if="revealedQuestionIds.has(q.id)" :size="11" class="text-emerald-400" />
                  <Eye v-else :size="11" class="text-[var(--muted)]" />
                  <span>{{ revealedQuestionIds.has(q.id) ? 'Ẩn đáp án' : 'Xem đáp án' }}</span>
                </button>
              </div>

              <!-- Question Content -->
              <p class="text-xs sm:text-sm font-semibold text-[var(--text)] line-clamp-2 leading-relaxed">
                {{ q.content || q.text }}
              </p>

              <!-- Answers Summary Pill (Neutral by default, highlighted only when revealed) -->
              <div v-if="q.answers && q.answers.length" class="mt-2 flex flex-wrap items-center gap-1.5">
                <span
                  v-for="(ans, aIdx) in q.answers"
                  :key="ans.id || aIdx"
                  class="inline-flex items-center gap-1 text-[11px] rounded-lg px-2 py-0.5 border transition-colors duration-150"
                  :class="isAnswerCorrectRevealed(q.id, ans, aIdx)
                    ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-400 font-bold shadow-sm'
                    : 'border-[var(--border)] bg-[var(--surface)] text-[var(--muted)] font-normal'"
                >
                  <span class="font-bold">{{ ans.answer_key || ans.key || String.fromCharCode(65 + aIdx) }}.</span>
                  <span class="truncate max-w-[140px]">{{ ans.content || ans.text }}</span>
                  <Check v-if="isAnswerCorrectRevealed(q.id, ans, aIdx)" :size="11" class="text-emerald-400" />
                </span>
              </div>
            </div>
          </div>

          <!-- PAGINATION CONTROLS -->
          <div
            v-if="pagination.last_page > 1"
            class="flex items-center justify-between border-t border-[var(--border)] pt-4 text-xs font-bold"
          >
            <button
              type="button"
              class="btn-ghost inline-flex items-center gap-1 border border-[var(--border)] !py-1.5 !px-3 disabled:opacity-40 cursor-pointer"
              :disabled="pagination.current_page <= 1"
              @click="changePage(pagination.current_page - 1)"
            >
              <ChevronLeft :size="14" />
              <span>Trang trước</span>
            </button>

            <span class="text-[var(--text)]">
              Trang {{ pagination.current_page }} / {{ pagination.last_page }}
            </span>

            <button
              type="button"
              class="btn-ghost inline-flex items-center gap-1 border border-[var(--border)] !py-1.5 !px-3 disabled:opacity-40 cursor-pointer"
              :disabled="pagination.current_page >= pagination.last_page"
              @click="changePage(pagination.current_page + 1)"
            >
              <span>Trang sau</span>
              <ChevronRight :size="14" />
            </button>
          </div>
        </template>
      </div>

      <!-- FOOTER -->
      <div class="border-t border-[var(--border)] p-4 sm:px-6 flex flex-wrap items-center justify-between gap-4 bg-[var(--surface-soft)]/60">
        <div class="text-xs">
          <p class="font-black text-[var(--text)]">
            Đã chọn <span class="text-[var(--primary)] font-black text-sm">{{ localSelectedIds.length }}</span> câu hỏi
          </p>
          <p class="text-[11px] text-[var(--muted)] mt-0.5">
            (Kho của tôi: <strong>{{ myBankSelectedCount }}</strong>, Ngân hàng: <strong>{{ publicBankSelectedCount }}</strong>)
          </p>
        </div>

        <div class="flex items-center gap-2.5">
          <button
            type="button"
            class="btn-ghost text-xs font-bold text-[var(--muted)] hover:text-[var(--text)] !py-2.5 !px-4 cursor-pointer"
            @click="handleCancel"
          >
            Hủy
          </button>

          <button
            type="button"
            class="btn-primary !py-2.5 !px-6 text-xs font-black shadow-lg shadow-[var(--primary)]/20 hover:shadow-[var(--primary)]/35 transition duration-200 cursor-pointer"
            @click="handleConfirm"
          >
            <span>Xác nhận ({{ localSelectedIds.length }} câu)</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import {
  X,
  User,
  Globe,
  Search,
  SearchCheck,
  RotateCcw,
  Check,
  ChevronLeft,
  ChevronRight,
  Eye,
  EyeOff,
  Loader2,
  CheckCircle2,
} from 'lucide-vue-next'
import { myQuestionsApi, questionsBankApi, taxonomyApi } from '@/services/api'

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  isOpen: {
    type: Boolean,
    default: false,
  },
  mode: {
    type: String,
    default: 'manual',
  },
})

const emit = defineEmits(['update:modelValue', 'close', 'confirm'])

/* =========================================================
   STATE
========================================================= */
const activeTab = ref('my_bank') // 'my_bank' | 'public_bank'
const localSelectedIds = ref([])
const selectedQuestionCache = ref(new Map())

const revealedQuestionIds = ref(new Set())
const revealedAnswersMap = ref(new Map())
const loadingAnswerId = ref(null)

const questions = ref([])
const isLoading = ref(false)

const taxonomyLevels = ref([])
const allSubjects = ref([])
const topicsList = ref([])

const filters = reactive({
  search: '',
  education_level_id: '',
  grade_id: '',
  subject_id: '',
  topic_name: '',
  difficulty: '',
})

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 12,
  total: 0,
})

/* =========================================================
   COMPUTED COUNTS & STATS
========================================================= */
const myBankSelectedCount = computed(() => {
  return localSelectedIds.value.filter(id => {
    const item = selectedQuestionCache.value.get(id)
    return item?.source === 'my_bank'
  }).length
})

const publicBankSelectedCount = computed(() => {
  return localSelectedIds.value.filter(id => {
    const item = selectedQuestionCache.value.get(id)
    return item?.source === 'public_bank'
  }).length
})

const hasActiveFilters = computed(() => {
  return Boolean(
    filters.search ||
    filters.education_level_id ||
    filters.grade_id ||
    filters.subject_id ||
    filters.topic_name ||
    filters.difficulty
  )
})

const availableGrades = computed(() => {
  if (!filters.education_level_id) {
    return taxonomyLevels.value.flatMap(l => l.grades || [])
  }
  const level = taxonomyLevels.value.find(l => l.id === Number(filters.education_level_id))
  return level ? level.grades || [] : []
})

const availableSubjects = computed(() => {
  if (!filters.grade_id) {
    return allSubjects.value
  }
  const grade = availableGrades.value.find(g => g.id === Number(filters.grade_id))
  return grade && grade.subjects && grade.subjects.length ? grade.subjects : allSubjects.value
})

const isCurrentPageAllSelected = computed(() => {
  if (questions.value.length === 0) return false
  return questions.value.every(q => localSelectedIds.value.includes(q.id))
})

/* =========================================================
   DIFFICULTY HELPERS
========================================================= */
const getDifficultyClass = (diff) => {
  switch (diff) {
    case 'easy':
      return 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'
    case 'hard':
      return 'bg-rose-500/10 text-rose-400 border border-rose-500/20'
    default:
      return 'bg-amber-500/10 text-amber-400 border border-amber-500/20'
  }
}

const getDifficultyLabel = (diff) => {
  switch (diff) {
    case 'easy':
      return 'Dễ'
    case 'hard':
      return 'Khó'
    default:
      return 'Vừa'
  }
}

/* =========================================================
   DATA FETCHING
========================================================= */
const fetchTaxonomy = async () => {
  if (taxonomyLevels.value.length > 0) return
  try {
    const data = await taxonomyApi.tree()
    const payload = data?.education_levels ? data : (data?.data ?? data)
    if (payload) {
      taxonomyLevels.value = payload.education_levels || payload.educationLevels || []
      allSubjects.value = payload.subjects || []
    }
  } catch (e) {
    console.error('Không tải được taxonomy:', e)
  }
}

const fetchTopicsList = async () => {
  try {
    const params = {
      education_level_id: filters.education_level_id || undefined,
      grade_id: filters.grade_id || undefined,
      subject_id: filters.subject_id || undefined,
    }
    const data = await questionsBankApi.fetchTopics(params)
    topicsList.value = Array.isArray(data) ? data : (data?.data && Array.isArray(data.data) ? data.data : [])
  } catch (e) {
    console.error('Không tải được danh sách Chủ đề:', e)
  }
}

const loadQuestions = async () => {
  isLoading.value = true
  try {
    const params = {
      search: filters.search || undefined,
      education_level_id: filters.education_level_id || undefined,
      grade_id: filters.grade_id || undefined,
      subject_id: filters.subject_id || undefined,
      topic_name: filters.topic_name || undefined,
      difficulty: filters.difficulty || undefined,
      page: pagination.current_page,
      per_page: pagination.per_page,
    }

    const apiCall = activeTab.value === 'my_bank'
      ? myQuestionsApi.fetchBank(params)
      : questionsBankApi.fetchBank(params)

    const res = await apiCall
    if (res && Array.isArray(res.items)) {
      questions.value = res.items
      pagination.total = res.total ?? res.items.length
      pagination.current_page = res.currentPage ?? 1
      pagination.last_page = res.lastPage ?? 1
      pagination.per_page = res.perPage ?? pagination.per_page
    } else if (Array.isArray(res)) {
      questions.value = res
      pagination.total = res.length
      pagination.current_page = 1
      pagination.last_page = 1
    } else {
      questions.value = []
      pagination.total = 0
      pagination.current_page = 1
      pagination.last_page = 1
    }

    // Cache current tab question items
    questions.value.forEach(q => {
      if (localSelectedIds.value.includes(q.id) && !selectedQuestionCache.value.has(q.id)) {
        selectedQuestionCache.value.set(q.id, {
          ...q,
          source: activeTab.value,
        })
      }
    })
  } catch (e) {
    console.error('Không tải được danh sách câu hỏi:', e)
    questions.value = []
  } finally {
    isLoading.value = false
  }
}

/* =========================================================
   HYDRATE MISSING SELECTED QUESTIONS CACHE
========================================================= */
const hydrateSelectedCache = async (ids) => {
  const missingIds = ids.filter(id => !selectedQuestionCache.value.has(id))
  if (missingIds.length === 0) return

  try {
    // Attempt to load from public bank first
    const publicRes = await questionsBankApi.fetchBank({ ids: missingIds, per_page: missingIds.length })
    const publicItems = publicRes?.items || (Array.isArray(publicRes) ? publicRes : [])
    publicItems.forEach(q => {
      selectedQuestionCache.value.set(q.id, { ...q, source: 'public_bank' })
    })

    // For any still missing, attempt to load from user's personal bank
    const stillMissing = ids.filter(id => !selectedQuestionCache.value.has(id))
    if (stillMissing.length > 0) {
      const userRes = await myQuestionsApi.fetchBank({ ids: stillMissing, per_page: stillMissing.length })
      const userItems = userRes?.items || (Array.isArray(userRes) ? userRes : [])
      userItems.forEach(q => {
        selectedQuestionCache.value.set(q.id, { ...q, source: 'my_bank' })
      })
    }
  } catch (e) {
    console.error('Không thể nạp thông tin chi tiết câu hỏi đã chọn:', e)
  }
}

/* =========================================================
   TAB SWITCHING & FILTERS
========================================================= */
const switchTab = (tab) => {
  if (activeTab.value === tab) return
  activeTab.value = tab
  pagination.current_page = 1
  loadQuestions()
}

const onLevelChange = () => {
  filters.grade_id = ''
  onGradeSubjectChange()
}

const onGradeSubjectChange = () => {
  fetchTopicsList()
  applyFilter()
}

const applyFilter = () => {
  pagination.current_page = 1
  loadQuestions()
}

const resetFilters = () => {
  filters.search = ''
  filters.education_level_id = ''
  filters.grade_id = ''
  filters.subject_id = ''
  filters.topic_name = ''
  filters.difficulty = ''
  pagination.current_page = 1
  fetchTopicsList()
  loadQuestions()
}

const changePage = (page) => {
  if (page < 1 || page > pagination.last_page || page === pagination.current_page) return
  pagination.current_page = page
  loadQuestions()
}

/* =========================================================
   SELECTION HANDLING
========================================================= */
const toggleSelectQuestion = (question) => {
  const index = localSelectedIds.value.indexOf(question.id)
  if (index > -1) {
    localSelectedIds.value.splice(index, 1)
  } else {
    localSelectedIds.value.push(question.id)
    selectedQuestionCache.value.set(question.id, {
      ...question,
      source: activeTab.value,
    })
  }
}

const toggleSelectAllCurrentPage = () => {
  const currentPageIds = questions.value.map(q => q.id)
  if (isCurrentPageAllSelected.value) {
    localSelectedIds.value = localSelectedIds.value.filter(id => !currentPageIds.includes(id))
  } else {
    questions.value.forEach(q => {
      if (!localSelectedIds.value.includes(q.id)) {
        localSelectedIds.value.push(q.id)
        selectedQuestionCache.value.set(q.id, {
          ...q,
          source: activeTab.value,
        })
      }
    })
  }
}

/* =========================================================
   REVEAL ANSWER DETAIL LOGIC
========================================================= */
const toggleRevealAnswer = async (questionId) => {
  if (revealedQuestionIds.value.has(questionId)) {
    revealedQuestionIds.value.delete(questionId)
    return
  }

  if (!revealedAnswersMap.value.has(questionId)) {
    loadingAnswerId.value = questionId
    try {
      const detail = await questionsBankApi.getQuestion(questionId)
      if (detail) {
        revealedAnswersMap.value.set(questionId, detail)
      }
    } catch (e) {
      console.error('Không thể tải đáp án câu hỏi:', e)
    } finally {
      loadingAnswerId.value = null
    }
  }

  revealedQuestionIds.value.add(questionId)
}

const isAnswerCorrectRevealed = (questionId, ans, aIdx) => {
  if (!revealedQuestionIds.value.has(questionId)) return false
  const detail = revealedAnswersMap.value.get(questionId)
  if (!detail || !detail.answers) return false
  const key = ans.answer_key || ans.key || String.fromCharCode(65 + aIdx)
  const matchedAns = detail.answers.find(a => (ans.id && a.id === ans.id) || a.key === key || a.answer_key === key)
  return Boolean(matchedAns?.is_correct)
}

/* =========================================================
   ACTIONS (CANCEL / CONFIRM)
========================================================= */
const handleCancel = () => {
  emit('close')
}

const handleConfirm = () => {
  const selectedList = localSelectedIds.value.map(id => {
    const cached = selectedQuestionCache.value.get(id)
    return cached || { id, content: `Câu hỏi #${id}`, difficulty: 'medium', source: 'public_bank' }
  })

  emit('update:modelValue', [...localSelectedIds.value])
  emit('confirm', {
    ids: [...localSelectedIds.value],
    questions: selectedList,
    myBankCount: myBankSelectedCount.value,
    publicBankCount: publicBankSelectedCount.value,
  })
  emit('close')
}

/* =========================================================
   WATCHERS & LIFECYCLE
========================================================= */
watch(
  () => props.isOpen,
  async (isOpen) => {
    if (isOpen) {
      localSelectedIds.value = Array.isArray(props.modelValue) ? [...props.modelValue] : []
      await fetchTaxonomy()
      await fetchTopicsList()
      await hydrateSelectedCache(localSelectedIds.value)
      await loadQuestions()
    }
  },
  { immediate: true }
)

onMounted(async () => {
  if (props.isOpen) {
    localSelectedIds.value = Array.isArray(props.modelValue) ? [...props.modelValue] : []
    await fetchTaxonomy()
    await fetchTopicsList()
    await hydrateSelectedCache(localSelectedIds.value)
    await loadQuestions()
  }
})
</script>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: scale(0.98);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.animate-fade-in {
  animation: fadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
